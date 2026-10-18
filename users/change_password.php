<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetch()['password_hash'];

    if (!password_verify($current, $hash)) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 10) {
        $error = 'New password must be at least 10 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
        audit_log('PASSWORD_CHANGED', 'user', $_SESSION['user_id']);
        flash('success', 'Password changed successfully.');
        header('Location: ' . url('/dashboard.php'));
        exit;
    }
}

$pageTitle = 'Change Password';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3">Change Password</h4>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="card" style="max-width:480px"><div class="card-body">
  <form method="post">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">New Password (min 10 characters)</label><input type="password" name="new_password" class="form-control" required minlength="10"></div>
    <div class="mb-3"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required minlength="10"></div>
    <button class="btn btn-primary w-100">Update Password</button>
  </form>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
