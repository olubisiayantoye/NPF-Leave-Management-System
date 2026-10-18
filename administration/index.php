<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('administration.manage');

$tiles = [
    ['url' => 'administration/users.php',       'icon' => 'bi-people',        'title' => 'Users & Roles',   'desc' => 'Create accounts and assign I-Admin / Administration Officer / PSO I / PSO II roles.'],
    ['url' => 'administration/roles.php',       'icon' => 'bi-shield-lock',   'title' => 'Roles & Permissions', 'desc' => 'Control exactly what each role is allowed to do, without touching code.'],
    ['url' => 'administration/mail_settings.php','icon' => 'bi-envelope-gear','title' => 'Email Settings',  'desc' => 'Turn on email notifications and configure how mail is sent.'],
    ['url' => 'administration/ranks.php',       'icon' => 'bi-award',         'title' => 'Ranks',           'desc' => 'The rank list used across personnel registration.'],
    ['url' => 'administration/departments.php', 'icon' => 'bi-building',      'title' => 'Departments',     'desc' => 'Departments personnel and applications are organised by.'],
    ['url' => 'administration/sections.php',    'icon' => 'bi-diagram-3',     'title' => 'Sections',        'desc' => 'Sub-units within each department.'],
    ['url' => 'administration/commands.php',    'icon' => 'bi-geo-alt',       'title' => 'Commands',        'desc' => 'Commands personnel can be posted to.'],
    ['url' => 'administration/leave_types.php', 'icon' => 'bi-calendar2-check','title' => 'Leave Types',    'desc' => 'Activate or deactivate the leave categories available for application.'],
    ['url' => 'administration/holidays.php',    'icon' => 'bi-calendar-x',    'title' => 'Public Holidays', 'desc' => 'Dates excluded, with Saturdays and Sundays, from leave-day calculation.'],
    ['url' => 'administration/entitlements.php','icon' => 'bi-clipboard-check','title' => 'Leave Entitlements', 'desc' => 'Set or roll over how many days each officer gets per leave type per year.'],
];

$pageTitle = 'Administration';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <?php foreach ($tiles as $t): ?>
    <div class="col-md-6 col-lg-4">
        <a href="<?= base_url($t['url']) ?>" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body">
                <i class="bi <?= $t['icon'] ?> fs-4 text-muted"></i>
                <div class="fw-semibold mt-2 mb-1"><?= e($t['title']) ?></div>
                <div class="small text-muted"><?= e($t['desc']) ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
