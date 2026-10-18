<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_permission('settings.permissions');

$pdo = db();

// The canonical set of permission keys the app understands. New pages that adopt
// require_permission() should add their key here so it shows up in this matrix.
$permissionCatalog = [
    'Settings' => [
        'settings.roles' => 'Manage roles',
        'settings.permissions' => 'Manage permissions',
        'settings.system' => 'Manage system settings',
        'settings.lookups' => 'Manage lookup categories (Rank, Bank, Section, Department, Zone, etc.)',
        'settings.featured_staff' => 'Manage front page featured staff',
    ],
];

$roles = $pdo->query('SELECT * FROM roles ORDER BY name')->fetchAll();
$selectedRoleId = (int)($_GET['role_id'] ?? ($roles[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $roleId = (int)$_POST['role_id'];
    $checked = $_POST['permissions'] ?? []; // array of permission_key strings

    $allKeys = [];
    foreach ($permissionCatalog as $group) $allKeys = array_merge($allKeys, array_keys($group));

    $pdo->beginTransaction();
    $del = $pdo->prepare('DELETE FROM permissions WHERE role_id = ? AND permission_key = ?');
    $ins = $pdo->prepare('INSERT IGNORE INTO permissions (role_id, permission_key) VALUES (?, ?)');
    foreach ($allKeys as $key) {
        if (in_array($key, $checked, true)) {
            $ins->execute([$roleId, $key]);
        } else {
            $del->execute([$roleId, $key]);
        }
    }
    $pdo->commit();

    audit_log('ROLE_PERMISSIONS_UPDATED', 'role', $roleId, 'Permissions: ' . implode(', ', $checked));
    flash('success', 'Permissions updated.');
    header('Location: ' . url("/settings/permissions.php?role_id=$roleId"));
    exit;
}

$grantedStmt = $pdo->prepare('SELECT permission_key FROM permissions WHERE role_id = ?');
$grantedStmt->execute([$selectedRoleId]);
$granted = array_column($grantedStmt->fetchAll(), 'permission_key');

$pageTitle = 'Permissions';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-key"></i> Permissions</h4>

<div class="card mb-3"><div class="card-body">
  <form method="get" class="row g-2">
    <div class="col-md-4">
      <label class="form-label small">Role</label>
      <select name="role_id" class="form-select auto-submit">
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r['id'] ?>" <?= $r['id'] == $selectedRoleId ? 'selected' : '' ?>><?= e($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div></div>

<?php if ($selectedRoleId): ?>
<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="role_id" value="<?= $selectedRoleId ?>">
  <?php foreach ($permissionCatalog as $groupName => $keys): ?>
  <div class="card mb-3">
    <div class="card-header bg-white"><?= e($groupName) ?></div>
    <div class="card-body">
      <?php foreach ($keys as $key => $label): ?>
      <div class="form-check">
        <input type="checkbox" class="form-check-input" name="permissions[]" value="<?= e($key) ?>"
               id="perm_<?= e($key) ?>" <?= in_array($key, $granted, true) ? 'checked' : '' ?>>
        <label class="form-check-label" for="perm_<?= e($key) ?>"><?= e($label) ?> <code class="text-muted"><?= e($key) ?></code></label>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <button class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Permissions</button>
</form>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
