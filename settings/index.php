<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

// Landing page for the Settings area — show only the cards the user actually has access to.
$cards = [
    ['key' => 'settings.roles', 'href' => '/settings/roles.php', 'icon' => 'bi-person-badge', 'title' => 'Role Management', 'desc' => 'Create and manage user roles.'],
    ['key' => 'settings.permissions', 'href' => '/settings/permissions.php', 'icon' => 'bi-key', 'title' => 'Permissions', 'desc' => 'Assign permissions to each role.'],
    ['key' => 'settings.system', 'href' => '/settings/system_settings.php', 'icon' => 'bi-gear', 'title' => 'System Settings', 'desc' => 'Site name, pagination, session timeout.'],
    ['key' => 'settings.lookups', 'href' => '/settings/lookups.php', 'icon' => 'bi-list-check', 'title' => 'Lookup Management', 'desc' => 'Rank, Bank, Section, Department, Geopolitical Zone, and more.'],
    ['key' => 'settings.featured_staff', 'href' => '/settings/featured_staff.php', 'icon' => 'bi-people', 'title' => 'Front Page Featured Staff', 'desc' => 'Choose which staff photos appear on the public front page.'],
];
$visibleCards = array_filter($cards, fn($c) => has_permission($c['key']));

if (!$visibleCards) {
    http_response_code(403);
    include __DIR__ . '/../403.php';
    exit;
}

$pageTitle = 'Settings';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-sliders"></i> Settings</h4>
<div class="row g-3">
  <?php foreach ($visibleCards as $c): ?>
  <div class="col-md-4">
    <a href="<?= BASE_URL . $c['href'] ?>" class="text-decoration-none">
      <div class="card h-100 stat-card">
        <div class="card-body">
          <i class="bi <?= $c['icon'] ?> display-6 text-primary"></i>
          <h6 class="mt-2 mb-1 text-dark"><?= e($c['title']) ?></h6>
          <p class="text-muted small mb-0"><?= e($c['desc']) ?></p>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
