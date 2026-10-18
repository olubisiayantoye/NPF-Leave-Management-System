<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lookup_config.php';
start_secure_session();
require_permission('settings.lookups');

$pdo = db();

// Whitelist of manageable lookup tables. 'parent' names a table this one's
// rows reference (rendered as a dropdown); 'extra' names an additional plain
// value column some lookup tables carry (e.g. ranks.hierarchy_level).
$manageable = manageable_lookup_tables();

$table = $_GET['table'] ?? 'ranks';
if (!isset($manageable[$table])) $table = 'ranks';
$meta = $manageable[$table];

$parentOptions = [];
if (!empty($meta['parent'])) {
    $parentOptions = $pdo->query("SELECT id, name FROM `{$meta['parent']}` ORDER BY name")->fetchAll();
}

$joinSql = '';
$selectExtra = '';
if (!empty($meta['parent'])) {
    $joinSql = "LEFT JOIN `{$meta['parent']}` p ON p.id = t.{$meta['parent_col']}";
    $selectExtra = ', p.name AS parent_name';
}
$rows = $pdo->query("SELECT t.* $selectExtra FROM `$table` t $joinSql ORDER BY t.name")->fetchAll();

// In-use counts, so we can block deleting a value that's still referenced —
// keeps this generic manager from silently orphaning personnel/allowance records.
$usageChecks = lookup_usage_checks();

$pageTitle = 'Lookup Management';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-list-check"></i> Lookup Management</h4>

<ul class="nav nav-pills mb-3">
  <?php foreach ($manageable as $key => $m): ?>
    <li class="nav-item">
      <a class="nav-link <?= $key === $table ? 'active' : '' ?>" href="<?= BASE_URL ?>/settings/lookups.php?table=<?= $key ?>"><?= e($m['label']) ?></a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card mb-3"><div class="card-body">
  <h6>Add <?= e($meta['label']) ?></h6>
  <form method="post" action="<?= BASE_URL ?>/settings/lookup_save.php" class="row g-2">
    <?= csrf_field() ?>
    <input type="hidden" name="table" value="<?= e($table) ?>">
    <div class="col-md-4">
      <input type="text" name="name" class="form-control" placeholder="<?= e($meta['label']) ?> name" required>
    </div>
    <?php if (!empty($meta['extra'])): ?>
    <div class="col-md-3">
      <input type="number" name="<?= e($meta['extra']) ?>" class="form-control" placeholder="Hierarchy level (lower = senior)">
    </div>
    <?php endif; ?>
    <?php if (!empty($meta['parent'])): ?>
    <div class="col-md-3">
      <select name="<?= e($meta['parent_col']) ?>" class="form-select" required>
        <option value="">Select <?= e($manageable[$meta['parent']]['label']) ?></option>
        <?php foreach ($parentOptions as $po): ?><option value="<?= $po['id'] ?>"><?= e($po['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> Add</button></div>
  </form>
</div></div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th>Name</th>
          <?php if (!empty($meta['extra'])): ?><th>Hierarchy Level</th><?php endif; ?>
          <?php if (!empty($meta['parent'])): ?><th><?= e($manageable[$meta['parent']]['label']) ?></th><?php endif; ?>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['name']) ?></td>
          <?php if (!empty($meta['extra'])): ?><td><?= e((string)($r[$meta['extra']] ?? '')) ?></td><?php endif; ?>
          <?php if (!empty($meta['parent'])): ?><td><?= e($r['parent_name'] ?? '') ?></td><?php endif; ?>
          <td class="text-end text-nowrap">
            <a href="<?= BASE_URL ?>/settings/lookup_edit.php?table=<?= $table ?>&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php
              $inUse = false;
              foreach ($usageChecks[$table] ?? [] as $sql) {
                  $chk = $pdo->prepare($sql);
                  $chk->execute([$r['id']]);
                  if ($chk->fetchColumn() > 0) { $inUse = true; break; }
              }
            ?>
            <?php if ($inUse): ?>
              <button class="btn btn-sm btn-outline-danger" disabled title="In use — cannot delete"><i class="bi bi-trash"></i></button>
            <?php else: ?>
              <a href="<?= BASE_URL ?>/settings/lookup_delete.php?table=<?= $table ?>&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Delete '<?= e($r['name']) ?>'?"><i class="bi bi-trash"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-muted py-3">No entries yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
