<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('audit.view');

$actionFilter = trim($_GET['action'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 40;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($actionFilter !== '') {
    $where = 'WHERE a.action = :action';
    $params['action'] = $actionFilter;
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM audit_logs a $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$stmt = db()->prepare(
    "SELECT a.*, u.full_name AS user_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     $where
     ORDER BY a.created_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$totalPages = (int) ceil($total / $perPage);

$actions = db()->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Audit Log';
require __DIR__ . '/../includes/header.php';
?>

<form class="d-flex mb-3" method="get" action="<?= base_url('audit/index.php') ?>" style="max-width: 320px;">
    <select name="action" class="form-select me-2" onchange="this.form.submit()">
        <option value="">All actions</option>
        <?php foreach ($actions as $a): ?>
        <option value="<?= e($a) ?>" <?= $actionFilter === $a ? 'selected' : '' ?>><?= e($a) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-sm mb-0 align-middle">
    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>IP</th><th>Description</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No audit entries yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td class="small text-muted"><?= e($r['created_at']) ?></td>
        <td class="small"><?= e($r['user_name'] ?? 'System') ?></td>
        <td><span class="badge text-bg-light border font-monospace small"><?= e($r['action']) ?></span></td>
        <td class="small text-muted"><?= e($r['record_type'] ? $r['record_type'] . ' #' . $r['record_id'] : '—') ?></td>
        <td class="small text-muted font-monospace"><?= e($r['ip_address'] ?? '—') ?></td>
        <td class="small"><?= e($r['description'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= base_url('audit/index.php?page=' . $i . ($actionFilter !== '' ? '&action=' . urlencode($actionFilter) : '')) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
