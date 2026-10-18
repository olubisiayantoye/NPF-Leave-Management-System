<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();

if (is_logged_in()) {
    header('Location: ' . url('/dashboard.php'));
    exit;
}

$error = '';
$MAX_ATTEMPTS = 5;
$LOCKOUT_MINUTES = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic rate limiting: count recent failed attempts for this username
    $stmt = db()->prepare(
        'SELECT COUNT(*) c FROM login_logs
         WHERE username_attempted = ? AND status = "failed"
         AND created_at > (NOW() - INTERVAL ? MINUTE)'
    );
    $stmt->execute([$username, $LOCKOUT_MINUTES]);
    $recentFails = $stmt->fetch()['c'];

    if ($recentFails >= $MAX_ATTEMPTS) {
        $error = "Too many failed attempts. Please try again in $LOCKOUT_MINUTES minutes.";
    } else {
        $stmt = db()->prepare(
            'SELECT u.*, r.name AS role_name FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.username = ? AND u.status = "active"'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            log_login_attempt($username, 'success', $user['id']);
            db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
            audit_log('LOGIN', 'user', $user['id']);
            header('Location: ' . url('/dashboard.php'));
            exit;
        } else {
            log_login_attempt($username, 'failed');
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | NRMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center" style="min-height:100vh; background: linear-gradient(135deg,#0b2545,#071a33);">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-lg border-0">
        <div class="card-body p-4">
          <div class="text-center mb-4">
            <i class="bi bi-shield-shaded display-4 text-primary"></i>
            <h4 class="mt-2 mb-0">Nominal Roll Management System</h4>
            <div class="text-muted small">IGP-Secretariat &mdash; Nigeria Police Force</div>
          </div>
          <?php if (!empty($_GET['timeout'])): ?>
            <div class="alert alert-warning small">Your session expired due to inactivity. Please log in again.</div>
          <?php endif; ?>
          <?php if ($error): ?><div class="alert alert-danger small"><?= e($error) ?></div><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
            <div class="text-center mt-3">
              <a href="<?= BASE_URL ?>/forgot_password.php" class="small">Forgot password?</a>
            </div>
          </form>
        </div>
      </div>
      <p class="text-center text-white-50 small mt-3">&copy; <?= date('Y') ?> Nigeria Police Force</p>
    </div>
  </div>
</div>
</body>
</html>
