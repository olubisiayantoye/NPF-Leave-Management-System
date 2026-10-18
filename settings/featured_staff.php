<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_permission('settings.featured_staff');

$pdo = db();

$featured = $pdo->query(
    "SELECT fs.id AS featured_id, fs.display_order, p.id AS personnel_id, p.full_name, p.photo_path,
            r.name AS rank_name, d.name AS dept_name
     FROM featured_staff fs
     JOIN personnel p ON p.id = fs.personnel_id
     LEFT JOIN ranks r ON r.id = p.rank_id
     LEFT JOIN departments d ON d.id = p.department_id
     ORDER BY fs.display_order ASC, p.full_name ASC"
)->fetchAll();

// Personnel not already featured, for the "add" datalist (excludes Archived records).
$notFeaturedIds = array_column($featured, 'personnel_id');
$placeholders = $notFeaturedIds ? implode(',', array_fill(0, count($notFeaturedIds), '?')) : '0';
$availableStmt = $pdo->prepare(
    "SELECT id, full_name, ap_number FROM personnel
     WHERE status != 'Archived' AND id NOT IN ($placeholders)
     ORDER BY full_name LIMIT 2000"
);
$availableStmt->execute($notFeaturedIds);
$available = $availableStmt->fetchAll();

$pageTitle = 'Front Page Featured Staff';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-people"></i> Front Page Featured Staff</h4>
<p class="text-muted small">Choose which existing staff profiles appear under "Leadership" on the public front
   page. Photos, names, and ranks are pulled live from each profile — updating a profile automatically updates
   what's shown here, and a profile can only be featured once.</p>

<div class="card mb-4"><div class="card-body">
  <h6>Add to Front Page</h6>
  <form method="post" action="<?= BASE_URL ?>/settings/featured_staff_save.php" class="row g-2">
    <?= csrf_field() ?>
    <div class="col-md-8">
      <input list="personnel-options" name="personnel_id_display" class="form-control" placeholder="Type name or AP number..." required
             onchange="document.getElementById('personnel_id_hidden').value = this.list.querySelector('option[value=\'' + this.value + '\']')?.dataset.id || ''">
      <datalist id="personnel-options">
        <?php foreach ($available as $p): ?>
          <option value="<?= e($p['full_name']) ?> (<?= e($p['ap_number']) ?>)" data-id="<?= $p['id'] ?>"></option>
        <?php endforeach; ?>
      </datalist>
      <input type="hidden" name="personnel_id" id="personnel_id_hidden">
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> Add</button></div>
  </form>
</div></div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light"><tr><th></th><th>Name</th><th>Rank</th><th>Department</th><th>Order</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($featured as $i => $f): ?>
        <tr>
          <td><img src="<?= e(url($f['photo_path'] ?: '/uploads/officer.png')) ?>" class="photo-thumb" style="width:40px;height:40px"></td>
          <td><a href="<?= BASE_URL ?>/personnel/view.php?id=<?= $f['personnel_id'] ?>"><?= e($f['full_name']) ?></a></td>
          <td><?= e($f['rank_name']) ?></td>
          <td><?= e($f['dept_name']) ?></td>
          <td><?= (int)$f['display_order'] ?></td>
          <td class="text-end text-nowrap">
            <?php if ($i > 0): ?>
              <a href="<?= BASE_URL ?>/settings/featured_staff_move.php?id=<?= $f['featured_id'] ?>&dir=up" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-up"></i></a>
            <?php endif; ?>
            <?php if ($i < count($featured) - 1): ?>
              <a href="<?= BASE_URL ?>/settings/featured_staff_move.php?id=<?= $f['featured_id'] ?>&dir=down" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-down"></i></a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/settings/featured_staff_delete.php?id=<?= $f['featured_id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Remove <?= e($f['full_name']) ?> from the front page?"><i class="bi bi-x-circle"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$featured): ?><tr><td colspan="6" class="text-center text-muted py-4">No staff currently featured on the front page.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
