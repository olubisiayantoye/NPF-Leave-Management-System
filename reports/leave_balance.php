<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/xlsx_writer.php';
require_once __DIR__ . '/../includes/pdf_writer.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('reports.view');

$year = (int) ($_GET['year'] ?? date('Y'));

$stmt = db()->prepare(
    "SELECT p.f_ap_number, p.first_name, p.surname, r.name AS rank_name, d.name AS department_name,
            lt.name AS leave_type_name, le.entitled_days, le.used_days,
            (le.entitled_days - le.used_days) AS balance_days
     FROM leave_entitlements le
     JOIN personnel p ON p.id = le.personnel_id
     JOIN ranks r ON r.id = p.rank_id
     JOIN departments d ON d.id = p.department_id
     JOIN leave_types lt ON lt.id = le.leave_type_id
     WHERE le.year = :year
     ORDER BY d.name, p.surname, lt.name"
);
$stmt->execute(['year' => $year]);
$rows = $stmt->fetchAll();

$exportColumns = [
    'F-AP Number' => 'f_ap_number', 'First Name' => 'first_name', 'Surname' => 'surname',
    'Rank' => 'rank_name', 'Department' => 'department_name', 'Leave Type' => 'leave_type_name',
    'Entitled' => 'entitled_days', 'Used' => 'used_days', 'Balance' => 'balance_days',
];
if (($_GET['export'] ?? '') === 'csv')  export_csv("leave_balance_{$year}.csv", $exportColumns, $rows);
if (($_GET['export'] ?? '') === 'xlsx') export_xlsx("leave_balance_{$year}.xlsx", $exportColumns, $rows);
if (($_GET['export'] ?? '') === 'pdf')  export_pdf_table("leave_balance_{$year}.pdf", "Leave Balance — {$year}", $exportColumns, $rows);

$pageTitle = "Leave Balance — {$year}";
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex gap-2" method="get">
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
    <div class="d-flex gap-2">
    <a href="?year=<?= $year ?>&export=csv" class="btn btn-sm btn-outline-dark"><i class="bi bi-filetype-csv"></i> CSV</a>
    <a href="?year=<?= $year ?>&export=xlsx" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    <a href="?year=<?= $year ?>&export=pdf" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr><th>Officer</th><th>Rank</th><th>Department</th><th>Leave Type</th><th>Entitled</th><th>Used</th><th>Balance</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No entitlements configured for <?= $year ?>.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= e($r['first_name'] . ' ' . $r['surname']) ?> <span class="text-muted small">(<?= e($r['f_ap_number']) ?>)</span></td>
        <td><?= e($r['rank_name']) ?></td>
        <td><?= e($r['department_name']) ?></td>
        <td><?= e($r['leave_type_name']) ?></td>
        <td><?= (int)$r['entitled_days'] ?></td>
        <td><?= (int)$r['used_days'] ?></td>
        <td class="fw-semibold <?= $r['balance_days'] <= 0 ? 'text-danger' : '' ?>"><?= (int)$r['balance_days'] ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
