<?php
/**
 * Shared form fields for creating/editing a monthly allowance record.
 * Field order follows the module spec exactly: F-AP Number, Rank, Name,
 * Account Number, Bank Name, Employee Code, Section.
 * Expects: $a (array of existing values, empty array for a new record)
 */
$a = $a ?? [];
$val = fn($key) => e($a[$key] ?? '');
$sel = fn($field, $optId) => (isset($a[$field]) && (string)$a[$field] === (string)$optId) ? 'selected' : '';
?>
<div class="row g-2">
  <div class="col-md-3">
    <label class="form-label">1. F-AP Number *</label>
    <input type="text" name="fap_number" class="form-control" required value="<?= $val('fap_number') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">2. Rank *</label>
    <select name="rank_id" class="form-select" required>
      <option value="">Select Rank</option>
      <?php foreach (lookup('ranks') as $r): ?>
        <option value="<?= $r['id'] ?>" <?= $sel('rank_id', $r['id']) ?>><?= e($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">3. Name *</label>
    <input type="text" name="full_name" class="form-control" required value="<?= $val('full_name') ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">4. Account Number</label>
    <input type="text" name="account_number" class="form-control" value="<?= $val('account_number') ?>" pattern="[0-9]{6,20}" title="Digits only (6-20 digits)">
  </div>
  <div class="col-md-3">
    <label class="form-label">5. Bank Name</label>
    <select name="bank_id" class="form-select">
      <option value="">Select Bank</option>
      <?php foreach (lookup('banks') as $b): ?>
        <option value="<?= $b['id'] ?>" <?= $sel('bank_id', $b['id']) ?>><?= e($b['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">6. Employee Code</label>
    <input type="text" name="employee_code" class="form-control" value="<?= $val('employee_code') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">7. Section</label>
    <select name="section" class="form-select">
      <option value="">Select Section</option>
      <?php foreach (lookup('sections') as $s): ?>
        <option value="<?= e($s['name']) ?>" <?= ($a['section'] ?? '') === $s['name'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
      <?php endforeach; ?>
      <?php if (!empty($a['section']) && !in_array($a['section'], array_column(lookup('sections'), 'name'), true)): ?>
        <option value="<?= $val('section') ?>" selected><?= $val('section') ?> (not in Section list)</option>
      <?php endif; ?>
    </select>
    <div class="form-text">Manage the Section list from Settings &gt; Lookup Management.</div>
  </div>

  <div class="col-md-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="Active" <?= ($a['status'] ?? 'Active') === 'Active' ? 'selected' : '' ?>>Active</option>
      <option value="Inactive" <?= ($a['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
  </div>
</div>
