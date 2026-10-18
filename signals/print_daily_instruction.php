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
            p.f_ap_number, p.first_name, p.middle_name, p.surname, p.gender,
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
audit_log($user['id'], 'SIGNAL_PRINTED', 'signals', $signal['id'], "Daily Instruction {$signal['signal_reference']} opened for print");

$fullName = trim($signal['first_name'] . ' ' . ($signal['middle_name'] ? $signal['middle_name'] . ' ' : '') . $signal['surname']);

// Pronoun logic based on gender
$gender = strtolower($signal['gender'] ?? 'male');
$possessive = ($gender === 'female') ? 'her' : 'his';
$subjectPronoun = ($gender === 'female') ? 'She' : 'He';

// Helper function to convert numbers to words (supports 1-99)
function numberToWords($num) {
    $words = [0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'];
    if ($num <= 20) return $words[$num];
    if ($num < 100) {
        $tens = floor($num / 10) * 10;
        $units = $num % 10;
        return $words[$tens] . ($units > 0 ? '-' . $words[$units] : '');
    }
    return (string)$num;
}

$daysInWords = numberToWords((int)$signal['days_requested']);
$proceedingDateFormatted = date('jS F, Y', strtotime($signal['proceeding_date']));
$resumptionDateFormatted = date('jS F, Y', strtotime($signal['expected_resumption_date']));
$instructionDate = date('jS F, Y', strtotime($signal['generated_at'] ?? 'now'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Daily Instruction - <?= e($signal['application_no']) ?></title>
<style>
    body { 
        font-family: 'Times New Roman', Times, serif; 
        color: #000; 
        background: #fff; 
        max-width: 700px; 
        margin: 40px auto; 
        padding: 40px; 
        line-height: 1.6; 
        font-size: 12pt;
    }
    .logo { text-align: center; margin-bottom: 15px; }
    .logo img { height: 90px; } /* Adjust path/size as needed */
    .header { text-align: center; font-weight: bold; margin-bottom: 25px; }
    .header .agency { font-size: 1.3rem; letter-spacing: 1px; }
    .header .hq { font-size: 1rem; margin-top: 5px; }
    .header .title { font-size: 1.2rem; margin-top: 15px; text-decoration: underline; }
    .meta { margin-bottom: 20px; }
    .meta .date { margin-bottom: 10px; }
    .meta .ref { margin-bottom: 15px; font-family: 'Courier New', monospace; }
    .subject { font-weight: bold; margin-bottom: 15px; }
    .body-text { 
        text-align: justify; 
        margin-bottom: 15px; 
        text-indent: 40px; 
    }
    .body-text.no-indent { text-indent: 0; }
    .sign-off { 
        margin-top: 50px; 
        text-align: center; 
        padding-right: 20px; 
        line-height: 1.4;
    }
    .sign-off .name { font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
    .no-print { text-align: center; margin-top: 40px; }
    .no-print button { 
        padding: 12px 24px; 
        background: #003366; 
        color: white; 
        border: none; 
        border-radius: 4px; 
        cursor: pointer; 
        font-size: 1rem; 
        font-family: sans-serif;
    }
    .no-print button:hover { background: #002244; }
    @media print {
        .no-print { display: none; }
        body { margin: 0; padding: 40px 50px; }
    }
</style>
</head>
<body>

<div class="logo">
    <!-- Replace src with your actual logo path -->
    <img src="<?= base_url('/assets/images/npf_logo.webp') ?>" alt="Nigeria Police Force Logo" onerror="this.style.display='none'">
</div>

<div class="header">
    <div class="agency">NIGERIA POLICE FORCE</div>
    <div class="hq">Force Headquarters, Abuja</div>
    <div class="title">DAILY INSTRUCTION</div>
</div>

<div class="meta">
    <div class="date" style="text-align: right;">Date: <?= e($instructionDate) ?></div>
    <div class="ref"><strong><?= e($signal['signal_reference']) ?></strong></div>
</div>

<!-- div class="subject">
    SUB NO. 1 : <?= strtoupper(e($signal['leave_type_name'])) ?> LEAVE: 
</div -->

<div class="body-text no-indent">
   <strong>SUB NO. 1 : <?= strtoupper(e($signal['leave_type_name'])) ?> LEAVE:</strong> This order is to inform <strong>AP/No. <?= e($signal['f_ap_number']) ?> <?= e($signal['rank_name']) ?> <?= e($fullName) ?></strong> that the Inspector-General of Police has graciously approved <?= $possessive ?> <?= $daysInWords ?> (<?= (int)$signal['days_requested'] ?>) working days <?= e($signal['leave_type_name']) ?> Leave Application <strong>W.E.F <?= $proceedingDateFormatted ?></strong>. <?= $subjectPronoun ?> is to resume duty on <strong><?= $resumptionDateFormatted ?></strong> unfailingly.
</div>

<div class="body-text no-indent">
    2. Above is for your information, please.
</div>

<div class="sign-off">
    <div class="name"><strong><?= e($signal['approving_authority'] ?? 'ASP DAUDA YUSUF OLATUNJI') ?></strong></div>
    <div>Staff Officer Administration (SOA)</div>
    <div>IGP. Secretariat,</div>
    <div>FHQ., Abuja</div>
</div>

<div class="no-print">
    <button onclick="window.print()">Print Daily Instruction</button>
</div>

</body>
</html>