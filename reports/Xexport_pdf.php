<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT p.*, d.name AS dept_name, r.name AS rank_name FROM personnel p
     LEFT JOIN departments d ON d.id = p.department_id
     LEFT JOIN ranks r ON r.id = p.rank_id
     WHERE p.id = ?"
);
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { die('Personnel record not found.'); }
audit_log('VERIFICATION_SLIP_PRINTED', 'personnel', $id);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Verification Slip &mdash; <?= e($p['full_name']) ?></title>
<style>
  body{font-family: Arial, sans-serif; margin:2rem;}
  .slip{border:2px solid #0b2545; padding:1.5rem; max-width:520px; margin:0 auto;}
  h3{text-align:center; color:#0b2545; margin-bottom:0;}
  .sub{text-align:center; color:#666; margin-bottom:1rem;}
  table{width:100%;} th{text-align:left; width:40%; padding:4px 0;} td{padding:4px 0;}
  img{display:block; margin:0 auto 1rem; width:120px; height:120px; object-fit:cover; border:1px solid #ccc;}
  .no-print{text-align:center; margin-bottom:1rem;}
  @media print{.no-print{display:none;}}
</style></head>
<body>
<div class="no-print"><button onclick="window.print()">Print / Save as PDF</button></div>
<div class="slip">
  <h3>NIGERIA POLICE FORCE</h3>
  <div class="sub">Personnel Verification Slip &mdash; IGP-Secretariat</div>
  <img src="<?= e(url($p['photo_path'] ?: 'https://via.placeholder.com/120?text=No+Photo')) ?>">
  <table>
    <tr><th>AP Number</th><td><?= e($p['ap_number']) ?></td></tr>
    <tr><th>Full Name</th><td><?= e($p['full_name']) ?></td></tr>
    <tr><th>Rank</th><td><?= e($p['rank_name']) ?></td></tr>
    <tr><th>Department</th><td><?= e($p['dept_name']) ?></td></tr>
    <tr><th>Status</th><td><?= e($p['status']) ?></td></tr>
    <tr><th>Verified On</th><td><?= date('d M Y, H:i') ?></td></tr>
  </table>
</div>
</body></html>
