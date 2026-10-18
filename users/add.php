<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? 0);
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$fullName || !$roleId) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 10) {
        $error = 'Temporary password must be at least 10 characters.';
    } else {
        $dup = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $dup->execute([$username, $email]);
        if ($dup->fetch()) {
            $error = 'A user with that username or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            db()->prepare(
                'INSERT INTO users (username, email, password_hash, full_name, role_id, status) VALUES (?, ?, ?, ?, ?, "active")'
            )->execute([$username, $email, $hash, $fullName, $roleId]);
            audit_log('USER_CREATED', 'user', db()->lastInsertId(), "Created user $username");
            flash('success', "User account created. Share the temporary password with $fullName securely and ask them to change it on first login.");
            header('Location: ' . url('/users/list.php'));
            exit;
        }
    }
}

$pageTitle = 'Add User';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3">Add System User</h4>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="card" style="max-width:520px"><div class="card-body">
  <form method="post">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Role</label>
      <select name="role_id" class="form-select" required>
        <option value="">Select Role</option>
        <?php foreach (lookup('roles') as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3"><label class="form-label">Temporary Password (min 10 characters)</label>
      <input type="text" name="password" class="form-control" required minlength="10">
      <div class="form-text">Share this with the user through a secure channel; they should change it after first login.</div>
    </div>
    <button class="btn btn-primary w-100">Create User</button>
  </form>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
