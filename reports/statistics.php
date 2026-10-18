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
    "SELECT MONTH(created_at) AS m,
            COUNT(*) AS submitted,
            SUM(status = 'approved') AS approved,
            SUM(status = 'disapproved') AS disapproved
     FROM leave_applications
     WHERE YEAR(created_at) = :year
     GROUP BY MONTH(created_at)
     ORDER BY m"
);
$stmt->execute(['year' => $year]);
$byMonth = [];
foreach ($stmt->fetchAll() as $row) {
    $byMonth[(int) $row['m']] = $row;
}

$monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

if (($_GET['export'] ?? '') === 'csv' || ($_GET['export'] ?? '') === 'xlsx' || ($_GET['export'] ?? '') === 'pdf') {
    $exportRows = [];
    foreach ($monthNames as $i => $name) {
        $m = $i + 1;
        $r = $byMonth[$m] ?? ['submitted' => 0, 'approved' => 0, 'disapproved' => 0];
        $exportRows[] = ['month' => $name, 'submitted' => $r['submitted'], 'approved' => $r['approved'], 'disapproved' => $r['disapproved']];
    }
    $exportColumns = ['Month' => 'month', 'Submitted' => 'submitted', 'Approved' => 'approved', 'Disapproved' => 'disapproved'];
    if ($_GET['export'] === 'csv') {
        export_csv("leave_statistics_{$year}.csv", $exportColumns, $exportRows);
    } elseif ($_GET['export'] === 'xlsx') {
        export_xlsx("leave_statistics_{$year}.xlsx", $exportColumns, $exportRows);
    } else {
        export_pdf_table("leave_statistics_{$year}.pdf", "Leave Statistics — {$year}", $exportColumns, $exportRows);
    }
}

$pageTitle = "Leave Statistics — {$year}";
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
<table class="table mb-0 align-middle">
    <thead><tr><th>Month</th><th>Submitted</th><th>Approved</th><th>Disapproved</th></tr></thead>
    <tbody>
    <?php foreach ($monthNames as $i => $name): $m = $i + 1; $r = $byMonth[$m] ?? ['submitted'=>0,'approved'=>0,'disapproved'=>0]; ?>
    <tr>
        <td><?= e($name) ?></td>
        <td><?= (int)$r['submitted'] ?></td>
        <td><?= (int)$r['approved'] ?></td>
        <td><?= (int)$r['disapproved'] ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
