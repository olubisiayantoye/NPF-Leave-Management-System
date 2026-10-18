<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('signals.view');
$user = current_user();

$leaveApplicationId = (int) ($_GET['leave_application_id'] ?? 0);

$stmt = db()->prepare(
    'SELECT s.*, la.application_no, la.days_requested, la.proceeding_date, la.expected_resumption_date,
            la.previous_leave_date, la.recommended_by_name, la.recommendation_date,
            la.checked_by_name, la.checked_up_to,
            la.home_town, la.leave_address, la.proceeding_with_wife, la.accumulation_authority, la.approval_remarks,
            lt.name AS leave_type_name,
            p.f_ap_number, p.first_name, p.middle_name, p.surname,
            r.name AS rank_name, d.name AS department_name,
            u.full_name AS approving_authority
     FROM signals s
     JOIN leave_applications la ON la.id = s.leave_application_id
     JOIN leave_types lt ON lt.id = la.leave_type_id
     JOIN personnel p ON p.id = la.personnel_id
     JOIN ranks r ON r.id = p.rank_id
     JOIN departments d ON d.id = p.department_id
     LEFT JOIN users u ON u.id = la.approved_by
     WHERE s.leave_application_id = :id'
);
$stmt->execute(['id' => $leaveApplicationId]);
$signal = $stmt->fetch();

if (!$signal) {
    http_response_code(404);
    die('No signal has been generated for that application.');
}

// Record the print (first time sets printed_at; every open is still audited).
if (!$signal['printed_at']) {
    db()->prepare('UPDATE signals SET printed_at = NOW() WHERE id = :id')->execute(['id' => $signal['id']]);
}
audit_log($user['id'], 'SIGNAL_PRINTED', 'signals', $signal['id'], "Signal {$signal['signal_reference']} opened for print");

