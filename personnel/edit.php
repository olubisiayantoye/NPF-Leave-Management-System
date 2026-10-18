<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator', 'Documentation Officer']);

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM personnel WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { flash('error', 'Personnel record not found.'); header('Location: ' . url('/personnel/list.php')); exit; }

$kinStmt = db()->prepare('SELECT * FROM personnel_next_of_kin WHERE personnel_id = ?');
$kinStmt->execute([$id]);
foreach ($kinStmt->fetchAll() as $kinRow) {
    $n = (int)$kinRow['kin_number'];
    foreach (['full_name', 'relationship', 'email_address', 'residential_address', 'phone_number', 'alt_phone_number'] as $field) {
        $p["kin{$n}_{$field}"] = $kinRow[$field];
    }
}

if (!empty($_SESSION['_old_input'])) { $p = array_merge($p, $_SESSION['_old_input']); unset($_SESSION['_old_input']); }

$pageTitle = 'Edit Personnel';
$isEdit = true;
include __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3">Edit Personnel &mdash; <?= e($p['full_name']) ?></h4>
<form method="post" action="<?= BASE_URL ?>/personnel/save.php" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="mode" value="update">
  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
  <div class="card"><div class="card-body">
    <?php include __DIR__ . '/../includes/personnel_form.php'; ?>
  </div></div>
  <div class="mt-3 text-end">
    <a href="<?= BASE_URL ?>/personnel/view.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Record</button>
  </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
