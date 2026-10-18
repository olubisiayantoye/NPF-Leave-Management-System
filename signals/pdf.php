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
$leaveExpires = $signal['expected_resumption_date'] ? date('Y-m-d', strtotime($signal['expected_resumption_date'] . ' -1 day')) : null;

$pdf = new SimplePdf();
$width = 595; $height = 842; // A4 portrait
$marginX = 60;
$ops = '';
$y = $height - 70;

$ops .= SimplePdf::text($width / 2 - 80, $y, 'NIGERIA POLICE FORCE', 14, 'F2');
$ops .= SimplePdf::text($width / 2 - 90, $y - 16, 'FORCE HEADQUARTERS, ABUJA', 8);
$ops .= SimplePdf::text($width / 2 - 90, $y - 40, 'LEAVE APPROVAL SIGNAL', 13, 'F2');
$ops .= SimplePdf::text($width / 2 - 70, $y - 56, $signal['signal_reference'] . '  ·  Ref. ' . $signal['application_no'], 8);

$y -= 90;
$ops .= SimplePdf::line($marginX, $y, $width - $marginX, $y);
$y -= 24;

$fields = [
    'F-AP Number'                  => $signal['f_ap_number'],
    'Name'                         => $fullName,
    'Rank'                         => $signal['rank_name'],
    'Station'                      => $signal['department_name'],
    'Date Resumed Duty from Last Leave' => $signal['previous_leave_date'] ?: '—',
    'Date Due for Leave'           => $signal['proceeding_date'],
    'Leave Type'                   => $signal['leave_type_name'],
    'Number of Days Requested'     => $signal['days_requested'] . ' working days',
    'Authority for Accumulation'   => $signal['accumulation_authority'] ?: '—',
    'Date Proceeding on Leave'     => $signal['proceeding_date'],
    'Date Due to Resume Duty'      => $signal['expected_resumption_date'] ?: '—',
    'Home Town'                    => $signal['home_town'] ?: '—',
    'Address While on Leave'       => $signal['leave_address'] ?: '—',
    'Proceeding with Wife?'        => $signal['proceeding_with_wife'] ? 'Yes' : 'No',
    'Leave Recommended By'         => $signal['recommended_by_name'] ?: '—',
    'Recommendation Date'          => $signal['recommendation_date'] ?: '—',
    'Leave Approved/Not Approved'  => 'Approved',
    'Approval Date'                => date('Y-m-d', strtotime($signal['generated_at'])),
    'Leave Checked Up To'          => $signal['checked_up_to'] ?: '—',
    'Leave Commences'              => $signal['proceeding_date'],
    'Leave Expires'                => $leaveExpires ?: '—',
    'Resumes Duty'                 => $signal['expected_resumption_date'] ?: '—',
    'Checked By'                   => $signal['checked_by_name'] ?: '—',
    'Approving Authority'          => $signal['approving_authority'] ?: '—',
];

foreach ($fields as $label => $value) {
    $ops .= SimplePdf::text($marginX, $y, strtoupper($label), 8, 'F2');
    $ops .= SimplePdf::text($marginX + 220, $y, (string) $value, 10);
    $y -= 22;
}

if ($signal['approval_remarks']) {
    $y -= 6;
    $ops .= SimplePdf::text($marginX, $y, 'REMARKS', 8, 'F2');
    $y -= 16;
    foreach (explode("\n", wordwrap($signal['approval_remarks'], 90)) as $line) {
        $ops .= SimplePdf::text($marginX, $y, $line, 9);
        $y -= 14;
    }
}

$y -= 50;
$ops .= SimplePdf::line($marginX, $y, $marginX + 180, $y);
$ops .= SimplePdf::line($width - $marginX - 180, $y, $width - $marginX, $y);
$ops .= SimplePdf::text($marginX + 30, $y - 12, 'Approving Officer', 8);
$ops .= SimplePdf::text($width - $marginX - 165, $y - 12, "Officer's Acknowledgement", 8);

$pdf->addPage($ops, $width, $height);
$pdf->stream('signal_' . $signal['signal_reference'] . '.pdf');
