<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('leave.apply');
$user = current_user();
$isAdmin = current_user_has_permission('administration.manage_users') || current_user_has_permission('personnel.manage');

// I-Admins see what they've submitted; admins can see everything.
if ($isAdmin) {
    $rows = db()->query(
        "SELECT la.id, la.application_no, la.status, la.days_requested, la.proceeding_date,
                lt.name AS leave_type_name, p.f_ap_number, p.first_name, p.surname
         FROM leave_applications la
         JOIN leave_types lt ON lt.id = la.leave_type_id
         JOIN personnel p ON p.id = la.personnel_id
         ORDER BY la.created_at DESC LIMIT 100"
    )->fetchAll();
} else {
    $stmt = db()->prepare(
        "SELECT la.id, la.application_no, la.status, la.days_requested, la.proceeding_date,
                lt.name AS leave_type_name, p.f_ap_number, p.first_name, p.surname
         FROM leave_applications la
         JOIN leave_types lt ON lt.id = la.leave_type_id
         JOIN personnel p ON p.id = la.personnel_id
         WHERE la.submitted_by = :uid
         ORDER BY la.created_at DESC LIMIT 100"
    );
    $stmt->execute(['uid' => $user['id']]);
    $rows = $stmt->fetchAll();
}

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

$pageTitle = 'My Application';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Track every application from submission through to signal.</p>
    <a href="<?= base_url('personnel/search.php') ?>" class="btn btn-npf-gold btn-sm"><i class="bi bi-file-earmark-plus"></i> New Application</a>
</div>

<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr><th>Application No.</th><th>Personnel</th><th>Type</th><th>Days</th><th>Proceeding</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No applications yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td class="font-monospace small"><?= e($r['application_no']) ?></td>
            <td><?= e($r['first_name'] . ' ' . $r['surname']) ?> <span class="text-muted small">(<?= e($r['f_ap_number']) ?>)</span></td>
            <td><?= e($r['leave_type_name']) ?></td>
            <td><?= (int)$r['days_requested'] ?></td>
            <td><?= e($r['proceeding_date']) ?></td>
            <td><span class="badge badge-status <?= $statusBadgeClass[$r['status']] ?? 'text-bg-secondary' ?>"><?= e($statusLabels[$r['status']] ?? $r['status']) ?></span></td>
            <td class="text-end"><a href="<?= base_url('leave/view.php?id=' . $r['id']) ?>" class="btn btn-sm btn-outline-dark">View</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
