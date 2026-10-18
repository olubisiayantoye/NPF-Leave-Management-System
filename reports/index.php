<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('reports.view');

$reports = [
    ['url' => 'reports/personnel_on_leave.php', 'icon' => 'bi-person-walking', 'title' => 'Personnel Currently on Leave', 'desc' => 'Everyone whose leave is active right now.'],
    ['url' => 'reports/leave_balance.php',       'icon' => 'bi-calculator',     'title' => 'Leave Balance Report',          'desc' => 'Entitlement, used and remaining days by officer, this year.'],
    ['url' => 'reports/overdue.php',             'icon' => 'bi-exclamation-triangle', 'title' => 'Overstayed Leave',        'desc' => 'Approved leave past its expected resumption date with no resumption recorded.'],
    ['url' => 'reports/by_department.php',       'icon' => 'bi-building',       'title' => 'Leave by Department',           'desc' => 'Application counts and status breakdown per department.'],
    ['url' => 'reports/statistics.php',          'icon' => 'bi-bar-chart',      'title' => 'Monthly / Annual Statistics',   'desc' => 'Submitted, approved and disapproved counts over time.'],
];

$pageTitle = 'Reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <?php foreach ($reports as $r): ?>
    <div class="col-md-6 col-lg-4">
        <a href="<?= base_url($r['url']) ?>" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body">
                <i class="bi <?= $r['icon'] ?> fs-4 text-muted"></i>
                <div class="fw-semibold mt-2 mb-1"><?= e($r['title']) ?></div>
                <div class="small text-muted"><?= e($r['desc']) ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<p class="text-muted small mt-4">More categories (cancelled/curtailed leave, personnel availability, leave-by-rank) follow the same pattern and can be added as the underlying workflows come online.</p>

<?php require __DIR__ . '/../includes/footer.php'; ?>
