<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/xlsx_reader.php';
require_once __DIR__ . '/../includes/xlsx_writer.php';

require_permission('personnel.manage');
$user = current_user();

// Template columns, in the exact order the parser expects.
$templateColumns = [
    'F-AP Number', 'First Name', 'Middle Name', 'Surname', 'Gender (M/F)',
    'Date of Birth (YYYY-MM-DD)', 'Rank', 'Department', 'Section', 'Command',
    'Date of First Appointment (YYYY-MM-DD)', 'Phone', 'Email',
];

// ---------------------------------------------------------------------
// Template download — .csv or .xlsx, same columns either way
// ---------------------------------------------------------------------
if (($_GET['template'] ?? '') === 'csv') {
    export_csv('npf_lms_personnel_template.csv', array_combine($templateColumns, range(0, count($templateColumns) - 1)), [
        array_fill(0, count($templateColumns), ''),
    ]);
}
if (($_GET['template'] ?? '') === 'xlsx') {
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    write_xlsx($tmp, $templateColumns, [['315443', 'Adewale', '', 'Bello', 'M', '1988-04-12', 'Inspector', 'IGP Secretariat', '', '', '2012-01-15', '08012345678', 'a.bello@npf.gov.ng']]);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="npf_lms_personnel_template.xlsx"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    unlink($tmp);
    exit;
}

// ---------------------------------------------------------------------
// Lookups, cached name => id (case-insensitive)
// ---------------------------------------------------------------------
function lookup_map(string $table): array
{
    $map = [];
    foreach (db()->query("SELECT id, name FROM {$table}") as $row) {
        $map[mb_strtolower(trim($row['name']))] = (int) $row['id'];
    }
    return $map;
}

