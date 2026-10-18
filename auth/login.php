<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Already signed in? Don't show the login form again.
if (is_logged_in() && current_user()) {
    redirect('dashboard/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter both your username and password.';
    } else {
        $result = attempt_login($username, $password);
        if ($result === true) {
            redirect('dashboard/index.php');
        }
        if ($result === 'mfa_required') {
            redirect('auth/mfa_verify.php');
        }
        // Deliberately generic — never reveal whether it was the username
        // or password that was wrong, and never reveal account existence.
        $errors[] = 'Incorrect username or password, or your account is temporarily locked.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in · <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Spectral:wght@600&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/landing.css') ?>">
<style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
    .login-box { background:var(--paper); color:var(--ink); width:100%; max-width:380px; padding:36px 32px; border-radius:2px; }
    .login-box h1 { font-size:1.3rem; margin-bottom:0.2em; }
    .login-box p.sub { font-size:0.85rem; color:#556; margin-bottom:1.6em; }
    .field { margin-bottom: 16px; }
    .field label { display:block; font-family:var(--mono); font-size:0.68rem; letter-spacing:0.06em; text-transform:uppercase; margin-bottom:6px; color:#556; }
    .field input { width:100%; padding:11px 12px; border:1px solid rgba(35,39,31,0.25); border-radius:2px; font-family:var(--sans); font-size:0.92rem; background:#fff; }
    .field input:focus-visible { outline:2px solid var(--gold); outline-offset:1px; }
    .login-box .btn { width:100%; margin-top:6px; }
    .back { display:inline-block; margin-top:20px; font-size:0.8rem; color:#556; }
    .error-box { background:#F3D8D8; color:#7A1F1F; font-size:0.84rem; padding:12px 14px; border-radius:2px; margin-bottom:18px; }
</style>
</head>
<body>
    <div class="login-box">
        <p class="eyebrow" style="color:#8a6d10;">NPF‑LMS · Authorised Personnel Only</p>
        <h1>Sign in</h1>
        <p class="sub">Use the account issued to you by your system administrator.</p>

        <?php if ($errors): ?>
        <div class="error-box">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('auth/login.php') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" autocomplete="username" required autofocus
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-gold">Sign in</button>
        </form>
        <a class="back" href="<?= base_url('/') ?>">&larr; Back to home</a>
    </div>
</body>
</html>
