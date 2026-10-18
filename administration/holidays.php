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
        $date = $_POST['holiday_date'] ?? '';
        $name = trim($_POST['name'] ?? '');
        if (!$date)      $errors[] = 'Date is required.';
        if ($name === '') $errors[] = 'Holiday name is required.';

        if (!$errors) {
            try {
                db()->prepare('INSERT INTO public_holidays (holiday_date, name) VALUES (:d, :n)')->execute(['d' => $date, 'n' => $name]);
                audit_log($user['id'], 'PUBLIC_HOLIDAY_CREATED', 'public_holidays', (int) db()->lastInsertId(), "{$name} ({$date})");
                flash_set('success', "\"{$name}\" added to the holiday calendar.");
                redirect('administration/holidays.php');
            } catch (PDOException $e) {
                $errors[] = str_contains($e->getMessage(), 'Duplicate') ? "A holiday is already recorded for {$date}." : 'Could not save the holiday.';
            }
        }
    } elseif ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $date = $_POST['holiday_date'] ?? '';
        $name = trim($_POST['name'] ?? '');
        if (!$date)       $errors[] = 'Date is required.';
        if ($name === '') $errors[] = 'Holiday name is required.';

        if (!$errors) {
            try {
                db()->prepare('UPDATE public_holidays SET holiday_date = :d, name = :n WHERE id = :id')
                    ->execute(['d' => $date, 'n' => $name, 'id' => $id]);
                audit_log($user['id'], 'PUBLIC_HOLIDAY_UPDATED', 'public_holidays', $id, "{$name} ({$date})");
                flash_set('success', 'Holiday updated.');
                redirect('administration/holidays.php');
            } catch (PDOException $e) {
                $errors[] = str_contains($e->getMessage(), 'Duplicate') ? "A holiday is already recorded for {$date}." : 'Could not save the holiday.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM public_holidays WHERE id = :id')->execute(['id' => $id]);
        audit_log($user['id'], 'PUBLIC_HOLIDAY_DELETED', 'public_holidays', $id);
        flash_set('success', 'Holiday removed.');
        redirect('administration/holidays.php');
    }
}

$holidays = db()->query('SELECT id, holiday_date, name FROM public_holidays ORDER BY holiday_date')->fetchAll();

$pageTitle = 'Public Holidays';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<p class="text-muted mb-4">Excluded, along with every Saturday and Sunday, from leave-day calculation when applying for leave.</p>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Add Public Holiday</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="add">
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Date</label>
                    <input type="date" name="holiday_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase">Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Democracy Day">
                </div>
                <button type="submit" class="btn btn-npf-gold">Add Holiday</button>
            </form>
        </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
        <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Date</th><th>Name</th><th></th></tr></thead>
            <tbody>
            <?php if (!$holidays): ?><tr><td colspan="3" class="text-center text-muted py-4">No public holidays configured yet.</td></tr><?php endif; ?>
            <?php foreach ($holidays as $h): $formId = 'hol-edit-' . $h['id']; ?>
            <tr>
                <td style="width:170px;">
                    <form method="post" id="<?= $formId ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="edit">
                        <input type="hidden" name="id" value="<?= $h['id'] ?>">
                        <input type="date" name="holiday_date" class="form-control form-control-sm" value="<?= e($h['holiday_date']) ?>" required>
                    </form>
                </td>
                <td>
                    <input type="text" name="name" form="<?= $formId ?>" class="form-control form-control-sm" value="<?= e($h['name']) ?>" required>
                </td>
                <td class="text-end">
                    <button type="submit" form="<?= $formId ?>" class="btn btn-sm btn-outline-dark">Save</button>
                    <form method="post" onsubmit="return confirm('Remove this holiday?');" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="delete">
                        <input type="hidden" name="id" value="<?= $h['id'] ?>">
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
