<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/xlsx_writer.php';
require_once __DIR__ . '/../includes/pdf_writer.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('reports.view');

$sql = "SELECT la.id, la.application_no, p.f_ap_number, p.first_name, p.surname,
               r.name AS rank_name, d.name AS department_name, lt.name AS leave_type_name,
               lr.expected_resumption_date, DATEDIFF(CURDATE(), lr.expected_resumption_date) AS days_overdue
        FROM leave_resumptions lr
        JOIN leave_applications la ON la.id = lr.leave_application_id
        JOIN personnel p ON p.id = la.personnel_id
        JOIN ranks r ON r.id = p.rank_id
        JOIN departments d ON d.id = p.department_id
        JOIN leave_types lt ON lt.id = la.leave_type_id
        WHERE lr.actual_resumption_date IS NULL
          AND lr.expected_resumption_date < CURDATE()
        ORDER BY days_overdue DESC";
$rows = db()->query($sql)->fetchAll();

$exportColumns = [
    'F-AP Number' => 'f_ap_number', 'First Name' => 'first_name', 'Surname' => 'surname',
    'Rank' => 'rank_name', 'Department' => 'department_name', 'Leave Type' => 'leave_type_name',
    'Expected Resumption' => 'expected_resumption_date', 'Days Overdue' => 'days_overdue',
];
if (($_GET['export'] ?? '') === 'csv')  export_csv('overstayed_leave.csv', $exportColumns, $rows);
if (($_GET['export'] ?? '') === 'xlsx') export_xlsx('overstayed_leave.xlsx', $exportColumns, $rows);
if (($_GET['export'] ?? '') === 'pdf')  export_pdf_table('overstayed_leave.pdf', 'Overstayed Leave', $exportColumns, $rows);

$pageTitle = 'Overstayed Leave';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?= count($rows) ?> officer(s) past their expected resumption date.</p>
    <div class="d-flex gap-2">
    <a href="?export=csv" class="btn btn-sm btn-outline-dark"><i class="bi bi-filetype-csv"></i> CSV</a>
    <a href="?export=xlsx" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    <a href="?export=pdf" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr><th>Officer</th><th>Rank</th><th>Department</th><th>Leave Type</th><th>Expected Resumption</th><th>Days Overdue</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Nothing overdue.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= e($r['first_name'] . ' ' . $r['surname']) ?> <span class="text-muted small">(<?= e($r['f_ap_number']) ?>)</span></td>
        <td><?= e($r['rank_name']) ?></td>
        <td><?= e($r['department_name']) ?></td>
        <td><?= e($r['leave_type_name']) ?></td>
        <td><?= e($r['expected_resumption_date']) ?></td>
        <td class="text-danger fw-semibold"><?= (int)$r['days_overdue'] ?></td>
        <td class="text-end"><a href="<?= base_url('leave/view.php?id=' . $r['id']) ?>" class="btn btn-sm btn-outline-dark">View</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
