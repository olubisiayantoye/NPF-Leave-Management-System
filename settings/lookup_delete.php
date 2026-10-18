<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lookup_config.php';
start_secure_session();
require_permission('settings.lookups');

$pdo = db();

$manageable = array_keys(manageable_lookup_tables());
$usageChecks = lookup_usage_checks();

$table = $_GET['table'] ?? '';
$id = (int)($_GET['id'] ?? 0);
if (!in_array($table, $manageable, true) || !$id) { header('Location: ' . url('/settings/lookups.php')); exit; }

foreach ($usageChecks[$table] ?? [] as $sql) {
    $chk = $pdo->prepare($sql);
    $chk->execute([$id]);
    if ($chk->fetchColumn() > 0) {
        flash('error', 'This entry is still in use by existing records and cannot be deleted.');
        header('Location: ' . url("/settings/lookups.php?table=$table"));
        exit;
    }
}

$stmt = $pdo->prepare("SELECT name FROM `$table` WHERE id = ?");
$stmt->execute([$id]);
$name = $stmt->fetchColumn();

if ($name !== false) {
    $pdo->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
    audit_log('LOOKUP_DELETED', $table, $id, "Deleted: $name");
    flash('success', "'$name' deleted.");
} else {
    flash('error', 'Entry not found.');
}

header('Location: ' . url("/settings/lookups.php?table=$table"));
exit;
