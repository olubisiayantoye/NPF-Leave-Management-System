<?php
/**
 * Authentication & authorization.
 * Requires config.php, db.php and functions.php to already be loaded.
 */

const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES    = 15;
const SESSION_IDLE_LIMIT_SECONDS = 30 * 60; // 30 minutes

/** True if a user is currently signed in. */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/** Full current-user row (users table) + their role names, or null. */
function current_user(): ?array
{
    static $user = null;
    if ($user !== null) {
        return $user ?: null;
    }
    if (!is_logged_in()) {
        $user = false;
        return null;
    }

    $stmt = db()->prepare('SELECT id, username, email, full_name, personnel_id, status FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $row = $stmt->fetch();

    if (!$row || $row['status'] !== 'active') {
        session_destroy();
        $user = false;
        return null;
    }

    $roleStmt = db()->prepare(
        'SELECT r.name FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :id'
    );
    $roleStmt->execute(['id' => $row['id']]);
    $row['roles'] = $roleStmt->fetchAll(PDO::FETCH_COLUMN);

    $user = $row;
    return $user;
}

function current_user_has_role(string ...$roles): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    return (bool) array_intersect($roles, $user['roles']);
}

/** Call at the top of any page that requires a signed-in user. */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }

    // Idle session timeout
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_IDLE_LIMIT_SECONDS) {
        $uid = $_SESSION['user_id'] ?? null;
        session_unset();
        session_destroy();
        session_start();
        audit_log($uid, 'SESSION_TIMEOUT');
        flash_set('warning', 'Your session expired due to inactivity. Please sign in again.');
        redirect('auth/login.php');
    }
    $_SESSION['last_activity'] = time();

    if (!current_user()) {
        redirect('auth/login.php');
    }
}

/**
 * Call after require_login() on pages restricted to specific roles.
 * Kept for reference/manual use, but every page in this app now uses
 * require_permission() below instead — role NAMES are hardcoded here,
 * whereas require_permission() consults role_permissions, so an admin
 * can actually reassign what a role can do (Administration → Roles)
 * without a code change.
 */
function require_role(string ...$roles): void
{
    require_login();
    if (!current_user_has_role(...$roles)) {
        http_response_code(403);
        die('You do not have permission to access this page.');
    }
}

/** True if the current user's role(s) grant the given permission code. */
function current_user_has_permission(string $code): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    static $cache = [];
    if (!array_key_exists($user['id'], $cache)) {
        try {
            $stmt = db()->prepare(
                'SELECT DISTINCT p.code FROM permissions p
                 JOIN role_permissions rp ON rp.permission_id = p.id
                 JOIN user_roles ur ON ur.role_id = rp.role_id
                 WHERE ur.user_id = :uid'
            );
            $stmt->execute(['uid' => $user['id']]);
            $cache[$user['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('current_user_has_permission() lookup failed: ' . $e->getMessage());
            $cache[$user['id']] = [];
        }
    }

    return in_array($code, $cache[$user['id']], true);
}

/** Call after require_login() (or standalone — it calls require_login() itself) on pages gated by a permission code. */
function require_permission(string $code): void
{
    require_login();
    if (!current_user_has_permission($code)) {
        http_response_code(403);
        die('You do not have permission to access this page.');
    }
}

/**
 * Attempt to sign a user in. Returns one of:
 *   true            — signed in fully
 *   'mfa_required'  — password correct, awaiting the TOTP code (see auth/mfa_verify.php)
 *   false           — invalid credentials or locked account
 * Handles failed-attempt counting, temporary lockout, and login_logs.
 */
function attempt_login(string $username, string $password): bool|string
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
    $stmt->execute(['u' => $username]);
    $user = $stmt->fetch();

    $log = function (bool $success, ?int $userId) use ($username) {
        $stmt = db()->prepare(
            'INSERT INTO login_logs (user_id, username_attempted, success, ip_address, user_agent)
             VALUES (:uid, :uname, :success, :ip, :ua)'
        );
        $stmt->execute([
            'uid'     => $userId,
            'uname'   => $username,
            'success' => $success ? 1 : 0,
            'ip'      => client_ip(),
            'ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    };

    if (!$user) {
        $log(false, null);
        return false;
    }

    // Locked?
    if ($user['status'] === 'locked' && !empty($user['locked_until'])) {
        if (strtotime($user['locked_until']) > time()) {
            $log(false, $user['id']);
            return false;
        }
        // Lock has expired — reset it.
        db()->prepare('UPDATE users SET status = "active", failed_login_attempts = 0, locked_until = NULL WHERE id = :id')
            ->execute(['id' => $user['id']]);
        $user['status'] = 'active';
        $user['failed_login_attempts'] = 0;
    }

    if ($user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
        $attempts = (int) $user['failed_login_attempts'] + 1;

        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
            db()->prepare('UPDATE users SET failed_login_attempts = :a, status = "locked", locked_until = :until WHERE id = :id')
                ->execute(['a' => $attempts, 'until' => $lockedUntil, 'id' => $user['id']]);
            audit_log($user['id'], 'ACCOUNT_LOCKED', 'users', $user['id'], "Locked after {$attempts} failed attempts");
        } else {
            db()->prepare('UPDATE users SET failed_login_attempts = :a WHERE id = :id')
                ->execute(['a' => $attempts, 'id' => $user['id']]);
        }

        $log(false, $user['id']);
        return false;
    }

    // Success — reset counters, record login. Session ownership (user_id)
    // is only granted here if MFA is off; otherwise it waits for
    // auth/mfa_verify.php to complete the second factor.
    db()->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id')
        ->execute(['id' => $user['id']]);

    session_regenerate_id(true);
    $log(true, $user['id']);
    audit_log($user['id'], 'USER_LOGIN', 'users', $user['id']);

    if (!empty($user['mfa_enabled']) && !empty($user['mfa_secret'])) {
        $_SESSION['mfa_pending_user_id'] = $user['id'];
        return 'mfa_required';
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['last_activity'] = time();

    return true;
}

function logout_user(): void
{
    $uid = $_SESSION['user_id'] ?? null;
    if ($uid) {
        audit_log($uid, 'USER_LOGOUT', 'users', $uid);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
