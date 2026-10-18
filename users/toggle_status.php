<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$id = (int)($_GET['id'] ?? 0);
if ($id === (int)$_SESSION['user_id']) {
    flash('error', 'You cannot disable your own account.');
    header('Location: ' . url('/users/list.php'));
    exit;
}

$stmt = db()->prepare('SELECT status FROM users WHERE id = ?');
$stmt->execute([$id]);
$u = $stmt->fetch();
if ($u) {
    $newStatus = $u['status'] === 'active' ? 'disabled' : 'active';
    db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
    audit_log('USER_STATUS_CHANGED', 'user', $id, "Status set to $newStatus");
    flash('success', "User account $newStatus.");
}
header('Location: ' . url('/users/list.php'));
exit;
