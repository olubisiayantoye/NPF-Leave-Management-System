<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'archive'; // archive | restore

$stmt = db()->prepare('SELECT id, full_name, status FROM personnel WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    flash('error', 'Personnel record not found.');
} elseif ($action === 'restore') {
    db()->prepare('UPDATE personnel SET status = "Active" WHERE id = ?')->execute([$id]);
    audit_log('PERSONNEL_RESTORED', 'personnel', $id, "Restored: {$p['full_name']}");
    flash('success', 'Personnel record restored.');
} else {
    db()->prepare('UPDATE personnel SET status = "Archived" WHERE id = ?')->execute([$id]);
    audit_log('PERSONNEL_ARCHIVED', 'personnel', $id, "Archived: {$p['full_name']}");
    flash('success', 'Personnel record archived. It can be restored by a Super Administrator or Administrator.');
}

header('Location: ' . url('/personnel/list.php'));
exit;
