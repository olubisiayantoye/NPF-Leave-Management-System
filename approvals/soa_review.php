<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('leave.review_soa');
$user = current_user();
$errors = [];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

// ---------------------------------------------------------------------
// With an id: review one application
// ---------------------------------------------------------------------
if ($id) {
    $stmt = db()->prepare(
        'SELECT la.*, lt.name AS leave_type_name,
                p.f_ap_number, p.first_name, p.middle_name, p.surname,
                r.name AS rank_name, d.name AS department_name
         FROM leave_applications la
         JOIN leave_types lt ON lt.id = la.leave_type_id
         JOIN personnel p ON p.id = la.personnel_id
         JOIN ranks r ON r.id = p.rank_id
         JOIN departments d ON d.id = p.department_id
         WHERE la.id = :id'
    );
    $stmt->execute(['id' => $id]);
    $app = $stmt->fetch();

    if (!$app) {
        flash_set('error', 'Application not found.');
        redirect('approvals/soa_review.php');
    }
    if ($app['status'] !== 'pending_soa') {
        flash_set('error', 'This application is not awaiting Administration Officer review (it is currently ' . $app['status'] . ').');
        redirect('leave/view.php?id=' . $id);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_require();

        $recommendation = $_POST['recommendation_decision'] ?? '';
        $soaRemarks = trim($_POST['soa_remarks'] ?? '') ?: null;
        $recommendedByName = trim($_POST['recommended_by_name'] ?? '') ?: $user['full_name'];
        $recommendationDate = $_POST['recommendation_date'] ?: date('Y-m-d');
        $assignedReviewerId = (int) ($_POST['assigned_reviewer_id'] ?? 0);

        if (!in_array($recommendation, ['approve', 'disapprove'], true)) {
            $errors[] = 'Select whether you are recommending approval or disapproval.';
        }
        if ($recommendation === 'disapprove' && !$soaRemarks) {
            $errors[] = 'Remarks are required when recommending disapproval.';
        }
        if (!$assignedReviewerId) {
            $errors[] = 'Select which PSO to forward this to.';
        }

        if (!$errors) {
            db()->prepare(
                'UPDATE leave_applications
                 SET status = "pending_pso", soa_reviewed_by = :uid, soa_reviewed_at = NOW(),
                     soa_remarks = :remarks, recommendation_decision = :decision,
                     recommended_by_name = :rec_name, recommendation_date = :rec_date,
                     assigned_reviewer_id = :assigned
                 WHERE id = :id'
            )->execute([
                'uid' => $user['id'], 'remarks' => $soaRemarks, 'decision' => $recommendation,
                'rec_name' => $recommendedByName, 'rec_date' => $recommendationDate,
                'assigned' => $assignedReviewerId, 'id' => $id,
            ]);

            db()->prepare(
                'INSERT INTO leave_approvals (leave_application_id, action, acted_by, remarks) VALUES (:app, :action, :uid, :remarks)'
            )->execute([
                'app' => $id, 'action' => $recommendation === 'approve' ? 'recommended_approve' : 'recommended_disapprove',
                'uid' => $user['id'], 'remarks' => $soaRemarks,
            ]);

            audit_log($user['id'], 'LEAVE_SOA_REVIEWED', 'leave_applications', $id,
                "Recommended " . ($recommendation === 'approve' ? 'approval' : 'disapproval') . ", forwarded to reviewer #{$assignedReviewerId}");

            notify((int) $assignedReviewerId, "Application {$app['application_no']} forwarded to you for final decision (recommended: {$recommendation}).");

            flash_set('success', 'Recommendation recorded and application forwarded for final decision.');
            redirect('approvals/soa_review.php');
        }
    }

    $psoUsers = db()->query(
        "SELECT u.id, u.full_name, r.name AS role_name
         FROM users u
         JOIN user_roles ur ON ur.user_id = u.id
         JOIN roles r ON r.id = ur.role_id
         WHERE r.name IN ('pso_i', 'pso_ii') AND u.status = 'active'
         ORDER BY r.name, u.full_name"
    )->fetchAll();

    $fullName = trim($app['first_name'] . ' ' . ($app['middle_name'] ? $app['middle_name'] . ' ' : '') . $app['surname']);

    $pageTitle = 'Review — ' . $app['application_no'];
    require __DIR__ . '/../includes/header.php';
    ?>

    <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row small">
                <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">F-AP No.</span><span class="font-monospace"><?= e($app['f_ap_number']) ?></span></div>
                <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Name</span><?= e($fullName) ?></div>
                <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Leave Type</span><?= e($app['leave_type_name']) ?></div>
                <div class="col-md-3"><span class="text-muted d-block text-uppercase" style="font-size:.68rem;">Days</span><?= (int)$app['days_requested'] ?> (<?= e($app['proceeding_date']) ?> → <?= e($app['expected_resumption_date'] ?? '—') ?>)</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
    <form method="post" action="<?= base_url('approvals/soa_review.php?id=' . $id) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label small text-muted text-uppercase">Recommended By</label>
                <input type="text" name="recommended_by_name" class="form-control" value="<?= e($user['full_name']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted text-uppercase">Recommendation Date</label>
                <input type="date" name="recommendation_date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label small text-muted text-uppercase d-block">Recommendation *</label>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="recommendation_decision" id="rec-approve" value="approve" autocomplete="off" checked>
                <label class="btn btn-outline-success" for="rec-approve">Recommend Approval</label>
                <input type="radio" class="btn-check" name="recommendation_decision" id="rec-disapprove" value="disapprove" autocomplete="off">
                <label class="btn btn-outline-danger" for="rec-disapprove">Recommend Disapproval</label>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label small text-muted text-uppercase">Remarks / Minutes <span id="remarks-required-note" class="text-muted normal-case" style="display:none;">(required for a disapproval recommendation)</span></label>
            <textarea name="soa_remarks" id="soa_remarks" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label small text-muted text-uppercase">Forward To *</label>
            <select name="assigned_reviewer_id" class="form-select" required>
                <option value="">Select a PSO…</option>
                <?php foreach ($psoUsers as $pso): ?>
                <option value="<?= $pso['id'] ?>"><?= e($pso['full_name']) ?> (<?= $pso['role_name'] === 'pso_i' ? 'PSO I' : 'PSO II' ?>)</option>
                <?php endforeach; ?>
            </select>
            <?php if (!$psoUsers): ?><div class="form-text text-danger">No active PSO I / PSO II accounts exist yet — create one under Administration → Users.</div><?php endif; ?>
        </div>

        <button type="submit" class="btn btn-npf-gold">Save &amp; Forward</button>
        <a href="<?= base_url('leave/view.php?id=' . $id) ?>" class="btn btn-outline-secondary">Cancel</a>
    </form>
    </div>
    </div>

    <script>
    const radios = document.querySelectorAll('input[name="recommendation_decision"]');
    const remarksField = document.getElementById('soa_remarks');
    const note = document.getElementById('remarks-required-note');
    function syncRequired() {
        const isDisapprove = document.getElementById('rec-disapprove').checked;
        remarksField.required = isDisapprove;
        note.style.display = isDisapprove ? '' : 'none';
    }
    radios.forEach(r => r.addEventListener('change', syncRequired));
    syncRequired();
    </script>

    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// ---------------------------------------------------------------------
// No id: show the queue
// ---------------------------------------------------------------------
$rows = db()->query(
    "SELECT la.id, la.application_no, la.days_requested, la.proceeding_date, la.expected_resumption_date,
            lt.name AS leave_type_name, p.f_ap_number, p.first_name, p.surname,
            r.name AS rank_name, d.name AS department_name
     FROM leave_applications la
     JOIN leave_types lt ON lt.id = la.leave_type_id
     JOIN personnel p ON p.id = la.personnel_id
     JOIN ranks r ON r.id = p.rank_id
     JOIN departments d ON d.id = p.department_id
     WHERE la.status = 'pending_soa'
     ORDER BY la.submitted_at ASC"
)->fetchAll();

$pageTitle = 'Pending Administration Officer Review';
require __DIR__ . '/../includes/header.php';
?>

<p class="text-muted mb-3">Oldest submissions first — <?= count($rows) ?> awaiting your review.</p>

<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead>
        <tr>
            <th>Application No.</th><th>Personnel</th><th>Rank</th><th>Station</th>
            <th>Leave Type</th><th>Days</th><th>From</th><th>To</th><th></th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">Nothing pending — you're all caught up.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td class="font-monospace small"><?= e($r['application_no']) ?></td>
            <td><?= e($r['first_name'] . ' ' . $r['surname']) ?> <span class="text-muted small">(<?= e($r['f_ap_number']) ?>)</span></td>
            <td><?= e($r['rank_name']) ?></td>
            <td><?= e($r['department_name']) ?></td>
            <td><?= e($r['leave_type_name']) ?></td>
            <td><?= (int)$r['days_requested'] ?></td>
            <td><?= e($r['proceeding_date']) ?></td>
            <td><?= e($r['expected_resumption_date'] ?? '—') ?></td>
            <td class="text-end"><a href="<?= base_url('approvals/soa_review.php?id=' . $r['id']) ?>" class="btn btn-sm btn-npf-gold">Review</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
