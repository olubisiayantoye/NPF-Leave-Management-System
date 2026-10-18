<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$transfers = db()->query(
    "SELECT t.*, p.full_name, p.ap_number, d1.name AS from_name, d2.name AS to_name, u.full_name AS approver
     FROM transfers t
     JOIN personnel p ON p.id = t.personnel_id
     LEFT JOIN departments d1 ON d1.id = t.from_department_id
     LEFT JOIN departments d2 ON d2.id = t.to_department_id
     LEFT JOIN users u ON u.id = t.approved_by
     ORDER BY t.transfer_date DESC LIMIT 200"
)->fetchAll();

$pageTitle = 'Transfers';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Personnel Transfers</h4>
  <a href="<?= BASE_URL ?>/transfers/transfer.php" class="btn btn-primary btn-sm"><i class="bi bi-arrow-left-right"></i> Move Personnel</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light"><tr><th>Date</th><th>Personnel</th><th>From</th><th>To</th><th>Approved By</th><th>Reason</th></tr></thead>
      <tbody>
        <?php foreach ($transfers as $t): ?>
        <tr>
          <td><?= format_date($t['transfer_date']) ?></td>
          <td><a href="<?= BASE_URL ?>/personnel/view.php?id=<?= $t['personnel_id'] ?>"><?= e($t['full_name']) ?></a> <span class="text-muted small">(<?= e($t['ap_number']) ?>)</span></td>
          <td><?= e($t['from_name'] ?: '—') ?></td>
          <td><?= e($t['to_name'] ?: '—') ?></td>
          <td><?= e($t['approver'] ?: '—') ?></td>
          <td><?= e($t['reason']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$transfers): ?><tr><td colspan="6" class="text-center text-muted py-4">No transfers recorded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
