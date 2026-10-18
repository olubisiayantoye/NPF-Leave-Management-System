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

if (!$signal['printed_at']) {
    db()->prepare('UPDATE signals SET printed_at = NOW() WHERE id = :id')->execute(['id' => $signal['id']]);
}
audit_log($user['id'], 'SIGNAL_PRINTED', 'signals', $signal['id'], "Signal {$signal['signal_reference']} opened for print");

$fullName = trim($signal['first_name'] . ' ' . ($signal['middle_name'] ? $signal['middle_name'] . ' ' : '') . $signal['surname']);

// Helper function to convert numbers to words (e.g., 20 -> TWENTY)
function numberToWords($num) {
    if (class_exists('NumberFormatter')) {
        $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        return strtoupper($f->format($num));
    }
    // Fallback
    $ones = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
    $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];
    if ($num < 20) return $ones[$num];
    if ($num < 100) return $tens[(int)($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
    if ($num < 1000) return $ones[(int)($num / 100)] . ' HUNDRED' . ($num % 100 ? ' AND ' . numberToWords($num % 100) : '');
    return (string)$num;
}

$daysInWords = numberToWords($signal['days_requested']);
$fromDept = strtoupper($signal['department_name'] ?: 'INGENPOL SEC ABUJA');
$toName = strtoupper($signal['rank_name'] . '  ' . $fullName);
$dto = date('dHi / m / Y', strtotime($signal['generated_at']));
$wef = date('d/m/Y', strtotime($signal['proceeding_date']));
$resume = date('d/m/Y', strtotime($signal['expected_resumption_date']));
$leaveType = strtoupper($signal['leave_type_name']);
$ref = strtoupper($signal['signal_reference']);
$vol = $signal['application_no'] ?: '1/1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Signal <?= e($signal['signal_reference']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
    body { 
        font-family: 'IBM Plex Mono', monospace; 
        color: #000; 
        background: #fff; 
        max-width: 720px; 
        margin: 40px auto; 
        padding: 20px; 
        font-size: 14px;
        line-height: 1.6;
    }
    .header { text-align: center; margin-bottom: 24px; font-weight: 700; font-size: 16px; letter-spacing: 0.05em; }
    .meta { margin-bottom: 24px; }
    .meta div { margin-bottom: 6px; }
    .label { font-weight: 700; display: inline-block; width: 70px; }
    .body-text { 
        text-align: justify; 
        text-justify: inter-word;
        margin-top: 24px;
        margin-bottom: 32px;
        line-height: 1.8;
        font-weight: 500;
    }
    .footer { margin-top: 40px; font-weight: 700; display: flex; justify-content: space-between; font-size: 13px; }
    .no-print { text-align: center; margin: 32px 0; }
    .no-print button { font-family: 'Inter', sans-serif; font-weight: 600; padding: 10px 24px; background: #C9A227; color: #000; border: none; border-radius: 4px; cursor: pointer; transition: background 0.2s; }
    .no-print button:hover { background: #b8921f; }
    @media print { 
        .no-print { display: none; } 
        body { margin: 0; padding: 40px; } 
    }
</style>
</head>
<body>

<div class="header">“POLICE WIRELESS MESSAGE”</div>

<div class="meta">
    <div style="text-align: right;"><span class="label">DTO:</span> <?= e($dto) ?></div>
    <div><span class="label">FROM:</span> <?= e($fromDept) ?></div>
    <div><span class="label">TO:</span> <?= e($toName) ?></div>
</div>

<div class="body-text">
<?= e("$ref / VOL. $vol X LEAVE SPO X  $leaveType X BE INFORMED THAT APPROVAL HAS BEEN GRACIOUSLY GRANTED FOR YOUR $daysInWords ({$signal['days_requested']}) WORKING DAYS $leaveType APPLICATION X W.E.F $wef X TO RESUME DUTY ON $resume X ABOVE FOR YOUR INFORMATION X PLEASE XXXX XXXXXXN XXXXXXX XXXX XXXXX XXXX XXXX XXXXX XXXXXXX XXXX XXXX XXXXXXX XXXXXXX XXXXXX XXXXXXXX .") ?>
</div>

<div class="footer">
    <div><?= e($fromDept) ?></div>
    <div>“M/IMMEDIATE”</div>
</div>

<div class="no-print">
    <button onclick="window.print()">Print this Signal</button>
</div>

</body>
</html>