<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/xlsx_writer.php';
require_once __DIR__ . '/../includes/pdf_writer.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('reports.view');

$sql = "SELECT p.f_ap_number, p.first_name, p.surname, r.name AS rank_name, d.name AS department_name,
               la.application_no, lt.name AS leave_type_name, la.proceeding_date, la.expected_resumption_date
        FROM personnel p
        JOIN ranks r ON r.id = p.rank_id
        JOIN departments d ON d.id = p.department_id
        JOIN leave_applications la ON la.personnel_id = p.id AND la.status = 'approved'
        JOIN leave_types lt ON lt.id = la.leave_type_id
        WHERE p.status = 'on_leave'
        ORDER BY d.name, p.surname";
$rows = db()->query($sql)->fetchAll();

$exportColumns = [
    'F-AP Number' => 'f_ap_number', 'First Name' => 'first_name', 'Surname' => 'surname',
    'Rank' => 'rank_name', 'Department' => 'department_name', 'Application No.' => 'application_no',
    'Leave Type' => 'leave_type_name', 'Proceeding' => 'proceeding_date', 'Due to Resume' => 'expected_resumption_date',
];
if (($_GET['export'] ?? '') === 'csv')  export_csv('personnel_on_leave.csv', $exportColumns, $rows);
if (($_GET['export'] ?? '') === 'xlsx') export_xlsx('personnel_on_leave.xlsx', $exportColumns, $rows);
if (($_GET['export'] ?? '') === 'pdf')  export_pdf_table('personnel_on_leave.pdf', 'Personnel Currently on Leave', $exportColumns, $rows);

$pageTitle = 'Personnel Currently on Leave';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?= count($rows) ?> personnel currently on leave.</p>
    <div class="d-flex gap-2">
    <a href="?export=csv" class="btn btn-sm btn-outline-dark"><i class="bi bi-filetype-csv"></i> CSV</a>
    <a href="?export=xlsx" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    <a href="?export=pdf" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr><th>Officer</th><th>Rank</th><th>Department</th><th>Leave Type</th><th>Proceeding</th><th>Due to Resume</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No one is currently on leave.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= e($r['first_name'] . ' ' . $r['surname']) ?> <span class="text-muted small">(<?= e($r['f_ap_number']) ?>)</span></td>
        <td><?= e($r['rank_name']) ?></td>
        <td><?= e($r['department_name']) ?></td>
        <td><?= e($r['leave_type_name']) ?></td>
        <td><?= e($r['proceeding_date']) ?></td>
        <td><?= e($r['expected_resumption_date'] ?? '—') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
