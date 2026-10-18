<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/attachments.php';

require_login();
$user = current_user();
$canReviewSoa = current_user_has_permission('leave.review_soa');
$canDecide = current_user_has_permission('leave.approve');       // PSO I / PSO II
$canManage = current_user_has_permission('leave.apply') || current_user_has_permission('leave.cancel_curtail');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT la.*, lt.name AS leave_type_name,
            p.f_ap_number, p.first_name, p.middle_name, p.surname,
            r.name AS rank_name, d.name AS department_name,
            u.full_name AS submitted_by_name,
            su.full_name AS soa_reviewed_by_name,
            au.full_name AS assigned_reviewer_name
     FROM leave_applications la
     JOIN leave_types lt ON lt.id = la.leave_type_id
     JOIN personnel p ON p.id = la.personnel_id
     JOIN ranks r ON r.id = p.rank_id
     JOIN departments d ON d.id = p.department_id
     LEFT JOIN users u ON u.id = la.submitted_by
     LEFT JOIN users su ON su.id = la.soa_reviewed_by
     LEFT JOIN users au ON au.id = la.assigned_reviewer_id
     WHERE la.id = :id'
);
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    flash_set('error', 'That application could not be found.');
    redirect('leave/index.php');
}

// A PSO only acts on applications specifically forwarded to them (or
// unassigned ones, as a fallback) — never on another PSO's queue.
$canDecideThisOne = $canDecide && (
    !$app['assigned_reviewer_id']
    || (int) $app['assigned_reviewer_id'] === (int) $user['id']
    || current_user_has_permission('administration.manage_users') // super_admin oversight
);

$resumption = null;
if ($app['status'] === 'approved') {
    $rStmt = db()->prepare('SELECT * FROM leave_resumptions WHERE leave_application_id = :id');
    $rStmt->execute(['id' => $app['id']]);
    $resumption = $rStmt->fetch();
}

$cancellation = null;
if ($app['status'] === 'cancelled') {
    $cStmt = db()->prepare(
        'SELECT lc.*, u.full_name AS cancelled_by_name FROM leave_cancellations lc
         LEFT JOIN users u ON u.id = lc.cancelled_by WHERE lc.leave_application_id = :id ORDER BY lc.id DESC LIMIT 1'
    );
    $cStmt->execute(['id' => $app['id']]);
    $cancellation = $cStmt->fetch();
}

$curtailment = null;
if ($app['status'] === 'curtailed') {
    $ctStmt = db()->prepare(
        'SELECT lc.*, u.full_name AS curtailed_by_name FROM leave_curtailments lc
         LEFT JOIN users u ON u.id = lc.curtailed_by WHERE lc.leave_application_id = :id ORDER BY lc.id DESC LIMIT 1'
    );
    $ctStmt->execute(['id' => $app['id']]);
    $curtailment = $ctStmt->fetch();
}

$fullName = trim($app['first_name'] . ' ' . ($app['middle_name'] ? $app['middle_name'] . ' ' : '') . $app['surname']);

$statusLabels = [
    'draft' => 'Draft', 'pending_soa' => 'Pending Review', 'pending_pso' => 'Pending Decision',
    'approved' => 'Approved', 'disapproved' => 'Disapproved', 'cancelled' => 'Cancelled',
    'curtailed' => 'Curtailed', 'completed' => 'Completed',
];
$statusBadgeClass = [
    'pending_soa' => 'badge-pending', 'pending_pso' => 'badge-pending',
    'approved' => 'badge-approved', 'disapproved' => 'badge-disapproved',
    'draft' => 'text-bg-secondary', 'cancelled' => 'text-bg-secondary', 'curtailed' => 'text-bg-secondary',
    'completed' => 'text-bg-secondary',
];

$pageTitle = 'Application ' . $app['application_no'];
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <div class="font-monospace text-muted small mb-1"><?= e($app['application_no']) ?></div>
        <h2 class="h5 mb-0"><?= e($app['leave_type_name']) ?> — <?= e($fullName) ?></h2>
    </div>
    <span class="badge badge-status <?= $statusBadgeClass[$app['status']] ?? 'text-bg-secondary' ?> fs-6">
        <?= e($statusLabels[$app['status']] ?? $app['status']) ?>
    </span>
</div>

