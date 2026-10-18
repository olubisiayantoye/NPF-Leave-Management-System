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
$newResumptionDate = $_POST['new_resumption_date'] ?? '';
$reason = trim($_POST['reason'] ?? '');

$stmt = db()->prepare('SELECT * FROM leave_applications WHERE id = :id');
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    flash_set('error', 'Application not found.');
    redirect('leave/index.php');
}
if ($app['status'] !== 'approved') {
    flash_set('error', 'Only approved (currently active) leave can be curtailed.');
    redirect('leave/view.php?id=' . $id);
}
if (!$newResumptionDate || !$reason) {
    flash_set('error', 'Please provide both the new resumption date and a reason.');
    redirect('leave/view.php?id=' . $id);
}
if (strtotime($newResumptionDate) >= strtotime($app['expected_resumption_date'] ?? '9999-12-31')) {
    flash_set('error', 'The curtailed resumption date must be earlier than the originally approved date.');
    redirect('leave/view.php?id=' . $id);
}

$db = db();
$db->beginTransaction();
try {
    $db->prepare('UPDATE leave_applications SET status = "curtailed" WHERE id = :id')->execute(['id' => $id]);

    $db->prepare(
        'INSERT INTO leave_curtailments (leave_application_id, new_resumption_date, reason, curtailed_by)
         VALUES (:app, :date, :reason, :uid)'
    )->execute(['app' => $id, 'date' => $newResumptionDate, 'reason' => $reason, 'uid' => $user['id']]);

    $db->prepare(
        'INSERT INTO leave_resumptions (leave_application_id, expected_resumption_date, actual_resumption_date, recorded_by, recorded_at)
         VALUES (:app, :expected, :actual, :uid, NOW())
         ON DUPLICATE KEY UPDATE actual_resumption_date = VALUES(actual_resumption_date), recorded_by = VALUES(recorded_by), recorded_at = NOW()'
    )->execute(['app' => $id, 'expected' => $app['expected_resumption_date'], 'actual' => $newResumptionDate, 'uid' => $user['id']]);

    $db->prepare('UPDATE personnel SET status = "active" WHERE id = :id')->execute(['id' => $app['personnel_id']]);

    // Credit back the unused days.
    $originalDays = (int) $app['days_requested'];
    $actualDaysUsed = (int) ((strtotime($newResumptionDate) - strtotime($app['proceeding_date'])) / 86400);
    $daysCredited = max(0, $originalDays - $actualDaysUsed);
    if ($daysCredited > 0) {
        $db->prepare(
            'UPDATE leave_entitlements SET used_days = GREATEST(0, used_days - :days)
             WHERE personnel_id = :p AND leave_type_id = :lt AND year = :y'
        )->execute([
            'days' => $daysCredited, 'p' => $app['personnel_id'], 'lt' => $app['leave_type_id'],
            'y' => date('Y', strtotime($app['proceeding_date'])),
        ]);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log('Curtailment failed: ' . $e->getMessage());
    flash_set('error', 'Something went wrong — please try again.');
    redirect('leave/view.php?id=' . $id);
}

audit_log($user['id'], 'LEAVE_CURTAILED', 'leave_applications', $id, "Curtailed {$app['application_no']} — resumed {$newResumptionDate}: {$reason}");

if ($app['submitted_by'] && (int) $app['submitted_by'] !== $user['id']) {
    notify((int) $app['submitted_by'], "Application {$app['application_no']} was curtailed — resumption recorded as {$newResumptionDate}.", 'warning');
}

flash_set('success', 'Leave curtailed and resumption recorded.');
redirect('leave/view.php?id=' . $id);