$fullName = trim($signal['first_name'] . ' ' . ($signal['middle_name'] ? $signal['middle_name'] . ' ' : '') . $signal['surname']);
$leaveExpires = $signal['expected_resumption_date'] ? date('Y-m-d', strtotime($signal['expected_resumption_date'] . ' -1 day')) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Signal <?= e($signal['signal_reference']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Spectral:wght@600;700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    body { font-family:'Inter',sans-serif; color:#23271F; background:#fff; max-width:720px; margin:40px auto; padding:0 20px; }
    .head { text-align:center; border-bottom:2px solid #23271F; padding-bottom:14px; margin-bottom:22px; }
    .head .agency { font-weight:700; letter-spacing:0.1em; font-size:1rem; }
    .head .hq { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:#556; margin-top:2px;}
    .head .title { font-family:'Spectral',serif; font-weight:700; font-size:1.3rem; margin-top:12px; }
    .head .ref { font-family:'IBM Plex Mono',monospace; font-size:0.8rem; color:#556; margin-top:4px; }
    table.fields { width:100%; border-collapse: collapse; margin-bottom:24px; }
    table.fields td { padding:8px 4px; border-bottom:1px solid #e4e2da; vertical-align:top; }
    table.fields td.label { width:38%; font-family:'IBM Plex Mono',monospace; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em; color:#667; }
    table.fields td.value { font-weight:600; }
    .remarks { margin-bottom:30px; }
    .remarks .label { font-family:'IBM Plex Mono',monospace; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em; color:#667; margin-bottom:4px; }
    .sign-row { display:flex; justify-content:space-between; margin-top:60px; }
    .sign-block { width:45%; border-top:1px solid #23271F; padding-top:6px; font-size:0.78rem; color:#556; text-align:center; }
    .no-print { text-align:center; margin:24px 0; }
    .no-print button { font-family:'Inter',sans-serif; font-weight:600; padding:10px 20px; background:#C9A227; border:none; border-radius:3px; cursor:pointer; }
    @media print { .no-print { display:none; } body { margin:0; } }
</style>
</head>
<body>

<div class="head">
    <div class="agency">NIGERIA POLICE FORCE</div>
    <div class="hq">Force Headquarters, Abuja</div>
    <div class="title">LEAVE APPROVAL SIGNAL</div>
    <div class="ref"><?= e($signal['signal_reference']) ?> &nbsp;·&nbsp; Ref. Application <?= e($signal['application_no']) ?></div>
</div>

<table class="fields">
    <tr><td class="label">F-AP Number</td><td class="value"><?= e($signal['f_ap_number']) ?></td></tr>
    <tr><td class="label">Name</td><td class="value"><?= e($fullName) ?></td></tr>
    <tr><td class="label">Rank</td><td class="value"><?= e($signal['rank_name']) ?></td></tr>
    <tr><td class="label">Station</td><td class="value"><?= e($signal['department_name']) ?></td></tr>
    <tr><td class="label">Date Resumed Duty from Last Leave</td><td class="value"><?= e($signal['previous_leave_date'] ?? '—') ?></td></tr>
    <tr><td class="label">Leave Type</td><td class="value"><?= e($signal['leave_type_name']) ?></td></tr>
    <tr><td class="label">Number of Days Requested</td><td class="value"><?= (int)$signal['days_requested'] ?> working days</td></tr>
    <tr><td class="label">Authority for Accumulation</td><td class="value"><?= e($signal['accumulation_authority'] ?? '—') ?></td></tr>
    <tr><td class="label">Date Proceeding on Leave</td><td class="value"><?= e($signal['proceeding_date']) ?></td></tr>
    <tr><td class="label">Date Due to Resume Duty from Leave</td><td class="value"><?= e($signal['expected_resumption_date'] ?? '—') ?></td></tr>
    <tr><td class="label">Home Town</td><td class="value"><?= e($signal['home_town'] ?? '—') ?></td></tr>
    <tr><td class="label">Address While on Leave</td><td class="value"><?= e($signal['leave_address'] ?? '—') ?></td></tr>
    <tr><td class="label">Proceeding with Wife?</td><td class="value"><?= $signal['proceeding_with_wife'] ? 'Yes' : 'No' ?></td></tr>
    <tr><td class="label">Leave Recommended By</td><td class="value"><?= e($signal['recommended_by_name'] ?? '—') ?></td></tr>
    <tr><td class="label">Recommendation Date</td><td class="value"><?= e($signal['recommendation_date'] ?? '—') ?></td></tr>
    <tr><td class="label">Leave Approved/Not Approved</td><td class="value">Approved</td></tr>
    <tr><td class="label">Approval Date</td><td class="value"><?= e(date('Y-m-d', strtotime($signal['generated_at']))) ?></td></tr>
    <tr><td class="label">Leave Checked Up To</td><td class="value"><?= e($signal['checked_up_to'] ?? '—') ?></td></tr>
    <tr><td class="label">Leave Commences</td><td class="value"><?= e($signal['proceeding_date']) ?></td></tr>
    <tr><td class="label">Leave Expires</td><td class="value"><?= e($leaveExpires ?? '—') ?></td></tr>
    <tr><td class="label">Resumes Duty</td><td class="value"><?= e($signal['expected_resumption_date'] ?? '—') ?></td></tr>
    <tr><td class="label">Checked By</td><td class="value"><?= e($signal['checked_by_name'] ?? '—') ?></td></tr>
    <tr><td class="label">Approving Authority</td><td class="value"><?= e($signal['approving_authority'] ?? '—') ?></td></tr>
</table>

<?php if ($signal['approval_remarks']): ?>
<div class="remarks">
    <div class="label">Remarks</div>
    <div><?= nl2br(e($signal['approval_remarks'])) ?></div>
</div>
<?php endif; ?>

<div class="sign-row">
    <div class="sign-block">Approving Officer</div>
    <div class="sign-block">Officer's Acknowledgement</div>
</div>

<div class="no-print">
    <button onclick="window.print()">Print this Signal</button>
</div>

</body>
</html>