<div class="card border-0 shadow-sm mb-3">
<div class="card-body">
    <h3 class="h6 mb-3">Personnel</h3>
    <div class="row small mb-4">
        <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">F-AP No.</span><span class="font-monospace"><?= e($app['f_ap_number']) ?></span></div>
        <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Rank</span><?= e($app['rank_name']) ?></div>
        <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Station</span><?= e($app['department_name']) ?></div>
        <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Submitted By</span><?= e($app['submitted_by_name'] ?? '—') ?></div>
    </div>

    <h3 class="h6 mb-3">Leave Details</h3>
    <div class="row small mb-1">
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Days Requested</span><?= (int)$app['days_requested'] ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Proceeding</span><?= e($app['proceeding_date']) ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Due to Resume</span><?= e($app['expected_resumption_date'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Date Resumed Duty from Last Leave</span><?= e($app['previous_leave_date'] ?? 'None on record') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Home Town</span><?= e($app['home_town'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Address While on Leave</span><?= e($app['leave_address'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Proceeding with Wife</span><?= $app['proceeding_with_wife'] ? 'Yes' : 'No' ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Accumulation Authority</span><?= e($app['accumulation_authority'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Checked By</span><?= e($app['checked_by_name'] ?? '—') ?></div>
        <div class="col-md-3 mb-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Leave Checked Up To</span><?= e($app['checked_up_to'] ?? '—') ?></div>
    </div>
    <?php if ($app['remarks']): ?>
    <div class="mb-1"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Remarks (I-Admin)</span><?= nl2br(e($app['remarks'])) ?></div>
    <?php endif; ?>
</div>
</div>

<?php if ($app['soa_reviewed_by']): ?>
<div class="card border-0 shadow-sm mb-3">
<div class="card-body">
    <h3 class="h6 mb-3">Administration Officer Review</h3>
    <div class="row small mb-2">
        <div class="col-md-4"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Reviewed By</span><?= e($app['soa_reviewed_by_name']) ?></div>
        <div class="col-md-4"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Recommendation</span>
            <span class="badge <?= $app['recommendation_decision'] === 'approve' ? 'text-bg-success' : 'text-bg-danger' ?>">
                <?= $app['recommendation_decision'] === 'approve' ? 'Recommend Approval' : 'Recommend Disapproval' ?>
            </span>
        </div>
        <div class="col-md-4"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Forwarded To</span><?= e($app['assigned_reviewer_name'] ?? '—') ?></div>
    </div>
    <?php if ($app['soa_remarks']): ?>
    <div><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Remarks / Minutes</span><?= nl2br(e($app['soa_remarks'])) ?></div>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>

<?php
$canSeeAttachments = can_access_attachment($app);
if ($canSeeAttachments):
    $attStmt = db()->prepare(
        'SELECT id, original_filename, mime_type, file_size, uploaded_at
         FROM leave_attachments WHERE leave_application_id = :id ORDER BY uploaded_at DESC'
    );
    $attStmt->execute(['id' => $app['id']]);
    $attachments = $attStmt->fetchAll();
    $canUpload = in_array($app['status'], ['draft', 'pending_soa', 'pending_pso', 'approved'], true);
?>
<div class="card border-0 shadow-sm mb-3">
<div class="card-body">
    <h3 class="h6 mb-3">Supporting Documents</h3>
    <?php if (!$attachments): ?>
        <p class="text-muted small mb-3">None attached yet.</p>
    <?php else: ?>
        <ul class="list-unstyled small mb-3">
        <?php foreach ($attachments as $att): ?>
            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <a href="<?= base_url('leave/attachment_download.php?id=' . $att['id']) ?>" target="_blank">
                    <i class="bi bi-paperclip"></i> <?= e($att['original_filename']) ?>
                </a>
                <span class="d-flex align-items-center gap-2">
                    <span class="text-muted"><?= format_file_size((int) $att['file_size']) ?> · <?= e($att['uploaded_at']) ?></span>
                    <?php if ($canUpload): ?>
                    <form method="post" action="<?= base_url('leave/attachment_delete.php') ?>" onsubmit="return confirm('Remove this attachment?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $att['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                    </form>
                    <?php endif; ?>
                </span>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($canUpload): ?>
    <form method="post" action="<?= base_url('leave/attachment_upload.php') ?>" enctype="multipart/form-data" class="d-flex gap-2 align-items-end flex-wrap">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $app['id'] ?>">
        <div class="flex-grow-1">
            <label class="form-label small text-muted text-uppercase mb-1">Add a Document</label>
            <input type="file" name="attachment" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
        </div>
        <button type="submit" class="btn btn-sm btn-outline-dark">Upload</button>
    </form>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>

<?php if ($app['status'] === 'approved'): ?>
<div class="alert alert-success d-flex justify-content-between align-items-center">
    <span>Approved <?= $app['approved_at'] ? 'on ' . e($app['approved_at']) : '' ?>. The Leave Approval Signal is generated automatically.</span>
    <a href="<?= base_url('signals/view.php?leave_application_id=' . $app['id']) ?>" class="btn btn-sm btn-npf-gold">View Signal</a>
</div>

<?php if ($resumption && $resumption['actual_resumption_date']): ?>
<div class="alert alert-secondary">
    Resumed duty on <?= e($resumption['actual_resumption_date']) ?>
    (recorded <?= e($resumption['recorded_at']) ?>).
</div>
<?php elseif (current_user_has_permission('leave.apply')): ?>
<div class="card border-0 shadow-sm mb-3">
<div class="card-body">
    <h3 class="h6 mb-3">Record Resumption</h3>
    <p class="text-muted small mb-3">Expected: <?= e($app['expected_resumption_date'] ?? '—') ?></p>
    <form method="post" action="<?= base_url('leave/resume.php') ?>" class="d-flex gap-2 align-items-end flex-wrap">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $app['id'] ?>">
        <div>
            <label class="form-label small text-muted text-uppercase">Actual Resumption Date</label>
            <input type="date" name="actual_resumption_date" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-npf-gold">Record Resumption</button>
    </form>
</div>
</div>
<?php endif; ?>

<?php elseif ($app['status'] === 'disapproved'): ?>
<div class="alert alert-danger">
    Disapproved<?= $app['disapproved_at'] ? ' on ' . e($app['disapproved_at']) : '' ?>.
    <?php if ($app['approval_remarks']): ?><br><strong>Remarks:</strong> <?= nl2br(e($app['approval_remarks'])) ?><?php endif; ?>
</div>
<?php elseif ($app['status'] === 'pending_soa' && !$canReviewSoa): ?>
<div class="alert alert-warning">Awaiting Administration Officer review.</div>
<?php elseif ($app['status'] === 'pending_pso' && !$canDecideThisOne): ?>
<div class="alert alert-warning">Forwarded to <?= e($app['assigned_reviewer_name'] ?? 'a PSO') ?> for final decision.</div>
<?php elseif ($app['status'] === 'cancelled' && $cancellation): ?>
<div class="alert alert-secondary">
    Cancelled by <?= e($cancellation['cancelled_by_name'] ?? 'system') ?> on <?= e($cancellation['cancelled_at']) ?>.
    <br><strong>Reason:</strong> <?= nl2br(e($cancellation['reason'])) ?>
</div>
<?php elseif ($app['status'] === 'curtailed' && $curtailment): ?>
<div class="alert alert-secondary">
    Curtailed by <?= e($curtailment['curtailed_by_name'] ?? 'system') ?> on <?= e($curtailment['curtailed_at']) ?> —
    resumed <?= e($curtailment['new_resumption_date']) ?> instead of <?= e($app['expected_resumption_date']) ?>.
    <br><strong>Reason:</strong> <?= nl2br(e($curtailment['reason'])) ?>
</div>
<?php endif; ?>

<?php if (in_array($app['status'], ['pending_soa', 'pending_pso', 'approved'], true) && $canManage): ?>
<div class="d-flex gap-2 flex-wrap mb-3">
    <?php if ($app['status'] === 'approved' && (!$resumption || !$resumption['actual_resumption_date'])): ?>
    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="collapse" data-bs-target="#curtail-form">
        <i class="bi bi-scissors"></i> Curtail Leave
    </button>
    <?php endif; ?>
    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#cancel-form">
        <i class="bi bi-x-circle"></i> Cancel Application
    </button>
</div>

<div class="collapse mb-3" id="cancel-form">
<div class="card border-0 shadow-sm"><div class="card-body">
    <h3 class="h6 mb-3">Cancel this application</h3>
    <form method="post" action="<?= base_url('leave/cancel.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $app['id'] ?>">
        <div class="mb-3">
            <label class="form-label small text-muted text-uppercase">Reason *</label>
            <textarea name="reason" class="form-control" rows="2" required></textarea>
        </div>
        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Cancel this application? This cannot be undone.');">Confirm Cancellation</button>
    </form>
</div></div>
</div>

<?php if ($app['status'] === 'approved'): ?>
<div class="collapse mb-3" id="curtail-form">
<div class="card border-0 shadow-sm"><div class="card-body">
    <h3 class="h6 mb-3">Curtail this leave</h3>
    <p class="text-muted small">Originally due to resume <?= e($app['expected_resumption_date'] ?? '—') ?>.</p>
    <form method="post" action="<?= base_url('leave/curtail.php') ?>" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $app['id'] ?>">
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">New Resumption Date *</label>
            <input type="date" name="new_resumption_date" class="form-control" required>
        </div>
        <div class="col-md-8">
            <label class="form-label small text-muted text-uppercase">Reason *</label>
            <input type="text" name="reason" class="form-control" required>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-outline-warning">Confirm Curtailment</button>
        </div>
    </form>
</div></div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php if ($app['status'] === 'pending_soa' && $canReviewSoa): ?>
<div class="alert alert-info d-flex justify-content-between align-items-center">
    <span>This application is awaiting your review as Administration Officer.</span>
    <a href="<?= base_url('approvals/soa_review.php?id=' . $app['id']) ?>" class="btn btn-sm btn-npf-gold">Review &amp; Forward</a>
</div>
<?php endif; ?>

<?php if ($app['status'] === 'pending_pso' && $canDecideThisOne): ?>
<div class="card border-0 shadow-sm mb-3">
<div class="card-body">
    <h3 class="h6 mb-3">Final Decision</h3>
    <form method="post" id="review-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $app['id'] ?>">
        <div class="mb-3">
            <label class="form-label small text-muted text-uppercase">Remarks <span class="text-muted normal-case">(required to disapprove)</span></label>
            <textarea name="remarks" class="form-control" rows="2"></textarea>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" formaction="<?= base_url('leave/approve.php') ?>" class="btn btn-npf-gold">Approve</button>
            <button type="submit" formaction="<?= base_url('leave/disapprove.php') ?>" class="btn btn-outline-danger">Disapprove</button>
        </div>
    </form>
</div>
</div>
<?php endif; ?>

<a href="<?= base_url('leave/index.php') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to My Application</a>

<?php require __DIR__ . '/../includes/footer.php'; ?>
