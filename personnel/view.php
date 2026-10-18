<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT p.*, d.name AS dept_name, r.name AS rank_name, s.name AS state_name, l.name AS lga_name,
            t.name AS tribe_name, z.name AS zone_name, c.name AS command_name, b.name AS bank_name,
            pfa.name AS pfa_name
     FROM personnel p
     LEFT JOIN departments d ON d.id = p.department_id
     LEFT JOIN ranks r ON r.id = p.rank_id
     LEFT JOIN states s ON s.id = p.state_id
     LEFT JOIN local_governments l ON l.id = p.lga_id
     LEFT JOIN tribes t ON t.id = p.tribe_id
     LEFT JOIN geo_political_zones z ON z.id = p.zone_id
     LEFT JOIN commands c ON c.id = p.previous_command_id
     LEFT JOIN banks b ON b.id = p.bank_id
     LEFT JOIN pfas pfa ON pfa.id = p.pfa_id
     WHERE p.id = ?"
);
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { flash('error', 'Personnel record not found.'); header('Location: ' . url('/personnel/list.php')); exit; }

$transferHistory = db()->prepare(
    "SELECT t.*, d1.name AS from_name, d2.name AS to_name FROM transfers t
     LEFT JOIN departments d1 ON d1.id = t.from_department_id
     LEFT JOIN departments d2 ON d2.id = t.to_department_id
     WHERE t.personnel_id = ? ORDER BY t.transfer_date DESC"
);
$transferHistory->execute([$id]);
$transfers = $transferHistory->fetchAll();

$nextOfKin = db()->prepare('SELECT * FROM personnel_next_of_kin WHERE personnel_id = ? ORDER BY kin_number ASC');
$nextOfKin->execute([$id]);
$kinRecords = $nextOfKin->fetchAll();

$featuredStmt = db()->prepare('SELECT id FROM featured_staff WHERE personnel_id = ?');
$featuredStmt->execute([$id]);
$featuredEntry = $featuredStmt->fetch();

$pageTitle = $p['full_name'];
include __DIR__ . '/../includes/header.php';

