<?php
/**
 * Shared header for internal (logged-in) pages.
 * Expects require_login() to already have been called by the including page,
 * and $pageTitle to be set.
 */
$user = current_user();
$pageTitle = $pageTitle ?? 'Dashboard';

$unreadCount = 0;
$recentNotifications = [];
if ($user) {
    try {
        $countStmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :id AND is_read = 0');
        $countStmt->execute(['id' => $user['id']]);
        $unreadCount = (int) $countStmt->fetchColumn();

        $recentStmt = db()->prepare('SELECT * FROM notifications WHERE user_id = :id ORDER BY created_at DESC LIMIT 6');
        $recentStmt->execute(['id' => $user['id']]);
        $recentNotifications = $recentStmt->fetchAll();
    } catch (PDOException $e) {
        // Notifications are a convenience, never a hard dependency for the page to render.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · <?= APP_SHORT_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Spectral:wght@600&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="app-main">
    <div class="app-topbar">
        <h1><?= e($pageTitle) ?></h1>
        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <?php if ($unreadCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;">
                        <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                    </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width:340px; max-height:400px; overflow-y:auto;">
                    <div class="px-3 py-2 border-bottom small fw-semibold">Notifications</div>
                    <?php if (!$recentNotifications): ?>
                    <div class="px-3 py-4 text-center text-muted small">Nothing yet.</div>
                    <?php endif; ?>
                    <?php foreach ($recentNotifications as $n): ?>
                    <div class="px-3 py-2 border-bottom small <?= $n['is_read'] ? '' : 'bg-light' ?>">
                        <?= e($n['message']) ?>
                        <div class="text-muted" style="font-size:.7rem;"><?= e($n['created_at']) ?></div>
                    </div>
                    <?php endforeach; ?>
                    <a href="<?= base_url('notifications/index.php') ?>" class="d-block text-center py-2 small">View all</a>
                </div>
            </div>
            <span class="text-muted small">
                <a href="<?= base_url('account/security.php') ?>" class="text-decoration-none text-muted">
                    <?= e($user['full_name'] ?? '') ?>
                </a>
            </span>
            <a href="<?= base_url('auth/logout.php') ?>" class="btn btn-sm btn-outline-secondary">Sign out</a>
        </div>
    </div>
    <div class="app-content">
        <?php $flash = flash_get(); if ($flash): ?>
        <div class="alert alert-<?= e($flash['type'] === 'error' ? 'danger' : $flash['type']) ?>" role="alert">
            <?= e($flash['message']) ?>
        </div>
        <?php endif; ?>
