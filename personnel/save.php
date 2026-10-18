<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator', 'Documentation Officer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . url('/personnel/list.php')); exit; }
verify_csrf();

$pdo = db();
$mode = $_POST['mode'] ?? 'create';
$id = (int)($_POST['id'] ?? 0);

/** Upserts (or clears) the two Next of Kin slots for a personnel record. */
function persist_next_of_kin(PDO $pdo, int $personnelId, array $kinInput): void
{
    $upsert = $pdo->prepare(
        'INSERT INTO personnel_next_of_kin
            (personnel_id, kin_number, full_name, relationship, email_address, residential_address, phone_number, alt_phone_number)
         VALUES (:personnel_id, :kin_number, :full_name, :relationship, :email_address, :residential_address, :phone_number, :alt_phone_number)
         ON DUPLICATE KEY UPDATE
            full_name = VALUES(full_name), relationship = VALUES(relationship), email_address = VALUES(email_address),
            residential_address = VALUES(residential_address), phone_number = VALUES(phone_number), alt_phone_number = VALUES(alt_phone_number)'
    );
    $delete = $pdo->prepare('DELETE FROM personnel_next_of_kin WHERE personnel_id = ? AND kin_number = ?');

    foreach ($kinInput as $k => $fields2) {
        if (count(array_filter($fields2)) === 0) {
            $delete->execute([$personnelId, $k]);
            continue;
        }
        $upsert->execute([
            'personnel_id' => $personnelId,
            'kin_number' => $k,
            'full_name' => $fields2['full_name'] ?: null,
            'relationship' => $fields2['relationship'] ?: null,
            'email_address' => $fields2['email_address'] ?: null,
            'residential_address' => $fields2['residential_address'] ?: null,
            'phone_number' => $fields2['phone_number'] ?: null,
            'alt_phone_number' => $fields2['alt_phone_number'] ?: null,
        ]);
    }
}

// Fields matching the personnel table (excluding id/photo/timestamps/created_by)
$fields = [
    'ap_number','full_name','gender','date_of_birth','state_id','lga_id','tribe_id','zone_id','rank_id',
    'date_of_enlistment','date_of_last_promotion','date_of_retirement','department_id','previous_command_id',
    'date_transferred','duty_post','grade_level','employee_code','ippis_number','phone_number','email_address',
    'bank_id','account_number','pfa_id','pension_pin','nhf_number','educational_qualification','gd_sp','mss',
    'remarks','status',
];

$data = [];
foreach ($fields as $f) {
    $v = trim($_POST[$f] ?? '');
    $data[$f] = ($v === '') ? null : $v;
}

// --- Validation ---
$errors = [];
if (!$data['ap_number']) $errors[] = 'AP Number / Service Number is required.';
if (!$data['full_name']) $errors[] = 'Full Name is required.';
if (!in_array($data['gender'], ['Male', 'Female'], true)) $errors[] = 'Gender is required.';

