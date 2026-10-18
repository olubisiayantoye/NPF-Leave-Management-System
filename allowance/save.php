<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator', 'Documentation Officer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . url('/allowance/list.php')); exit; }
verify_csrf();

$pdo = db();
$mode = $_POST['mode'] ?? 'create';
$id = (int)($_POST['id'] ?? 0);

$fields = ['fap_number', 'rank_id', 'full_name', 'account_number', 'bank_id', 'employee_code', 'section', 'status'];
$data = [];
foreach ($fields as $f) {
    $v = trim($_POST[$f] ?? '');
    $data[$f] = ($v === '') ? null : $v;
}
$data['status'] = in_array($data['status'], ['Active', 'Inactive'], true) ? $data['status'] : 'Active';

// --- Validation ---
$errors = [];
if (!$data['fap_number']) $errors[] = 'F-AP Number is required.';
if (!$data['full_name']) $errors[] = 'Full Name is required.';
if (!$data['rank_id']) $errors[] = 'Rank is required.';
if ($data['account_number'] && !preg_match('/^\d{6,20}$/', $data['account_number'])) {
    $errors[] = 'Account Number must be 6-20 digits.';
}

// Duplicate F-AP Number check (defense in depth alongside the DB UNIQUE constraint,
// so we can show a friendly message instead of a raw SQL error)
if ($data['fap_number']) {
    $dupStmt = $pdo->prepare('SELECT id FROM monthly_allowance_personnel WHERE fap_number = ? AND id != ?');
    $dupStmt->execute([$data['fap_number'], $id]);
    if ($dupStmt->fetch()) {
        $errors[] = 'This F-AP Number is already registered to another allowance record.';
    }
}

if ($errors) {
    $_SESSION['_old_allowance_input'] = array_merge($data, ['id' => $id]);
    flash('error', implode(' ', $errors));
    header('Location: ' . url($mode === 'update' ? "/allowance/edit.php?id=$id" : '/allowance/add.php'));
    exit;
}

if ($mode === 'update' && $id) {
    $setSql = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
    $stmt = $pdo->prepare("UPDATE monthly_allowance_personnel SET $setSql WHERE id = :id");
    $stmt->execute($data + ['id' => $id]);

    audit_log('ALLOWANCE_UPDATED', 'monthly_allowance_personnel', $id, "Updated by user #{$_SESSION['user_id']}: {$data['full_name']}");
    flash('success', 'Allowance record updated successfully.');
    header('Location: ' . url("/allowance/view.php?id=$id"));
    exit;

} else {
    try {
        $cols = array_merge($fields, ['created_by']);
        $placeholders = implode(', ', array_map(fn($c) => ":$c", $cols));
        $stmt = $pdo->prepare('INSERT INTO monthly_allowance_personnel (' . implode(', ', $cols) . ") VALUES ($placeholders)");
        $stmt->execute($data + ['created_by' => $_SESSION['user_id']]);
        $newId = $pdo->lastInsertId();

        audit_log('ALLOWANCE_CREATED', 'monthly_allowance_personnel', $newId, "Registered by user #{$_SESSION['user_id']}: {$data['full_name']}");
        flash('success', 'Personnel registered for monthly allowance successfully.');
        header('Location: ' . url("/allowance/view.php?id=$newId"));
        exit;
    } catch (PDOException $e) {
        // Catches the rare race-condition duplicate that slips past the check above
        // (two people saving the same F-AP Number at the same instant).
        $_SESSION['_old_allowance_input'] = array_merge($data, ['id' => $id]);
        flash('error', str_contains($e->getMessage(), 'Duplicate entry')
            ? 'This F-AP Number is already registered to another allowance record.'
            : 'Could not save this record. Please check the details and try again.');
        header('Location: ' . url($mode === 'update' ? "/allowance/edit.php?id=$id" : '/allowance/add.php'));
        exit;
    }
}
