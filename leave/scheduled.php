<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('reports.view');

$month = (int) ($_GET['month'] ?? date('n'));
$year  = (int) ($_GET['year'] ?? date('Y'));
if ($month < 1 || $month > 12) $month = (int) date('n');

$deptFilter   = $_GET['department_id'] ?? '';
$typeFilter   = $_GET['leave_type_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$monthStart = sprintf('%04d-%02d-01', $year, $month);
$monthEnd   = date('Y-m-t', strtotime($monthStart));

$where = ['la.proceeding_date <= :month_end', 'la.expected_resumption_date >= :month_start', "la.status IN ('pending_soa','pending_pso','approved','completed')"];
$params = ['month_end' => $monthEnd, 'month_start' => $monthStart];

if ($deptFilter !== '')   { $where[] = 'p.department_id = :dept'; $params['dept'] = $deptFilter; }
if ($typeFilter !== '')   { $where[] = 'la.leave_type_id = :type'; $params['type'] = $typeFilter; }
if ($statusFilter !== '') { $where[] = 'la.status = :status'; $params['status'] = $statusFilter; }

$sql = 'SELECT la.id, la.application_no, la.status, la.proceeding_date, la.expected_resumption_date,
               lt.name AS leave_type_name, p.f_ap_number, p.first_name, p.surname, r.name AS rank_name
        FROM leave_applications la
        JOIN leave_types lt ON lt.id = la.leave_type_id
        JOIN personnel p ON p.id = la.personnel_id
        JOIN ranks r ON r.id = p.rank_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY la.proceeding_date';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$leaveTypes  = db()->query('SELECT id, name FROM leave_types ORDER BY name')->fetchAll();

$statusLabels = ['pending_soa' => 'Pending Review', 'pending_pso' => 'Pending Decision', 'approved' => 'Approved', 'completed' => 'Completed'];
$statusBadgeClass = ['pending_soa' => 'badge-pending', 'pending_pso' => 'badge-pending', 'approved' => 'badge-approved', 'completed' => 'text-bg-secondary'];
$monthLabel = date('F Y', strtotime($monthStart));
$prevMonth = date('Y-m', strtotime($monthStart . ' -1 month'));
$nextMonth = date('Y-m', strtotime($monthStart . ' +1 month'));

$pageTitle = 'Leave Scheduled';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('leave/scheduled.php?year=' . date('Y', strtotime($prevMonth)) . '&month=' . date('n', strtotime($prevMonth))) ?>">&larr;</a>
        <h2 class="h6 mb-0" style="min-width:140px; text-align:center;"><?= e($monthLabel) ?></h2>
        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('leave/scheduled.php?year=' . date('Y', strtotime($nextMonth)) . '&month=' . date('n', strtotime($nextMonth))) ?>">&rarr;</a>
    </div>

    <form class="d-flex gap-2 flex-wrap" method="get">
        <input type="hidden" name="year" value="<?= $year ?>">
        <input type="hidden" name="month" value="<?= $month ?>">
        <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Departments</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="leave_type_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Leave Types</option>
            <?php foreach ($leaveTypes as $lt): ?>
            <option value="<?= $lt['id'] ?>" <?= $typeFilter == $lt['id'] ? 'selected' : '' ?>><?= e($lt['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pending_soa" <?= $statusFilter === 'pending_soa' ? 'selected' : '' ?>>Pending Review</option>
            <option value="pending_pso" <?= $statusFilter === 'pending_pso' ? 'selected' : '' ?>>Pending Decision</option>
            <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
        </select>
    </form>
</div>

<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr><th>Officer</th><th>Rank</th><th>Type</th><th>From</th><th>To</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No leave overlaps <?= e($monthLabel) ?> for the selected filters.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= e($r['first_name'] . ' ' . $r['surname']) ?> <span class="text-muted small">(<?= e($r['f_ap_number']) ?>)</span></td>
        <td><?= e($r['rank_name']) ?></td>
        <td><?= e($r['leave_type_name']) ?></td>
        <td><?= e($r['proceeding_date']) ?></td>
        <td><?= e($r['expected_resumption_date'] ?? '—') ?></td>
        <td><span class="badge badge-status <?= $statusBadgeClass[$r['status']] ?? 'text-bg-secondary' ?>"><?= e($statusLabels[$r['status']] ?? $r['status']) ?></span></td>
        <td class="text-end"><a href="<?= base_url('leave/view.php?id=' . $r['id']) ?>" class="btn btn-sm btn-outline-dark">View</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
