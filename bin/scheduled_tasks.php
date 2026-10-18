<?php
/**
 * Scheduled tasks — fills the gap noted in the README: personnel status
 * previously only changed at the moment of approval/resumption-recording,
 * not on the actual calendar date. Run this once a day (00:05 local time
 * is a reasonable choice) and it becomes date-exact.
 *
 * Two ways to run it:
 *
 *   1. Real cron (preferred — works on any host with shell access):
 *        5 0 * * * php /path/to/npf-leave-management/bin/scheduled_tasks.php
 *
 *   2. HTTP trigger (for shared hosting with only a "cron via URL" panel):
 *        Set a secret in Administration → System, or directly via SQL:
 *          UPDATE system_settings SET setting_value = 'some-long-random-string'
 *          WHERE setting_key = 'cron_secret';
 *        Then hit, e.g. via your host's URL-cron feature or plain curl:
 *          https://your-domain/bin/scheduled_tasks.php?token=some-long-random-string
 *
 * Every run is idempotent — running it twice in a row (or twice in one
 * day) does not double-apply anything or send duplicate notifications.
 * Every action it takes is written to audit_logs with a NULL user_id
 * (meaning "the system", not a person).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $stmt = db()->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'cron_secret'");
    $stmt->execute();
    $secret = $stmt->fetchColumn();

    if (!$secret || !hash_equals($secret, $_GET['token'] ?? '')) {
        http_response_code(403);
        die("Not authorized. Set system_settings.cron_secret and pass it as ?token=... to run this over HTTP.\n");
    }
    header('Content-Type: text/plain');
}

function task_log(string $message): void
{
    global $isCli;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    echo $line . ($isCli ? PHP_EOL : "\n");
}

task_log('Scheduled tasks starting.');

// ---------------------------------------------------------------------
// Task 1: sync personnel to on_leave exactly on their proceeding date.
// (Approval already sets this immediately as an approximation — this
// only catches the case where proceeding_date is in the future at the
// time of approval, so the status was correctly left as-is until now.)
// ---------------------------------------------------------------------
$stmt = db()->prepare(
    "SELECT la.id, la.personnel_id, la.application_no
     FROM leave_applications la
     JOIN personnel p ON p.id = la.personnel_id
     WHERE la.status = 'approved'
       AND la.proceeding_date <= CURDATE()
       AND p.status <> 'on_leave'"
);
$stmt->execute();
$toSync = $stmt->fetchAll();

foreach ($toSync as $row) {
    db()->prepare('UPDATE personnel SET status = "on_leave" WHERE id = :id')->execute(['id' => $row['personnel_id']]);
    audit_log(null, 'PERSONNEL_STATUS_AUTO_SYNCED', 'personnel', (int) $row['personnel_id'],
        "Set on_leave — {$row['application_no']} proceeding date reached");
}
task_log(count($toSync) . ' personnel record(s) synced to on_leave.');

// ---------------------------------------------------------------------
// Task 2: notify once when a resumption becomes overdue (expected date
// passed with no actual resumption recorded). Uses a NOT EXISTS guard
// against a matching audit_logs row so this fires exactly once per
// application, no matter how many times the job runs.
// ---------------------------------------------------------------------
$stmt = db()->prepare(
    "SELECT la.id, la.application_no, la.personnel_id, la.submitted_by,
            p.first_name, p.surname, p.f_ap_number
     FROM leave_resumptions lr
     JOIN leave_applications la ON la.id = lr.leave_application_id
     JOIN personnel p ON p.id = la.personnel_id
     WHERE lr.actual_resumption_date IS NULL
       AND lr.expected_resumption_date < CURDATE()
       AND NOT EXISTS (
           SELECT 1 FROM audit_logs al
           WHERE al.action = 'OVERDUE_NOTIFICATION_SENT'
             AND al.record_type = 'leave_applications' AND al.record_id = la.id
       )"
);
$stmt->execute();
$overdue = $stmt->fetchAll();

foreach ($overdue as $row) {
    $name = trim($row['first_name'] . ' ' . $row['surname']);
    $message = "Overdue: {$name} ({$row['f_ap_number']}) has not resumed duty — application {$row['application_no']}.";
    notify_role('administration_officer', $message, 'danger');
    if ($row['submitted_by']) {
        notify((int) $row['submitted_by'], $message, 'danger');
    }
    audit_log(null, 'OVERDUE_NOTIFICATION_SENT', 'leave_applications', (int) $row['id'], $message);
}
task_log(count($overdue) . ' new overdue notification(s) sent.');

// ---------------------------------------------------------------------
// Task 3: remind the I-Admin the day before someone is due back.
// Same NOT EXISTS guard, so this also fires exactly once.
// ---------------------------------------------------------------------
$stmt = db()->prepare(
    "SELECT la.id, la.application_no, la.submitted_by, p.first_name, p.surname, p.f_ap_number
     FROM leave_resumptions lr
     JOIN leave_applications la ON la.id = lr.leave_application_id
     JOIN personnel p ON p.id = la.personnel_id
     WHERE lr.actual_resumption_date IS NULL
       AND lr.expected_resumption_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
       AND NOT EXISTS (
           SELECT 1 FROM audit_logs al
           WHERE al.action = 'RESUMPTION_REMINDER_SENT'
             AND al.record_type = 'leave_applications' AND al.record_id = la.id
       )"
);
$stmt->execute();
$reminders = $stmt->fetchAll();

foreach ($reminders as $row) {
    $name = trim($row['first_name'] . ' ' . $row['surname']);
    $message = "{$name} ({$row['f_ap_number']}) is due to resume duty tomorrow — application {$row['application_no']}.";
    if ($row['submitted_by']) {
        notify((int) $row['submitted_by'], $message, 'warning');
    }
    audit_log(null, 'RESUMPTION_REMINDER_SENT', 'leave_applications', (int) $row['id'], $message);
}
task_log(count($reminders) . ' resumption reminder(s) sent.');

task_log('Scheduled tasks complete.');