if ($data['email_address'] && !filter_var($data['email_address'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email address is not valid.';
}

// Next of Kin — up to 2 records, each optional as a whole but validated if any field is filled in.
$kinInput = [];
for ($k = 1; $k <= 2; $k++) {
    $kinInput[$k] = [
        'full_name' => trim($_POST["kin{$k}_full_name"] ?? ''),
        'relationship' => trim($_POST["kin{$k}_relationship"] ?? ''),
        'email_address' => trim($_POST["kin{$k}_email_address"] ?? ''),
        'residential_address' => trim($_POST["kin{$k}_residential_address"] ?? ''),
        'phone_number' => trim($_POST["kin{$k}_phone_number"] ?? ''),
        'alt_phone_number' => trim($_POST["kin{$k}_alt_phone_number"] ?? ''),
    ];
    $hasAnyKinField = count(array_filter($kinInput[$k])) > 0;
    if ($hasAnyKinField && !$kinInput[$k]['full_name']) {
        $errors[] = "Next of Kin $k: Full Name is required if any other detail is provided.";
    }
    if ($kinInput[$k]['email_address'] && !filter_var($kinInput[$k]['email_address'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Next of Kin $k: Email address is not valid.";
    }
}

// Duplicate AP number check
$dupStmt = $pdo->prepare('SELECT id FROM personnel WHERE ap_number = ? AND id != ?');
$dupStmt->execute([$data['ap_number'], $id]);
if ($dupStmt->fetch()) {
    $errors[] = 'This AP Number is already registered to another personnel record.';
}

if ($errors) {
    $flatKin = [];
    foreach ($kinInput as $k => $fields2) {
        foreach ($fields2 as $key => $val) $flatKin["kin{$k}_{$key}"] = $val;
    }
    $_SESSION['_old_input'] = array_merge($data, $flatKin, ['id' => $id]);
    flash('error', implode(' ', $errors));
    header('Location: ' . url($mode === 'update' ? "/personnel/edit.php?id=$id" : '/personnel/add.php'));
    exit;
}

// --- Photo upload ---
$photoPath = null;
if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        flash('error', 'Photo must be a JPEG or PNG image.');
        header('Location: ' . url($mode === 'update' ? "/personnel/edit.php?id=$id" : '/personnel/add.php'));
        exit;
    }
    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        flash('error', 'Photo must be under 2MB.');
        header('Location: ' . url($mode === 'update' ? "/personnel/edit.php?id=$id" : '/personnel/add.php'));
        exit;
    }
    $dir = __DIR__ . '/../uploads/photos';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    move_uploaded_file($_FILES['photo']['tmp_name'], "$dir/$filename");
    $photoPath = '/uploads/photos/' . $filename;
}

if ($mode === 'update' && $id) {
    // Detect department change -> auto-log a transfer
    $current = $pdo->prepare('SELECT department_id, photo_path FROM personnel WHERE id = ?');
    $current->execute([$id]);
    $existing = $current->fetch();

    if ($existing && $existing['department_id'] != $data['department_id'] && $data['department_id']) {
        $pdo->prepare(
            'INSERT INTO transfers (personnel_id, from_department_id, to_department_id, transfer_date, approved_by)
             VALUES (?, ?, ?, CURDATE(), ?)'
        )->execute([$id, $existing['department_id'], $data['department_id'], $_SESSION['user_id']]);
        $data['status'] = $data['status'] ?: 'Transferred';
    }

    $setSql = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
    if ($photoPath) $setSql .= ', photo_path = :photo_path';
    $sql = "UPDATE personnel SET $setSql WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $params = $data + ['id' => $id];
    if ($photoPath) $params['photo_path'] = $photoPath;
    $stmt->execute($params);

    persist_next_of_kin($pdo, $id, $kinInput);

    audit_log('PERSONNEL_UPDATED', 'personnel', $id, "Updated by {$_SESSION['user_id']}");
    flash('success', 'Personnel record updated successfully.');
    header('Location: ' . url("/personnel/view.php?id=$id"));
    exit;

} else {
    $cols = array_merge($fields, ['created_by']);
    if ($photoPath) $cols[] = 'photo_path';
    $placeholders = implode(', ', array_map(fn($c) => ":$c", $cols));
    $sql = 'INSERT INTO personnel (' . implode(', ', $cols) . ") VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $params = $data + ['created_by' => $_SESSION['user_id']];
    if ($photoPath) $params['photo_path'] = $photoPath;
    $stmt->execute($params);
    $newId = $pdo->lastInsertId();

    persist_next_of_kin($pdo, (int)$newId, $kinInput);

    audit_log('PERSONNEL_CREATED', 'personnel', $newId, "Created by {$_SESSION['user_id']}");

    // Missing-information notification (common gaps worth flagging)
    $missing = [];
    foreach (['date_of_birth', 'phone_number', 'date_of_enlistment', 'department_id'] as $chk) {
        if (!$data[$chk]) $missing[] = $chk;
    }
    if ($missing) {
        $pdo->prepare('INSERT INTO notifications (type, personnel_id, message) VALUES ("MISSING_INFO", ?, ?)')
            ->execute([$newId, 'Missing fields: ' . implode(', ', $missing)]);
    }
    $pdo->prepare('INSERT INTO notifications (type, personnel_id, message) VALUES ("NEW_PERSONNEL", ?, ?)')
        ->execute([$newId, 'New personnel registered: ' . $data['full_name']]);

    flash('success', 'Personnel registered successfully.');
    header('Location: ' . url("/personnel/view.php?id=$newId"));
    exit;
}
