<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
require_login();

$pdo = db();

// --- Core stats ---
$totalPersonnel  = $pdo->query("SELECT COUNT(*) c FROM personnel WHERE status != 'Archived'")->fetch()['c'];
$activePersonnel = $pdo->query("SELECT COUNT(*) c FROM personnel WHERE status = 'Active'")->fetch()['c'];
$malePersonnel   = $pdo->query("SELECT COUNT(*) c FROM personnel WHERE gender = 'Male' AND status != 'Archived'")->fetch()['c'];
$femalePersonnel = $pdo->query("SELECT COUNT(*) c FROM personnel WHERE gender = 'Female' AND status != 'Archived'")->fetch()['c'];
$totalDepartments = $pdo->query("SELECT COUNT(*) c FROM departments")->fetch()['c'];

// Retiring within next 90 days
$retiringSoon = $pdo->query(
    "SELECT id, full_name, ap_number, date_of_retirement FROM personnel
     WHERE date_of_retirement BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
     AND status = 'Active' ORDER BY date_of_retirement ASC LIMIT 8"
)->fetchAll();

// Birthdays this week (month/day match, ignoring year)
$birthdaysSoon = $pdo->query(
    "SELECT id, full_name, ap_number, date_of_birth FROM personnel
     WHERE status = 'Active' AND DATE_FORMAT(date_of_birth, '%m-%d')
     BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')
     ORDER BY DATE_FORMAT(date_of_birth, '%m-%d') ASC LIMIT 8"
)->fetchAll();

$recentTransfers = $pdo->query(
    "SELECT t.transfer_date, p.full_name, p.ap_number, d1.name AS from_dept, d2.name AS to_dept
     FROM transfers t
     JOIN personnel p ON p.id = t.personnel_id
     LEFT JOIN departments d1 ON d1.id = t.from_department_id
     LEFT JOIN departments d2 ON d2.id = t.to_department_id
     ORDER BY t.created_at DESC LIMIT 6"
)->fetchAll();

$recentRegistrations = $pdo->query(
    "SELECT id, full_name, ap_number, created_at FROM personnel ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

$byDepartment = $pdo->query(
    "SELECT d.name, COUNT(p.id) cnt FROM departments d
     LEFT JOIN personnel p ON p.department_id = d.id AND p.status != 'Archived'
     GROUP BY d.id ORDER BY cnt DESC LIMIT 8"
)->fetchAll();

$byRank = $pdo->query(
    "SELECT r.name, COUNT(p.id) cnt FROM ranks r
     LEFT JOIN personnel p ON p.rank_id = r.id AND p.status != 'Archived'
     GROUP BY r.id HAVING cnt > 0 ORDER BY cnt DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Dashboard</h4>
  <div class="text-muted small"><?= date('l, d F Y') ?></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card"><div class="card-body d-flex justify-content-between align-items-center">
      <div><div class="text-muted small">Total Personnel</div><div class="stat-value text-primary"><?= $totalPersonnel ?></div></div>
      <i class="bi bi-people"></i>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card"><div class="card-body d-flex justify-content-between align-items-center">
      <div><div class="text-muted small">Active</div><div class="stat-value text-success"><?= $activePersonnel ?></div></div>
      <i class="bi bi-person-check"></i>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card"><div class="card-body d-flex justify-content-between align-items-center">
      <div><div class="text-muted small">Male Officers</div><div class="stat-value"><?= $malePersonnel ?></div></div>
      <i class="bi bi-person"></i>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card"><div class="card-body d-flex justify-content-between align-items-center">
      <div><div class="text-muted small">Female Officers</div><div class="stat-value"><?= $femalePersonnel ?></div></div>
      <i class="bi bi-person-dress"></i>
    </div></div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-white"><i class="bi bi-hourglass-split text-warning"></i> Retirement Alerts (next 90 days)</div>
      <ul class="list-group list-group-flush">
        <?php if (!$retiringSoon): ?><li class="list-group-item text-muted small">No upcoming retirements.</li><?php endif; ?>
        <?php foreach ($retiringSoon as $r): ?>
          <li class="list-group-item d-flex justify-content-between">
            <a href="<?= BASE_URL ?>/personnel/view.php?id=<?= (int)$r['id'] ?>"><?= e($r['full_name']) ?> (<?= e($r['ap_number']) ?>)</a>
            <span class="text-muted small"><?= format_date($r['date_of_retirement']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-white"><i class="bi bi-balloon text-danger"></i> Birthday Notifications (next 7 days)</div>
      <ul class="list-group list-group-flush">
        <?php if (!$birthdaysSoon): ?><li class="list-group-item text-muted small">No birthdays this week.</li><?php endif; ?>
        <?php foreach ($birthdaysSoon as $b): ?>
          <li class="list-group-item d-flex justify-content-between">
            <a href="<?= BASE_URL ?>/personnel/view.php?id=<?= (int)$b['id'] ?>"><?= e($b['full_name']) ?> (<?= e($b['ap_number']) ?>)</a>
            <span class="text-muted small"><?= date('d M', strtotime($b['date_of_birth'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-white"><i class="bi bi-arrow-left-right"></i> Recent Transfers</div>
      <ul class="list-group list-group-flush">
        <?php if (!$recentTransfers): ?><li class="list-group-item text-muted small">No recent transfers.</li><?php endif; ?>
        <?php foreach ($recentTransfers as $t): ?>
          <li class="list-group-item">
            <div class="fw-semibold"><?= e($t['full_name']) ?> <span class="text-muted small">(<?= e($t['ap_number']) ?>)</span></div>
            <div class="small text-muted"><?= e($t['from_dept'] ?: '—') ?> &rarr; <?= e($t['to_dept'] ?: '—') ?> on <?= format_date($t['transfer_date']) ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-white"><i class="bi bi-person-plus"></i> Recent Registrations</div>
      <ul class="list-group list-group-flush">
        <?php if (!$recentRegistrations): ?><li class="list-group-item text-muted small">No recent registrations.</li><?php endif; ?>
        <?php foreach ($recentRegistrations as $r): ?>
          <li class="list-group-item d-flex justify-content-between">
            <a href="<?= BASE_URL ?>/personnel/view.php?id=<?= (int)$r['id'] ?>"><?= e($r['full_name']) ?> (<?= e($r['ap_number']) ?>)</a>
            <span class="text-muted small"><?= format_date($r['created_at']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card"><div class="card-header bg-white">Personnel by Department (Top 8)</div>
      <div class="card-body"><canvas id="deptChart" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-header bg-white">Personnel by Rank (Top 8)</div>
      <div class="card-body"><canvas id="rankChart" height="220"></canvas></div>
    </div>
  </div>
</div>

<div class="text-end mb-4">
  <a href="<?= BASE_URL ?>/reports/nominal_roll.php" class="btn btn-primary"><i class="bi bi-file-earmark-text"></i> Generate Nominal Roll Report</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('deptChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($byDepartment, 'name')) ?>,
    datasets: [{ label: 'Personnel', data: <?= json_encode(array_map('intval', array_column($byDepartment, 'cnt'))) ?>, backgroundColor: '#0b2545' }]
  },
  options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { autoSkip: false, maxRotation: 60 } } } }
});
new Chart(document.getElementById('rankChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($byRank, 'name')) ?>,
    datasets: [{ data: <?= json_encode(array_map('intval', array_column($byRank, 'cnt'))) ?>,
      backgroundColor: ['#0b2545','#c9a227','#198754','#0dcaf0','#dc3545','#6c757d','#6610f2','#fd7e14'] }]
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
