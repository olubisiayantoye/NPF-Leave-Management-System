<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('leave.approve');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('approvals/pending.php');
}
csrf_require();

$id = (int) ($_POST['id'] ?? 0);
$remarks = trim($_POST['remarks'] ?? '');

$stmt = db()->prepare('SELECT * FROM leave_applications WHERE id = :id');
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    flash_set('error', 'Application not found.');
    redirect('approvals/pending.php');
}
if ($app['status'] !== 'pending_pso') {
    flash_set('error', 'Only applications forwarded for a final decision can be disapproved. This one is currently ' . $app['status'] . '.');
    redirect('leave/view.php?id=' . $id);
}
if ($app['assigned_reviewer_id'] && (int) $app['assigned_reviewer_id'] !== (int) $user['id'] && !current_user_has_permission('administration.manage_users')) {
    http_response_code(403);
    die('This application was forwarded to a different PSO.');
}
if ($remarks === '') {
    flash_set('error', 'Please state a reason before disapproving an application.');
    redirect('leave/view.php?id=' . $id);
}

$db = db();
$db->beginTransaction();
try {
    $db->prepare(
        'UPDATE leave_applications
         SET status = "disapproved", disapproved_by = :uid, disapproved_at = NOW(), approval_remarks = :remarks
         WHERE id = :id'
    )->execute(['uid' => $user['id'], 'remarks' => $remarks, 'id' => $id]);

    $db->prepare(
        'INSERT INTO leave_approvals (leave_application_id, action, acted_by, remarks) VALUES (:app, "disapproved", :uid, :remarks)'
    )->execute(['app' => $id, 'uid' => $user['id'], 'remarks' => $remarks]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log('Disapproval failed: ' . $e->getMessage());
    flash_set('error', 'Something went wrong — please try again.');
    redirect('leave/view.php?id=' . $id);
}

audit_log($user['id'], 'LEAVE_DISAPPROVED', 'leave_applications', $id, "Disapproved {$app['application_no']}: {$remarks}");

if ($app['submitted_by']) {
    notify((int) $app['submitted_by'], "Application {$app['application_no']} was disapproved: {$remarks}", 'danger');
}

flash_set('info', 'Application disapproved.');
redirect('leave/view.php?id=' . $id);
