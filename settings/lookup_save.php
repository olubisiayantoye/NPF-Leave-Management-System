<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lookup_config.php';
start_secure_session();
require_permission('settings.lookups');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . url('/settings/lookups.php')); exit; }
verify_csrf();

$pdo = db();

$manageable = manageable_lookup_tables();

$table = $_POST['table'] ?? '';
if (!isset($manageable[$table])) { flash('error', 'Invalid lookup category.'); header('Location: ' . url('/settings/lookups.php')); exit; }
$meta = $manageable[$table];

$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');

if (!$name) {
    flash('error', 'Name is required.');
    header('Location: ' . url($id ? "/settings/lookup_edit.php?table=$table&id=$id" : "/settings/lookups.php?table=$table"));
    exit;
}

$dup = $pdo->prepare("SELECT id FROM `$table` WHERE name = ? AND id != ?");
$dup->execute([$name, $id]);
if ($dup->fetch()) {
    flash('error', "'$name' already exists in $table.");
    header('Location: ' . url($id ? "/settings/lookup_edit.php?table=$table&id=$id" : "/settings/lookups.php?table=$table"));
    exit;
}

$extraCol = $meta['extra'] ?? null;
$extraVal = $extraCol ? (trim((string)($_POST[$extraCol] ?? '')) ?: null) : null;

$parentCol = $meta['parent_col'] ?? null;
$parentVal = $parentCol ? (int)($_POST[$parentCol] ?? 0) : null;
if ($parentCol && !$parentVal) {
    flash('error', 'Please select a ' . $manageable[$meta['parent']]['label'] . '.');
    header('Location: ' . url($id ? "/settings/lookup_edit.php?table=$table&id=$id" : "/settings/lookups.php?table=$table"));
    exit;
}

if ($id) {
    $sets = ['name = :name'];
    $params = ['name' => $name, 'id' => $id];
    if ($extraCol) { $sets[] = "$extraCol = :extra"; $params['extra'] = $extraVal; }
    if ($parentCol) { $sets[] = "$parentCol = :parent"; $params['parent'] = $parentVal; }
    $pdo->prepare("UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = :id")->execute($params);
    audit_log('LOOKUP_UPDATED', $table, $id, "Updated to: $name");
    flash('success', ucfirst($meta['label']) . ' updated.');
} else {
    $cols = ['name'];
    $params = ['name' => $name];
    if ($extraCol) { $cols[] = $extraCol; $params['extra'] = $extraVal; }
    if ($parentCol) { $cols[] = $parentCol; $params['parent'] = $parentVal; }
    $placeholders = array_map(fn($c) => ':' . ($c === $extraCol ? 'extra' : ($c === $parentCol ? 'parent' : $c)), $cols);
    $pdo->prepare("INSERT INTO `$table` (" . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')')->execute($params);
    audit_log('LOOKUP_CREATED', $table, $pdo->lastInsertId(), "Created: $name");
    flash('success', ucfirst($meta['label']) . ' added.');
}

header('Location: ' . url("/settings/lookups.php?table=$table"));
exit;
