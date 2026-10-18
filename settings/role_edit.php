<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_permission('settings.roles');

$pdo = db();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$role = ['name' => '', 'description' => ''];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM roles WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { flash('error', 'Role not found.'); header('Location: ' . url('/settings/roles.php')); exit; }
    $role = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$name) {
        $error = 'Role name is required.';
    } else {
        $dup = $pdo->prepare('SELECT id FROM roles WHERE name = ? AND id != ?');
        $dup->execute([$name, $id]);
        if ($dup->fetch()) {
            $error = 'A role with that name already exists.';
        } elseif ($id) {
            $pdo->prepare('UPDATE roles SET name = ?, description = ? WHERE id = ?')->execute([$name, $description, $id]);
            audit_log('ROLE_UPDATED', 'role', $id, "Renamed/updated: $name");
            flash('success', 'Role updated.');
            header('Location: ' . url('/settings/roles.php'));
            exit;
        } else {
            $pdo->prepare('INSERT INTO roles (name, description) VALUES (?, ?)')->execute([$name, $description]);
            $newId = $pdo->lastInsertId();
            audit_log('ROLE_CREATED', 'role', $newId, "Created role: $name");
            flash('success', 'Role created. Assign its permissions next.');
            header('Location: ' . url("/settings/permissions.php?role_id=$newId"));
            exit;
        }
    }
    $role['name'] = $name;
    $role['description'] = $description;
}

$pageTitle = $id ? 'Edit Role' : 'New Role';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><?= $id ? 'Edit Role' : 'New Role' ?></h4>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="card" style="max-width:520px"><div class="card-body">
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="mb-3"><label class="form-label">Role Name</label>
      <input type="text" name="name" class="form-control" required value="<?= e($role['name']) ?>"></div>
    <div class="mb-3"><label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="2"><?= e($role['description']) ?></textarea></div>
    <button class="btn btn-primary w-100">Save Role</button>
  </form>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
