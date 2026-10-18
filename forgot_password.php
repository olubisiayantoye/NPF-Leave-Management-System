<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND status = "active"');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always show the same message whether or not the email exists, to avoid leaking
    // which addresses are registered.
    $message = 'If that email address is registered, a password reset link has been sent.';

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        db()->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)')
            ->execute([$user['id'], $token, $expires]);

        // TODO: wire up a real mailer (PHPMailer / SMTP). For now the reset link is
        // logged server-side so an administrator can relay it manually if needed.
        $resetLink = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . url('/reset_password.php?token=' . $token);
        error_log("NRMS password reset link for user_id {$user['id']}: $resetLink");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Forgot Password | NRMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center" style="min-height:100vh; background:#0b2545;">
<div class="container"><div class="row justify-content-center"><div class="col-md-5">
  <div class="card shadow-lg border-0"><div class="card-body p-4">
    <h5 class="mb-3">Reset Your Password</h5>
    <?php if ($message): ?><div class="alert alert-info small"><?= e($message) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Registered Email</label>
        <input type="email" name="email" class="form-control" required></div>
      <button class="btn btn-primary w-100">Send Reset Link</button>
      <div class="text-center mt-3"><a href="<?= BASE_URL ?>/login.php" class="small">Back to login</a></div>
    </form>
  </div></div>
</div></div></div>
</body></html>
