<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator', 'Documentation Officer']);

$pageTitle = 'Register Personnel';
$isEdit = false;
$p = $_SESSION['_old_input'] ?? [];
unset($_SESSION['_old_input']);
include __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3">Register New Personnel</h4>
<form method="post" action="<?= BASE_URL ?>/personnel/save.php" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="mode" value="create">
  <div class="card"><div class="card-body">
    <?php include __DIR__ . '/../includes/personnel_form.php'; ?>
  </div></div>
  <div class="mt-3 text-end">
    <a href="<?= BASE_URL ?>/personnel/list.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Personnel Record</button>
  </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
