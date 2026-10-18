<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_permission('settings.roles');

$pdo = db();
$roles = $pdo->query(
    "SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS user_count
     FROM roles r ORDER BY r.name"
)->fetchAll();

$pageTitle = 'Role Management';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-person-badge"></i> Role Management</h4>
  <a href="<?= BASE_URL ?>/settings/role_edit.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New Role</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light"><tr><th>Name</th><th>Description</th><th>Users</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($roles as $r): ?>
        <tr>
          <td><?= e($r['name']) ?></td>
          <td class="text-muted small"><?= e($r['description']) ?></td>
          <td><?= (int)$r['user_count'] ?></td>
          <td class="text-end">
            <a href="<?= BASE_URL ?>/settings/permissions.php?role_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-key"></i> Permissions</a>
            <a href="<?= BASE_URL ?>/settings/role_edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php if ((int)$r['user_count'] === 0): ?>
            <a href="<?= BASE_URL ?>/settings/role_delete.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Delete the role '<?= e($r['name']) ?>'?"><i class="bi bi-trash"></i></a>
            <?php else: ?>
            <button class="btn btn-sm btn-outline-danger" disabled title="Cannot delete — <?= (int)$r['user_count'] ?> user(s) still assigned"><i class="bi bi-trash"></i></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<p class="text-muted small mt-3">Note: the app's core modules (Personnel, Allowance, Transfers, User Management,
   Audit Trail) currently check for the 5 built-in role names directly. A newly created custom role will govern
   access to this Settings area (once granted permissions below) but won't automatically gain access to those
   core modules unless it's one of the 5 built-in names.</p>
<?php include __DIR__ . '/../includes/footer.php'; ?>
