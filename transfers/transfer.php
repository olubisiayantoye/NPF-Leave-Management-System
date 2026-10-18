<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $personnelId = (int)($_POST['personnel_id'] ?? 0);
    $toDept = (int)($_POST['to_department_id'] ?? 0);
    $transferDate = $_POST['transfer_date'] ?: date('Y-m-d');
    $reason = trim($_POST['reason'] ?? '');

    $stmt = db()->prepare('SELECT department_id, full_name FROM personnel WHERE id = ?');
    $stmt->execute([$personnelId]);
    $personnel = $stmt->fetch();

    if (!$personnel) {
        $error = 'Personnel record not found.';
    } elseif (!$toDept) {
        $error = 'Please select a destination department.';
    } else {
        db()->prepare(
            'INSERT INTO transfers (personnel_id, from_department_id, to_department_id, transfer_date, reason, approved_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$personnelId, $personnel['department_id'], $toDept, $transferDate, $reason, $_SESSION['user_id']]);

        db()->prepare('UPDATE personnel SET department_id = ?, date_transferred = ?, status = "Transferred" WHERE id = ?')
            ->execute([$toDept, $transferDate, $personnelId]);

        audit_log('PERSONNEL_TRANSFERRED', 'personnel', $personnelId, "Transferred {$personnel['full_name']} to department #$toDept");
        flash('success', 'Personnel transferred successfully.');
        header('Location: ' . url('/transfers/list.php'));
        exit;
    }
}

// AJAX-free simple approach: personnel list for the select, filterable client-side with datalist
$personnelList = db()->query(
    "SELECT id, full_name, ap_number FROM personnel WHERE status != 'Archived' ORDER BY full_name LIMIT 2000"
)->fetchAll();

$pageTitle = 'Move Personnel';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3">Move Personnel Between Units</h4>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="card" style="max-width:640px">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Personnel *</label>
        <input list="personnel-options" name="personnel_id_display" class="form-control" placeholder="Type name or AP number..." required
               onchange="document.getElementById('personnel_id_hidden').value = this.list.querySelector('option[value=\'' + this.value + '\']')?.dataset.id || ''">
        <datalist id="personnel-options">
          <?php foreach ($personnelList as $p): ?>
            <option value="<?= e($p['full_name']) ?> (<?= e($p['ap_number']) ?>)" data-id="<?= $p['id'] ?>"></option>
          <?php endforeach; ?>
        </datalist>
        <input type="hidden" name="personnel_id" id="personnel_id_hidden">
      </div>
      <div class="mb-3">
        <label class="form-label">Destination Department *</label>
        <select name="to_department_id" class="form-select" required>
          <option value="">Select Department</option>
          <?php foreach (lookup('departments') as $d): ?>
            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Transfer Date</label>
        <input type="date" name="transfer_date" class="form-control" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Reason</label>
        <textarea name="reason" class="form-control" rows="2"></textarea>
      </div>
      <button class="btn btn-primary w-100"><i class="bi bi-arrow-left-right"></i> Process Transfer</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
