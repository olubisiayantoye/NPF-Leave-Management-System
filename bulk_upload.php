<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/xlsx_reader.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$pdo = db();
$result = null;

// Expected column order, matching the spec's bulk-upload template
$expectedColumns = [
    'AP Number','Rank','Name','Gender','State of Origin','LGA','Tribe','Geo Political Zone',
    'Date of Birth','Date of Enlistment','Date of Last Promotion','Date of Retirement',
    'Command Served Last','Date Transferred','Educational Qualification','GD/SP','Duty Post',
    'Grade Level','Phone Number','Email Address','Account Number','Bank Name','Employee Code',
    'IPPIS Number','PFA','PEN-PIN','NHF Number','MSS',
];

/** Converts an Excel date cell (either a real date serial or a typed-in text date) to Y-m-d. */
function xlsx_cell_to_date($value): ?string
{
    $value = trim((string)$value);
    if ($value === '') return null;

    if (is_numeric($value)) {
        // Excel stores dates as a day-count serial from 1899-12-30 (accounting for
        // the historical 1900 leap-year bug baked into every spreadsheet app).
        $unixTime = ((float)$value - 25569) * 86400;
        return date('Y-m-d', (int)$unixTime);
    }
    return strtotime($value) ? date('Y-m-d', strtotime($value)) : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['import_file']['tmp_name'])) {
    verify_csrf();

    $uploadedName = $_FILES['import_file']['name'];
    $ext = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));

    if ($ext !== 'xlsx') {
        flash('error', 'Please upload a valid Excel (.xlsx) file.');
        header('Location: ' . url('/bulk_upload.php'));
        exit;
    }

    try {
        $reader = new XlsxReader($_FILES['import_file']['tmp_name']);
        $allRows = $reader->rows();
        $reader->close();
    } catch (Throwable $e) {
        flash('error', 'Could not read that file: ' . $e->getMessage());
        header('Location: ' . url('/bulk_upload.php'));
        exit;
    }

    // First row is the header — skip it, we match columns by position against $expectedColumns.
    array_shift($allRows);

    $successCount = 0;
    $errorRows = [];
    $rowNum = 1;

    // Build lookup name->id maps for referenced tables
    $mapByName = function (string $table) use ($pdo) {
        $rows = $pdo->query("SELECT id, name FROM `$table`")->fetchAll();
        $map = [];
        foreach ($rows as $r) $map[mb_strtolower(trim($r['name']))] = $r['id'];
        return $map;
    };
    $ranks = $mapByName('ranks');
    $states = $mapByName('states');
    $tribes = $mapByName('tribes');
    $zones = $mapByName('geo_political_zones');
    $commands = $mapByName('commands');
    $banks = $mapByName('banks');
    $pfas = $mapByName('pfas');

    $insertStmt = $pdo->prepare(
        'INSERT INTO personnel (ap_number, rank_id, full_name, gender, state_id, tribe_id, zone_id,
            date_of_birth, date_of_enlistment, date_of_last_promotion, date_of_retirement,
            previous_command_id, date_transferred, educational_qualification, gd_sp, duty_post,
            grade_level, phone_number, email_address, account_number, bank_id, employee_code,
            ippis_number, pfa_id, pension_pin, nhf_number, mss, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );

    foreach ($allRows as $row) {
        $rowNum++;
        if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue; // skip blank rows

        $row = array_pad($row, count($expectedColumns), '');
        $rec = array_combine($expectedColumns, array_slice($row, 0, count($expectedColumns)));

        $apNumber = trim($rec['AP Number']);
        $name = trim($rec['Name']);
        $gender = ucfirst(strtolower(trim($rec['Gender'])));

        if (!$apNumber || !$name || !in_array($gender, ['Male', 'Female'], true)) {
            $errorRows[] = "Row $rowNum: missing AP Number/Name or invalid Gender.";
            continue;
        }

        $dup = $pdo->prepare('SELECT id FROM personnel WHERE ap_number = ?');
        $dup->execute([$apNumber]);
        if ($dup->fetch()) {
            $errorRows[] = "Row $rowNum: AP Number '$apNumber' already exists (duplicate skipped).";
            continue;
        }

        $lookupId = fn($map, $v) => $v ? ($map[mb_strtolower(trim($v))] ?? null) : null;

        try {
            $insertStmt->execute([
                $apNumber,
                $lookupId($ranks, $rec['Rank']),
                $name,
                $gender,
                $lookupId($states, $rec['State of Origin']),
                $lookupId($tribes, $rec['Tribe']),
                $lookupId($zones, $rec['Geo Political Zone']),
                xlsx_cell_to_date($rec['Date of Birth']),
                xlsx_cell_to_date($rec['Date of Enlistment']),
                xlsx_cell_to_date($rec['Date of Last Promotion']),
                xlsx_cell_to_date($rec['Date of Retirement']),
                $lookupId($commands, $rec['Command Served Last']),
                xlsx_cell_to_date($rec['Date Transferred']),
                trim($rec['Educational Qualification']) ?: null,
                trim($rec['GD/SP']) ?: null,
                trim($rec['Duty Post']) ?: null,
                trim($rec['Grade Level']) ?: null,
                trim($rec['Phone Number']) ?: null,
                trim($rec['Email Address']) ?: null,
                trim($rec['Account Number']) ?: null,
                $lookupId($banks, $rec['Bank Name']),
                trim($rec['Employee Code']) ?: null,
                trim($rec['IPPIS Number']) ?: null,
                $lookupId($pfas, $rec['PFA']),
                trim($rec['PEN-PIN']) ?: null,
                trim($rec['NHF Number']) ?: null,
                trim($rec['MSS']) ?: null,
                $_SESSION['user_id'],
            ]);
            $successCount++;
        } catch (PDOException $e) {
            $errorRows[] = "Row $rowNum: database error — " . $e->getMessage();
        }
    }

    $total = $rowNum - 1;
    $pdo->prepare(
        'INSERT INTO import_history (uploaded_by, file_name, total_rows, success_count, error_count, error_report)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$_SESSION['user_id'], $uploadedName, $total, $successCount, count($errorRows), implode("\n", $errorRows)]);

    audit_log('BULK_IMPORT', 'personnel', null, "Imported $successCount / $total rows from $uploadedName");
    $result = ['total' => $total, 'success' => $successCount, 'errors' => $errorRows];
}

