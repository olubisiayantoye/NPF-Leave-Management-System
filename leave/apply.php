<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/leave_calc.php';
require_once __DIR__ . '/../includes/attachments.php';

require_permission('leave.apply');
$user = current_user();

$personnelId = (int) ($_GET['personnel_id'] ?? $_POST['personnel_id'] ?? 0);
if (!$personnelId) {
    flash_set('error', 'Select a personnel record before applying for leave.');
    redirect('personnel/search.php');
}

$pStmt = db()->prepare(
    'SELECT p.*, r.name AS rank_name, d.name AS department_name
     FROM personnel p
     JOIN ranks r ON r.id = p.rank_id
     JOIN departments d ON d.id = p.department_id
     WHERE p.id = :id'
);
$pStmt->execute(['id' => $personnelId]);
$person = $pStmt->fetch();

if (!$person) {
    flash_set('error', 'That personnel record could not be found.');
    redirect('personnel/search.php');
}
$fullName = trim($person['first_name'] . ' ' . ($person['middle_name'] ? $person['middle_name'] . ' ' : '') . $person['surname']);

$leaveTypeId = (int) ($_GET['leave_type_id'] ?? $_POST['leave_type_id'] ?? 0);

// -------------------------------------------------------------------
// Step 1: no leave type chosen yet — show the type-selection grid
// -------------------------------------------------------------------
if (!$leaveTypeId && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $leaveTypes = db()->query('SELECT id, name, description FROM leave_types WHERE is_active = 1 ORDER BY name')->fetchAll();

    $pageTitle = 'Select Leave Type';
    require __DIR__ . '/../includes/header.php';
    ?>
    <p class="text-muted mb-1">Applying on behalf of</p>
    <h2 class="h5 mb-4"><?= e($fullName) ?> <span class="text-muted fw-normal">— <?= e($person['rank_name']) ?>, <?= e($person['department_name']) ?></span></h2>

    <div class="row g-3">
        <?php foreach ($leaveTypes as $lt): ?>
        <div class="col-md-4 col-lg-3">
            <a href="<?= base_url('leave/apply.php?personnel_id=' . $personnelId . '&leave_type_id=' . $lt['id']) ?>"
               class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
                <div class="card-body">
                    <div class="fw-semibold mb-1"><?= e($lt['name']) ?></div>
                    <div class="small text-muted"><?= e($lt['description'] ?? '') ?></div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// -------------------------------------------------------------------
// Step 2: leave type chosen — load type, prior leave info, entitlement
// -------------------------------------------------------------------
$ltStmt = db()->prepare('SELECT * FROM leave_types WHERE id = :id AND is_active = 1');
$ltStmt->execute(['id' => $leaveTypeId]);
$leaveType = $ltStmt->fetch();
if (!$leaveType) {
    flash_set('error', 'That leave type is not available.');
    redirect('leave/apply.php?personnel_id=' . $personnelId);
}

$currentYear = date('Y');
$entStmt = db()->prepare(
    'SELECT entitled_days, used_days FROM leave_entitlements WHERE personnel_id = :p AND leave_type_id = :lt AND year = :y'
);
$entStmt->execute(['p' => $personnelId, 'lt' => $leaveTypeId, 'y' => $currentYear]);
$entitlement = $entStmt->fetch();
$balance = $entitlement ? ($entitlement['entitled_days'] - $entitlement['used_days']) : null;

// Auto-populate "Date Resumed Duty from Last Leave" — per the actual NPF
// paper form, this is the officer's ACTUAL resumption date from their
// most recent completed leave (any type), not a proceeding date and not
// limited to this leave type.
$prevStmt = db()->prepare(
    "SELECT lr.actual_resumption_date
     FROM leave_resumptions lr
     JOIN leave_applications la ON la.id = lr.leave_application_id
     WHERE la.personnel_id = :p AND lr.actual_resumption_date IS NOT NULL
     ORDER BY lr.actual_resumption_date DESC LIMIT 1"
);
$prevStmt->execute(['p' => $personnelId]);
$previousLeaveDate = $prevStmt->fetchColumn() ?: null;

$errors = [];
$old = $_POST;

// -------------------------------------------------------------------
// Handle submission (Save Draft or Submit Application)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $daysRequested = (int) ($_POST['days_requested'] ?? 0);
    $proceedingDate = $_POST['proceeding_date'] ?? '';
    $accumulationAuthority = trim($_POST['accumulation_authority'] ?? '') ?: null;
    $checkedByName = trim($_POST['checked_by_name'] ?? '') ?: null;
    $checkedUpTo = $_POST['checked_up_to'] ?? '';
    $checkedUpTo = $checkedUpTo !== '' ? $checkedUpTo : null;
    $homeTown = trim($_POST['home_town'] ?? '') ?: null;
    $leaveAddress = trim($_POST['leave_address'] ?? '') ?: null;
    $proceedingWithWife = isset($_POST['proceeding_with_wife']) ? 1 : 0;
    $remarks = trim($_POST['remarks'] ?? '') ?: null;
    $action = $_POST['action'] ?? 'submit'; // 'draft' or 'submit'

    if ($daysRequested < 1) $errors[] = 'Number of days requested must be at least 1.';
    if (!$proceedingDate)   $errors[] = 'Date proceeding on leave is required.';

    if (!$errors) {
        $expectedResumption = calculate_resumption_date($proceedingDate, $daysRequested);
        $status = $action === 'draft' ? 'draft' : 'pending_soa';

        $stmt = db()->prepare(
            'INSERT INTO leave_applications
                (application_no, personnel_id, leave_type_id, previous_leave_date, days_requested,
                 accumulation_authority, checked_by_name, checked_up_to,
                 proceeding_date, expected_resumption_date,
                 home_town, leave_address, proceeding_with_wife, remarks, status,
                 submitted_by, submitted_at)
             VALUES
                (:app_no, :personnel_id, :leave_type_id, :previous_leave_date, :days_requested,
                 :accumulation_authority, :checked_by_name, :checked_up_to,
                 :proceeding_date, :expected_resumption_date,
                 :home_town, :leave_address, :proceeding_with_wife, :remarks, :status,
                 :submitted_by, :submitted_at)'
        );
        $stmt->execute([
            'app_no'                 => next_application_no(),
            'personnel_id'           => $personnelId,
            'leave_type_id'          => $leaveTypeId,
            'previous_leave_date'    => $previousLeaveDate,
            'days_requested'         => $daysRequested,
            'accumulation_authority' => $accumulationAuthority,
            'checked_by_name'        => $checkedByName,
            'checked_up_to'          => $checkedUpTo,
            'proceeding_date'        => $proceedingDate,
            'expected_resumption_date' => $expectedResumption,
            'home_town'              => $homeTown,
            'leave_address'          => $leaveAddress,
            'proceeding_with_wife'   => $proceedingWithWife,
            'remarks'                => $remarks,
            'status'                 => $status,
            'submitted_by'           => $user['id'],
            'submitted_at'           => $status === 'pending_soa' ? date('Y-m-d H:i:s') : null,
        ]);

        $newId = (int) db()->lastInsertId();
        $action_label = $status === 'pending_soa' ? 'LEAVE_APPLICATION_SUBMITTED' : 'LEAVE_APPLICATION_CREATED';
        audit_log($user['id'], $action_label, 'leave_applications', $newId,
            "{$leaveType['name']} for {$fullName} ({$person['f_ap_number']})");

        if (!empty($_FILES['attachment']['tmp_name'])) {
            try {
                store_attachment($_FILES['attachment'], $newId, $user['id']);
                audit_log($user['id'], 'ATTACHMENT_UPLOADED', 'leave_applications', $newId, basename($_FILES['attachment']['name']));
            } catch (RuntimeException $e) {
                // The application itself was saved successfully — don't lose that over an attachment problem.
                flash_set('warning', 'Application saved, but the attachment could not be uploaded: ' . $e->getMessage());
            }
        }

        flash_set('success', $status === 'pending_soa'
            ? 'Application submitted and is now pending Administration Officer review.'
            : 'Draft saved. You can submit it later from My Application.');

        if ($status === 'pending_soa') {
            notify_role('administration_officer', "New leave application pending your review: {$leaveType['name']} for {$fullName} ({$person['f_ap_number']}).");
        }

        redirect('leave/view.php?id=' . $newId);
    }
}

