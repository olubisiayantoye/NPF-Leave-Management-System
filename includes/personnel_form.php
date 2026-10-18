<?php
/**
 * Shared form fields for creating/editing a personnel record.
 * Expects: $p (array of existing values, empty array for a new record), $isEdit (bool)
 */
$p = $p ?? [];
$val = fn($key) => e($p[$key] ?? '');
$sel = fn($field, $optId) => (isset($p[$field]) && (string)$p[$field] === (string)$optId) ? 'selected' : '';
?>
<div class="row g-3">

  <div class="col-md-3 text-center">
    <img id="photo_preview" src="<?= e(url(($p['photo_path'] ?? '') ?: '/uploads/officer.png')) ?>" class="photo-preview mb-2">
    <input type="file" id="photo_upload" name="photo" accept="image/*" class="form-control form-control-sm">
    <div class="form-text">JPEG/PNG, max 2MB.</div>
  </div>

  <div class="col-md-9">
    <h6 class="text-primary">Personal Information</h6>
    <div class="row g-2">
      <div class="col-md-4"><label class="form-label">AP Number / Service Number *</label>
        <input type="text" name="ap_number" class="form-control" required value="<?= $val('ap_number') ?>"></div>
      <div class="col-md-5"><label class="form-label">Full Name *</label>
        <input type="text" name="full_name" class="form-control" required value="<?= $val('full_name') ?>"></div>
      <div class="col-md-3"><label class="form-label">Gender *</label>
        <select name="gender" class="form-select" required>
          <option value="">Select</option>
          <option value="Male" <?= ($p['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= ($p['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
        </select></div>

      <div class="col-md-3"><label class="form-label">Date of Birth</label>
        <input type="date" name="date_of_birth" class="form-control" value="<?= $val('date_of_birth') ?>"></div>
      <div class="col-md-3"><label class="form-label">Rank</label>
        <select name="rank_id" class="form-select">
          <option value="">Select Rank</option>
          <?php foreach (lookup('ranks') as $r): ?>
            <option value="<?= $r['id'] ?>" <?= $sel('rank_id', $r['id']) ?>><?= e($r['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label">State of Origin</label>
        <select name="state_id" class="form-select">
          <option value="">Select State</option>
          <?php foreach (lookup('states') as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $sel('state_id', $s['id']) ?>><?= e($s['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label">LGA</label>
        <select name="lga_id" class="form-select">
          <option value="">Select LGA</option>
          <?php foreach (lookup('local_governments') as $l): ?>
            <option value="<?= $l['id'] ?>" <?= $sel('lga_id', $l['id']) ?>><?= e($l['name']) ?></option>
          <?php endforeach; ?>
        </select></div>

      <div class="col-md-3"><label class="form-label">Tribe</label>
        <select name="tribe_id" class="form-select">
          <option value="">Select Tribe</option>
          <?php foreach (lookup('tribes') as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $sel('tribe_id', $t['id']) ?>><?= e($t['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label">Geopolitical Zone</label>
        <select name="zone_id" class="form-select">
          <option value="">Select Zone</option>
          <?php foreach (lookup('geo_political_zones') as $z): ?>
            <option value="<?= $z['id'] ?>" <?= $sel('zone_id', $z['id']) ?>><?= e($z['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
    </div>
  </div>
</div>

<hr>
<h6 class="text-primary">Employment Information</h6>
<div class="row g-2">
  <div class="col-md-3"><label class="form-label">Date of Enlistment</label>
    <input type="date" name="date_of_enlistment" class="form-control" value="<?= $val('date_of_enlistment') ?>"></div>
  <div class="col-md-3"><label class="form-label">Date of Last Promotion</label>
    <input type="date" name="date_of_last_promotion" class="form-control" value="<?= $val('date_of_last_promotion') ?>"></div>
  <div class="col-md-3"><label class="form-label">Date of Retirement</label>
    <input type="date" name="date_of_retirement" class="form-control" value="<?= $val('date_of_retirement') ?>"></div>
  <div class="col-md-3"><label class="form-label">Current Unit / Department</label>
    <select name="department_id" class="form-select">
      <option value="">Select Department</option>
      <?php foreach (lookup('departments') as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $sel('department_id', $d['id']) ?>><?= e($d['name']) ?></option>
      <?php endforeach; ?>
    </select></div>

  <div class="col-md-3"><label class="form-label">Previous Command</label>
    <select name="previous_command_id" class="form-select">
      <option value="">Select Command</option>
      <?php foreach (lookup('commands') as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $sel('previous_command_id', $c['id']) ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="col-md-3"><label class="form-label">Date Transferred</label>
    <input type="date" name="date_transferred" class="form-control" value="<?= $val('date_transferred') ?>"></div>
  <div class="col-md-3"><label class="form-label">Duty Post</label>
    <input type="text" name="duty_post" class="form-control" value="<?= $val('duty_post') ?>"></div>
  <div class="col-md-3"><label class="form-label">Grade Level</label>
    <input type="text" name="grade_level" class="form-control" value="<?= $val('grade_level') ?>"></div>

  <div class="col-md-3"><label class="form-label">Employee Code</label>
    <input type="text" name="employee_code" class="form-control" value="<?= $val('employee_code') ?>"></div>
  <div class="col-md-3"><label class="form-label">IPPIS Number</label>
    <input type="text" name="ippis_number" class="form-control" value="<?= $val('ippis_number') ?>"></div>
</div>

<hr>
<h6 class="text-primary">Contact Information</h6>
<div class="row g-2">
  <div class="col-md-4"><label class="form-label">Phone Number</label>
    <input type="text" name="phone_number" class="form-control" value="<?= $val('phone_number') ?>"></div>
  <div class="col-md-4"><label class="form-label">Email Address</label>
    <input type="email" name="email_address" class="form-control" value="<?= $val('email_address') ?>"></div>
</div>

<hr>
<h6 class="text-primary">Financial Information</h6>
<div class="row g-2">
  <div class="col-md-3"><label class="form-label">Bank</label>
    <select name="bank_id" class="form-select">
      <option value="">Select Bank</option>
      <?php foreach (lookup('banks') as $b): ?>
        <option value="<?= $b['id'] ?>" <?= $sel('bank_id', $b['id']) ?>><?= e($b['name']) ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="col-md-3"><label class="form-label">Account Number</label>
    <input type="text" name="account_number" class="form-control" value="<?= $val('account_number') ?>"></div>
  <div class="col-md-3"><label class="form-label">Pension Fund Administrator (PFA)</label>
    <select name="pfa_id" class="form-select">
      <option value="">Select PFA</option>
      <?php foreach (lookup('pfas') as $pfa): ?>
        <option value="<?= $pfa['id'] ?>" <?= $sel('pfa_id', $pfa['id']) ?>><?= e($pfa['name']) ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="col-md-3"><label class="form-label">Pension PIN</label>
    <input type="text" name="pension_pin" class="form-control" value="<?= $val('pension_pin') ?>"></div>
  <div class="col-md-3"><label class="form-label">NHF Number</label>
    <input type="text" name="nhf_number" class="form-control" value="<?= $val('nhf_number') ?>"></div>
</div>

<hr>
<h6 class="text-primary">Education & Other</h6>
<div class="row g-2">
  <div class="col-md-4"><label class="form-label">Educational Qualification</label>
    <input type="text" name="educational_qualification" class="form-control" value="<?= $val('educational_qualification') ?>"></div>
  <div class="col-md-2"><label class="form-label">GD/SP</label>
    <input type="text" name="gd_sp" class="form-control" value="<?= $val('gd_sp') ?>"></div>
  <div class="col-md-2"><label class="form-label">MSS</label>
    <input type="text" name="mss" class="form-control" value="<?= $val('mss') ?>"></div>
  <div class="col-md-2"><label class="form-label">Status</label>
    <select name="status" class="form-select">
      <?php foreach (['Active','Retired','Transferred','Suspended'] as $s): ?>
        <option value="<?= $s ?>" <?= ($p['status'] ?? 'Active') === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select></div>
</div>

<hr>
<h6 class="text-primary">Next of Kin</h6>
<?php for ($k = 1; $k <= 2; $k++): ?>
<div class="row g-2 mb-2">
  <div class="col-12"><strong class="small text-muted">Next of Kin <?= $k ?></strong></div>
  <div class="col-md-3"><label class="form-label">Full Name</label>
    <input type="text" name="kin<?= $k ?>_full_name" class="form-control" value="<?= $val("kin{$k}_full_name") ?>"></div>
  <div class="col-md-2"><label class="form-label">Relationship</label>
    <input type="text" name="kin<?= $k ?>_relationship" class="form-control" value="<?= $val("kin{$k}_relationship") ?>"></div>
  <div class="col-md-3"><label class="form-label">Email Address</label>
    <input type="email" name="kin<?= $k ?>_email_address" class="form-control" value="<?= $val("kin{$k}_email_address") ?>"></div>
  <div class="col-md-4"><label class="form-label">Residential Address</label>
    <input type="text" name="kin<?= $k ?>_residential_address" class="form-control" value="<?= $val("kin{$k}_residential_address") ?>"></div>
  <div class="col-md-3"><label class="form-label">Phone Number</label>
    <input type="text" name="kin<?= $k ?>_phone_number" class="form-control" value="<?= $val("kin{$k}_phone_number") ?>"></div>
  <div class="col-md-3"><label class="form-label">Alternative Phone Number</label>
    <input type="text" name="kin<?= $k ?>_alt_phone_number" class="form-control" value="<?= $val("kin{$k}_alt_phone_number") ?>"></div>
</div>
<?php endfor; ?>

<hr>
<div class="row g-2">
  <div class="col-md-12"><label class="form-label">Remarks</label>
    <textarea name="remarks" class="form-control" rows="2"><?= $val('remarks') ?></textarea></div>
</div>
