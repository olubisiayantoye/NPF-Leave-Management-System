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

audit_log($user['id'], 'SIGNAL_PDF_DOWNLOADED', 'signals', $signal['id'], "Daily Instruction {$signal['signal_reference']} downloaded as PDF");

$fullName = trim($signal['first_name'] . ' ' . ($signal['middle_name'] ? $signal['middle_name'] . ' ' : '') . $signal['surname']);

// Pronoun logic
$gender = strtolower($signal['gender'] ?? 'male');
$possessive = ($gender === 'female') ? 'her' : 'his';
$subjectPronoun = ($gender === 'female') ? 'She' : 'He';

// Helper function to convert numbers to words (supports 1-99)
function numberToWordsPdf($num) {
    $words = [0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'];
    if ($num <= 20) return $words[$num];
    if ($num < 100) {
        $tens = floor($num / 10) * 10;
        $units = $num % 10;
        return $words[$tens] . ($units > 0 ? '-' . $words[$units] : '');
    }
    return (string)$num;
}

$daysInWords = numberToWordsPdf((int)$signal['days_requested']);
$proceedingDateFormatted = date('jS F, Y', strtotime($signal['proceeding_date']));
$resumptionDateFormatted = date('jS F, Y', strtotime($signal['expected_resumption_date']));
$instructionDate = date('jS F, Y', strtotime($signal['generated_at'] ?? 'now'));

$pdf = new SimplePdf();
$width = 595; $height = 842; // A4 portrait
$marginX = 60;
$textWidth = $width - ($marginX * 2); // 475
$ops = '';
$y = $height - 60;

// Header
$ops .= SimplePdf::text($width / 2 - 85, $y, 'NIGERIA POLICE FORCE', 14, 'C'); // 'C' for center
$ops .= SimplePdf::text($width / 2 - 95, $y - 18, 'FORCE HEADQUARTERS, ABUJA', 10, 'C');
$ops .= SimplePdf::text($width / 2 - 80, $y - 40, 'DAILY INSTRUCTION', 12, 'C');

$y -= 70;
$ops .= SimplePdf::text($marginX, $y, 'Date: ' . $instructionDate, 11);
$y -= 18;
$ops .= SimplePdf::text($marginX, $y, $signal['signal_reference'], 11);
$y -= 25;

// Subject
$subjectText = 'SUB NO. 1 : ' . strtoupper($signal['leave_type_name']) . ' LEAVE:';
$ops .= SimplePdf::text($marginX, $y, $subjectText, 11, 'B'); // 'B' for bold if supported
$y -= 20;

// Body Paragraph 1 (Justified)
$body1 = "This order is to inform AP/No. {$signal['f_ap_number']} {$signal['rank_name']} {$fullName} that the Inspector-General of Police has graciously approved {$possessive} {$daysInWords} ({$signal['days_requested']}) working days {$signal['leave_type_name']} Leave Application W.E.F {$proceedingDateFormatted}. {$subjectPronoun} is to resume duty on {$resumptionDateFormatted} unfailingly.";

// Word wrap for PDF body (approx 75 chars per line for justified look)
$lines1 = explode("\n", wordwrap($body1, 78));
foreach ($lines1 as $line) {
    // Note: Change 'J' to 'justify' if your SimplePdf wrapper expects that string for justification
    $ops .= SimplePdf::text($marginX, $y, $line, 11, 'J'); 
    $y -= 18;
}

$y -= 10;

// Body Paragraph 2
$ops .= SimplePdf::text($marginX, $y, '2. Above is for your information, please.', 11, 'J');
$y -= 40;

// Sign-off (Right aligned)
$signOffX = $width - $marginX - 150;
$approvingName = strtoupper($signal['approving_authority'] ?? 'ASP DAUDA YUSUF OLATUNJI');
$ops .= SimplePdf::text($signOffX, $y, $approvingName, 11, 'B');
$y -= 18;
$ops .= SimplePdf::text($signOffX, $y, 'Staff Officer Administration (SOA)', 11);
$y -= 18;
$ops .= SimplePdf::text($signOffX, $y, 'IGP. Secretariat,', 11);
$y -= 18;
$ops .= SimplePdf::text($signOffX, $y, 'FHQ., Abuja', 11);

$pdf->addPage($ops, $width, $height);
$pdf->stream('daily_instruction_' . $signal['application_no'] . '.pdf');