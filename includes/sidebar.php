<?php $role = $user['role_name'] ?? ''; $path = $_SERVER['SCRIPT_NAME']; function nav_active($needle,$path){return strpos($path,$needle)!==false ? 'active' : '';} ?>
<aside class="nrms-sidebar">
  <ul class="nav flex-column">
    <li class="nav-item"><a class="nav-link <?= nav_active('dashboard.php',$path) ?>" href="<?= BASE_URL ?>/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>

    <li class="nav-item"><a class="nav-link <?= nav_active('/personnel/',$path) ?>" href="<?= BASE_URL ?>/personnel/list.php"><i class="bi bi-people"></i> Personnel</a></li>

    <?php if (in_array($role, ['Super Administrator','Administrator','Documentation Officer'])): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('personnel/add.php',$path) ?>" href="<?= BASE_URL ?>/personnel/add.php"><i class="bi bi-person-plus"></i> Register Personnel</a></li>
    <?php endif; ?>

    <?php if (in_array($role, ['Super Administrator','Administrator'])): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('/transfers/',$path) ?>" href="<?= BASE_URL ?>/transfers/list.php"><i class="bi bi-arrow-left-right"></i> Transfers</a></li>
    <li class="nav-item"><a class="nav-link <?= nav_active('bulk_upload.php',$path) ?>" href="<?= BASE_URL ?>/bulk_upload.php"><i class="bi bi-file-earmark-excel"></i> Bulk Upload</a></li>
    <?php endif; ?>

    <li class="nav-item"><a class="nav-link <?= nav_active('/verification/',$path) ?>" href="<?= BASE_URL ?>/verification/search.php"><i class="bi bi-patch-check"></i> Verification</a></li>

    <li class="nav-item"><a class="nav-link <?= nav_active('/reports/',$path) ?>" href="<?= BASE_URL ?>/reports/nominal_roll.php"><i class="bi bi-file-earmark-text"></i> Nominal Roll / Reports</a></li>

    <li class="nav-item"><a class="nav-link <?= nav_active('/allowance/list.php',$path) ?>" href="<?= BASE_URL ?>/allowance/list.php"><i class="bi bi-cash-coin"></i> Monthly Allowance</a></li>

    <?php if (in_array($role, ['Super Administrator','Administrator','Documentation Officer'])): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('allowance/add.php',$path) ?>" href="<?= BASE_URL ?>/allowance/add.php"><i class="bi bi-person-plus"></i> Register Allowance Personnel</a></li>
    <?php endif; ?>

    <?php if (in_array($role, ['Super Administrator','Administrator'])): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('allowance/bulk_upload.php',$path) ?>" href="<?= BASE_URL ?>/allowance/bulk_upload.php"><i class="bi bi-file-earmark-excel"></i> Allowance Bulk Upload</a></li>
    <?php endif; ?>

    <li class="nav-item"><a class="nav-link <?= nav_active('allowance/report.php',$path) ?>" href="<?= BASE_URL ?>/allowance/report.php"><i class="bi bi-file-earmark-bar-graph"></i> Allowance Reports</a></li>

    <?php if (in_array($role, ['Super Administrator','Administrator'])): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('/users/',$path) ?>" href="<?= BASE_URL ?>/users/list.php"><i class="bi bi-person-badge"></i> User Management</a></li>
    <?php endif; ?>

    <?php if ($role === 'Super Administrator'): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('audit_logs.php',$path) ?>" href="<?= BASE_URL ?>/audit_logs.php"><i class="bi bi-clock-history"></i> Audit Trail</a></li>
    <?php endif; ?>

    <?php if (has_permission('settings.roles') || has_permission('settings.permissions') || has_permission('settings.system') || has_permission('settings.lookups') || has_permission('settings.featured_staff')): ?>
    <li class="nav-item mt-2"><span class="nav-link text-uppercase small text-muted" style="letter-spacing:.05em;">Settings</span></li>
    <li class="nav-item"><a class="nav-link <?= nav_active('/settings/index.php',$path) ?>" href="<?= BASE_URL ?>/settings/index.php"><i class="bi bi-sliders"></i> Settings Overview</a></li>
    <?php if (has_permission('settings.roles')): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('settings/roles.php',$path) . nav_active('settings/role_edit.php',$path) ?>" href="<?= BASE_URL ?>/settings/roles.php"><i class="bi bi-person-badge"></i> Role Management</a></li>
    <?php endif; ?>
    <?php if (has_permission('settings.permissions')): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('settings/permissions.php',$path) ?>" href="<?= BASE_URL ?>/settings/permissions.php"><i class="bi bi-key"></i> Permissions</a></li>
    <?php endif; ?>
    <?php if (has_permission('settings.system')): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('settings/system_settings.php',$path) ?>" href="<?= BASE_URL ?>/settings/system_settings.php"><i class="bi bi-gear"></i> System Settings</a></li>
    <?php endif; ?>
    <?php if (has_permission('settings.lookups')): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('settings/lookup',$path) ?>" href="<?= BASE_URL ?>/settings/lookups.php"><i class="bi bi-list-check"></i> Lookup Management</a></li>
    <?php endif; ?>
    <?php if (has_permission('settings.featured_staff')): ?>
    <li class="nav-item"><a class="nav-link <?= nav_active('settings/featured_staff',$path) ?>" href="<?= BASE_URL ?>/settings/featured_staff.php"><i class="bi bi-people"></i> Front Page Featured Staff</a></li>
    <?php endif; ?>
    <?php endif; ?>
  </ul>
</aside>
