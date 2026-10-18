<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_permission('settings.featured_staff');

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT fs.*, p.full_name FROM featured_staff fs JOIN personnel p ON p.id = fs.personnel_id WHERE fs.id = ?'
);
$stmt->execute([$id]);
$row = $stmt->fetch();

if ($row) {
    $pdo->prepare('DELETE FROM featured_staff WHERE id = ?')->execute([$id]);
    audit_log('FEATURED_STAFF_REMOVED', 'personnel', $row['personnel_id'], "Removed {$row['full_name']} from front page");
    flash('success', "{$row['full_name']} removed from the front page.");
} else {
    flash('error', 'Entry not found.');
}

$redirect = $_GET['redirect_to'] ?? '';
if (!preg_match('#^/personnel/view\.php\?id=\d+$#', $redirect)) {
    $redirect = '/settings/featured_staff.php';
}
header('Location: ' . url($redirect));
exit;
