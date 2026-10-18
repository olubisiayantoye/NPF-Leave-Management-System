<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if (($_POST['form_action'] ?? '') === 'mark_all_read') {
        db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :id')->execute(['id' => $user['id']]);
    } elseif (($_POST['form_action'] ?? '') === 'mark_read') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid')->execute(['id' => $id, 'uid' => $user['id']]);
    }
    redirect('notifications/index.php');
}

$stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = :id ORDER BY created_at DESC LIMIT 100');
$stmt->execute(['id' => $user['id']]);
$notifications = $stmt->fetchAll();

$typeIcon = ['info' => 'bi-info-circle text-primary', 'success' => 'bi-check-circle text-success', 'warning' => 'bi-exclamation-triangle text-warning', 'danger' => 'bi-x-circle text-danger'];

$pageTitle = 'Notifications';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-end mb-3">
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="mark_all_read">
        <button type="submit" class="btn btn-sm btn-outline-dark">Mark all as read</button>
    </form>
</div>

<div class="card border-0 shadow-sm">
<?php if (!$notifications): ?>
    <div class="card-body text-center text-muted py-5">No notifications yet.</div>
<?php endif; ?>
<?php foreach ($notifications as $n): ?>
<div class="d-flex align-items-start gap-3 px-4 py-3 border-bottom <?= $n['is_read'] ? '' : 'bg-light' ?>">
    <i class="bi <?= $typeIcon[$n['type']] ?? 'bi-info-circle' ?> fs-5"></i>
    <div class="flex-grow-1">
        <div><?= e($n['message']) ?></div>
        <div class="text-muted small"><?= e($n['created_at']) ?></div>
    </div>
    <?php if (!$n['is_read']): ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="mark_read">
        <input type="hidden" name="id" value="<?= $n['id'] ?>">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Mark read</button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
