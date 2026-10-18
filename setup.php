<?php
/**
 * ONE-TIME SETUP SCRIPT
 * Run this once, right after importing database/schema.sql, to create the
 * first Super Administrator account. DELETE THIS FILE (or rename it out of
 * the web root) immediately after use — it is a security risk to leave it
 * reachable in production.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

// Refuse to run if any user already exists — prevents re-use as a backdoor.
$existing = db()->query('SELECT COUNT(*) c FROM users')->fetch();
if ($existing['c'] > 0) {
    die('Setup has already been completed. Delete setup.php. If you are locked out, '
      . 'reset a password directly in the database instead of re-running this script.');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$username || !$email || !$fullName || !$password) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 10) {
        $error = 'Password must be at least 10 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role_id, status)
             VALUES (?, ?, ?, ?, 1, "active")'
        );
        $stmt->execute([$username, $email, $hash, $fullName]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>NRMS Initial Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:480px; margin-top:4rem;">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h4 class="mb-3">NRMS Initial Setup</h4>
      <p class="text-muted small">Create the first Super Administrator account.</p>
      <?php if ($success): ?>
        <div class="alert alert-success">Super Administrator account created successfully.
        <strong>Now delete setup.php from the server.</strong></div>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary w-100">Go to Login</a>
      <?php else: ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
          <div class="mb-2"><label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Password (min 10 characters)</label>
            <input type="password" name="password" class="form-control" required minlength="10"></div>
          <div class="mb-3"><label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="10"></div>
          <button class="btn btn-primary w-100" type="submit">Create Super Administrator</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
