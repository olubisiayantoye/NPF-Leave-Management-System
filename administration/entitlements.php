<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('administration.manage');
$user = current_user();
$errors = [];
$bulkResult = null;

$leaveTypes = db()->query('SELECT id, name FROM leave_types WHERE is_active = 1 ORDER BY name')->fetchAll();
$currentYear = (int) date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['form_action'] ?? '';

    // -------------------------------------------------------------
    // Bulk initialize — e.g. "give everyone 30 Annual Leave days for 2026"
    // -------------------------------------------------------------
    if ($action === 'bulk_init') {
        $year = (int) ($_POST['bulk_year'] ?? 0);
        $leaveTypeId = (int) ($_POST['bulk_leave_type_id'] ?? 0);
        $entitledDays = (int) ($_POST['bulk_entitled_days'] ?? 0);
        $overwrite = isset($_POST['bulk_overwrite']);
        $onlyActive = isset($_POST['bulk_only_active']);

        if ($year < 2000 || $year > 2100) $errors[] = 'Enter a valid year.';
        if (!$leaveTypeId)                $errors[] = 'Select a leave type.';
        if ($entitledDays < 0)            $errors[] = 'Entitled days cannot be negative.';

        if (!$errors) {
            $db = db();
            $statusFilter = $onlyActive ? "AND status <> 'retired'" : '';

            if ($overwrite) {
                // Update existing rows, then insert whichever personnel don't have one yet.
                $db->prepare(
                    "UPDATE leave_entitlements le
                     JOIN personnel p ON p.id = le.personnel_id
                     SET le.entitled_days = :days
                     WHERE le.leave_type_id = :lt AND le.year = :y {$statusFilter}"
                )->execute(['days' => $entitledDays, 'lt' => $leaveTypeId, 'y' => $year]);
            }

            $insertStmt = $db->prepare(
                "INSERT INTO leave_entitlements (personnel_id, leave_type_id, year, entitled_days, used_days)
                 SELECT p.id, :lt, :y, :days, 0
                 FROM personnel p
                 WHERE 1=1 {$statusFilter}
                   AND NOT EXISTS (
                       SELECT 1 FROM leave_entitlements le2
                       WHERE le2.personnel_id = p.id AND le2.leave_type_id = :lt2 AND le2.year = :y2
                   )"
            );
            $insertStmt->execute([
                'lt' => $leaveTypeId, 'y' => $year, 'days' => $entitledDays,
                'lt2' => $leaveTypeId, 'y2' => $year,
            ]);
            $created = $insertStmt->rowCount();

            audit_log($user['id'], 'ENTITLEMENTS_BULK_SET', 'leave_entitlements', null,
                "Year {$year}, leave type #{$leaveTypeId}, {$entitledDays} days — {$created} created" . ($overwrite ? ', existing rows overwritten' : ''));

            $bulkResult = ['created' => $created, 'overwritten' => $overwrite];
        }
    }

    // -------------------------------------------------------------
    // Single officer set/override
    // -------------------------------------------------------------
    elseif ($action === 'set_individual') {
        $personnelId = (int) ($_POST['personnel_id'] ?? 0);
        $year = (int) ($_POST['year'] ?? 0);
        $leaveTypeId = (int) ($_POST['leave_type_id'] ?? 0);
        $entitledDays = (int) ($_POST['entitled_days'] ?? 0);
        $usedDays = (int) ($_POST['used_days'] ?? 0);

        if (!$personnelId) $errors[] = 'Select a personnel record first (search below).';
        if ($year < 2000 || $year > 2100) $errors[] = 'Enter a valid year.';
        if (!$leaveTypeId) $errors[] = 'Select a leave type.';
        if ($entitledDays < 0 || $usedDays < 0) $errors[] = 'Days cannot be negative.';

        if (!$errors) {
            db()->prepare(
                'INSERT INTO leave_entitlements (personnel_id, leave_type_id, year, entitled_days, used_days)
                 VALUES (:p, :lt, :y, :entitled, :used)
                 ON DUPLICATE KEY UPDATE entitled_days = VALUES(entitled_days), used_days = VALUES(used_days)'
            )->execute(['p' => $personnelId, 'lt' => $leaveTypeId, 'y' => $year, 'entitled' => $entitledDays, 'used' => $usedDays]);

            audit_log($user['id'], 'ENTITLEMENT_SET', 'leave_entitlements', $personnelId,
                "Personnel #{$personnelId}, year {$year}, leave type #{$leaveTypeId}: entitled {$entitledDays}, used {$usedDays}");

            flash_set('success', 'Entitlement saved.');
            redirect('administration/entitlements.php?personnel_id=' . $personnelId . '&year=' . $year);
        }
    }
}

// Personnel search + selected officer's current entitlements, for the individual-override panel
$q = trim($_GET['q'] ?? '');
$searchResults = $q !== '' ? search_personnel($q, 15) : [];

$selectedPersonnelId = (int) ($_GET['personnel_id'] ?? 0);
$selectedYear = (int) ($_GET['year'] ?? $currentYear);
$selectedPerson = null;
$selectedEntitlements = [];

