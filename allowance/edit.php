<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator', 'Documentation Officer']);

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM monthly_allowance_personnel WHERE id = ?');
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) { flash('error', 'Allowance record not found.'); header('Location: ' . url('/allowance/list.php')); exit; }

if (!empty($_SESSION['_old_allowance_input'])) { $a = array_merge($a, $_SESSION['_old_allowance_input']); unset($_SESSION['_old_allowance_input']); }

$pageTitle = 'Edit Allowance Record';
include __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3">Edit Allowance Record &mdash; <?= e($a['full_name']) ?></h4>
<form method="post" action="<?= BASE_URL ?>/allowance/save.php">
  <?= csrf_field() ?>
  <input type="hidden" name="mode" value="update">
  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
  <div class="card"><div class="card-body">
    <?php include __DIR__ . '/../includes/allowance_form.php'; ?>
  </div></div>
  <div class="mt-3 text-end">
    <a href="<?= BASE_URL ?>/allowance/view.php?id=<?= (int)$a['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Record</button>
  </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
