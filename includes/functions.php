<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// ---------------------------------------------------------------------
// URL HELPERS
// ---------------------------------------------------------------------

/**
 * Builds a site-relative URL prefixed with BASE_URL. Use this for every
 * internal link, redirect, form action, and asset reference so the app
 * can be moved to a subdirectory (or off one) by changing only BASE_URL.
 *
 *   url('/dashboard.php')            -> '/nrms/dashboard.php'  (BASE_URL=/nrms)
 *   url('/personnel/view.php?id=5')  -> '/personnel/view.php?id=5'  (BASE_URL='')
 *   url($p['photo_path'])            -> prefixes a DB-stored upload path
 */
function url(?string $path = ''): string
{
    $path = $path ?? '';
    if ($path === '') return BASE_URL ?: '/';
    // Leave absolute (http://, https://, //) URLs untouched.
    if (preg_match('#^([a-z][a-z0-9+.-]*:)?//#i', $path)) return $path;
    if ($path[0] !== '/') $path = '/' . $path;
    return BASE_URL . $path;
}

// ---------------------------------------------------------------------
// SESSION / AUTH
// ---------------------------------------------------------------------

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            // 'secure' => true, // enable once served over HTTPS
        ]);
        session_start();
    }
    // Session timeout, configurable from Settings > System Settings (default 30 minutes).
    $timeout = (int) setting('session_timeout_minutes', 30) * 60;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        header('Location: ' . url('/login.php?timeout=1'));
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare(
            'SELECT u.*, r.name AS role_name FROM users u
             JOIN roles r ON r.id = u.role_id WHERE u.id = ?'
        );
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . url('/login.php'));
        exit;
    }
}

/**
 * Role-based access control.
 * Usage: require_role(['Super Administrator', 'Administrator']);
 */
function require_role(array $allowedRoles): void
{
    require_login();
    $user = current_user();
    if (!$user || !in_array($user['role_name'], $allowedRoles, true)) {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
}

/**
 * Permission-based access control, backed by the `permissions` table
 * (role_id, permission_key) managed from Settings > Roles & Permissions.
 *
 * This is the newer, dynamic counterpart to require_role() above: any role
 * — including custom roles created from Settings > Role Management — can be
 * granted a permission_key here and immediately gain access to the pages
 * that check for it. Currently used by the Settings area itself
 * (settings.roles, settings.permissions, settings.system, settings.lookups,
 * settings.featured_staff). Existing core modules (Personnel, Allowance,
 * Transfers, Users, Audit Trail, etc.) still use require_role() with the
 * app's 5 built-in roles, so a custom role created here will only govern
 * the newer permission-gated pages until those are migrated too — see
 * README.md for details.
 */
function has_permission(string $permissionKey): bool
{
    $user = current_user();
    if (!$user) return false;
    static $cache = [];
    if (!isset($cache[$user['role_id']])) {
        $stmt = db()->prepare('SELECT permission_key FROM permissions WHERE role_id = ?');
        $stmt->execute([$user['role_id']]);
        $cache[$user['role_id']] = array_column($stmt->fetchAll(), 'permission_key');
    }
    return in_array($permissionKey, $cache[$user['role_id']], true);
}

function require_permission(string $permissionKey): void
{
    require_login();
    if (!has_permission($permissionKey)) {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Invalid or expired form submission (CSRF check failed). Please go back and try again.');
    }
}

// ---------------------------------------------------------------------
// AUDIT / LOGGING
// ---------------------------------------------------------------------

function audit_log(string $action, string $entityType = '', ?int $entityId = null, string $details = ''): void
{
    $stmt = db()->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $action,
        $entityType,
        $entityId,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
}

function log_login_attempt(string $username, string $status, ?int $userId = null): void
{
    $stmt = db()->prepare(
        'INSERT INTO login_logs (user_id, username_attempted, ip_address, status) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $username, $_SERVER['REMOTE_ADDR'] ?? '', $status]);
}

// ---------------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------------

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function format_date(?string $date): string
{
    if (!$date || $date === '0000-00-00') return '—';
    return date('d M Y', strtotime($date));
}

function age_from_dob(?string $dob): ?int
{
    if (!$dob) return null;
    return (new DateTime($dob))->diff(new DateTime())->y;
}

function years_until(?string $date): ?int
{
    if (!$date) return null;
    $now = new DateTime();
    $target = new DateTime($date);
    return (int) $now->diff($target)->format('%r%a') / 365;
}

function paginate(int $page, int $perPage = 25): array
{
    $page = max(1, $page);
    return ['limit' => $perPage, 'offset' => ($page - 1) * $perPage];
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

/** Simple lookup-table loader, cached per-request. */
function lookup(string $table): array
{
    static $cache = [];
    if (!isset($cache[$table])) {
        $allowed = ['departments', 'ranks', 'states', 'local_governments', 'tribes',
                    'geo_political_zones', 'commands', 'banks', 'pfas', 'roles', 'sections'];
        if (!in_array($table, $allowed, true)) {
            throw new InvalidArgumentException('Invalid lookup table');
        }
        $cache[$table] = db()->query("SELECT * FROM `$table` ORDER BY name ASC")->fetchAll();
    }
    return $cache[$table];
}

/**
 * Reads a value from the `settings` table (Settings > System Settings),
 * falling back to $default if the key isn't set. Cached per-request.
 */
function setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}
