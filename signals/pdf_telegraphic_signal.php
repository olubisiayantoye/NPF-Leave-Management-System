<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pdf_writer.php';

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

audit_log($user['id'], 'SIGNAL_PDF_DOWNLOADED', 'signals', $signal['id'], "Signal {$signal['signal_reference']} downloaded as PDF");

$fullName = trim($signal['first_name'] . ' ' . ($signal['middle_name'] ? $signal['middle_name'] . ' ' : '') . $signal['surname']);

// Helper function to convert numbers to words
function numberToWords($num) {
    if (class_exists('NumberFormatter')) {
        $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        return strtoupper($f->format($num));
    }
    $ones = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
    $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];
    if ($num < 20) return $ones[$num];
    if ($num < 100) return $tens[(int)($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
    if ($num < 1000) return $ones[(int)($num / 100)] . ' HUNDRED' . ($num % 100 ? ' AND ' . numberToWords($num % 100) : '');
    return (string)$num;
}

$pdf = new SimplePdf();
$width = 595; $height = 842; // A4 portrait
$marginX = 50;
$ops = '';
$y = $height - 60;

// Header
$ops .= SimplePdf::text($width / 2 - 90, $y, '“POLICE WIRELESS MESSAGE”', 14, 'F2');
$y -= 35;

// Meta Details
$dto = date('dHi / m / Y', strtotime($signal['generated_at']));
$fromDept = strtoupper($signal['department_name'] ?: 'INGENPOL SEC ABUJA');
$toName = strtoupper($signal['rank_name'] . '  ' . $fullName);

$ops .= SimplePdf::text($marginX, $y, 'DTO:  ' . $dto, 10);
$y -= 18;
$ops .= SimplePdf::text($marginX, $y, 'FROM: ' . $fromDept, 10);
$y -= 18;
$ops .= SimplePdf::text($marginX, $y, 'TO:   ' . $toName, 10);
$y -= 30;

// Body Text Construction
$daysInWords = numberToWords($signal['days_requested']);
$leaveType = strtoupper($signal['leave_type_name']);
$wef = date('d/m/Y', strtotime($signal['proceeding_date']));
$resume = date('d/m/Y', strtotime($signal['expected_resumption_date']));
$ref = strtoupper($signal['signal_reference']);
$vol = $signal['application_no'] ?: '1/1';

$bodyText = "$ref / VOL. $vol X $leaveType X BE INFORMED THAT APPROVAL HAS BEEN GRACIOUSLY GRANTED FOR YOUR $daysInWords ({$signal['days_requested']}) WORKING DAYS $leaveType APPLICATION X W.E.F $wef X TO RESUME DUTY ON $resume X ABOVE FOR YOUR INFORMATION X PLEASE XXXX XXXXXXN XXXXXXX XXXX XXXXX XXXX XXXX XXXXX XXXXXXX XXXX XXXX XXXXXXX XXXXXXX XXXXXX XXXXXXXX .";

// Word wrap for PDF (approx 75 chars per line to simulate block alignment)
$wrapped = wordwrap($bodyText, 75, "\n");
$lines = explode("\n", $wrapped);

foreach ($lines as $line) {
    // Note: If your SimplePdf class supports a 'justify' parameter, use it here instead.
    // Example: SimplePdf::text($marginX, $y, $line, 10, '', true);
    $ops .= SimplePdf::text($marginX, $y, $line, 10);
    $y -= 16;
}

// Footer
$y -= 40;
$ops .= SimplePdf::text($marginX, $y, $fromDept, 10, 'F2');
$ops .= SimplePdf::text($width - $marginX - 110, $y, '“M/IMMEDIATE”', 10, 'F2');

$pdf->addPage($ops, $width, $height);
$pdf->stream('signal_' . $signal['signal_reference'] . '.pdf');