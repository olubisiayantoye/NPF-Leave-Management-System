<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lookup_config.php';
start_secure_session();
require_permission('settings.lookups');

$pdo = db();

$manageable = manageable_lookup_tables();

$table = $_GET['table'] ?? '';
$id = (int)($_GET['id'] ?? 0);
if (!isset($manageable[$table]) || !$id) { header('Location: ' . url('/settings/lookups.php')); exit; }
$meta = $manageable[$table];

$stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { flash('error', 'Entry not found.'); header('Location: ' . url("/settings/lookups.php?table=$table")); exit; }

$parentOptions = [];
if (!empty($meta['parent'])) {
    $parentOptions = $pdo->query("SELECT id, name FROM `{$meta['parent']}` ORDER BY name")->fetchAll();
}

$pageTitle = 'Edit ' . $meta['label'];
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3">Edit <?= e($meta['label']) ?></h4>
<div class="card" style="max-width:480px"><div class="card-body">
  <form method="post" action="<?= BASE_URL ?>/settings/lookup_save.php">
    <?= csrf_field() ?>
    <input type="hidden" name="table" value="<?= e($table) ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="mb-3">
      <label class="form-label"><?= e($meta['label']) ?> Name</label>
      <input type="text" name="name" class="form-control" required value="<?= e($row['name']) ?>">
    </div>
    <?php if (!empty($meta['extra'])): ?>
    <div class="mb-3">
      <label class="form-label">Hierarchy Level (lower = senior)</label>
      <input type="number" name="<?= e($meta['extra']) ?>" class="form-control" value="<?= e((string)($row[$meta['extra']] ?? '')) ?>">
    </div>
    <?php endif; ?>
    <?php if (!empty($meta['parent'])): ?>
    <div class="mb-3">
      <label class="form-label"><?= e($manageable[$meta['parent']]['label']) ?></label>
      <select name="<?= e($meta['parent_col']) ?>" class="form-select" required>
        <?php foreach ($parentOptions as $po): ?>
          <option value="<?= $po['id'] ?>" <?= $po['id'] == $row[$meta['parent_col']] ? 'selected' : '' ?>><?= e($po['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <button class="btn btn-primary w-100">Save Changes</button>
  </form>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
