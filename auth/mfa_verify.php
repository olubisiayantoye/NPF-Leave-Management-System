<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/totp.php';

if (empty($_SESSION['mfa_pending_user_id'])) {
    redirect('auth/login.php');
}
$pendingUserId = (int) $_SESSION['mfa_pending_user_id'];

$stmt = db()->prepare('SELECT id, username, full_name, mfa_secret, status FROM users WHERE id = :id');
$stmt->execute(['id' => $pendingUserId]);
$pendingUser = $stmt->fetch();

if (!$pendingUser || $pendingUser['status'] !== 'active') {
    unset($_SESSION['mfa_pending_user_id']);
    redirect('auth/login.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $code = trim($_POST['code'] ?? '');

    if (totp_verify($pendingUser['mfa_secret'], $code)) {
        unset($_SESSION['mfa_pending_user_id']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $pendingUser['id'];
        $_SESSION['last_activity'] = time();

        audit_log($pendingUser['id'], 'MFA_VERIFIED', 'users', $pendingUser['id']);
        redirect('dashboard/index.php');
    }

    audit_log($pendingUser['id'], 'MFA_VERIFICATION_FAILED', 'users', $pendingUser['id']);
    $errors[] = 'That code was incorrect or has expired. Codes refresh every 30 seconds — try the current one from your authenticator app.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify · <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Spectral:wght@600&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/landing.css') ?>">
<style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
    .box { background:var(--paper); color:var(--ink); width:100%; max-width:380px; padding:36px 32px; border-radius:2px; }
    .box h1 { font-size:1.3rem; margin-bottom:0.2em; }
    .box p.sub { font-size:0.85rem; color:#556; margin-bottom:1.6em; }
    .field input { width:100%; padding:14px 12px; border:1px solid rgba(35,39,31,0.25); border-radius:2px;
                   font-family:var(--mono); font-size:1.4rem; letter-spacing:0.3em; text-align:center; background:#fff; }
    .box .btn { width:100%; margin-top:16px; }
    .back { display:inline-block; margin-top:20px; font-size:0.8rem; color:#556; }
    .error-box { background:#F3D8D8; color:#7A1F1F; font-size:0.84rem; padding:12px 14px; border-radius:2px; margin-bottom:18px; }
</style>
</head>
<body>
    <div class="box">
        <p class="eyebrow" style="color:#8a6d10;">Two-Factor Verification</p>
        <h1>Enter your code</h1>
        <p class="sub">Signing in as <strong><?= e($pendingUser['full_name']) ?></strong>. Open your authenticator app and enter the current 6-digit code.</p>

        <?php if ($errors): ?>
        <div class="error-box"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('auth/mfa_verify.php') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <input type="text" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6"
                       autocomplete="one-time-code" required autofocus placeholder="000000">
            </div>
            <button type="submit" class="btn btn-gold">Verify &amp; Sign In</button>
        </form>
        <a class="back" href="<?= base_url('auth/logout.php') ?>">&larr; Cancel and sign out</a>
    </div>
</body>
</html>
