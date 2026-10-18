<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('signals.view');
$user = current_user();

$leaveApplicationId = (int) ($_GET['leave_application_id'] ?? 0);

$stmt = db()->prepare(
    'SELECT s.*, la.application_no, la.days_requested, la.proceeding_date, la.expected_resumption_date,
            la.previous_leave_date, la.accumulation_authority, la.recommended_by_name, la.recommendation_date,
            la.checked_by_name, la.checked_up_to,
            la.home_town, la.leave_address, la.proceeding_with_wife, la.approval_remarks,
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
    flash_set('error', 'No signal has been generated for that application yet — it may not be approved.');
    redirect('leave/index.php');
}

$fullName = trim($signal['first_name'] . ' ' . ($signal['middle_name'] ? $signal['middle_name'] . ' ' : '') . $signal['surname']);
$leaveExpires = $signal['expected_resumption_date'] ? date('Y-m-d', strtotime($signal['expected_resumption_date'] . ' -1 day')) : null;

$pageTitle = 'Leave Approval Signal';
require __DIR__ . '/../includes/header.php';
?>


<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Generated automatically on approval — this record can never be edited.</p>
      
    <div class="d-flex gap-2">
        <a href="<?= base_url('signals/print.php?leave_application_id=' . $leaveApplicationId) ?>" target="_blank" class="btn btn-npf-gold btn-sm">
            <i class="bi bi-printer"></i> Print Signal
        </a>
        <a href="<?= base_url('signals/pdf.php?leave_application_id=' . $leaveApplicationId) ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
        </a>
        <a href="<?= base_url('leave/view.php?id=' . $leaveApplicationId) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Application</a>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
     <div class="d-flex gap-2">
       <a href="<?= base_url('signals/print_telegraphic_signal.php?leave_application_id=' . $leaveApplicationId) ?>" target="_blank" class="btn btn-npf-gold btn-sm">
            <i class="bi bi-printer"></i> Print Telegraphic-Signal
        </a>
        <a href="<?= base_url('signals/pdf_telegraphic_signal.php?leave_application_id=' . $leaveApplicationId) ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-file-earmark-pdf"></i> Download Telegraphic-Signal-PDF
        </a>
         <a href="<?= base_url('signals/print_daily_instruction.php?leave_application_id=' . $leaveApplicationId) ?>" target="_blank" class="btn btn-npf-gold btn-sm">
            <i class="bi bi-printer"></i> Print Daily Instruction
        </a>
        <a href="<?= base_url('signals/pdf_daily_instruction.php?leave_application_id=' . $leaveApplicationId) ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-file-earmark-pdf"></i> Download Daily Instruction-PDF
        </a> 
    </div>     
 </div> 

<div class="card border-0 shadow-sm">
<div class="card-body p-4">
    <div class="text-center mb-4">
        <div class="fw-bold" style="letter-spacing:0.08em;">NIGERIA POLICE FORCE</div>
        <div class="text-muted small text-uppercase">Force Headquarters, Abuja</div>
        <div class="fw-semibold mt-2" style="font-family:'Spectral',serif; font-size:1.1rem;">LEAVE APPROVAL SIGNAL</div>
        <div class="font-monospace small text-muted"><?= e($signal['signal_reference']) ?></div>
    </div>

    <div class="row small mb-4">
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">F-AP No.</span><span class="font-monospace"><?= e($signal['f_ap_number']) ?></span></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Name</span><?= e($fullName) ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Rank</span><?= e($signal['rank_name']) ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Station</span><?= e($signal['department_name']) ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Leave Type</span><?= e($signal['leave_type_name']) ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Number of Days</span><?= (int)$signal['days_requested'] ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Date Resumed Duty from Last Leave</span><?= e($signal['previous_leave_date'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Authority for Accumulation</span><?= e($signal['accumulation_authority'] ?? '—') ?></div>
    </div>

    <hr>
    <div class="row small mb-4">
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Leave Commences</span><?= e($signal['proceeding_date']) ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Leave Expires</span><?= e($leaveExpires ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Resumes Duty</span><?= e($signal['expected_resumption_date'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Proceeding with Wife</span><?= $signal['proceeding_with_wife'] ? 'Yes' : 'No' ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Home Town</span><?= e($signal['home_town'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Address While on Leave</span><?= e($signal['leave_address'] ?? '—') ?></div>
    </div>

    <hr>
    <div class="row small mb-4">
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Leave Recommended By</span><?= e($signal['recommended_by_name'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Recommendation Date</span><?= e($signal['recommendation_date'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Approving Authority</span><?= e($signal['approving_authority'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Approval Date</span><?= e($signal['generated_at']) ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Checked By</span><?= e($signal['checked_by_name'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Leave Checked Up To</span><?= e($signal['checked_up_to'] ?? '—') ?></div>
    </div>
    <?php if ($signal['approval_remarks']): ?>
    <div><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Remarks</span><?= nl2br(e($signal['approval_remarks'])) ?></div>
    <?php endif; ?>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
