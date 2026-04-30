<?php
// ============================================================
// includes/auth.php — Authentication & roles
// Roles: admin > editor > contributor
// ============================================================

require_once __DIR__ . '/config.php';

define('ROLE_ADMIN',       'admin');
define('ROLE_EDITOR',      'editor');
define('ROLE_CONTRIBUTOR', 'contributor');

// ── Session checks ─────────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect(CMS_PATH . '/login.php');
    }
    checkSessionTimeout();
    $_SESSION['last_activity'] = time();
}

function requireEditor(): void {
    requireLogin();
    if (!canPublish()) {
        flashError('You do not have permission to do that.');
        redirect(CMS_PATH . '/');
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        include dirname(__DIR__) . '/ripincms/403.php';
        exit;
    }
}

// ── Role checks ────────────────────────────────────────────────
function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['user_role'] === ROLE_ADMIN;
}

function isEditor(): bool {
    return isLoggedIn() && in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_EDITOR]);
}

function isContributor(): bool {
    return isLoggedIn(); // all logged-in users can contribute
}

// Can publish directly (admin or editor only)
function canPublish(): bool {
    return isEditor();
}

// Can manage files (admin and editor)
function canManageFiles(): bool {
    return isEditor();
}

// Can manage users and settings (admin only)
function canManageUsers(): bool {
    return isAdmin();
}

// ── Current user ───────────────────────────────────────────────
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user) return $user;
    $stmt = getDB()->prepare('SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $user = $stmt->fetch() ?: null;
}

function currentUserId(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

function roleBadge(string $role): string {
    $map = [
        'admin'       => '<span class="badge bg-danger">Admin</span>',
        'editor'      => '<span class="badge bg-primary">Editor</span>',
        'contributor' => '<span class="badge bg-secondary">Contributor</span>',
    ];
    return $map[$role] ?? '';
}

// ── Login ──────────────────────────────────────────────────────
function attemptLogin(string $email, string $password): array {
    $email = strtolower(trim($email));

    if (tooManyLoginAttempts($email)) {
        return ['success' => false, 'error' => 'Too many failed attempts. Please wait 15 minutes.'];
    }

    $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ? AND active = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        logLoginAttempt($email, false);
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['last_activity'] = time();

    getDB()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
    logLoginAttempt($email, true);

    return ['success' => true, 'role' => $user['role']];
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    redirect(CMS_PATH . '/login.php');
}

function checkSessionTimeout(): void {
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 7200) {
        logout();
    }
}

// ── Brute force ────────────────────────────────────────────────
function tooManyLoginAttempts(string $email): bool {
    $stmt = getDB()->prepare(
        'SELECT COUNT(*) FROM login_log
         WHERE email = ? AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
    );
    $stmt->execute([$email]);
    return (int) $stmt->fetchColumn() >= 5;
}

function logLoginAttempt(string $email, bool $success): void {
    try {
        getDB()->prepare(
            'INSERT INTO login_log (email, success, ip_address) VALUES (?, ?, ?)'
        )->execute([$email, $success ? 1 : 0, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {}
}

// ── Password validation ────────────────────────────────────────
function validatePassword(string $password, string $name = '', string $email = ''): array {
    $errors = [];
    if (strlen($password) < 10)                        $errors[] = 'At least 10 characters.';
    if (!preg_match('/[A-Z]/', $password))             $errors[] = 'At least one uppercase letter.';
    if (!preg_match('/[a-z]/', $password))             $errors[] = 'At least one lowercase letter.';
    if (!preg_match('/[0-9]/', $password))             $errors[] = 'At least one number.';
    if (!preg_match('/[^A-Za-z0-9]/', $password))     $errors[] = 'At least one special character (e.g. !@#$%).';
    if (stripos($password, 'ripin') !== false)         $errors[] = 'Cannot contain the word "RIPIN".';

    if ($name) {
        foreach (array_filter(explode(' ', strtolower($name))) as $part) {
            if (strlen($part) > 2 && stripos($password, $part) !== false) {
                $errors[] = 'Cannot contain your name.';
                break;
            }
        }
    }
    if ($email) {
        $user = strtolower(explode('@', $email)[0]);
        if (strlen($user) > 2 && stripos($password, $user) !== false) {
            $errors[] = 'Cannot contain part of your email.';
        }
    }
    return $errors;
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
