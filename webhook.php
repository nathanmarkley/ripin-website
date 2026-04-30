<?php
// webhook.php — GitHub auto-deploy
// Setup: GitHub repo → Settings → Webhooks → Add webhook
// Payload URL: https://dev.ripin.org/webhook.php
// Content type: application/json
// Secret: same as WEBHOOK_SECRET in your .env
// Which events: Just the push event

require_once __DIR__ . '/includes/config.php';

$secret    = $_ENV['WEBHOOK_SECRET'] ?? '';
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload   = file_get_contents('php://input');

// Verify request is from GitHub
if ($secret) {
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expected, $signature)) {
        http_response_code(401);
        die('Unauthorized');
    }
}

// Only deploy pushes to main branch
$data = json_decode($payload, true);
if (($data['ref'] ?? '') !== 'refs/heads/main') {
    http_response_code(200);
    die('Not main branch — skipping.');
}

// Pull latest code
$dir    = escapeshellarg(__DIR__);
$output = shell_exec("cd {$dir} && git pull origin main 2>&1");
$time   = date('Y-m-d H:i:s');

// Log it
file_put_contents(
    __DIR__ . '/logs/deploy.log',
    "[{$time}] Deploy triggered\n{$output}\n" . str_repeat('-', 40) . "\n",
    FILE_APPEND | LOCK_EX
);

http_response_code(200);
echo json_encode(['status' => 'ok', 'time' => $time, 'output' => $output]);