$results = null; // ['created' => n, 'skipped' => n, 'errors' => [ 'row 3: ...', ... ]]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        flash_set('error', 'Please choose a .csv or .xlsx file to upload.');
        redirect('personnel/bulk_upload.php');
    }

    $tmpPath = $_FILES['file']['tmp_name'];
    $origName = $_FILES['file']['name'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    $dataRows = [];
    if ($ext === 'csv') {
        if (($handle = fopen($tmpPath, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $dataRows[] = $row;
            }
            fclose($handle);
        }
    } elseif ($ext === 'xlsx') {
        $dataRows = read_xlsx($tmpPath) ?? [];
    } else {
        flash_set('error', 'Unsupported file type — please upload a .csv or .xlsx file.');
        redirect('personnel/bulk_upload.php');
    }

    if (count($dataRows) < 2) {
        flash_set('error', 'That file has no data rows below the header.');
        redirect('personnel/bulk_upload.php');
    }

    array_shift($dataRows); // drop header row

    $ranks = lookup_map('ranks');
    $departments = lookup_map('departments');
    $commands = lookup_map('commands');
    // Sections are scoped to a department, so key on "department|section"
    $sectionMap = [];
    foreach (db()->query('SELECT s.id, s.name, d.name AS department_name FROM sections s JOIN departments d ON d.id = s.department_id') as $row) {
        $sectionMap[mb_strtolower(trim($row['department_name'])) . '|' . mb_strtolower(trim($row['name']))] = (int) $row['id'];
    }

    $created = 0;
    $skipped = 0;
    $errors = [];

    $insertStmt = db()->prepare(
        'INSERT INTO personnel
            (f_ap_number, first_name, middle_name, surname, gender, date_of_birth,
             rank_id, department_id, section_id, command_id, date_of_first_appointment,
             phone, email, status)
         VALUES
            (:f_ap_number, :first_name, :middle_name, :surname, :gender, :dob,
             :rank_id, :department_id, :section_id, :command_id, :appoint_date,
             :phone, :email, "active")'
    );
    $dupeStmt = db()->prepare('SELECT id FROM personnel WHERE f_ap_number = :f');

    foreach ($dataRows as $i => $row) {
        $rowNum = $i + 2; // account for header + 1-indexing, matches what a user sees in Excel
        $row = array_pad($row, count($templateColumns), '');

        [$fApNumber, $firstName, $middleName, $surname, $gender, $dob,
         $rankName, $deptName, $sectionName, $commandName, $appointDate, $phone, $email] = array_map('trim', $row);

        if ($fApNumber === '' && $firstName === '' && $surname === '') {
            continue; // silently skip fully blank rows
        }
        if ($fApNumber === '' || $firstName === '' || $surname === '') {
            $errors[] = "Row {$rowNum}: F-AP Number, First Name and Surname are all required.";
            $skipped++;
            continue;
        }

        $genderCode = strtoupper(substr($gender, 0, 1));
        if (!in_array($genderCode, ['M', 'F'], true)) {
            $errors[] = "Row {$rowNum} ({$fApNumber}): Gender must be M or F.";
            $skipped++;
            continue;
        }

        $rankId = $ranks[mb_strtolower($rankName)] ?? null;
        if (!$rankId) {
            $errors[] = "Row {$rowNum} ({$fApNumber}): Rank \"{$rankName}\" is not configured. Add it under Administration → Ranks first.";
            $skipped++;
            continue;
        }
        $deptId = $departments[mb_strtolower($deptName)] ?? null;
        if (!$deptId) {
            $errors[] = "Row {$rowNum} ({$fApNumber}): Department \"{$deptName}\" is not configured. Add it under Administration → Departments first.";
            $skipped++;
            continue;
        }
        $sectionId = $sectionName !== '' ? ($sectionMap[mb_strtolower($deptName) . '|' . mb_strtolower($sectionName)] ?? null) : null;
        $commandId = $commandName !== '' ? ($commands[mb_strtolower($commandName)] ?? null) : null;

        $dupeStmt->execute(['f' => $fApNumber]);
        if ($dupeStmt->fetch()) {
            $errors[] = "Row {$rowNum} ({$fApNumber}): Already registered — skipped.";
            $skipped++;
            continue;
        }

        try {
            $insertStmt->execute([
                'f_ap_number'   => $fApNumber,
                'first_name'    => $firstName,
                'middle_name'   => $middleName ?: null,
                'surname'       => $surname,
                'gender'        => $genderCode,
                'dob'           => $dob ?: null,
                'rank_id'       => $rankId,
                'department_id' => $deptId,
                'section_id'    => $sectionId,
                'command_id'    => $commandId,
                'appoint_date'  => $appointDate ?: null,
                'phone'         => $phone ?: null,
                'email'         => $email ?: null,
            ]);
            $created++;
        } catch (PDOException $e) {
            $errors[] = "Row {$rowNum} ({$fApNumber}): Could not save — check the date formats and try again.";
            $skipped++;
        }
    }

    audit_log($user['id'], 'PERSONNEL_BULK_UPLOAD', 'personnel', null, "{$created} created, {$skipped} skipped from {$origName}");
    $results = ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
}

$pageTitle = 'Bulk Upload Personnel';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($results): ?>
<div class="alert <?= $results['created'] > 0 ? 'alert-success' : 'alert-warning' ?>">
    <strong><?= $results['created'] ?></strong> record(s) created, <strong><?= $results['skipped'] ?></strong> skipped.
</div>
<?php if ($results['errors']): ?>
<div class="card border-0 shadow-sm mb-4">
<div class="card-body">
    <h3 class="h6 mb-2">Rows that need attention</h3>
    <ul class="small mb-0">
        <?php foreach ($results['errors'] as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
</div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Upload File</h2>
            <p class="text-muted small">Accepts <strong>.csv</strong> or <strong>.xlsx</strong>. First row must be the header. Rank and Department names must already exist (Administration → Ranks / Departments) — everything else is optional.</p>
            <form method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <input type="file" name="file" accept=".csv,.xlsx" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-npf-gold">Upload &amp; Process</button>
            </form>
        </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Download a Template</h2>
            <p class="text-muted small mb-3">Column order: <?= e(implode(' · ', $templateColumns)) ?></p>
            <div class="d-flex gap-2">
                <a href="?template=csv" class="btn btn-outline-dark btn-sm"><i class="bi bi-filetype-csv"></i> CSV Template</a>
                <a href="?template=xlsx" class="btn btn-outline-dark btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel Template</a>
            </div>
        </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
