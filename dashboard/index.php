<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$user = current_user();
$roles = $user['roles'];

/**
 * Live counts for the dashboard tiles. Wrapped defensively so the page
 * still renders (with zeros) before the schema/data is fully seeded.
 */
function safe_count(string $sql, array $params = []): int
{
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('dashboard stat query failed: ' . $e->getMessage());
        return 0;
    }
}

$totalPersonnel   = safe_count('SELECT COUNT(*) FROM personnel');
$onLeave          = safe_count("SELECT COUNT(*) FROM personnel WHERE status = 'on_leave'");
$pendingCount     = safe_count("SELECT COUNT(*) FROM leave_applications WHERE status IN ('pending_soa','pending_pso')");
$resumingToday    = safe_count(
    "SELECT COUNT(*) FROM leave_resumptions WHERE expected_resumption_date = CURDATE() AND actual_resumption_date IS NULL"
);
$overdueCount     = safe_count(
    "SELECT COUNT(*) FROM leave_resumptions WHERE expected_resumption_date < CURDATE() AND actual_resumption_date IS NULL"
);

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<p class="text-muted mb-4">Welcome back, <strong><?= e($user['full_name']) ?></strong>
    (<?= e(implode(', ', array_map('role_label', $roles))) ?: 'no role assigned' ?>).
</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-tile">
            <div class="value"><?= number_format($totalPersonnel) ?></div>
            <div class="label">Total Personnel</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-tile">
            <div class="value"><?= number_format($onLeave) ?></div>
            <div class="label">Currently on Leave</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-tile">
            <div class="value"><?= number_format($pendingCount) ?></div>
            <div class="label">Pending Applications</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-tile">
            <div class="value"><?= number_format($resumingToday) ?></div>
            <div class="label">Resuming Today</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-tile">
            <div class="value"><?= number_format($overdueCount) ?></div>
            <div class="label">Overdue</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 mb-3">Quick actions</h2>
        <div class="d-flex flex-wrap gap-2">
            <?php if (current_user_has_permission('personnel.search')): ?>
                <a href="<?= base_url('personnel/search.php') ?>" class="btn btn-npf-gold btn-sm">
                    <i class="bi bi-search"></i> Apply for Leave
                </a>
            <?php endif; ?>
            <?php if (current_user_has_permission('leave.review_soa')): ?>
                <a href="<?= base_url('approvals/soa_review.php') ?>" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-clipboard-check"></i> Review Applications
                </a>
            <?php endif; ?>
            <?php if (current_user_has_permission('leave.approve')): ?>
                <a href="<?= base_url('approvals/pending.php') ?>" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-hourglass-split"></i> Decide Applications
                </a>
            <?php endif; ?>
            <a href="<?= base_url('leave/scheduled.php') ?>" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-calendar3"></i> Leave Scheduled
            </a>
            <a href="<?= base_url('reports/index.php') ?>" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-bar-chart"></i> Generate Reports
            </a>
            <?php if (current_user_has_permission('personnel.manage')): ?>
                <a href="<?= base_url('personnel/create.php') ?>" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-person-plus"></i> Register Personnel
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
