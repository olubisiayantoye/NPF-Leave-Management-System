<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/attachments.php';

require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('leave/index.php');
}
csrf_require();

$attachmentId = (int) ($_POST['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT la.*, att.id AS attachment_id, att.original_filename, att.stored_filename
     FROM leave_attachments att
     JOIN leave_applications la ON la.id = att.leave_application_id
     WHERE att.id = :id'
);
$stmt->execute(['id' => $attachmentId]);
$attachment = $stmt->fetch();

if (!$attachment) {
    flash_set('error', 'Attachment not found.');
    redirect('leave/index.php');
}
if (!can_access_attachment($attachment)) {
    http_response_code(403);
    die('You do not have permission to remove this attachment.');
}

$path = attachment_storage_path() . '/' . $attachment['stored_filename'];
db()->prepare('DELETE FROM leave_attachments WHERE id = :id')->execute(['id' => $attachmentId]);
if (is_file($path)) {
    unlink($path);
}

audit_log($user['id'], 'ATTACHMENT_DELETED', 'leave_applications', (int) $attachment['leave_application_id'], $attachment['original_filename']);
flash_set('success', 'Attachment removed.');
redirect('leave/view.php?id=' . $attachment['leave_application_id']);
