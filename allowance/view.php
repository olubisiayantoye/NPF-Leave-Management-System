<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT a.*, r.name AS rank_name, b.name AS bank_name FROM monthly_allowance_personnel a
     LEFT JOIN ranks r ON r.id = a.rank_id
     LEFT JOIN banks b ON b.id = a.bank_id
     WHERE a.id = ?"
);
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) { flash('error', 'Allowance record not found.'); header('Location: ' . url('/allowance/list.php')); exit; }

$pageTitle = $a['full_name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h4 class="mb-0"><?= e($a['full_name']) ?> <span class="badge <?= $a['status'] === 'Active' ? 'badge-active' : 'bg-secondary' ?>"><?= e($a['status']) ?></span></h4>
    <div class="text-muted"><?= e($a['rank_name']) ?> &middot; <?= e($a['fap_number']) ?></div>
  </div>
  <div>
    <?php if (in_array($user['role_name'], ['Super Administrator','Administrator','Documentation Officer'])): ?>
    <a href="<?= BASE_URL ?>/allowance/edit.php?id=<?= $a['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/allowance/list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to List</a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <table class="table table-sm mb-0">
      <tr><th width="25%">F-AP Number</th><td><?= e($a['fap_number']) ?></td></tr>
      <tr><th>Rank</th><td><?= e($a['rank_name']) ?></td></tr>
      <tr><th>Full Name</th><td><?= e($a['full_name']) ?></td></tr>
      <tr><th>Account Number</th><td><?= e($a['account_number']) ?></td></tr>
      <tr><th>Bank Name</th><td><?= e($a['bank_name']) ?></td></tr>
      <tr><th>Employee Code</th><td><?= e($a['employee_code']) ?></td></tr>
      <tr><th>Section</th><td><?= e($a['section']) ?></td></tr>
      <tr><th>Status</th><td><?= e($a['status']) ?></td></tr>
      <tr><th>Registered</th><td><?= format_date($a['created_at']) ?></td></tr>
      <tr><th>Last Updated</th><td><?= format_date($a['updated_at']) ?></td></tr>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
