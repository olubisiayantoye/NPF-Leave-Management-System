<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$pdo = db();
$where = ["p.status != 'Archived'"];
$params = [];
if (!empty($_GET['department_id'])) { $where[] = 'p.department_id = ?'; $params[] = $_GET['department_id']; }
if (!empty($_GET['gender'])) { $where[] = 'p.gender = ?'; $params[] = $_GET['gender']; }
if (!empty($_GET['state_id'])) { $where[] = 'p.state_id = ?'; $params[] = $_GET['state_id']; }
if (!empty($_GET['rank_id'])) { $where[] = 'p.rank_id = ?'; $params[] = $_GET['rank_id']; }
if (!empty($_GET['zone_id'])) { $where[] = 'p.zone_id = ?'; $params[] = $_GET['zone_id']; }
if (!empty($_GET['educational_qualification'])) { $where[] = 'p.educational_qualification LIKE ?'; $params[] = '%' . $_GET['educational_qualification'] . '%'; }
if (!empty($_GET['retirement_from'])) { $where[] = 'p.date_of_retirement >= ?'; $params[] = $_GET['retirement_from']; }
if (!empty($_GET['retirement_to'])) { $where[] = 'p.date_of_retirement <= ?'; $params[] = $_GET['retirement_to']; }

$sql = "SELECT p.ap_number, p.full_name, r.name AS rank_name, d.name AS dept_name, p.gender, z.name AS zone_name,
               p.date_of_enlistment, p.date_of_retirement, p.status
        FROM personnel p
        LEFT JOIN ranks r ON r.id = p.rank_id
        LEFT JOIN departments d ON d.id = p.department_id
        LEFT JOIN geo_political_zones z ON z.id = p.zone_id
        WHERE " . implode(' AND ', $where) . " ORDER BY d.name, p.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
audit_log('REPORT_EXPORTED_PDF', 'nominal_roll', null, 'Filters: ' . json_encode($_GET));
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Nominal Roll</title>
<style>
  body{font-family: Arial, sans-serif; font-size:12px; margin:2rem;}
  h2,h4{text-align:center; margin:.2rem 0;}
  table{width:100%; border-collapse:collapse; margin-top:1rem;}
  th,td{border:1px solid #444; padding:4px 6px; text-align:left;}
  th{background:#0b2545; color:#fff;}
  .no-print{ text-align:center; margin-bottom:1rem; }
  @media print { .no-print{ display:none; } }
</style>
</head>
<body>
<div class="no-print"><button onclick="window.print()">Print / Save as PDF</button></div>
<h2>NIGERIA POLICE FORCE</h2>
<h4>IGP-Secretariat &mdash; Nominal Roll Report</h4>
<p style="text-align:center">Generated: <?= date('d M Y, H:i') ?> &middot; Total Records: <?= count($rows) ?></p>
<table>
<thead><tr><th>#</th><th>AP Number</th><th>Full Name</th><th>Rank</th><th>Department</th><th>Gender</th><th>Zone</th><th>Enlisted</th><th>Retirement</th><th>Status</th></tr></thead>
<tbody>
<?php $i=1; foreach ($rows as $r): ?>
<tr>
  <td><?= $i++ ?></td><td><?= e($r['ap_number']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['rank_name']) ?></td>
  <td><?= e($r['dept_name']) ?></td><td><?= e($r['gender']) ?></td><td><?= e($r['zone_name']) ?></td>
  <td><?= format_date($r['date_of_enlistment']) ?></td><td><?= format_date($r['date_of_retirement']) ?></td><td><?= e($r['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body></html>
