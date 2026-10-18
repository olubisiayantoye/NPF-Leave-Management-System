<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/totp.php';

require_login();
$user = current_user();
$errors = [];
$success = null;

$stmt = db()->prepare('SELECT mfa_enabled, mfa_secret FROM users WHERE id = :id');
$stmt->execute(['id' => $user['id']]);
$mfaRow = $stmt->fetch();
$mfaEnabled = (bool) $mfaRow['mfa_enabled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['form_action'] ?? '';

    // -------------------------------------------------------------
    // Start MFA enrollment — generate a secret, hold it in session
    // until the user proves they've actually added it to an app.
    // -------------------------------------------------------------
    if ($action === 'mfa_start') {
        $_SESSION['pending_mfa_secret'] = totp_generate_secret();
    }

    // -------------------------------------------------------------
    // Confirm enrollment
    // -------------------------------------------------------------
    elseif ($action === 'mfa_confirm') {
        $secret = $_SESSION['pending_mfa_secret'] ?? '';
        $code = trim($_POST['code'] ?? '');

        if (!$secret) {
            $errors[] = 'Your enrollment session expired — please start again.';
        } elseif (!totp_verify($secret, $code)) {
            $errors[] = 'That code did not match. Make sure your device clock is correct and try the current code.';
        } else {
            db()->prepare('UPDATE users SET mfa_secret = :s, mfa_enabled = 1 WHERE id = :id')
                ->execute(['s' => $secret, 'id' => $user['id']]);
            unset($_SESSION['pending_mfa_secret']);
            audit_log($user['id'], 'MFA_ENABLED', 'users', $user['id']);
            flash_set('success', 'Two-factor authentication is now enabled on your account.');
            redirect('account/security.php');
        }
    }

    // -------------------------------------------------------------
    // Disable MFA — requires current password
    // -------------------------------------------------------------
    elseif ($action === 'mfa_disable') {
        $password = (string) ($_POST['password'] ?? '');
        $pwStmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id');
        $pwStmt->execute(['id' => $user['id']]);
        $hash = $pwStmt->fetchColumn();

        if (!password_verify($password, $hash)) {
            $errors[] = 'Incorrect password.';
        } else {
            db()->prepare('UPDATE users SET mfa_secret = NULL, mfa_enabled = 0 WHERE id = :id')->execute(['id' => $user['id']]);
            audit_log($user['id'], 'MFA_DISABLED', 'users', $user['id']);
            flash_set('success', 'Two-factor authentication has been disabled.');
            redirect('account/security.php');
        }
    }

    // -------------------------------------------------------------
    // Change password
    // -------------------------------------------------------------
    elseif ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $pwStmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id');
        $pwStmt->execute(['id' => $user['id']]);
        $hash = $pwStmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 10) {
            $errors[] = 'New password must be at least 10 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
                ->execute(['h' => password_hash($new, PASSWORD_DEFAULT), 'id' => $user['id']]);
            audit_log($user['id'], 'PASSWORD_CHANGED', 'users', $user['id']);
            flash_set('success', 'Password updated.');
            redirect('account/security.php');
        }
    }
}

$pendingSecret = $_SESSION['pending_mfa_secret'] ?? null;
$provisioningUri = $pendingSecret ? totp_provisioning_uri($pendingSecret, $user['username']) : null;

$pageTitle = 'Account Security';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Change Password</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="change_password">
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="10">
                    <div class="form-text">At least 10 characters.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="10">
                </div>
                <button type="submit" class="btn btn-npf-gold">Update Password</button>
            </form>
        </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Two-Factor Authentication</h2>

            <?php if ($mfaEnabled): ?>
                <p class="text-muted small">
                    <i class="bi bi-shield-check text-success"></i> Enabled — your account requires a code from your authenticator app at every sign-in.
                </p>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="mfa_disable">
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase">Confirm your password to disable</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Disable two-factor authentication?');">Disable 2FA</button>
                </form>

            <?php elseif ($pendingSecret): ?>
                <p class="text-muted small">Add this account to your authenticator app (Google Authenticator, Authy, 1Password, etc.), then enter the 6-digit code it shows.</p>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Manual Entry Key</label>
                    <input type="text" class="form-control font-monospace" value="<?= e($pendingSecret) ?>" readonly onclick="this.select()">
                    <div class="form-text">No camera handy? Most apps let you type this key in directly instead of scanning a QR code.</div>
                </div>
                <form method="post" class="d-flex gap-2 align-items-end flex-wrap">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="mfa_confirm">
                    <div>
                        <label class="form-label small text-muted text-uppercase">6-Digit Code</label>
                        <input type="text" name="code" class="form-control font-monospace" style="max-width:140px;" inputmode="numeric" maxlength="6" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-npf-gold">Confirm &amp; Enable</button>
                </form>

            <?php else: ?>
                <p class="text-muted small">Not enabled. Adding a second factor means signing in needs your password <em>and</em> a rotating code from your phone.</p>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="mfa_start">
                    <button type="submit" class="btn btn-outline-dark">Set Up Two-Factor Authentication</button>
                </form>
            <?php endif; ?>
        </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
