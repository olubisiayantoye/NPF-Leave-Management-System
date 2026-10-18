<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator', 'Documentation Officer']);

$pageTitle = 'Register Allowance Personnel';
$a = $_SESSION['_old_allowance_input'] ?? [];
unset($_SESSION['_old_allowance_input']);
include __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3">Register Personnel for Monthly Allowance</h4>
<form method="post" action="<?= BASE_URL ?>/allowance/save.php">
  <?= csrf_field() ?>
  <input type="hidden" name="mode" value="create">
  <div class="card"><div class="card-body">
    <?php include __DIR__ . '/../includes/allowance_form.php'; ?>
  </div></div>
  <div class="mt-3 text-end">
    <a href="<?= BASE_URL ?>/allowance/list.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Record</button>
  </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
