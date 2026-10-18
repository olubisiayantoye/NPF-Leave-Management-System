<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$pdo = db();
$where = ['1=1'];
$params = [];
if (!empty($_GET['section'])) { $where[] = 'a.section = ?'; $params[] = $_GET['section']; }
if (!empty($_GET['bank_id'])) { $where[] = 'a.bank_id = ?'; $params[] = $_GET['bank_id']; }
if (!empty($_GET['rank_id'])) { $where[] = 'a.rank_id = ?'; $params[] = $_GET['rank_id']; }
if (!empty($_GET['status'])) { $where[] = 'a.status = ?'; $params[] = $_GET['status']; }

$sql = "SELECT a.fap_number, r.name AS rank_name, a.full_name, a.account_number, b.name AS bank_name,
               a.employee_code, a.section, a.status
        FROM monthly_allowance_personnel a
        LEFT JOIN ranks r ON r.id = a.rank_id
        LEFT JOIN banks b ON b.id = a.bank_id
        WHERE " . implode(' AND ', $where) . " ORDER BY a.section, a.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
audit_log('ALLOWANCE_REPORT_EXPORTED_PDF', 'monthly_allowance_personnel', null, 'Filters: ' . json_encode($_GET));
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Monthly Allowance Report</title>
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
<h4>IGP-Secretariat &mdash; Monthly Allowance Report</h4>
<p style="text-align:center">Generated: <?= date('d M Y, H:i') ?> &middot; Total Records: <?= count($rows) ?></p>
<table>
<thead><tr><th>#</th><th>F-AP Number</th><th>Rank</th><th>Name</th><th>Account No.</th><th>Bank</th><th>Employee Code</th><th>Section</th><th>Status</th></tr></thead>
<tbody>
<?php $i=1; foreach ($rows as $r): ?>
<tr>
  <td><?= $i++ ?></td><td><?= e($r['fap_number']) ?></td><td><?= e($r['rank_name']) ?></td><td><?= e($r['full_name']) ?></td>
  <td><?= e($r['account_number']) ?></td><td><?= e($r['bank_name']) ?></td><td><?= e($r['employee_code']) ?></td>
  <td><?= e($r['section']) ?></td><td><?= e($r['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body></html>
