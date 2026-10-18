<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('leave.apply');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('leave/index.php');
}
csrf_require();

$id = (int) ($_POST['id'] ?? 0);
$actualDate = $_POST['actual_resumption_date'] ?? '';

$stmt = db()->prepare('SELECT * FROM leave_applications WHERE id = :id');
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    flash_set('error', 'Application not found.');
    redirect('leave/index.php');
}
if ($app['status'] !== 'approved') {
    flash_set('error', 'Only approved applications can have a resumption recorded.');
    redirect('leave/view.php?id=' . $id);
}
if (!$actualDate) {
    flash_set('error', 'Please provide the actual resumption date.');
    redirect('leave/view.php?id=' . $id);
}

$db = db();
$db->beginTransaction();
try {
    $db->prepare(
        'INSERT INTO leave_resumptions (leave_application_id, expected_resumption_date, actual_resumption_date, recorded_by, recorded_at)
         VALUES (:app, :expected, :actual, :uid, NOW())
         ON DUPLICATE KEY UPDATE actual_resumption_date = VALUES(actual_resumption_date), recorded_by = VALUES(recorded_by), recorded_at = NOW()'
    )->execute([
        'app' => $id, 'expected' => $app['expected_resumption_date'], 'actual' => $actualDate, 'uid' => $user['id'],
    ]);

    $db->prepare('UPDATE leave_applications SET status = "completed" WHERE id = :id')->execute(['id' => $id]);
    $db->prepare('UPDATE personnel SET status = "active" WHERE id = :id')->execute(['id' => $app['personnel_id']]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log('Resumption recording failed: ' . $e->getMessage());
    flash_set('error', 'Something went wrong recording resumption — please try again.');
    redirect('leave/view.php?id=' . $id);
}

$late = strtotime($actualDate) > strtotime($app['expected_resumption_date'] ?? $actualDate);
audit_log($user['id'], 'RESUMPTION_RECORDED', 'leave_applications', $id,
    "Resumed {$actualDate}" . ($late ? ' (later than expected)' : ''));

flash_set('success', 'Resumption recorded. The officer is now marked active.');
redirect('leave/view.php?id=' . $id);
