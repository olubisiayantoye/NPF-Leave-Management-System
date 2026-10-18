<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_permission('settings.featured_staff');

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$dir = $_GET['dir'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM featured_staff WHERE id = ?');
$stmt->execute([$id]);
$current = $stmt->fetch();

if ($current && in_array($dir, ['up', 'down'], true)) {
    $neighborStmt = $pdo->prepare(
        $dir === 'up'
            ? 'SELECT * FROM featured_staff WHERE display_order < ? ORDER BY display_order DESC LIMIT 1'
            : 'SELECT * FROM featured_staff WHERE display_order > ? ORDER BY display_order ASC LIMIT 1'
    );
    $neighborStmt->execute([$current['display_order']]);
    $neighbor = $neighborStmt->fetch();

    if ($neighbor) {
        $pdo->prepare('UPDATE featured_staff SET display_order = ? WHERE id = ?')->execute([$neighbor['display_order'], $current['id']]);
        $pdo->prepare('UPDATE featured_staff SET display_order = ? WHERE id = ?')->execute([$current['display_order'], $neighbor['id']]);
        audit_log('FEATURED_STAFF_REORDERED', 'featured_staff', $id, "Moved $dir");
    }
}

header('Location: ' . url('/settings/featured_staff.php'));
exit;
