<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('personnel.manage');
$user = current_user();

$ranks       = db()->query('SELECT id, name FROM ranks ORDER BY sort_order, name')->fetchAll();
$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$sections    = db()->query('SELECT s.id, s.name, s.department_id FROM sections s ORDER BY s.name')->fetchAll();
$commands    = db()->query('SELECT id, name FROM commands ORDER BY name')->fetchAll();

$errors = [];
$old = $_POST ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $fApNumber   = trim($_POST['f_ap_number'] ?? '');
    $firstName   = trim($_POST['first_name'] ?? '');
    $middleName  = trim($_POST['middle_name'] ?? '') ?: null;
    $surname     = trim($_POST['surname'] ?? '');
    $gender      = $_POST['gender'] ?? '';
    $dob         = $_POST['date_of_birth'] ?? '';
    $rankId      = $_POST['rank_id'] ?? '';
    $departmentId= $_POST['department_id'] ?? '';
    $sectionId   = $_POST['section_id'] ?: null;
    $commandId   = $_POST['command_id'] ?: null;
    $appointDate = $_POST['date_of_first_appointment'] ?? '';
    $phone       = trim($_POST['phone'] ?? '') ?: null;
    $email       = trim($_POST['email'] ?? '') ?: null;

    if ($fApNumber === '')            $errors[] = 'F-AP Number is required.';
    if ($firstName === '')            $errors[] = 'First name is required.';
    if ($surname === '')              $errors[] = 'Surname is required.';
    if (!in_array($gender, ['M','F'], true)) $errors[] = 'Please select a gender.';
    if ($rankId === '')               $errors[] = 'Rank is required.';
    if ($departmentId === '')         $errors[] = 'Department is required.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email address is not valid.';

    if (!$errors) {
        $dupe = db()->prepare('SELECT id FROM personnel WHERE f_ap_number = :f');
        $dupe->execute(['f' => $fApNumber]);
        if ($dupe->fetch()) {
            $errors[] = "F-AP Number {$fApNumber} is already registered.";
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO personnel
                (f_ap_number, first_name, middle_name, surname, gender, date_of_birth,
                 rank_id, department_id, section_id, command_id, date_of_first_appointment,
                 phone, email, status)
             VALUES
                (:f_ap_number, :first_name, :middle_name, :surname, :gender, :dob,
                 :rank_id, :department_id, :section_id, :command_id, :appoint_date,
                 :phone, :email, "active")'
        );
        $stmt->execute([
            'f_ap_number'   => $fApNumber,
            'first_name'    => $firstName,
            'middle_name'   => $middleName,
            'surname'       => $surname,
            'gender'        => $gender,
            'dob'           => $dob ?: null,
            'rank_id'       => $rankId,
            'department_id' => $departmentId,
            'section_id'    => $sectionId,
            'command_id'    => $commandId,
            'appoint_date'  => $appointDate ?: null,
            'phone'         => $phone,
            'email'         => $email,
        ]);

        $newId = (int) db()->lastInsertId();
        audit_log($user['id'], 'PERSONNEL_CREATED', 'personnel', $newId, "Registered {$fApNumber} — {$firstName} {$surname}");

        flash_set('success', "Personnel record for {$firstName} {$surname} ({$fApNumber}) was created.");
        redirect('personnel/view.php?id=' . $newId);
    }
}

$pageTitle = 'Register Personnel';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
<div class="card-body p-4">
<form method="post" action="<?= base_url('personnel/create.php') ?>" novalidate>
    <?= csrf_field() ?>

    <h2 class="h6 mb-3">Personnel Identification</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">F-AP Number *</label>
            <input type="text" name="f_ap_number" class="form-control" required
                   value="<?= e($old['f_ap_number'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Rank *</label>
            <select name="rank_id" class="form-select" required>
                <option value="">Select rank…</option>
                <?php foreach ($ranks as $r): ?>
                <option value="<?= $r['id'] ?>" <?= ($old['rank_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!$ranks): ?><div class="form-text text-danger">No ranks configured yet — add some in Administration → Ranks.</div><?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Gender *</label>
            <select name="gender" class="form-select" required>
                <option value="">Select…</option>
                <option value="M" <?= ($old['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Male</option>
                <option value="F" <?= ($old['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Female</option>
            </select>
        </div>
    </div>

    <h2 class="h6 mb-3">Name</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">First Name *</label>
            <input type="text" name="first_name" class="form-control" required value="<?= e($old['first_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Middle Name</label>
            <input type="text" name="middle_name" class="form-control" value="<?= e($old['middle_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Surname *</label>
            <input type="text" name="surname" class="form-control" required value="<?= e($old['surname'] ?? '') ?>">
        </div>
    </div>

    <h2 class="h6 mb-3">Posting</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Department *</label>
            <select name="department_id" id="department_id" class="form-select" required>
                <option value="">Select department…</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($old['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!$departments): ?><div class="form-text text-danger">No departments configured yet.</div><?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Section</label>
            <select name="section_id" id="section_id" class="form-select">
                <option value="">Select section…</option>
                <?php foreach ($sections as $s): ?>
                <option value="<?= $s['id'] ?>" data-department="<?= $s['department_id'] ?>"
                    <?= ($old['section_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted text-uppercase">Command</label>
            <select name="command_id" class="form-select">
                <option value="">Select command…</option>
                <?php foreach ($commands as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($old['command_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <h2 class="h6 mb-3">Other Details</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label small text-muted text-uppercase">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control" value="<?= e($old['date_of_birth'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted text-uppercase">Date of First Appointment</label>
            <input type="date" name="date_of_first_appointment" class="form-control" value="<?= e($old['date_of_first_appointment'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted text-uppercase">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= e($old['phone'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted text-uppercase">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($old['email'] ?? '') ?>">
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-npf-gold">Save Personnel Record</button>
        <a href="<?= base_url('personnel/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
</div>
</div>

<script>
// Narrow the Section dropdown to the selected Department's sections.
const deptSelect = document.getElementById('department_id');
const sectionSelect = document.getElementById('section_id');
const allSectionOptions = Array.from(sectionSelect.options);

function filterSections() {
    const deptId = deptSelect.value;
    sectionSelect.innerHTML = '';
    sectionSelect.appendChild(allSectionOptions[0].cloneNode(true)); // "Select section…"
    allSectionOptions.forEach(opt => {
        if (opt.dataset.department && opt.dataset.department === deptId) {
            sectionSelect.appendChild(opt.cloneNode(true));
        }
    });
}
deptSelect.addEventListener('change', filterSections);
if (deptSelect.value) filterSections();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
