<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$users = db()->query(
    "SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.full_name"
)->fetchAll();

$pageTitle = 'User Management';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">User Management</h4>
  <a href="<?= BASE_URL ?>/users/add.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Add User</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light"><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['full_name']) ?></td>
          <td><?= e($u['username']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="badge bg-primary"><?= e($u['role_name']) ?></span></td>
          <td><span class="badge <?= $u['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= e($u['status']) ?></span></td>
          <td><?= $u['last_login'] ? format_date($u['last_login']) : 'Never' ?></td>
          <td class="text-end">
            <a href="<?= BASE_URL ?>/users/edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <a href="<?= BASE_URL ?>/users/toggle_status.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning" data-confirm="<?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?> this user account?">
              <i class="bi bi-power"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