$statusBadge = ['Active'=>'badge-active','Retired'=>'badge-retired','Transferred'=>'badge-transferred','Suspended'=>'badge-suspended','Archived'=>'badge-archived'];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h4 class="mb-0"><?= e($p['full_name']) ?> <span class="badge <?= $statusBadge[$p['status']] ?? 'bg-secondary' ?>"><?= e($p['status']) ?></span></h4>
    <div class="text-muted"><?= e($p['rank_name']) ?> &middot; <?= e($p['ap_number']) ?></div>
  </div>
  <div>
    <?php if (in_array($user['role_name'], ['Super Administrator','Administrator','Documentation Officer'])): ?>
    <a href="<?= BASE_URL ?>/personnel/edit.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/reports/export_pdf.php?id=<?= $p['id'] ?>"
   target="_blank"
   rel="noopener noreferrer"
   class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-printer"></i> Print Verification Slip
   </a>
    <?php if (has_permission('settings.featured_staff')): ?>
      <?php if ($featuredEntry): ?>
        <a href="<?= BASE_URL ?>/settings/featured_staff_delete.php?id=<?= $featuredEntry['id'] ?>&redirect_to=<?= urlencode('/personnel/view.php?id=' . $p['id']) ?>"
           class="btn btn-outline-warning btn-sm" data-confirm="Remove <?= e($p['full_name']) ?> from the front page?">
           <i class="bi bi-star-fill"></i> Remove from Front Page</a>
      <?php else: ?>
        <form method="post" action="<?= BASE_URL ?>/settings/featured_staff_save.php" class="d-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="personnel_id" value="<?= $p['id'] ?>">
          <input type="hidden" name="redirect_to" value="/personnel/view.php?id=<?= $p['id'] ?>">
          <button type="submit" class="btn btn-outline-warning btn-sm"><i class="bi bi-star"></i> Feature on Front Page</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-3 text-center">
    <img src="<?= e(url($p['photo_path'] ?: '/uploads/officer.png')) ?>" class="img-fluid rounded border">
  </div>
  <div class="col-md-9">
    <div class="row">
      <div class="col-md-6">
        <table class="table table-sm">
          <tr><th width="45%">Gender</th><td><?= e($p['gender']) ?></td></tr>
          <tr><th>Date of Birth</th><td><?= format_date($p['date_of_birth']) ?> <?php $a = age_from_dob($p['date_of_birth']); if ($a) echo "($a yrs)"; ?></td></tr>
          <tr><th>State of Origin</th><td><?= e($p['state_name']) ?></td></tr>
          <tr><th>LGA</th><td><?= e($p['lga_name']) ?></td></tr>
          <tr><th>Tribe</th><td><?= e($p['tribe_name']) ?></td></tr>
          <tr><th>Geopolitical Zone</th><td><?= e($p['zone_name']) ?></td></tr>
          <tr><th>Phone</th><td><?= e($p['phone_number']) ?></td></tr>
          <tr><th>Email</th><td><?= e($p['email_address']) ?></td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-sm">
          <tr><th width="45%">Department</th><td><?= e($p['dept_name']) ?></td></tr>
          <tr><th>Duty Post</th><td><?= e($p['duty_post']) ?></td></tr>
          <tr><th>Date of Enlistment</th><td><?= format_date($p['date_of_enlistment']) ?></td></tr>
          <tr><th>Last Promotion</th><td><?= format_date($p['date_of_last_promotion']) ?></td></tr>
          <tr><th>Date of Retirement</th><td><?= format_date($p['date_of_retirement']) ?></td></tr>
          <tr><th>Grade Level</th><td><?= e($p['grade_level']) ?></td></tr>
          <tr><th>Employee Code</th><td><?= e($p['employee_code']) ?></td></tr>
          <tr><th>IPPIS Number</th><td><?= e($p['ippis_number']) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-6">
    <div class="card"><div class="card-header bg-white">Financial Information</div>
      <table class="table table-sm mb-0">
        <tr><th width="45%">Bank</th><td><?= e($p['bank_name']) ?></td></tr>
        <tr><th>Account Number</th><td><?= e($p['account_number']) ?></td></tr>
        <tr><th>PFA</th><td><?= e($p['pfa_name']) ?></td></tr>
        <tr><th>Pension PIN</th><td><?= e($p['pension_pin']) ?></td></tr>
        <tr><th>NHF Number</th><td><?= e($p['nhf_number']) ?></td></tr>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-header bg-white">Education & Other</div>
      <table class="table table-sm mb-0">
        <tr><th width="45%">Educational Qualification</th><td><?= e($p['educational_qualification']) ?></td></tr>
        <tr><th>GD/SP</th><td><?= e($p['gd_sp']) ?></td></tr>
        <tr><th>MSS</th><td><?= e($p['mss']) ?></td></tr>
      </table>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header bg-white">Next of Kin</div>
  <div class="card-body">
    <div class="row">
      <?php for ($k = 1; $k <= 2; $k++): $kin = null; foreach ($kinRecords as $r) if ((int)$r['kin_number'] === $k) $kin = $r; ?>
      <div class="col-md-6">
        <h6 class="text-primary">Next of Kin <?= $k ?></h6>
        <?php if ($kin && ($kin['full_name'] || $kin['phone_number'])): ?>
        <table class="table table-sm mb-3">
          <tr><th width="45%">Full Name</th><td><?= e($kin['full_name']) ?></td></tr>
          <tr><th>Relationship</th><td><?= e($kin['relationship']) ?></td></tr>
          <tr><th>Email Address</th><td><?= e($kin['email_address']) ?></td></tr>
          <tr><th>Residential Address</th><td><?= e($kin['residential_address']) ?></td></tr>
          <tr><th>Phone Number</th><td><?= e($kin['phone_number']) ?></td></tr>
          <tr><th>Alt. Phone Number</th><td><?= e($kin['alt_phone_number']) ?></td></tr>
        </table>
        <?php else: ?>
        <p class="text-muted small">Not provided.</p>
        <?php endif; ?>
      </div>
      <?php endfor; ?>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header bg-white">Remarks</div>
  <div class="card-body">
    <?= nl2br(e($p['remarks'])) ?: '<span class="text-muted">None.</span>' ?>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header bg-white">Transfer History</div>
  <ul class="list-group list-group-flush">
    <?php if (!$transfers): ?><li class="list-group-item text-muted small">No transfer history recorded.</li><?php endif; ?>
    <?php foreach ($transfers as $t): ?>
      <li class="list-group-item small"><?= e($t['from_name'] ?: '—') ?> &rarr; <?= e($t['to_name'] ?: '—') ?> on <?= format_date($t['transfer_date']) ?></li>
    <?php endforeach; ?>
  </ul>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