if ($selectedPersonnelId) {
    $pStmt = db()->prepare(
        'SELECT p.id, p.f_ap_number, p.first_name, p.surname, r.name AS rank_name, d.name AS department_name
         FROM personnel p JOIN ranks r ON r.id = p.rank_id JOIN departments d ON d.id = p.department_id
         WHERE p.id = :id'
    );
    $pStmt->execute(['id' => $selectedPersonnelId]);
    $selectedPerson = $pStmt->fetch();

    if ($selectedPerson) {
        $eStmt = db()->prepare(
            'SELECT lt.id AS leave_type_id, lt.name AS leave_type_name, le.entitled_days, le.used_days
             FROM leave_types lt
             LEFT JOIN leave_entitlements le ON le.leave_type_id = lt.id AND le.personnel_id = :p AND le.year = :y
             WHERE lt.is_active = 1
             ORDER BY lt.name'
        );
        $eStmt->execute(['p' => $selectedPersonnelId, 'y' => $selectedYear]);
        $selectedEntitlements = $eStmt->fetchAll();
    }
}

$pageTitle = 'Leave Entitlements';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($bulkResult): ?>
<div class="alert alert-success">
    Done — <strong><?= $bulkResult['created'] ?></strong> new entitlement record(s) created<?= $bulkResult['overwritten'] ? ', and existing records for that year/type were overwritten with the new value' : ' (existing records for that year/type were left untouched)' ?>.
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
            <h2 class="h6 mb-1">Set for Everyone (Year Rollover)</h2>
            <p class="text-muted small mb-3">Use this at the start of a new leave year — e.g. give every active officer 30 Annual Leave days for 2026 in one go. Personnel who already have a record for that year/type are skipped unless you tick "overwrite".</p>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="bulk_init">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase">Year</label>
                        <input type="number" name="bulk_year" class="form-control" value="<?= $currentYear + 1 ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase">Leave Type</label>
                        <select name="bulk_leave_type_id" class="form-select" required>
                            <option value="">Select…</option>
                            <?php foreach ($leaveTypes as $lt): ?>
                            <option value="<?= $lt['id'] ?>"><?= e($lt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase">Entitled Days</label>
                        <input type="number" min="0" name="bulk_entitled_days" class="form-control" value="30" required>
                    </div>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="bulk_only_active" id="bulk_only_active" checked>
                    <label class="form-check-label small" for="bulk_only_active">Skip retired personnel</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="bulk_overwrite" id="bulk_overwrite">
                    <label class="form-check-label small" for="bulk_overwrite">Overwrite existing records for this year/type (used-days figures are left alone)</label>
                </div>
                <button type="submit" class="btn btn-npf-gold" onclick="return confirm('Apply this entitlement to every matching officer?');">Apply to Everyone</button>
            </form>
        </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
            <h2 class="h6 mb-1">Set for One Officer</h2>
            <p class="text-muted small mb-3">For exceptions — a new joiner mid-year, a manual correction, or a one-off adjustment. Search for the officer below.</p>
            <form method="get" action="entitlements.php" class="d-flex gap-2 mb-3">
                <input type="text" name="q" class="form-control" placeholder="F-AP number or name" value="<?= e($q) ?>">
                <button class="btn btn-outline-dark" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <?php if ($searchResults): ?>
            <div class="list-group">
                <?php foreach ($searchResults as $p): ?>
                <a href="?personnel_id=<?= $p['id'] ?>&year=<?= $selectedYear ?>" class="list-group-item list-group-item-action small">
                    <?= e($p['name']) ?> <span class="text-muted">(<?= e($p['f_ap_number']) ?> — <?= e($p['rank']) ?>, <?= e($p['department']) ?>)</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php elseif ($q !== ''): ?>
            <p class="text-muted small">No matches.</p>
            <?php endif; ?>
        </div>
        </div>
    </div>
</div>

<?php if ($selectedPerson): ?>
<div class="card border-0 shadow-sm">
<div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h6 mb-1"><?= e($selectedPerson['first_name'] . ' ' . $selectedPerson['surname']) ?> <span class="text-muted small font-monospace">(<?= e($selectedPerson['f_ap_number']) ?>)</span></h2>
            <p class="text-muted small mb-0"><?= e($selectedPerson['rank_name']) ?> · <?= e($selectedPerson['department_name']) ?></p>
        </div>
        <form method="get" class="d-flex gap-2 align-items-end">
            <input type="hidden" name="personnel_id" value="<?= $selectedPerson['id'] ?>">
            <div>
                <label class="form-label small text-muted text-uppercase mb-1">Year</label>
                <input type="number" name="year" class="form-control form-control-sm" value="<?= $selectedYear ?>" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Leave Type</th><th>Entitled</th><th>Used</th><th>Balance</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($selectedEntitlements as $row): $entitled = (int)($row['entitled_days'] ?? 0); $used = (int)($row['used_days'] ?? 0); $formId = 'ent-form-' . $row['leave_type_id']; ?>
        <tr>
            <td><?= e($row['leave_type_name']) ?></td>
            <td style="width:100px;"><input type="number" min="0" name="entitled_days" form="<?= $formId ?>" class="form-control form-control-sm" value="<?= $entitled ?>"></td>
            <td style="width:100px;"><input type="number" min="0" name="used_days" form="<?= $formId ?>" class="form-control form-control-sm" value="<?= $used ?>"></td>
            <td class="<?= ($entitled - $used) <= 0 ? 'text-danger fw-semibold' : '' ?>"><?= $entitled - $used ?></td>
            <td>
                <form method="post" id="<?= $formId ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="set_individual">
                    <input type="hidden" name="personnel_id" value="<?= $selectedPerson['id'] ?>">
                    <input type="hidden" name="year" value="<?= $selectedYear ?>">
                    <input type="hidden" name="leave_type_id" value="<?= $row['leave_type_id'] ?>">
                </form>
                <button type="submit" form="<?= $formId ?>" class="btn btn-sm btn-outline-dark">Save</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
