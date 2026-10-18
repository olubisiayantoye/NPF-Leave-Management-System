<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_permission('settings.featured_staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . url('/settings/featured_staff.php')); exit; }
verify_csrf();

$pdo = db();
$personnelId = (int)($_POST['personnel_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, full_name FROM personnel WHERE id = ? AND status != 'Archived'");
$stmt->execute([$personnelId]);
$personnel = $stmt->fetch();

if (!$personnel) {
    flash('error', 'Please select a valid personnel record from the list.');
} else {
    $dup = $pdo->prepare('SELECT id FROM featured_staff WHERE personnel_id = ?');
    $dup->execute([$personnelId]);
    if ($dup->fetch()) {
        flash('error', "{$personnel['full_name']} is already featured on the front page.");
    } else {
        $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(display_order), 0) FROM featured_staff')->fetchColumn();
        $pdo->prepare('INSERT INTO featured_staff (personnel_id, display_order, added_by) VALUES (?, ?, ?)')
            ->execute([$personnelId, $maxOrder + 1, $_SESSION['user_id']]);
        audit_log('FEATURED_STAFF_ADDED', 'personnel', $personnelId, "Added {$personnel['full_name']} to front page");
        flash('success', "{$personnel['full_name']} added to the front page.");
    }
}

$redirect = $_POST['redirect_to'] ?? '';
// Only allow redirecting back to a personnel profile or the featured-staff page itself —
// never trust an arbitrary user-supplied path as a redirect target (open-redirect risk).
if (!preg_match('#^/personnel/view\.php\?id=\d+$#', $redirect)) {
    $redirect = '/settings/featured_staff.php';
}
header('Location: ' . url($redirect));
exit;
