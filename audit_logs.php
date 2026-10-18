<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
require_role(['Super Administrator']);

$page = max(1, (int)($_GET['page'] ?? 1));
['limit' => $limit, 'offset' => $offset] = paginate($page, 40);

$total = db()->query('SELECT COUNT(*) c FROM audit_logs')->fetch()['c'];
$logs = db()->query(
    "SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset"
)->fetchAll();

$pageTitle = 'Audit Trail';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-clock-history"></i> Audit Trail</h4>
<div class="card">
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead class="table-light"><tr><th>Date/Time</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td class="text-nowrap"><?= date('d M Y H:i', strtotime($l['created_at'])) ?></td>
          <td><?= e($l['full_name'] ?: 'System') ?></td>
          <td><span class="badge bg-secondary"><?= e($l['action']) ?></span></td>
          <td><?= e($l['entity_type']) ?> <?= $l['entity_id'] ? '#' . $l['entity_id'] : '' ?></td>
          <td class="small"><?= e($l['details']) ?></td>
          <td class="small text-muted"><?= e($l['ip_address']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="6" class="text-center text-muted py-4">No audit entries yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $totalPages = max(1, ceil($total / $limit)); if ($totalPages > 1): ?>
<nav class="mt-3"><ul class="pagination justify-content-center">
  <?php for ($i = 1; $i <= min($totalPages, 20); $i++): ?>
    <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
