<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('leave.cancel_curtail');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('leave/index.php');
}
csrf_require();

$id = (int) ($_POST['id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

$stmt = db()->prepare('SELECT * FROM leave_applications WHERE id = :id');
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    flash_set('error', 'Application not found.');
    redirect('leave/index.php');
}
if (!in_array($app['status'], ['pending_soa', 'pending_pso', 'approved'], true)) {
    flash_set('error', 'Only pending or approved applications can be cancelled. This one is ' . $app['status'] . '.');
    redirect('leave/view.php?id=' . $id);
}
if ($reason === '') {
    flash_set('error', 'Please state a reason for cancelling this application.');
    redirect('leave/view.php?id=' . $id);
}

$wasApproved = $app['status'] === 'approved';

$db = db();
$db->beginTransaction();
try {
    // Status changes, application is never deleted (spec §32).
    $db->prepare('UPDATE leave_applications SET status = "cancelled" WHERE id = :id')->execute(['id' => $id]);

    $db->prepare(
        'INSERT INTO leave_cancellations (leave_application_id, reason, cancelled_by) VALUES (:app, :reason, :uid)'
    )->execute(['app' => $id, 'reason' => $reason, 'uid' => $user['id']]);

    // If the officer had already started the leave, return them to active duty.
    if ($wasApproved) {
        $db->prepare('UPDATE personnel SET status = "active" WHERE id = :id')->execute(['id' => $app['personnel_id']]);
        // Credit the entitlement back since the leave never (fully) happened.
        $db->prepare(
            'UPDATE leave_entitlements SET used_days = GREATEST(0, used_days - :days)
             WHERE personnel_id = :p AND leave_type_id = :lt AND year = :y'
        )->execute([
            'days' => $app['days_requested'], 'p' => $app['personnel_id'], 'lt' => $app['leave_type_id'],
            'y' => date('Y', strtotime($app['proceeding_date'])),
        ]);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log('Cancellation failed: ' . $e->getMessage());
    flash_set('error', 'Something went wrong — please try again.');
    redirect('leave/view.php?id=' . $id);
}

audit_log($user['id'], 'LEAVE_CANCELLED', 'leave_applications', $id, "Cancelled {$app['application_no']}: {$reason}");

if ($app['submitted_by'] && (int) $app['submitted_by'] !== $user['id']) {
    notify((int) $app['submitted_by'], "Application {$app['application_no']} was cancelled: {$reason}", 'warning');
}

flash_set('success', 'Application cancelled.');
redirect('leave/view.php?id=' . $id);
