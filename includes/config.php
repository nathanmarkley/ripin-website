<?php
// ============================================================
// includes/config.php — Core configuration
// ============================================================

// ── Load .env ─────────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

// ── Constants ──────────────────────────────────────────────────
define('SITE_URL',    rtrim($_ENV['SITE_URL']   ?? 'https://dev.ripin.org', '/'));
define('SITE_NAME',   $_ENV['SITE_NAME']  ?? 'RIPIN');
define('CMS_PATH',    '/ripincms');
define('ASSETS_URL',  SITE_URL . '/assets');
define('UPLOAD_DIR',  dirname(__DIR__) . '/assets');
define('APP_VERSION', '1.0.0');

// ── Session ────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure',   1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime',  7200);
    session_name('ripin_cms');
    session_start();
}

// ── Database ───────────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        // Use Eastern time for all queries
        $pdo->exec("SET time_zone = 'America/New_York'");
    } catch (PDOException $e) {
        error_log('DB error: ' . $e->getMessage());
        die('A database error occurred. Please try again later.');
    }
    return $pdo;
}

// ── Helpers ────────────────────────────────────────────────────

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid request. Please go back and try again.');
    }
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function formatDate(string $date, string $fmt = 'F j, Y'): string {
    return date($fmt, strtotime($date));
}

function formatDateTime(string $date): string {
    return date('F j, Y \a\t g:i A', strtotime($date));
}

function truncate(string $text, int $len = 150): string {
    $text = strip_tags($text);
    return strlen($text) <= $len ? $text : substr($text, 0, $len) . '…';
}

function humanFileSize(int $bytes): string {
    foreach (['B','KB','MB','GB'] as $unit) {
        if ($bytes < 1024) return round($bytes, 1) . ' ' . $unit;
        $bytes /= 1024;
    }
    return $bytes . ' GB';
}

function isValidUrl(string $url): bool {
    return (bool) filter_var($url, FILTER_VALIDATE_URL);
}

// ── Content zones ──────────────────────────────────────────────
function content(string $key, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $stmt = getDB()->prepare('SELECT value FROM content_zones WHERE zone_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? $row['value'] : $default;
    } catch (Exception $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function zone(string $key, string $default = ''): void {
    echo content($key, $default);
}

// ── Flash messages ─────────────────────────────────────────────
function flashSuccess(string $msg): void { $_SESSION['flash_success'] = $msg; }
function flashError(string $msg): void   { $_SESSION['flash_error']   = $msg; }

// ── Review queue helpers ───────────────────────────────────────
function addToReviewQueue(string $type, int $id, string $title, int $submittedBy): void {
    try {
        $db = getDB();
        // Remove any existing pending entry for this item
        $db->prepare(
            'DELETE FROM review_queue WHERE content_type = ? AND content_id = ? AND action = "pending"'
        )->execute([$type, $id]);
        // Add fresh entry
        $db->prepare(
            'INSERT INTO review_queue (content_type, content_id, content_title, submitted_by)
             VALUES (?, ?, ?, ?)'
        )->execute([$type, $id, $title, $submittedBy]);
    } catch (Exception $e) {
        error_log('Review queue error: ' . $e->getMessage());
    }
}

function getPendingReviewCount(): int {
    try {
        return (int) getDB()
            ->query('SELECT COUNT(*) FROM review_queue WHERE action = "pending"')
            ->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}