$pageTitle = 'Apply for Leave — ' . $leaveType['name'];
require __DIR__ . '/../includes/header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row small">
            <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">F-AP No.</span><span class="font-monospace"><?= e($person['f_ap_number']) ?></span></div>
            <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Name</span><?= e($fullName) ?></div>
            <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Rank</span><?= e($person['rank_name']) ?></div>
            <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Station / Department</span><?= e($person['department_name']) ?></div>
        </div>
    </div>
</div>

<?php if ($balance !== null): ?>
<div class="alert <?= $balance > 0 ? 'alert-info' : 'alert-warning' ?> py-2 small">
    <?= e($leaveType['name']) ?> balance for <?= $currentYear ?>:
    <strong><?= $balance ?> day<?= $balance === 1 ? '' : 's' ?></strong>
    (<?= (int)$entitlement['entitled_days'] ?> entitled, <?= (int)$entitlement['used_days'] ?> used).
</div>
<?php else: ?>
<div class="alert alert-secondary py-2 small">No <?= e($leaveType['name']) ?> entitlement configured for <?= $currentYear ?> — the application can still be submitted for Administration Officer review.</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
<div class="card-body p-4">
<form method="post" action="<?= base_url('leave/apply.php') ?>" novalidate id="leave-form" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="personnel_id" value="<?= $personnelId ?>">
    <input type="hidden" name="leave_type_id" value="<?= $leaveTypeId ?>">

    <h2 class="h6 mb-3">Application for Leave — <?= e($leaveType['name']) ?></h2>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Date Resumed Duty from Last Leave</label>
            <input type="text" class="form-control" disabled value="<?= e($previousLeaveDate ?? 'None on record') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Number of Days Requested *</label>
            <input type="number" min="1" name="days_requested" id="days_requested" class="form-control" required
                   value="<?= e($old['days_requested'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Authority for Accumulation</label>
            <input type="text" name="accumulation_authority" class="form-control" value="<?= e($old['accumulation_authority'] ?? '') ?>">
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label small text-muted text-uppercase">Checked By <span class="text-muted normal-case">(optional)</span></label>
            <input type="text" name="checked_by_name" class="form-control" placeholder="e.g. Inspr. Darlington Mike"
                   value="<?= e($old['checked_by_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small text-muted text-uppercase">Leave Checked Up To <span class="text-muted normal-case">(optional)</span></label>
            <input type="date" name="checked_up_to" class="form-control" value="<?= e($old['checked_up_to'] ?? '') ?>">
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Date Proceeding on Leave *</label>
            <input type="date" name="proceeding_date" id="proceeding_date" class="form-control" required
                   value="<?= e($old['proceeding_date'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Date Due to Resume</label>
            <input type="text" id="expected_resumption_date" class="form-control" disabled placeholder="Calculated automatically">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="proceeding_with_wife" id="proceeding_with_wife" <?= !empty($old['proceeding_with_wife']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="proceeding_with_wife">Proceeding with wife</label>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label small text-muted text-uppercase">Home Town</label>
            <input type="text" name="home_town" class="form-control" value="<?= e($old['home_town'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small text-muted text-uppercase">Address While on Leave</label>
            <input type="text" name="leave_address" class="form-control" value="<?= e($old['leave_address'] ?? '') ?>">
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label small text-muted text-uppercase">Remarks</label>
        <textarea name="remarks" class="form-control" rows="2"><?= e($old['remarks'] ?? '') ?></textarea>
    </div>

    <div class="mb-4">
        <label class="form-label small text-muted text-uppercase">Supporting Document <span class="text-muted normal-case">(optional — e.g. a medical certificate for sick leave)</span></label>
        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        <div class="form-text">PDF, JPG, PNG, DOC, or DOCX — up to 5MB. You can also add this after submitting.</div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" name="action" value="submit" class="btn btn-npf-gold">Submit Application</button>
        <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save Draft</button>
        <a href="<?= base_url('personnel/view.php?id=' . $personnelId) ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
</div>
</div>

<script>
const baseUrl = <?= json_encode(base_url('')) ?>;
const proceedingInput = document.getElementById('proceeding_date');
const daysInput = document.getElementById('days_requested');
const resumptionField = document.getElementById('expected_resumption_date');
let debounceTimer;

async function previewResumption() {
    const proceeding = proceedingInput.value;
    const days = parseInt(daysInput.value, 10);
    if (!proceeding || !days || days < 1) {
        resumptionField.value = '';
        return;
    }
    try {
        const res = await fetch(`${baseUrl}api/calculate_leave.php?proceeding_date=${proceeding}&days=${days}`);
        const data = await res.json();
        resumptionField.value = data.expected_resumption_date || '';
    } catch (e) {
        resumptionField.value = '';
    }
}
proceedingInput.addEventListener('change', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(previewResumption, 150); });
daysInput.addEventListener('input', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(previewResumption, 300); });
previewResumption();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
