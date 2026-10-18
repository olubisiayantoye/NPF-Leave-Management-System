<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator', 'Verification Officer', 'Documentation Officer', 'Read-only User']);

$query = trim($_GET['q'] ?? '');
$result = null;

if ($query !== '') {
    $stmt = db()->prepare(
        "SELECT p.*, d.name AS dept_name, r.name AS rank_name FROM personnel p
         LEFT JOIN departments d ON d.id = p.department_id
         LEFT JOIN ranks r ON r.id = p.rank_id
         WHERE p.ap_number = ? OR p.full_name LIKE ? OR p.phone_number = ? OR p.employee_code = ? OR p.ippis_number = ?
         LIMIT 10"
    );
    $like = "%$query%";
    $stmt->execute([$query, $like, $query, $query, $query]);
    $result = $stmt->fetchAll();

    audit_log('PERSONNEL_VERIFICATION_SEARCH', 'personnel', null, "Query: $query");
}

$statusBadge = ['Active'=>'badge-active','Retired'=>'badge-retired','Transferred'=>'badge-transferred','Suspended'=>'badge-suspended','Archived'=>'badge-archived'];
$pageTitle = 'Verification';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-patch-check"></i> Personnel Verification</h4>
<div class="card mb-4"><div class="card-body">
  <form method="get" class="row g-2">
    <div class="col-md-10">
      <input type="text" name="q" class="form-control form-control-lg" placeholder="Enter AP Number, Name, Phone Number, Employee Code, or IPPIS Number"
             value="<?= e($query) ?>" autofocus>
    </div>
    <div class="col-md-2"><button class="btn btn-primary btn-lg w-100"><i class="bi bi-search"></i> Verify</button></div>
  </form>
</div></div>

<?php if ($result !== null): ?>
  <?php if (!$result): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> No personnel record found matching "<?= e($query) ?>". This individual could not be verified against the nominal roll.</div>
  <?php endif; ?>
  <?php foreach ($result as $p): ?>
    <div class="card mb-3 border-success">
      <div class="card-header bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i> Verified &mdash; Active Record Found</div>
      <div class="card-body row">
        <div class="col-md-2 text-center">
          <img src="<?= e(url($p['photo_path'] ?: '/uploads/officer.png')) ?>" class="photo-preview" style="width:120px;height:120px">
        </div>
        <div class="col-md-10">
          <h5><?= e($p['full_name']) ?> <span class="badge <?= $statusBadge[$p['status']] ?? 'bg-secondary' ?>"><?= e($p['status']) ?></span></h5>
          <table class="table table-sm mb-0">
            <tr><th width="20%">AP Number</th><td><?= e($p['ap_number']) ?></td>
                <th width="20%">Rank</th><td><?= e($p['rank_name']) ?></td></tr>
            <tr><th>Current Posting</th><td><?= e($p['dept_name']) ?></td>
                <th>Duty Post</th><td><?= e($p['duty_post']) ?></td></tr>
            <tr><th>Phone</th><td><?= e($p['phone_number']) ?></td>
                <th>Employee Code</th><td><?= e($p['employee_code']) ?></td></tr>
          </table>
          <a href="<?= BASE_URL ?>/personnel/view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary mt-2">Full Record</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
