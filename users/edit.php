<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$u = $stmt->fetch();
if (!$u) { flash('error', 'User not found.'); header('Location: ' . url('/users/list.php')); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? 0);

    if (!$fullName || !$email || !$roleId) {
        $error = 'All fields are required.';
    } else {
        db()->prepare('UPDATE users SET full_name = ?, email = ?, role_id = ? WHERE id = ?')
            ->execute([$fullName, $email, $roleId, $id]);
        audit_log('USER_UPDATED', 'user', $id, "Updated role/details");
        flash('success', 'User updated.');
        header('Location: ' . url('/users/list.php'));
        exit;
    }
}

$pageTitle = 'Edit User';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3">Edit User &mdash; <?= e($u['full_name']) ?></h4>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="card" style="max-width:520px"><div class="card-body">
  <form method="post">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= e($u['full_name']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($u['email']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Role</label>
      <select name="role_id" class="form-select" required>
        <?php foreach (lookup('roles') as $r): ?>
          <option value="<?= $r['id'] ?>" <?= $u['role_id'] == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary w-100">Save Changes</button>
  </form>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
