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
        $departmentId = $_POST['department_id'] ?? '';
        if ($name === '')          $errors[] = 'Section name is required.';
        if ($departmentId === '')  $errors[] = 'Please select a department.';

        if (!$errors) {
            db()->prepare('INSERT INTO sections (name, department_id) VALUES (:n, :d)')->execute(['n' => $name, 'd' => $departmentId]);
            audit_log($user['id'], 'SECTION_CREATED', 'sections', (int) db()->lastInsertId(), $name);
            flash_set('success', "Section \"{$name}\" added.");
            redirect('administration/sections.php');
        }
    } elseif ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $departmentId = $_POST['department_id'] ?? '';
        if ($name === '')          $errors[] = 'Section name is required.';
        if ($departmentId === '')  $errors[] = 'Please select a department.';

        if (!$errors) {
            db()->prepare('UPDATE sections SET name = :n, department_id = :d WHERE id = :id')
                ->execute(['n' => $name, 'd' => $departmentId, 'id' => $id]);
            audit_log($user['id'], 'SECTION_UPDATED', 'sections', $id, $name);
            flash_set('success', 'Section updated.');
            redirect('administration/sections.php');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            db()->prepare('DELETE FROM sections WHERE id = :id')->execute(['id' => $id]);
            audit_log($user['id'], 'SECTION_DELETED', 'sections', $id);
            flash_set('success', 'Section removed.');
        } catch (PDOException $e) {
            flash_set('error', 'That section is in use by existing personnel and cannot be removed.');
        }
        redirect('administration/sections.php');
    }
}

$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$sections = db()->query(
    'SELECT s.id, s.name, s.department_id, d.name AS department_name
     FROM sections s JOIN departments d ON d.id = s.department_id
     ORDER BY d.name, s.name'
)->fetchAll();

$pageTitle = 'Sections';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Add Section</h2>
            <?php if (!$departments): ?>
                <p class="text-muted small">Add a department first.</p>
            <?php else: ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="add">
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Department</label>
                    <select name="department_id" class="form-select" required>
                        <option value="">Select…</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Section Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-npf-gold">Add Section</button>
            </form>
            <?php endif; ?>
        </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
        <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Section</th><th>Department</th><th></th></tr></thead>
            <tbody>
            <?php if (!$sections): ?><tr><td colspan="3" class="text-center text-muted py-4">No sections configured yet.</td></tr><?php endif; ?>
            <?php foreach ($sections as $s): $formId = 'sec-edit-' . $s['id']; ?>
            <tr>
                <td>
                    <form method="post" id="<?= $formId ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="edit">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <input type="text" name="name" class="form-control form-control-sm" value="<?= e($s['name']) ?>" required>
                    </form>
                </td>
                <td>
                    <select name="department_id" form="<?= $formId ?>" class="form-select form-select-sm">
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (int)$s['department_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="text-end">
                    <button type="submit" form="<?= $formId ?>" class="btn btn-sm btn-outline-dark">Save</button>
                    <form method="post" onsubmit="return confirm('Remove this section?');" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="delete">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
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
