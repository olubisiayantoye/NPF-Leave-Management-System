<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/attachments.php';

require_login();
$user = current_user();

$attachmentId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT la.*, att.original_filename, att.stored_filename, att.mime_type, att.file_size
     FROM leave_attachments att
     JOIN leave_applications la ON la.id = att.leave_application_id
     WHERE att.id = :id'
);
$stmt->execute(['id' => $attachmentId]);
$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    die('Attachment not found.');
}
if (!can_access_attachment($attachment)) {
    http_response_code(403);
    die('You do not have permission to view this attachment.');
}

$path = attachment_storage_path() . '/' . $attachment['stored_filename'];
if (!is_file($path)) {
    http_response_code(404);
    die('That file is missing from storage.');
}

audit_log($user['id'], 'ATTACHMENT_DOWNLOADED', 'leave_attachments', $attachmentId, $attachment['original_filename']);

header('Content-Type: ' . $attachment['mime_type']);
header('Content-Disposition: inline; filename="' . basename($attachment['original_filename']) . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
