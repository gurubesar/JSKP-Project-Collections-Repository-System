<?php

declare(strict_types=1);

const SESSION_IDLE_TIMEOUT = 1800;
const CSRF_TOKEN_BYTES = 32;

/**
 * Starts a hardened PHP session and sets cookie flags before session data is read.
 */
function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_start();
}

/**
 * Destroys the active session and its cookie to fully log a user out.
 */
function destroy_secure_session(): void
{
    secure_session_start();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

/**
 * Enforces a 30-minute inactivity timeout and periodically refreshes session IDs.
 */
function validate_session_activity(): void
{
    secure_session_start();
    $now = time();

    if (isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        destroy_secure_session();
        header('Location: /public/login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = $now;

    if (!isset($_SESSION['session_regenerated_at']) || ($now - (int) $_SESSION['session_regenerated_at']) > 600) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated_at'] = $now;
    }
}

/**
 * Escapes output for HTML contexts to prevent cross-site scripting.
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Normalizes untrusted text input before validation and database storage.
 */
function clean_input(?string $value, int $maxLength = 1000): string
{
    $value = trim((string) $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    return mb_substr($value, 0, $maxLength);
}

/**
 * Validates the minimum password policy before password_hash() is used.
 */
function validate_password_policy(string $password): void
{
    if (strlen($password) < 8) {
        throw new InvalidArgumentException('Password must be at least 8 characters long.');
    }
}

/**
 * Hashes a password with PHP's current default password_hash() algorithm.
 */
function hash_password(string $password): string
{
    validate_password_policy($password);
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Requires an authenticated user and redirects anonymous users to login.
 */
function require_auth(): void
{
    validate_session_activity();
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
        header('Location: /public/login.php');
        exit;
    }
}

/**
 * Requires one of the supplied roles before allowing a page/controller to continue.
 */
function require_role(array $roles): void
{
    require_auth();
    if (!in_array((string) $_SESSION['user_role'], $roles, true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

/**
 * Creates or returns the session CSRF token used by state-changing forms.
 */
function csrf_token(): string
{
    secure_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_BYTES));
    }
    return (string) $_SESSION['csrf_token'];
}

/**
 * Renders a hidden CSRF input field for POST forms.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validates a submitted CSRF token and rejects forged or stale POST requests.
 */
function validate_csrf_token(?string $token = null): void
{
    secure_session_start();
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

/**
 * Enforces CSRF validation for the current request when it is POST.
 */
function require_valid_post_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        validate_csrf_token();
    }
}

/**
 * Checks project access for Admin, supervising Lecturer, or member Student.
 */
function can_access_project(PDO $db, int $userId, string $role, int $projectId): bool
{
    if ($role === 'admin') {
        return true;
    }

    if ($role === 'lecturer') {
        $stmt = $db->prepare('SELECT COUNT(*) FROM projects WHERE project_id = ? AND lecturer_id = ?');
        $stmt->execute([$projectId, $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    if ($role === 'student') {
        $stmt = $db->prepare('SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ? AND role = ?');
        $stmt->execute([$projectId, $userId, 'student']);
        return (int) $stmt->fetchColumn() > 0;
    }

    return false;
}

/**
 * Stops direct URL manipulation by requiring project access before continuing.
 */
function require_project_access(PDO $db, int $projectId): void
{
    require_auth();
    if ($projectId <= 0 || !can_access_project($db, (int) $_SESSION['user_id'], (string) $_SESSION['user_role'], $projectId)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

/**
 * Records security-relevant user actions for accountability and incident review.
 */
function audit_log(PDO $db, ?int $userId, string $action, string $description = ''): void
{
    try {
        $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            clean_input($action, 100),
            clean_input($description, 2000),
            clean_input($_SERVER['REMOTE_ADDR'] ?? '', 45),
        ]);
    } catch (Throwable $error) {
        error_log('Audit log failed: ' . $error->getMessage());
    }
}

