<?php
/**
 * Sidebar nav. Included by header.php. Uses $user (from current_user())
 * and current script path to highlight the active link.
 *
 * Menu order follows the spec exactly: Dashboard, Personnel, My
 * Application, Notification, Leave Scheduled, Reports, Settings, Audit
 * Log. The two "queue" links (Administration Officer review / PSO final
 * decision) aren't part of that named list, but someone in those roles
 * needs a way to reach their queue — they're added as a supplementary
 * "Leave Review" section rather than replacing any of the 8 named items.
 */
$current = $_SERVER['SCRIPT_NAME'];
$active = fn(string $needle) => str_contains($current, $needle) ? 'active' : '';

$canManagePersonnel = current_user_has_permission('personnel.manage');
$canSearchPersonnel = current_user_has_permission('personnel.search');
$canApplyLeave = current_user_has_permission('leave.apply');
$canReviewSoa = current_user_has_permission('leave.review_soa');
$canDecide = current_user_has_permission('leave.approve');
$canManageAdmin = current_user_has_permission('administration.manage');
$canViewAudit = current_user_has_permission('audit.view');
$canViewReports = current_user_has_permission('reports.view');
?>
<aside class="app-sidebar">
    <a class="brand" href="<?= base_url('dashboard/index.php') ?>">
        <span class="brand-mark">✺</span>
        <span>
            <strong>NPF · LMS</strong>
            <small>Leave Management</small>
        </span>
    </a>
    <nav>
        <a href="<?= base_url('dashboard/index.php') ?>" class="<?= $active('/dashboard/') ?>">
            <i class="bi bi-grid"></i> Dashboard
        </a>

        <?php if ($canManagePersonnel || $canSearchPersonnel): ?>
        <a href="<?= base_url($canManagePersonnel ? 'personnel/index.php' : 'personnel/search.php') ?>" class="<?= $active('/personnel/') ?>">
            <i class="bi bi-people"></i> Personnel
        </a>
        <?php endif; ?>

        <?php if ($canApplyLeave): ?>
        <a href="<?= base_url('leave/index.php') ?>" class="<?= $active('/leave/index') ?>">
            <i class="bi bi-file-earmark-text"></i> My Application
        </a>
        <?php endif; ?>

        <a href="<?= base_url('notifications/index.php') ?>" class="<?= $active('/notifications/') ?>">
            <i class="bi bi-bell"></i> Notification
        </a>

        <?php if ($canReviewSoa || $canDecide): ?>
        <div class="nav-section">Leave Review</div>
        <?php if ($canReviewSoa): ?>
        <a href="<?= base_url('approvals/soa_review.php') ?>" class="<?= $active('/soa_review') ?>">
            <i class="bi bi-clipboard-check"></i> Review Queue
        </a>
        <?php endif; ?>
        <?php if ($canDecide): ?>
        <a href="<?= base_url('approvals/pending.php') ?>" class="<?= $active('/approvals/pending') ?>">
            <i class="bi bi-hourglass-split"></i> Decision Queue
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($canViewReports): ?>
        <a href="<?= base_url('leave/scheduled.php') ?>" class="<?= $active('/scheduled') ?>">
            <i class="bi bi-calendar3"></i> Leave Scheduled
        </a>
        <a href="<?= base_url('reports/index.php') ?>" class="<?= $active('/reports/') ?>">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
        <?php endif; ?>

        <?php if ($canManageAdmin): ?>
        <a href="<?= base_url('administration/index.php') ?>" class="<?= $active('/administration/') ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
        <?php endif; ?>

        <?php if ($canViewAudit): ?>
        <a href="<?= base_url('audit/index.php') ?>" class="<?= $active('/audit/') ?>">
            <i class="bi bi-clock-history"></i> Audit Log
        </a>
        <?php endif; ?>
    </nav>
</aside>
