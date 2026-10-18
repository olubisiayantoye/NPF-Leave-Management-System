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

$id = (int) ($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM leave_applications WHERE id = :id');
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    flash_set('error', 'Application not found.');
    redirect('leave/index.php');
}
if (!can_access_attachment($app)) {
    http_response_code(403);
    die('You do not have permission to attach files to this application.');
}
if (!in_array($app['status'], ['draft', 'pending_soa', 'pending_pso', 'approved'], true)) {
    flash_set('error', 'Attachments cannot be added to a ' . $app['status'] . ' application.');
    redirect('leave/view.php?id=' . $id);
}
if (empty($_FILES['attachment']['tmp_name'])) {
    flash_set('error', 'Choose a file to attach.');
    redirect('leave/view.php?id=' . $id);
}

try {
    store_attachment($_FILES['attachment'], $id, $user['id']);
    audit_log($user['id'], 'ATTACHMENT_UPLOADED', 'leave_applications', $id,
        'Uploaded ' . basename($_FILES['attachment']['name']));
    flash_set('success', 'Attachment uploaded.');
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
}

redirect('leave/view.php?id=' . $id);