$history = $pdo->query(
    "SELECT ih.*, u.full_name AS uploader FROM import_history ih
     LEFT JOIN users u ON u.id = ih.uploaded_by ORDER BY ih.created_at DESC LIMIT 10"
)->fetchAll();

$pageTitle = 'Bulk Upload';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-file-earmark-excel"></i> Bulk Upload Personnel</h4>

<div class="card mb-4"><div class="card-body">
  <p class="text-muted small mb-2">Upload an Excel (.xlsx) file matching the template below.
     Existing AP Numbers are skipped as duplicates and reported.</p>
  <a href="<?= BASE_URL ?>/bulk_upload_template.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-download"></i> Download Excel Template</a>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="mb-3"><input type="file" name="import_file" accept=".xlsx" class="form-control" required></div>
    <button class="btn btn-primary"><i class="bi bi-upload"></i> Import Personnel</button>
  </form>
</div></div>

<?php if ($result): ?>
<div class="card mb-4">
  <div class="card-header bg-white">Import Result</div>
  <div class="card-body">
    <p>Processed <strong><?= $result['total'] ?></strong> rows &mdash;
       <span class="text-success"><?= $result['success'] ?> imported successfully</span>,
       <span class="text-danger"><?= count($result['errors']) ?> error(s)</span>.</p>
    <?php if ($result['errors']): ?>
      <div class="alert alert-warning small" style="max-height:250px; overflow-y:auto;">
        <?php foreach ($result['errors'] as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header bg-white">Recent Import History</div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead class="table-light"><tr><th>Date</th><th>File</th><th>Uploaded By</th><th>Total</th><th>Success</th><th>Errors</th></tr></thead>
      <tbody>
        <?php foreach ($history as $h): ?>
        <tr>
          <td><?= format_date($h['created_at']) ?></td><td><?= e($h['file_name']) ?></td><td><?= e($h['uploader']) ?></td>
          <td><?= $h['total_rows'] ?></td><td class="text-success"><?= $h['success_count'] ?></td><td class="text-danger"><?= $h['error_count'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$history): ?><tr><td colspan="6" class="text-center text-muted py-3">No imports yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
