<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('administration.manage');
$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '') ?: null;

        if ($name === '') $errors[] = 'Leave type name is required.';
        if ($code === '')  $errors[] = 'A short code is required (e.g. ANNUAL).';

        if (!$errors) {
            try {
                db()->prepare('INSERT INTO leave_types (name, code, description, is_active) VALUES (:n, :c, :d, 1)')
                    ->execute(['n' => $name, 'c' => $code, 'd' => $description]);
                audit_log($user['id'], 'LEAVE_TYPE_CREATED', 'leave_types', (int) db()->lastInsertId(), $name);
                flash_set('success', "Leave type \"{$name}\" added.");
                redirect('administration/leave_types.php');
            } catch (PDOException $e) {
                $errors[] = 'That name or code is already in use.';
            }
        }
    } elseif ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '') ?: null;

        if ($name === '') $errors[] = 'Leave type name is required.';
        if ($code === '')  $errors[] = 'A short code is required.';

        if (!$errors) {
            try {
                db()->prepare('UPDATE leave_types SET name = :n, code = :c, description = :d WHERE id = :id')
                    ->execute(['n' => $name, 'c' => $code, 'd' => $description, 'id' => $id]);
                audit_log($user['id'], 'LEAVE_TYPE_UPDATED', 'leave_types', $id, $name);
                flash_set('success', 'Leave type updated.');
                redirect('administration/leave_types.php');
            } catch (PDOException $e) {
                $errors[] = 'That name or code is already in use.';
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE leave_types SET is_active = NOT is_active WHERE id = :id')->execute(['id' => $id]);
        audit_log($user['id'], 'LEAVE_TYPE_TOGGLED', 'leave_types', $id);
        redirect('administration/leave_types.php');
    }
}

$leaveTypes = db()->query('SELECT id, name, code, description, is_active FROM leave_types ORDER BY name')->fetchAll();

$pageTitle = 'Leave Types';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Add Leave Type</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="add">
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Code</label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. SPECIAL" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-npf-gold">Add Leave Type</button>
            </form>
        </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
        <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($leaveTypes as $lt): ?>
            <tr>
                <td>
                    <?= e($lt['name']) ?>
                    <?php if ($lt['description']): ?><div class="small text-muted"><?= e($lt['description']) ?></div><?php endif; ?>
                </td>
                <td class="font-monospace small"><?= e($lt['code']) ?></td>
                <td><span class="badge text-bg-<?= $lt['is_active'] ? 'success' : 'secondary' ?>"><?= $lt['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="collapse" data-bs-target="#lt-edit-<?= $lt['id'] ?>">Edit</button>
                    <form method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="toggle">
                        <input type="hidden" name="id" value="<?= $lt['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-dark">
                            <?= $lt['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                </td>
            </tr>
            <tr class="collapse" id="lt-edit-<?= $lt['id'] ?>">
                <td colspan="4" class="bg-light">
                    <form method="post" class="row g-2 align-items-end py-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="edit">
                        <input type="hidden" name="id" value="<?= $lt['id'] ?>">
                        <div class="col-md-3">
                            <label class="form-label small text-muted text-uppercase mb-1">Name</label>
                            <input type="text" name="name" class="form-control form-control-sm" value="<?= e($lt['name']) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted text-uppercase mb-1">Code</label>
                            <input type="text" name="code" class="form-control form-control-sm" value="<?= e($lt['code']) ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small text-muted text-uppercase mb-1">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm" value="<?= e($lt['description'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-npf-gold w-100">Save</button>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
