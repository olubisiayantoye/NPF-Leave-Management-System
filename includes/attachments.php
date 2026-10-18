<?php
/**
 * Leave application attachment handling. Files live in
 * storage/attachments/ (outside any web-servable "assets"-style path,
 * and additionally denied via .htaccess as defense in depth) and are
 * ONLY ever reachable through leave/attachment_download.php, which
 * checks the requester is actually entitled to the file.
 */

const ATTACHMENT_MAX_BYTES = 5 * 1024 * 1024; // 5MB

const ATTACHMENT_ALLOWED_MIME_TYPES = [
    'application/pdf'    => 'pdf',
    'image/jpeg'          => 'jpg',
    'image/png'           => 'png',
    'application/msword'  => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
];

function attachment_storage_path(): string
{
    return dirname(__DIR__) . '/storage/attachments';
}

/**
 * Validates and stores an uploaded file (one entry of $_FILES[...]) for
 * a leave application. Returns the new leave_attachments row id, or
 * throws a RuntimeException with a user-facing message on failure.
 */
function store_attachment(array $fileUpload, int $leaveApplicationId, int $uploadedByUserId): int
{
    if ($fileUpload['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file could not be uploaded — please try again.');
    }
    if ($fileUpload['size'] > ATTACHMENT_MAX_BYTES) {
        throw new RuntimeException('That file is larger than the 5MB limit.');
    }

    // Trust the actual file content over the browser-supplied MIME type.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedType = $finfo->file($fileUpload['tmp_name']);

    if (!isset(ATTACHMENT_ALLOWED_MIME_TYPES[$detectedType])) {
        throw new RuntimeException('Only PDF, JPG, PNG, DOC, or DOCX files are accepted.');
    }

    $extension = ATTACHMENT_ALLOWED_MIME_TYPES[$detectedType];
    $storedFilename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = attachment_storage_path() . '/' . $storedFilename;

    if (!is_dir(attachment_storage_path())) {
        mkdir(attachment_storage_path(), 0750, true);
    }
    if (!move_uploaded_file($fileUpload['tmp_name'], $destination)) {
        throw new RuntimeException('The file could not be saved — please try again.');
    }

    $stmt = db()->prepare(
        'INSERT INTO leave_attachments (leave_application_id, original_filename, stored_filename, mime_type, file_size, uploaded_by)
         VALUES (:app, :orig, :stored, :mime, :size, :uid)'
    );
    $stmt->execute([
        'app'    => $leaveApplicationId,
        'orig'   => mb_substr(basename($fileUpload['name']), 0, 255),
        'stored' => $storedFilename,
        'mime'   => $detectedType,
        'size'   => $fileUpload['size'],
        'uid'    => $uploadedByUserId,
    ]);

    return (int) db()->lastInsertId();
}

/** True if the current user may view/download attachments on this application. */
function can_access_attachment(array $leaveApplication): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    if (current_user_has_permission('leave.approve')) {
        return true; // HODs/admins reviewing the application
    }
    return (int) $leaveApplication['submitted_by'] === (int) $user['id'];
}

function format_file_size(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / (1024 * 1024), 1) . ' MB';
}
