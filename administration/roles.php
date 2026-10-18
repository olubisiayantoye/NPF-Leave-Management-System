<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Role/permission management is account-security-adjacent — same gate as user management.
require_permission('administration.manage_users');
$user = current_user();
$errors = [];

$permissions = db()->query('SELECT id, code, description FROM permissions ORDER BY code')->fetchAll();
$roles = db()->query("SELECT id, name, description FROM roles ORDER BY FIELD(name, 'super_admin') DESC, name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save_role_permissions') {
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $roleStmt = db()->prepare('SELECT name FROM roles WHERE id = :id');
        $roleStmt->execute(['id' => $roleId]);
        $roleName = $roleStmt->fetchColumn();

        if ($roleName === 'super_admin') {
            // Structurally impossible to edit — super_admin always has every
            // permission, checked again here even though the form for it is
            // never rendered, in case of a crafted request.
            flash_set('error', 'The super_admin role cannot be modified — it always has full access.');
        } elseif (!$roleName) {
            flash_set('error', 'Role not found.');
        } else {
            $selectedPermissionIds = array_map('intval', $_POST['permission_ids'] ?? []);

            $db = db();
            $db->beginTransaction();
            $db->prepare('DELETE FROM role_permissions WHERE role_id = :id')->execute(['id' => $roleId]);
            if ($selectedPermissionIds) {
                $insert = $db->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:r, :p)');
                foreach ($selectedPermissionIds as $pid) {
                    $insert->execute(['r' => $roleId, 'p' => $pid]);
                }
            }
            $db->commit();

            audit_log($user['id'], 'ROLE_PERMISSIONS_UPDATED', 'roles', $roleId,
                role_label($roleName) . ': ' . count($selectedPermissionIds) . ' permission(s) set');
            flash_set('success', 'Permissions updated for ' . role_label($roleName) . '.');
        }
        redirect('administration/roles.php');
    }
}

// Current grants: role_id => [permission_id => true]
$grants = [];
foreach (db()->query('SELECT role_id, permission_id FROM role_permissions') as $row) {
    $grants[(int) $row['role_id']][(int) $row['permission_id']] = true;
}

$pageTitle = 'Roles & Permissions';
require __DIR__ . '/../includes/header.php';
?>

<p class="text-muted mb-4">Each protected page in the system checks one of these permission codes rather than a hardcoded role name — reassigning a checkbox here takes effect immediately, no code changes needed.</p>

<?php foreach ($roles as $role): $isSuperAdmin = $role['name'] === 'super_admin'; ?>
<div class="card border-0 shadow-sm mb-3">
<div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0"><?= e(role_label($role['name'])) ?></h2>
        <?php if ($isSuperAdmin): ?>
        <span class="badge text-bg-secondary">Fixed — always full access</span>
        <?php endif; ?>
    </div>

    <?php if ($isSuperAdmin): ?>
        <p class="text-muted small mb-0">The super_admin role always has every permission. This is enforced in code, not just here, so it's structurally impossible to lock every administrator out of the system at once.</p>
    <?php else: ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="save_role_permissions">
        <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
        <div class="row g-2 mb-3">
            <?php foreach ($permissions as $perm): $checked = isset($grants[$role['id']][$perm['id']]); ?>
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="permission_ids[]" value="<?= $perm['id'] ?>"
                           id="perm-<?= $role['id'] ?>-<?= $perm['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="perm-<?= $role['id'] ?>-<?= $perm['id'] ?>">
                        <span class="font-monospace"><?= e($perm['code']) ?></span>
                        <span class="text-muted"> — <?= e($perm['description']) ?></span>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn btn-sm btn-npf-gold">Save</button>
    </form>
    <?php endif; ?>
</div>
</div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
