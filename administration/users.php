<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Only super_admin manages accounts/roles — a plain administrator granting
// itself super_admin would be a privilege-escalation hole.
require_permission('administration.manage_users');
$user = current_user();
$errors = [];

$roles = db()->query('SELECT id, name, description FROM roles ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '') ?: null;
        $password = (string) ($_POST['password'] ?? '');
        $roleId   = (int) ($_POST['role_id'] ?? 0);

        if ($username === '')        $errors[] = 'Username is required.';
        if ($fullName === '')        $errors[] = 'Full name is required.';
        if (strlen($password) < 10)  $errors[] = 'Password must be at least 10 characters.';
        if (!$roleId)                $errors[] = 'Select a role for this user.';
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email address is not valid.';

        if (!$errors) {
            $dupe = db()->prepare('SELECT id FROM users WHERE username = :u');
            $dupe->execute(['u' => $username]);
            if ($dupe->fetch()) {
                $errors[] = "Username \"{$username}\" is already taken.";
            }
        }

        if (!$errors) {
            $db = db();
            $db->beginTransaction();
            try {
                $db->prepare('INSERT INTO users (username, email, password_hash, full_name, status) VALUES (:u, :e, :p, :f, "active")')
                   ->execute([
                       'u' => $username, 'e' => $email,
                       'p' => password_hash($password, PASSWORD_DEFAULT),
                       'f' => $fullName,
                   ]);
                $newId = (int) $db->lastInsertId();
                $db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:u, :r)')->execute(['u' => $newId, 'r' => $roleId]);
                $db->commit();

                audit_log($user['id'], 'USER_CREATED', 'users', $newId, "Created {$username} ({$fullName})");
                flash_set('success', "Account for {$fullName} created.");
                redirect('administration/users.php');
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Could not create the account — please try again.';
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === $user['id']) {
            flash_set('error', 'You cannot deactivate your own account.');
        } else {
            $stmt = db()->prepare('SELECT status FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $current = $stmt->fetchColumn();
            $new = $current === 'active' ? 'inactive' : 'active';
            db()->prepare('UPDATE users SET status = :s WHERE id = :id')->execute(['s' => $new, 'id' => $id]);
            audit_log($user['id'], 'USER_STATUS_CHANGED', 'users', $id, "Set to {$new}");
            flash_set('success', 'Account status updated.');
        }
        redirect('administration/users.php');

    } elseif ($action === 'change_role') {
        $id = (int) ($_POST['id'] ?? 0);
        $roleId = (int) ($_POST['role_id'] ?? 0);

        if ($id === $user['id']) {
            flash_set('error', 'You cannot change your own role.');
        } elseif (!$roleId) {
            flash_set('error', 'Select a role.');
        } else {
            $db = db();
            $db->beginTransaction();
            $db->prepare('DELETE FROM user_roles WHERE user_id = :id')->execute(['id' => $id]);
            $db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:u, :r)')->execute(['u' => $id, 'r' => $roleId]);
            $db->commit();
            audit_log($user['id'], 'USER_ROLE_CHANGED', 'users', $id, "Role changed to role #{$roleId}");
            flash_set('success', "Role updated.");
        }
        redirect('administration/users.php');

    } elseif ($action === 'reset_password') {
        $id = (int) ($_POST['id'] ?? 0);
        $newPassword = (string) ($_POST['new_password'] ?? '');

        if (strlen($newPassword) < 10) {
            flash_set('error', 'Temporary password must be at least 10 characters.');
        } else {
            db()->prepare(
                'UPDATE users SET password_hash = :h, failed_login_attempts = 0, locked_until = NULL, status = "active" WHERE id = :id'
            )->execute(['h' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $id]);
            audit_log($user['id'], 'USER_PASSWORD_RESET', 'users', $id, 'Password reset by administrator');
            flash_set('success', 'Password reset. Share the new temporary password with the user securely — it is not shown again.');
        }
        redirect('administration/users.php');

    } elseif ($action === 'disable_mfa') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE users SET mfa_secret = NULL, mfa_enabled = 0 WHERE id = :id')->execute(['id' => $id]);
        audit_log($user['id'], 'MFA_DISABLED_BY_ADMIN', 'users', $id, 'Two-factor authentication disabled by administrator');
        flash_set('success', 'Two-factor authentication disabled for that account.');
        redirect('administration/users.php');
    }
}

