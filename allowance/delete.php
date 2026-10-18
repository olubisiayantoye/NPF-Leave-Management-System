<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM monthly_allowance_personnel WHERE id = ?');
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    flash('error', 'Allowance record not found.');
} else {
    // Log the full record before deleting it, since this is a hard delete
    // (not an archive) — the audit trail is the only remaining trace.
    audit_log('ALLOWANCE_DELETED', 'monthly_allowance_personnel', $id, json_encode($record));

    db()->prepare('DELETE FROM monthly_allowance_personnel WHERE id = ?')->execute([$id]);
    flash('success', "Allowance record for {$record['full_name']} ({$record['fap_number']}) deleted.");
}

header('Location: ' . url('/allowance/list.php'));
exit;
