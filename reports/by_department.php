<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/xlsx_writer.php';
require_once __DIR__ . '/../includes/pdf_writer.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('reports.view');

$sql = "SELECT d.name AS department_name,
               COUNT(*) AS total,
               SUM(la.status IN ('pending_soa','pending_pso')) AS pending,
               SUM(la.status = 'approved') AS approved,
               SUM(la.status = 'disapproved') AS disapproved,
               SUM(la.status = 'completed') AS completed
        FROM leave_applications la
        JOIN personnel p ON p.id = la.personnel_id
        JOIN departments d ON d.id = p.department_id
        GROUP BY d.id, d.name
        ORDER BY total DESC";
$rows = db()->query($sql)->fetchAll();

$exportColumns = [
    'Department' => 'department_name', 'Total' => 'total', 'Pending' => 'pending',
    'Approved' => 'approved', 'Disapproved' => 'disapproved', 'Completed' => 'completed',
];
if (($_GET['export'] ?? '') === 'csv')  export_csv('leave_by_department.csv', $exportColumns, $rows);
if (($_GET['export'] ?? '') === 'xlsx') export_xlsx('leave_by_department.xlsx', $exportColumns, $rows);
if (($_GET['export'] ?? '') === 'pdf')  export_pdf_table('leave_by_department.pdf', 'Leave by Department', $exportColumns, $rows);

$pageTitle = 'Leave by Department';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="?export=csv" class="btn btn-sm btn-outline-dark"><i class="bi bi-filetype-csv"></i> CSV</a>
    <a href="?export=xlsx" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    <a href="?export=pdf" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
</div>

<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr><th>Department</th><th>Total</th><th>Pending</th><th>Approved</th><th>Disapproved</th><th>Completed</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No applications on record yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= e($r['department_name']) ?></td>
        <td class="fw-semibold"><?= (int)$r['total'] ?></td>
        <td><span class="badge badge-status badge-pending"><?= (int)$r['pending'] ?></span></td>
        <td><span class="badge badge-status badge-approved"><?= (int)$r['approved'] ?></span></td>
        <td><span class="badge badge-status badge-disapproved"><?= (int)$r['disapproved'] ?></span></td>
        <td><span class="badge text-bg-secondary"><?= (int)$r['completed'] ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
