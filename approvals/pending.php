<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('leave.approve');
$user = current_user();
$isOversight = current_user_has_permission('administration.manage_users'); // super_admin sees everyone's queue

$sql = "SELECT la.id, la.application_no, la.days_requested, la.proceeding_date, la.expected_resumption_date,
               la.recommendation_decision,
               lt.name AS leave_type_name, p.f_ap_number, p.first_name, p.surname, p.id AS personnel_id,
               r.name AS rank_name, d.name AS department_name,
               su.full_name AS soa_reviewed_by_name
        FROM leave_applications la
        JOIN leave_types lt ON lt.id = la.leave_type_id
        JOIN personnel p ON p.id = la.personnel_id
        JOIN ranks r ON r.id = p.rank_id
        JOIN departments d ON d.id = p.department_id
        LEFT JOIN users su ON su.id = la.soa_reviewed_by
        WHERE la.status = 'pending_pso'";
$params = [];
if (!$isOversight) {
    $sql .= " AND (la.assigned_reviewer_id = :uid OR la.assigned_reviewer_id IS NULL)";
    $params['uid'] = $user['id'];
}
$sql .= " ORDER BY la.soa_reviewed_at ASC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Pending Final Decision';
require __DIR__ . '/../includes/header.php';
?>

<p class="text-muted mb-3">Forwarded by the Administration Officer, oldest first — <?= count($rows) ?> awaiting your decision.</p>

<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead>
        <tr>
            <th>Application No.</th><th>Personnel</th><th>Rank</th><th>Station</th>
            <th>Leave Type</th><th>Days</th><th>From</th><th>To</th><th>Recommendation</th><th></th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">Nothing pending — you're all caught up.</td></tr>
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
            <td>
                <span class="badge <?= $r['recommendation_decision'] === 'approve' ? 'text-bg-success' : 'text-bg-danger' ?>">
                    <?= $r['recommendation_decision'] === 'approve' ? 'Approve' : 'Disapprove' ?>
                </span>
                <div class="text-muted small"><?= e($r['soa_reviewed_by_name'] ?? '') ?></div>
            </td>
            <td class="text-end"><a href="<?= base_url('leave/view.php?id=' . $r['id']) ?>" class="btn btn-sm btn-npf-gold">Decide</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
