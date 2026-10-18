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
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($name === '') {
            $errors[] = 'Rank name is required.';
        } else {
            try {
                db()->prepare('INSERT INTO ranks (name, sort_order) VALUES (:n, :s)')->execute(['n' => $name, 's' => $sortOrder]);
                audit_log($user['id'], 'RANK_CREATED', 'ranks', (int) db()->lastInsertId(), $name);
                flash_set('success', "Rank \"{$name}\" added.");
                redirect('administration/ranks.php');
            } catch (PDOException $e) {
                $errors[] = str_contains($e->getMessage(), 'Duplicate') ? "Rank \"{$name}\" already exists." : 'Could not save the rank.';
            }
        }
    } elseif ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($name === '') {
            $errors[] = 'Rank name is required.';
        } else {
            try {
                db()->prepare('UPDATE ranks SET name = :n, sort_order = :s WHERE id = :id')
                    ->execute(['n' => $name, 's' => $sortOrder, 'id' => $id]);
                audit_log($user['id'], 'RANK_UPDATED', 'ranks', $id, $name);
                flash_set('success', "Rank updated.");
                redirect('administration/ranks.php');
            } catch (PDOException $e) {
                $errors[] = str_contains($e->getMessage(), 'Duplicate') ? "Rank \"{$name}\" already exists." : 'Could not save the rank.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            db()->prepare('DELETE FROM ranks WHERE id = :id')->execute(['id' => $id]);
            audit_log($user['id'], 'RANK_DELETED', 'ranks', $id);
            flash_set('success', 'Rank removed.');
        } catch (PDOException $e) {
            flash_set('error', 'That rank is in use by existing personnel and cannot be removed.');
        }
        redirect('administration/ranks.php');
    }
}

$ranks = db()->query('SELECT id, name, sort_order FROM ranks ORDER BY sort_order, name')->fetchAll();

$pageTitle = 'Ranks';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Add Rank</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="add">
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="0">
                    <div class="form-text">Lower numbers appear first (e.g. by seniority).</div>
                </div>
                <button type="submit" class="btn btn-npf-gold">Add Rank</button>
            </form>
        </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
        <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Name</th><th>Sort Order</th><th></th></tr></thead>
            <tbody>
            <?php if (!$ranks): ?><tr><td colspan="3" class="text-center text-muted py-4">No ranks configured yet.</td></tr><?php endif; ?>
            <?php foreach ($ranks as $r): $formId = 'rank-edit-' . $r['id']; ?>
            <tr>
                <td>
                    <form method="post" id="<?= $formId ?>" class="d-flex gap-2 align-items-center">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="edit">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="text" name="name" class="form-control form-control-sm" value="<?= e($r['name']) ?>" required>
                    </form>
                </td>
                <td>
                    <input type="number" name="sort_order" form="<?= $formId ?>" class="form-control form-control-sm" style="width:80px;" value="<?= (int)$r['sort_order'] ?>">
                </td>
                <td class="text-end">
                    <button type="submit" form="<?= $formId ?>" class="btn btn-sm btn-outline-dark">Save</button>
                    <form method="post" onsubmit="return confirm('Remove this rank?');" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
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