$users = db()->query(
    "SELECT u.id, u.username, u.full_name, u.email, u.status, u.last_login_at, u.mfa_enabled,
            GROUP_CONCAT(r.name SEPARATOR ', ') AS role_names,
            MIN(r.id) AS role_id
     FROM users u
     LEFT JOIN user_roles ur ON ur.user_id = u.id
     LEFT JOIN roles r ON r.id = ur.role_id
     GROUP BY u.id
     ORDER BY u.full_name"
)->fetchAll();

$pageTitle = 'Users & Roles';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Create Account</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="create">
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Temporary Password</label>
                    <input type="text" name="password" class="form-control" required minlength="10">
                    <div class="form-text">At least 10 characters. Share this securely — the user should change it on first login (self-service password change is a Phase 2 item).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Role</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Select role…</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= e(role_label($r['name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-npf-gold">Create Account</button>
            </form>
        </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
        <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Name</th><th>Username</th><th>Role(s)</th><th>Status</th><th>2FA</th><th>Last Login</th><th></th></tr></thead>
            <tbody>
            <?php if (!$users): ?><tr><td colspan="7" class="text-center text-muted py-4">No accounts yet.</td></tr><?php endif; ?>
            <?php foreach ($users as $u): $isSelf = (int)$u['id'] === $user['id']; ?>
            <tr>
                <td><?= e($u['full_name']) ?></td>
                <td class="font-monospace small"><?= e($u['username']) ?></td>
                <td class="small"><?= e($u['role_names'] ? implode(', ', array_map('role_label', explode(', ', $u['role_names']))) : '—') ?></td>
                <td><span class="badge text-bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($u['status']) ?></span></td>
                <td><?= $u['mfa_enabled'] ? '<i class="bi bi-shield-check text-success" title="2FA enabled"></i>' : '<span class="text-muted small">—</span>' ?></td>
                <td class="small text-muted"><?= e($u['last_login_at'] ?? 'Never') ?></td>
                <td class="text-end">
                    <?php if ($isSelf): ?>
                    <span class="text-muted small">You</span>
                    <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="collapse" data-bs-target="#manage-<?= $u['id'] ?>">Manage</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if (!$isSelf): ?>
            <tr class="collapse" id="manage-<?= $u['id'] ?>">
                <td colspan="7" class="bg-light">
                    <div class="row g-4 py-2">
                        <div class="col-md-4">
                            <form method="post" class="d-flex gap-2 align-items-end">
                                <?= csrf_field() ?>
                                <input type="hidden" name="form_action" value="change_role">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <div class="flex-grow-1">
                                    <label class="form-label small text-muted text-uppercase mb-1">Role</label>
                                    <select name="role_id" class="form-select form-select-sm">
                                        <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= (int)$u['role_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= e(role_label($r['name'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-sm btn-outline-dark">Save</button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <form method="post" class="d-flex gap-2 align-items-end">
                                <?= csrf_field() ?>
                                <input type="hidden" name="form_action" value="reset_password">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <div class="flex-grow-1">
                                    <label class="form-label small text-muted text-uppercase mb-1">New Temporary Password</label>
                                    <input type="text" name="new_password" class="form-control form-control-sm" minlength="10" placeholder="min. 10 characters" required>
                                </div>
                                <button type="submit" class="btn btn-sm btn-outline-dark" onclick="return confirm('Reset this user\'s password?');">Reset</button>
                            </form>
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <?php if ($u['mfa_enabled']): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="form_action" value="disable_mfa">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Disable 2FA for this user? Use this if they\'ve lost their authenticator device.');">Disable Their 2FA</button>
                            </form>
                            <?php endif; ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="form_action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                    <?= $u['status'] === 'active' ? 'Deactivate' : 'Reactivate' ?> Account
                                </button>
                            </form>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
