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
$remarks = trim($_POST['remarks'] ?? '') ?: null;

$stmt = db()->prepare('SELECT * FROM leave_applications WHERE id = :id');
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    flash_set('error', 'Application not found.');
    redirect('approvals/pending.php');
}
if ($app['status'] !== 'pending_pso') {
    flash_set('error', 'Only applications forwarded for a final decision can be approved. This one is currently ' . $app['status'] . '.');
    redirect('leave/view.php?id=' . $id);
}
if ($app['assigned_reviewer_id'] && (int) $app['assigned_reviewer_id'] !== (int) $user['id'] && !current_user_has_permission('administration.manage_users')) {
    http_response_code(403);
    die('This application was forwarded to a different PSO.');
}

$db = db();
$db->beginTransaction();

try {
    // 1. Approve the application
    $upd = $db->prepare(
        'UPDATE leave_applications
         SET status = "approved", approved_by = :uid, approved_at = NOW(), approval_remarks = :remarks
         WHERE id = :id'
    );
    $upd->execute(['uid' => $user['id'], 'remarks' => $remarks, 'id' => $id]);

    // 2. Record the approval action in the audit-safe approvals history
    $db->prepare(
        'INSERT INTO leave_approvals (leave_application_id, action, acted_by, remarks) VALUES (:app, "approved", :uid, :remarks)'
    )->execute(['app' => $id, 'uid' => $user['id'], 'remarks' => $remarks]);

    // 3. Track expected resumption (Module 19 — resumption management)
    $db->prepare(
        'INSERT INTO leave_resumptions (leave_application_id, expected_resumption_date) VALUES (:app, :date)
         ON DUPLICATE KEY UPDATE expected_resumption_date = VALUES(expected_resumption_date)'
    )->execute(['app' => $id, 'date' => $app['expected_resumption_date']]);

    // 4. Debit the entitlement balance for this year, if configured
    $db->prepare(
        'UPDATE leave_entitlements SET used_days = used_days + :days
         WHERE personnel_id = :p AND leave_type_id = :lt AND year = :y'
    )->execute([
        'days' => $app['days_requested'],
        'p'    => $app['personnel_id'],
        'lt'   => $app['leave_type_id'],
        'y'    => date('Y', strtotime($app['proceeding_date'])),
    ]);

    // 5. Approval must ALWAYS generate the signal automatically (spec §15, §35)
    $signalRef = next_signal_reference();
    $db->prepare(
        'INSERT INTO signals (leave_application_id, signal_reference, generated_by) VALUES (:app, :ref, :uid)'
    )->execute(['app' => $id, 'ref' => $signalRef, 'uid' => $user['id']]);

    // 6. Mark the officer on leave — but only if the leave has actually
    // started. If it's approved ahead of time (proceeding_date in the
    // future), bin/scheduled_tasks.php flips this exactly on that date
    // instead, so "currently on leave" stays accurate rather than
    // jumping the gun the moment a PSO approves something for next month.
    if (strtotime($app['proceeding_date']) <= strtotime(date('Y-m-d'))) {
        $db->prepare('UPDATE personnel SET status = "on_leave" WHERE id = :id')->execute(['id' => $app['personnel_id']]);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log('Approval failed: ' . $e->getMessage());
    flash_set('error', 'Something went wrong while approving this application. Nothing was changed — please try again.');
    redirect('leave/view.php?id=' . $id);
}

audit_log($user['id'], 'LEAVE_APPROVED', 'leave_applications', $id, "Approved {$app['application_no']}");
audit_log($user['id'], 'SIGNAL_GENERATED', 'signals', null, "Signal {$signalRef} for {$app['application_no']}");

if ($app['submitted_by']) {
    notify((int) $app['submitted_by'], "Application {$app['application_no']} was approved. Signal {$signalRef} has been generated.", 'success');
}

flash_set('success', 'Application approved. The Leave Approval Signal has been generated.');
redirect('leave/view.php?id=' . $id);
