<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_permission('settings.roles');

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM roles WHERE id = ?');
$stmt->execute([$id]);
$role = $stmt->fetch();

if (!$role) {
    flash('error', 'Role not found.');
} else {
    $countStmt = $pdo->prepare('SELECT COUNT(*) c FROM users WHERE role_id = ?');
    $countStmt->execute([$id]);
    if ($countStmt->fetch()['c'] > 0) {
        flash('error', 'Cannot delete a role that still has users assigned to it. Reassign those users first.');
    } else {
        // permissions rows for this role cascade-delete via the FK ON DELETE CASCADE
        $pdo->prepare('DELETE FROM roles WHERE id = ?')->execute([$id]);
        audit_log('ROLE_DELETED', 'role', $id, "Deleted role: {$role['name']}");
        flash('success', "Role '{$role['name']}' deleted.");
    }
}

header('Location: ' . url('/settings/roles.php'));
exit;
