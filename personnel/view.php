<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$user = current_user();
$roles = $user['roles'];
$canApply = current_user_has_permission('leave.apply');

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    flash_set('error', 'No personnel record specified.');
    redirect('personnel/search.php');
}

$stmt = db()->prepare(
    'SELECT p.*, r.name AS rank_name, d.name AS department_name,
            s.name AS section_name, c.name AS command_name
     FROM personnel p
     JOIN ranks r ON r.id = p.rank_id
     JOIN departments d ON d.id = p.department_id
     LEFT JOIN sections s ON s.id = p.section_id
     LEFT JOIN commands c ON c.id = p.command_id
     WHERE p.id = :id'
);
$stmt->execute(['id' => $id]);
$person = $stmt->fetch();

if (!$person) {
    flash_set('error', 'That personnel record could not be found.');
    redirect('personnel/search.php');
}

$currentYear = date('Y');
$entStmt = db()->prepare(
    'SELECT lt.name AS leave_type, le.entitled_days, le.used_days
     FROM leave_entitlements le
     JOIN leave_types lt ON lt.id = le.leave_type_id
     WHERE le.personnel_id = :id AND le.year = :year
     ORDER BY lt.name'
);
$entStmt->execute(['id' => $id, 'year' => $currentYear]);
$entitlements = $entStmt->fetchAll();

$histStmt = db()->prepare(
    'SELECT la.application_no, lt.name AS leave_type, la.status,
            la.proceeding_date, la.expected_resumption_date, la.days_requested
     FROM leave_applications la
     JOIN leave_types lt ON lt.id = la.leave_type_id
     WHERE la.personnel_id = :id
     ORDER BY la.created_at DESC
     LIMIT 15'
);
$histStmt->execute(['id' => $id]);
$history = $histStmt->fetchAll();

$fullName = trim($person['first_name'] . ' ' . ($person['middle_name'] ? $person['middle_name'] . ' ' : '') . $person['surname']);

$statusBadgeClass = [
    'pending' => 'badge-pending', 'approved' => 'badge-approved', 'disapproved' => 'badge-disapproved',
    'draft' => 'text-bg-secondary', 'cancelled' => 'text-bg-secondary', 'curtailed' => 'text-bg-secondary',
    'completed' => 'text-bg-secondary',
];

$pageTitle = 'Personnel Profile';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-muted small font-monospace mb-1"><?= e($person['f_ap_number']) ?></div>
                    <?php if (current_user_has_permission('administration.manage')): ?>
                    <a href="<?= base_url('personnel/edit.php?id=' . $person['id']) ?>" class="text-muted small text-decoration-none">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <?php endif; ?>
                </div>
                <h2 class="h5 mb-1"><?= e($fullName) ?></h2>
                <div class="text-muted mb-3"><?= e($person['rank_name']) ?> · <?= e($person['department_name']) ?></div>

                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Section</dt><dd class="col-7"><?= e($person['section_name'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Command</dt><dd class="col-7"><?= e($person['command_name'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Phone</dt><dd class="col-7"><?= e($person['phone'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Email</dt><dd class="col-7"><?= e($person['email'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Status</dt><dd class="col-7"><?= e(str_replace('_', ' ', $person['status'])) ?></dd>
                </dl>
            </div>
        </div>

        <?php if ($canApply): ?>
        <a href="<?= base_url('leave/apply.php?personnel_id=' . $person['id']) ?>" class="btn btn-npf-gold w-100">
            <i class="bi bi-file-earmark-plus"></i> Apply for Leave
        </a>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 mb-0"><?= $currentYear ?> Leave Entitlement &amp; Balance</h2>
                    <?php if (current_user_has_permission('administration.manage')): ?>
                    <a href="<?= base_url('administration/entitlements.php?personnel_id=' . $person['id'] . '&year=' . $currentYear) ?>" class="btn btn-sm btn-outline-dark">
                        <i class="bi bi-pencil"></i> Set Entitlement
                    </a>
                    <?php endif; ?>
                </div>
                <?php if (!$entitlements): ?>
                    <p class="text-muted small mb-0">
                        No entitlements configured for <?= $currentYear ?> yet.
                        <?php if (current_user_has_permission('administration.manage')): ?>
                        <a href="<?= base_url('administration/entitlements.php?personnel_id=' . $person['id'] . '&year=' . $currentYear) ?>">Set one now.</a>
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Leave Type</th><th>Entitled</th><th>Used</th><th>Balance</th></tr></thead>
                    <tbody>
                    <?php foreach ($entitlements as $ent): $balance = $ent['entitled_days'] - $ent['used_days']; ?>
                        <tr>
                            <td><?= e($ent['leave_type']) ?></td>
                            <td><?= (int)$ent['entitled_days'] ?></td>
                            <td><?= (int)$ent['used_days'] ?></td>
                            <td class="fw-semibold <?= $balance <= 0 ? 'text-danger' : '' ?>"><?= $balance ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Leave History</h2>
                <?php if (!$history): ?>
                    <p class="text-muted small mb-0">No leave applications on record yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Application No.</th><th>Type</th><th>Days</th><th>Proceeding</th><th>Resume</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td class="font-monospace small"><?= e($h['application_no']) ?></td>
                            <td><?= e($h['leave_type']) ?></td>
                            <td><?= (int)$h['days_requested'] ?></td>
                            <td><?= e($h['proceeding_date']) ?></td>
                            <td><?= e($h['expected_resumption_date'] ?? '—') ?></td>
                            <td><span class="badge badge-status <?= $statusBadgeClass[$h['status']] ?? 'text-bg-secondary' ?>"><?= e($h['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
