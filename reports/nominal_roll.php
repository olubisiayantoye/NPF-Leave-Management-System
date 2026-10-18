<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$pdo = db();

$deptFilter = $_GET['department_id'] ?? '';
$genderFilter = $_GET['gender'] ?? '';
$stateFilter = $_GET['state_id'] ?? '';
$rankFilter = $_GET['rank_id'] ?? '';
$zoneFilter = $_GET['zone_id'] ?? '';
$eduFilter = trim($_GET['educational_qualification'] ?? '');
$retFrom = $_GET['retirement_from'] ?? '';
$retTo = $_GET['retirement_to'] ?? '';
$run = isset($_GET['run']);

$rows = [];
if ($run) {
    $where = ["p.status != 'Archived'"];
    $params = [];
    if ($deptFilter !== '') { $where[] = 'p.department_id = ?'; $params[] = $deptFilter; }
    if ($genderFilter !== '') { $where[] = 'p.gender = ?'; $params[] = $genderFilter; }
    if ($stateFilter !== '') { $where[] = 'p.state_id = ?'; $params[] = $stateFilter; }
    if ($rankFilter !== '') { $where[] = 'p.rank_id = ?'; $params[] = $rankFilter; }
    if ($zoneFilter !== '') { $where[] = 'p.zone_id = ?'; $params[] = $zoneFilter; }
    if ($eduFilter !== '') { $where[] = 'p.educational_qualification LIKE ?'; $params[] = "%$eduFilter%"; }
    if ($retFrom !== '') { $where[] = 'p.date_of_retirement >= ?'; $params[] = $retFrom; }
    if ($retTo !== '') { $where[] = 'p.date_of_retirement <= ?'; $params[] = $retTo; }

    $sql = "SELECT p.ap_number, p.full_name, r.name AS rank_name, d.name AS dept_name, p.gender,
                   s.name AS state_name, z.name AS zone_name, p.date_of_enlistment, p.date_of_retirement, p.status
            FROM personnel p
            LEFT JOIN ranks r ON r.id = p.rank_id
            LEFT JOIN departments d ON d.id = p.department_id
            LEFT JOIN states s ON s.id = p.state_id
            LEFT JOIN geo_political_zones z ON z.id = p.zone_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY d.name, p.full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    audit_log('REPORT_GENERATED', 'nominal_roll', null, 'Filters: ' . json_encode($_GET));
}

$pageTitle = 'Nominal Roll Report';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-file-earmark-text"></i> Nominal Roll Report Generator</h4>

<div class="card mb-4"><div class="card-body">
  <form method="get" class="row g-2">
    <input type="hidden" name="run" value="1">
    <div class="col-md-3">
      <label class="form-label small">Department</label>
      <select name="department_id" class="form-select">
        <option value="">All Departments</option>
        <?php foreach (lookup('departments') as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small">Gender</label>
      <select name="gender" class="form-select">
        <option value="">All</option>
        <option value="Male" <?= $genderFilter === 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $genderFilter === 'Female' ? 'selected' : '' ?>>Female</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small">State of Origin</label>
      <select name="state_id" class="form-select">
        <option value="">All States</option>
        <?php foreach (lookup('states') as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $stateFilter == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
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
      <label class="form-label small">Geopolitical Zone</label>
      <select name="zone_id" class="form-select">
        <option value="">All Zones</option>
        <?php foreach (lookup('geo_political_zones') as $z): ?>
          <option value="<?= $z['id'] ?>" <?= $zoneFilter == $z['id'] ? 'selected' : '' ?>><?= e($z['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small">Retiring From</label>
      <input type="date" name="retirement_from" class="form-control" value="<?= e($retFrom) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small">Retiring To</label>
      <input type="date" name="retirement_to" class="form-control" value="<?= e($retTo) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label small">Educational Qualification contains</label>
      <input type="text" name="educational_qualification" class="form-control" value="<?= e($eduFilter) ?>">
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
    <a href="<?= BASE_URL ?>/reports/export_xlsx.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel (.xlsx)</a>
    <a href="<?= BASE_URL ?>/reports/print_view.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Print / Export PDF</a>
  </div>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-striped table-sm mb-0">
      <thead class="table-dark">
        <tr><th>AP Number</th><th>Name</th><th>Rank</th><th>Department</th><th>Gender</th><th>State</th><th>Zone</th><th>Enlisted</th><th>Retirement</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['ap_number']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['rank_name']) ?></td>
          <td><?= e($r['dept_name']) ?></td><td><?= e($r['gender']) ?></td><td><?= e($r['state_name']) ?></td>
          <td><?= e($r['zone_name']) ?></td>
          <td><?= format_date($r['date_of_enlistment']) ?></td><td><?= format_date($r['date_of_retirement']) ?></td>
          <td><?= e($r['status']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="10" class="text-center text-muted py-4">No records match this filter.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
