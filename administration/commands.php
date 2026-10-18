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
        if ($name === '') {
            $errors[] = 'Command name is required.';
        } else {
            try {
                db()->prepare('INSERT INTO commands (name) VALUES (:n)')->execute(['n' => $name]);
                audit_log($user['id'], 'COMMAND_CREATED', 'commands', (int) db()->lastInsertId(), $name);
                flash_set('success', "Command \"{$name}\" added.");
                redirect('administration/commands.php');
            } catch (PDOException $e) {
                $errors[] = str_contains($e->getMessage(), 'Duplicate') ? "Command \"{$name}\" already exists." : 'Could not save the command.';
            }
        }
    } elseif ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $errors[] = 'Command name is required.';
        } else {
            try {
                db()->prepare('UPDATE commands SET name = :n WHERE id = :id')->execute(['n' => $name, 'id' => $id]);
                audit_log($user['id'], 'COMMAND_UPDATED', 'commands', $id, $name);
                flash_set('success', 'Command updated.');
                redirect('administration/commands.php');
            } catch (PDOException $e) {
                $errors[] = str_contains($e->getMessage(), 'Duplicate') ? "Command \"{$name}\" already exists." : 'Could not save the command.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            db()->prepare('DELETE FROM commands WHERE id = :id')->execute(['id' => $id]);
            audit_log($user['id'], 'COMMAND_DELETED', 'commands', $id);
            flash_set('success', 'Command removed.');
        } catch (PDOException $e) {
            flash_set('error', 'That command is in use by existing personnel and cannot be removed.');
        }
        redirect('administration/commands.php');
    }
}

$commands = db()->query('SELECT id, name FROM commands ORDER BY name')->fetchAll();

$pageTitle = 'Commands';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Add Command</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="add">
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Lagos State Command">
                </div>
                <button type="submit" class="btn btn-npf-gold">Add Command</button>
            </form>
        </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
        <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Name</th><th></th></tr></thead>
            <tbody>
            <?php if (!$commands): ?><tr><td colspan="2" class="text-center text-muted py-4">No commands configured yet.</td></tr><?php endif; ?>
            <?php foreach ($commands as $c): $formId = 'cmd-edit-' . $c['id']; ?>
            <tr>
                <td>
                    <form method="post" id="<?= $formId ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="edit">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <input type="text" name="name" class="form-control form-control-sm" value="<?= e($c['name']) ?>" required>
                    </form>
                </td>
                <td class="text-end">
                    <button type="submit" form="<?= $formId ?>" class="btn btn-sm btn-outline-dark">Save</button>
                    <form method="post" onsubmit="return confirm('Remove this command?');" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
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
