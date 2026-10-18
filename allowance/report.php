<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$pdo = db();

$sectionFilter = trim($_GET['section'] ?? '');
$bankFilter = $_GET['bank_id'] ?? '';
$rankFilter = $_GET['rank_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$run = isset($_GET['run']);

$rows = [];
if ($run) {
    $where = ['1=1'];
    $params = [];
    if ($sectionFilter !== '') { $where[] = 'a.section = ?'; $params[] = $sectionFilter; }
    if ($bankFilter !== '') { $where[] = 'a.bank_id = ?'; $params[] = $bankFilter; }
    if ($rankFilter !== '') { $where[] = 'a.rank_id = ?'; $params[] = $rankFilter; }
    if ($statusFilter !== '') { $where[] = 'a.status = ?'; $params[] = $statusFilter; }

    $sql = "SELECT a.fap_number, r.name AS rank_name, a.full_name, a.account_number, b.name AS bank_name,
                   a.employee_code, a.section, a.status
            FROM monthly_allowance_personnel a
            LEFT JOIN ranks r ON r.id = a.rank_id
            LEFT JOIN banks b ON b.id = a.bank_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.section, a.full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    audit_log('ALLOWANCE_REPORT_GENERATED', 'monthly_allowance_personnel', null, 'Filters: ' . json_encode($_GET));
}

$sections = $pdo->query(
    "SELECT DISTINCT section FROM monthly_allowance_personnel WHERE section IS NOT NULL AND section != '' ORDER BY section"
)->fetchAll();

$pageTitle = 'Monthly Allowance Report';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-file-earmark-text"></i> Monthly Allowance Report</h4>

<div class="card mb-4"><div class="card-body">
  <form method="get" class="row g-2">
    <input type="hidden" name="run" value="1">
    <div class="col-md-3">
      <label class="form-label small">Rank</label>
      <select name="rank_id" class="form-select">
        <option value="">All Ranks</option>
        <?php foreach (lookup('ranks') as $r): ?>
          <option value="<?= $r['id'] ?>" <?= $rankFilter == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Section</label>
      <select name="section" class="form-select">
        <option value="">All Sections</option>
        <?php foreach ($sections as $s): ?>
          <option value="<?= e($s['section']) ?>" <?= $sectionFilter === $s['section'] ? 'selected' : '' ?>><?= e($s['section']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Bank</label>
      <select name="bank_id" class="form-select">
        <option value="">All Banks</option>
        <?php foreach (lookup('banks') as $b): ?>
          <option value="<?= $b['id'] ?>" <?= $bankFilter == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Status</label>
      <select name="status" class="form-select">
        <option value="">All Status</option>
        <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
        <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
    <div class="col-md-12 mt-2">
      <button class="btn btn-primary"><i class="bi bi-funnel"></i> Generate Report</button>
    </div>
  </form>
</div></div>

<?php if ($run): ?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <div class="text-muted">Showing <?= count($rows) ?> record(s)</div>
  <div>
    <a href="<?= BASE_URL ?>/allowance/export_xlsx.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel (.xlsx)</a>
    <a href="<?= BASE_URL ?>/allowance/print_view.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-outline-danger btn-sm"><i class="bi bi-printer"></i> Print / Export PDF</a>
  </div>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-striped table-sm mb-0">
      <thead class="table-dark">
        <tr><th>F-AP Number</th><th>Rank</th><th>Name</th><th>Account No.</th><th>Bank</th><th>Employee Code</th><th>Section</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['fap_number']) ?></td><td><?= e($r['rank_name']) ?></td><td><?= e($r['full_name']) ?></td>
          <td><?= e($r['account_number']) ?></td><td><?= e($r['bank_name']) ?></td><td><?= e($r['employee_code']) ?></td>
          <td><?= e($r['section']) ?></td><td><?= e($r['status']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">No records match this filter.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
