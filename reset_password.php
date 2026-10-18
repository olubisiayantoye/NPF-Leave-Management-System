<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;

$stmt = db()->prepare(
    'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()'
);
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = 'This password reset link is invalid or has expired. Please request a new one.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 10) {
        $error = 'Password must be at least 10 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $reset['user_id']]);
        db()->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$reset['id']]);
        audit_log('PASSWORD_RESET', 'user', $reset['user_id']);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Reset Password | NRMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="d-flex align-items-center" style="min-height:100vh; background:#0b2545;">
<div class="container"><div class="row justify-content-center"><div class="col-md-5">
<div class="card shadow-lg border-0"><div class="card-body p-4">
<h5 class="mb-3">Set a New Password</h5>
<?php if ($success): ?>
  <div class="alert alert-success">Password updated. You can now log in.</div>
  <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary w-100">Go to Login</a>
<?php else: ?>
  <?php if ($error): ?><div class="alert alert-danger small"><?= e($error) ?></div><?php endif; ?>
  <?php if ($reset): ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="mb-3"><label class="form-label">New Password</label>
      <input type="password" name="password" class="form-control" required minlength="10"></div>
    <div class="mb-3"><label class="form-label">Confirm Password</label>
      <input type="password" name="confirm_password" class="form-control" required minlength="10"></div>
    <button class="btn btn-primary w-100">Update Password</button>
  </form>
  <?php endif; ?>
<?php endif; ?>
</div></div>
</div></div></div>
</body></html>
