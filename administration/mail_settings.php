<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('administration.manage_users'); // mail config is account-security-adjacent; keep it super_admin-only
$user = current_user();
$errors = [];

$settingKeys = [
    'email_notifications_enabled', 'mail_driver', 'mail_from_address', 'mail_from_name',
    'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
];

function get_setting(string $key, string $default = ''): string
{
    $stmt = db()->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :k');
    $stmt->execute(['k' => $key]);
    $v = $stmt->fetchColumn();
    return $v !== false ? $v : $default;
}

function set_setting(string $key, string $value): void
{
    db()->prepare(
        'INSERT INTO system_settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    )->execute(['k' => $key, 'v' => $value]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        set_setting('email_notifications_enabled', isset($_POST['enabled']) ? '1' : '0');
        set_setting('mail_driver', in_array($_POST['mail_driver'] ?? '', ['mail', 'smtp'], true) ? $_POST['mail_driver'] : 'mail');
        set_setting('mail_from_address', trim($_POST['mail_from_address'] ?? ''));
        set_setting('mail_from_name', trim($_POST['mail_from_name'] ?? ''));
        set_setting('smtp_host', trim($_POST['smtp_host'] ?? ''));
        set_setting('smtp_port', trim($_POST['smtp_port'] ?? '587'));
        set_setting('smtp_username', trim($_POST['smtp_username'] ?? ''));
        if (trim($_POST['smtp_password'] ?? '') !== '') {
            // Only overwrite if a new value was actually typed — the field is masked/blank on reload.
            set_setting('smtp_password', $_POST['smtp_password']);
        }
        set_setting('smtp_encryption', in_array($_POST['smtp_encryption'] ?? '', ['tls', 'ssl', 'none'], true) ? $_POST['smtp_encryption'] : 'tls');

        audit_log($user['id'], 'MAIL_SETTINGS_UPDATED', 'system_settings', null);
        flash_set('success', 'Email settings saved.');
        redirect('administration/mail_settings.php');

    } elseif ($action === 'send_test') {
        $sent = send_email($user['email'] ?: '', $user['full_name'], 'Test email from ' . APP_SHORT_NAME,
            "This is a test message to confirm email delivery is working.\n\nSent " . date('Y-m-d H:i:s') . '.');
        if (!$user['email']) {
            flash_set('error', 'Your account has no email address on file — add one first (Account Security won\'t help here; ask a super admin to set it).');
        } elseif ($sent) {
            flash_set('success', "Test email sent to {$user['email']}. If it doesn't arrive, check your driver settings and server logs.");
        } else {
            flash_set('error', 'Could not send the test email. Check that notifications are enabled and your driver settings are correct — see the server error log for details.');
        }
        redirect('administration/mail_settings.php');
    }
}

$settings = [];
foreach ($settingKeys as $k) {
    $settings[$k] = get_setting($k);
}

$pageTitle = 'Email Settings';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Delivery Settings</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="save">

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="enabled" id="enabled" <?= $settings['email_notifications_enabled'] === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enabled"><strong>Send email notifications</strong> — off by default; every in-app notification (submissions, approvals, disapprovals, cancellations, curtailments) will also email the recipient once this is on and a driver is configured below.</label>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Driver</label>
                    <select name="mail_driver" class="form-select" id="mail_driver">
                        <option value="mail" <?= $settings['mail_driver'] === 'mail' ? 'selected' : '' ?>>PHP mail() — uses the server's local sendmail/postfix</option>
                        <option value="smtp" <?= $settings['mail_driver'] === 'smtp' ? 'selected' : '' ?>>SMTP — relay through an external mail server</option>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase">From Address</label>
                        <input type="email" name="mail_from_address" class="form-control" value="<?= e($settings['mail_from_address']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase">From Name</label>
                        <input type="text" name="mail_from_name" class="form-control" value="<?= e($settings['mail_from_name']) ?>">
                    </div>
                </div>

                <div id="smtp-fields" style="<?= $settings['mail_driver'] === 'smtp' ? '' : 'display:none;' ?>">
                    <hr>
                    <h3 class="h6 mb-3">SMTP Server</h3>
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small text-muted text-uppercase">Host</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?= e($settings['smtp_host']) ?>" placeholder="smtp.example.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted text-uppercase">Port</label>
                            <input type="number" name="smtp_port" class="form-control" value="<?= e($settings['smtp_port'] ?: '587') ?>">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small text-muted text-uppercase">Username</label>
                            <input type="text" name="smtp_username" class="form-control" value="<?= e($settings['smtp_username']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted text-uppercase">Password</label>
                            <input type="password" name="smtp_password" class="form-control" placeholder="<?= $settings['smtp_password'] ? '••••••••  (leave blank to keep)' : '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted text-uppercase">Encryption</label>
                            <select name="smtp_encryption" class="form-select">
                                <option value="tls" <?= $settings['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>STARTTLS</option>
                                <option value="ssl" <?= $settings['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= $settings['smtp_encryption'] === 'none' ? 'selected' : '' ?>>None</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-npf-gold">Save Settings</button>
            </form>
        </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Test Delivery</h2>
            <p class="text-muted small">Sends a test email to your own account (<?= e($user['email'] ?: 'no email on file') ?>) using the settings currently saved.</p>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="send_test">
                <button type="submit" class="btn btn-outline-dark">Send Test Email</button>
            </form>
        </div>
        </div>
    </div>
</div>

<script>
document.getElementById('mail_driver').addEventListener('change', function () {
    document.getElementById('smtp-fields').style.display = this.value === 'smtp' ? '' : 'none';
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
