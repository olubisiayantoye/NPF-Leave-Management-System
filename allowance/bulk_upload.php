<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/xlsx_reader.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$pdo = db();
$result = null;

// Column order matching the module's registration form / spec
$expectedColumns = ['F-AP Number', 'Rank', 'Name', 'Account Number', 'Bank Name', 'Employee Code', 'Section'];

/** Reads either .xlsx or .csv into a uniform array of rows (array of string cells). */
function read_upload_rows(string $tmpPath, string $ext): array
{
    if ($ext === 'xlsx') {
        $reader = new XlsxReader($tmpPath);
        $rows = $reader->rows();
        $reader->close();
        return $rows;
    }

    // CSV
    $rows = [];
    $handle = fopen($tmpPath, 'r');
    if ($handle === false) throw new RuntimeException('Could not open the uploaded CSV file.');
    $first = true;
    while (($row = fgetcsv($handle)) !== false) {
        if ($first && isset($row[0])) {
            $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]); // strip UTF-8 BOM
            $first = false;
        }
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['import_file']['tmp_name'])) {
    verify_csrf();

    $uploadedName = $_FILES['import_file']['name'];
    $ext = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'csv'], true)) {
        flash('error', 'Please upload a valid Excel (.xlsx) or CSV (.csv) file.');
        header('Location: ' . url('/allowance/bulk_upload.php'));
        exit;
    }

    try {
        $allRows = read_upload_rows($_FILES['import_file']['tmp_name'], $ext);
    } catch (Throwable $e) {
        flash('error', 'Could not read that file: ' . $e->getMessage());
        header('Location: ' . url('/allowance/bulk_upload.php'));
        exit;
    }

    // First row is the header — skip it, columns are matched by position.
    array_shift($allRows);

    $successCount = 0;
    $errorRows = [];
    $rowNum = 1;
    $seenInFile = []; // catches duplicate F-AP numbers within the same upload

    $mapByName = function (string $table) use ($pdo) {
        $rows = $pdo->query("SELECT id, name FROM `$table`")->fetchAll();
        $map = [];
        foreach ($rows as $r) $map[mb_strtolower(trim($r['name']))] = $r['id'];
        return $map;
    };
    $ranks = $mapByName('ranks');
    $banks = $mapByName('banks');

    $insertStmt = $pdo->prepare(
        'INSERT INTO monthly_allowance_personnel
            (fap_number, rank_id, full_name, account_number, bank_id, employee_code, section, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($allRows as $row) {
        $rowNum++;
        if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue; // skip blank rows

        $row = array_pad($row, count($expectedColumns), '');
        $rec = array_combine($expectedColumns, array_slice($row, 0, count($expectedColumns)));

        $fap = trim($rec['F-AP Number']);
        $name = trim($rec['Name']);
        $rankName = trim($rec['Rank']);

        if (!$fap || !$name || !$rankName) {
            $errorRows[] = "Row $rowNum: F-AP Number, Rank, and Name are all required.";
            continue;
        }

        $rankId = $ranks[mb_strtolower($rankName)] ?? null;
        if (!$rankId) {
            $errorRows[] = "Row $rowNum: Rank '$rankName' does not match any known rank.";
            continue;
        }

        $accountNumber = trim($rec['Account Number']);
        if ($accountNumber && !preg_match('/^\d{6,20}$/', $accountNumber)) {
            $errorRows[] = "Row $rowNum: Account Number '$accountNumber' must be 6-20 digits.";
            continue;
        }

        $fapKey = mb_strtolower($fap);
        if (isset($seenInFile[$fapKey])) {
            $errorRows[] = "Row $rowNum: F-AP Number '$fap' is duplicated earlier in this same file (row {$seenInFile[$fapKey]}) — skipped.";
            continue;
        }

        $dup = $pdo->prepare('SELECT id FROM monthly_allowance_personnel WHERE fap_number = ?');
        $dup->execute([$fap]);
        if ($dup->fetch()) {
            $errorRows[] = "Row $rowNum: F-AP Number '$fap' already exists (duplicate skipped).";
            continue;
        }

        $bankName = trim($rec['Bank Name']);
        $bankId = $bankName ? ($banks[mb_strtolower($bankName)] ?? null) : null;

        try {
            $insertStmt->execute([
                $fap,
                $rankId,
                $name,
                $accountNumber ?: null,
                $bankId,
                trim($rec['Employee Code']) ?: null,
                trim($rec['Section']) ?: null,
                $_SESSION['user_id'],
            ]);
            $seenInFile[$fapKey] = $rowNum;
            $successCount++;
        } catch (PDOException $e) {
            $errorRows[] = "Row $rowNum: database error — " . $e->getMessage();
        }
    }

    $total = $rowNum - 1;
    $pdo->prepare(
        'INSERT INTO import_history (uploaded_by, module, file_name, total_rows, success_count, error_count, error_report)
         VALUES (?, "monthly_allowance", ?, ?, ?, ?, ?)'
    )->execute([$_SESSION['user_id'], $uploadedName, $total, $successCount, count($errorRows), implode("\n", $errorRows)]);

    audit_log('ALLOWANCE_BULK_IMPORT', 'monthly_allowance_personnel', null, "Imported $successCount / $total rows from $uploadedName");
    $result = ['total' => $total, 'success' => $successCount, 'errors' => $errorRows];
}

$history = $pdo->query(
    "SELECT ih.*, u.full_name AS uploader FROM import_history ih
     LEFT JOIN users u ON u.id = ih.uploaded_by
     WHERE ih.module = 'monthly_allowance'
     ORDER BY ih.created_at DESC LIMIT 10"
)->fetchAll();

$pageTitle = 'Allowance Bulk Upload';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-file-earmark-excel"></i> Bulk Upload &mdash; Monthly Allowance Personnel</h4>

<div class="card mb-4"><div class="card-body">
  <p class="text-muted small mb-2">Upload an Excel (.xlsx) or CSV file with columns: F-AP Number, Rank, Name,
     Account Number, Bank Name, Employee Code, Section. Rank and Bank Name must match existing lookup values
     exactly (case-insensitive). Existing F-AP Numbers are skipped as duplicates and reported.</p>
  <a href="<?= BASE_URL ?>/allowance/bulk_upload_template.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-download"></i> Download Excel Template</a>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="mb-3"><input type="file" name="import_file" accept=".xlsx,.csv" class="form-control" required></div>
    <button class="btn btn-primary"><i class="bi bi-upload"></i> Import Records</button>
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
  <div class="card-header bg-white">Recent Import History (Monthly Allowance)</div>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
