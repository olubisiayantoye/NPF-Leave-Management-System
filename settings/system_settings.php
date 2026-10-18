<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_permission('settings.system');

$pdo = db();

// The known settings the app actually reads. Extending this array is how you
// add a new configurable setting — the app reads it elsewhere via setting('key', $default).
$fields = [
    'site_name' => ['label' => 'Site Name', 'type' => 'text', 'help' => 'Shown in the page title and on the front page.'],
    'records_per_page' => ['label' => 'Records Per Page', 'type' => 'number', 'help' => 'Used by Personnel and Monthly Allowance list pagination.'],
    'session_timeout_minutes' => ['label' => 'Session Timeout (minutes)', 'type' => 'number', 'help' => 'Minutes of inactivity before a user is logged out automatically.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $errors = [];
    $values = [];
    foreach ($fields as $key => $meta) {
        $v = trim($_POST[$key] ?? '');
        if ($meta['type'] === 'number' && $v !== '' && (!ctype_digit($v) || (int)$v < 1)) {
            $errors[] = "{$meta['label']} must be a positive whole number.";
        }
        $values[$key] = $v;
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($values as $key => $v) {
            $stmt->execute([$key, $v]);
        }
        audit_log('SYSTEM_SETTINGS_UPDATED', 'settings', null, json_encode($values));
        flash('success', 'System settings updated.');
        header('Location: ' . url('/settings/system_settings.php'));
        exit;
    }
}

$current = [];
foreach ($fields as $key => $meta) {
    $current[$key] = setting($key, '');
}
if (isset($errors) && $errors) {
    $current = array_merge($current, $values);
}

$pageTitle = 'System Settings';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-gear"></i> System Settings</h4>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?= implode(' ', array_map('e', $errors)) ?></div><?php endif; ?>
<div class="card" style="max-width:560px"><div class="card-body">
  <form method="post">
    <?= csrf_field() ?>
    <?php foreach ($fields as $key => $meta): ?>
      <div class="mb-3">
        <label class="form-label"><?= e($meta['label']) ?></label>
        <input type="<?= $meta['type'] ?>" name="<?= e($key) ?>" class="form-control" value="<?= e((string)$current[$key]) ?>">
        <div class="form-text"><?= e($meta['help']) ?></div>
      </div>
    <?php endforeach; ?>
    <button class="btn btn-primary w-100">Save Settings</button>
  </form>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
