<?php
/**
 * General-purpose helpers used across the app.
 */

require_once __DIR__ . '/mailer.php';

/** Shorthand for htmlspecialchars() with sane defaults. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Human-readable role name — special-cases the abbreviations (I-Admin, SOA, PSO I/II) that ucwords() alone gets wrong. */
function role_label(string $roleName): string
{
    $labels = [
        'super_admin'            => 'Super Admin',
        'administrator'          => 'Administrator',
        'i_admin'                => 'I-Admin',
        'administration_officer' => 'Administration Officer (SOA)',
        'pso_i'                  => 'PSO I',
        'pso_ii'                 => 'PSO II',
        'read_only'              => 'Read Only',
    ];
    return $labels[$roleName] ?? ucwords(str_replace('_', ' ', $roleName));
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

/** One-time "flash" message shown after a redirect (e.g. after login/logout). */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Record an entry in the audit trail. Every meaningful state change in the
 * app should call this — approvals, disapprovals, signal prints, personnel
 * edits, logins, etc. Per spec: audit records are never deleted or edited.
 */
function audit_log(?int $userId, string $action, ?string $recordType = null, ?int $recordId = null, ?string $description = null): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO audit_logs (user_id, action, record_type, record_id, ip_address, description)
             VALUES (:user_id, :action, :record_type, :record_id, :ip, :description)'
        );
        $stmt->execute([
            'user_id'     => $userId,
            'action'      => $action,
            'record_type' => $recordType,
            'record_id'   => $recordId,
            'ip'          => client_ip(),
            'description' => $description,
        ]);
    } catch (PDOException $e) {
        // Auditing must never take the app down — log and move on.
        error_log('audit_log() failed: ' . $e->getMessage());
    }
}

/** Generates the next application number, e.g. NPF-LV-2026-000001 */
function next_application_no(): string
{
    $year = date('Y');
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM leave_applications WHERE application_no LIKE :prefix"
    );
    $prefix = "NPF-LV-{$year}-%";
    $stmt->execute(['prefix' => $prefix]);
    $count = (int) $stmt->fetchColumn() + 1;
    return sprintf('NPF-LV-%s-%06d', $year, $count);
}

/** Generates the next signal reference, e.g. NPF-SGL-2026-000001 */
function next_signal_reference(): string
{
    $year = date('Y');
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM signals WHERE signal_reference LIKE :prefix"
    );
    $prefix = "NPF-SGL-{$year}-%";
    $stmt->execute(['prefix' => $prefix]);
    $count = (int) $stmt->fetchColumn() + 1;
    return sprintf('NPF-SGL-%s-%06d', $year, $count);
}

/**
 * Shared personnel search — by F-AP Number, Name, Rank, or Department
 * (spec §5). Used by both the JSON API (for live search) and the
 * search page's own server-rendered fallback, so the two can never
 * drift out of sync with each other.
 */
function search_personnelERROR(string $q, int $limit = 20): array
{
    $q = trim($q);
    if (mb_strlen($q) < 2) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT p.id, p.f_ap_number, p.first_name, p.middle_name, p.surname,
                r.name AS rank_name, d.name AS department_name, p.status
         FROM personnel p
         JOIN ranks r ON r.id = p.rank_id
         JOIN departments d ON d.id = p.department_id
         WHERE p.f_ap_number LIKE :q
            OR p.first_name LIKE :q
            OR p.surname LIKE :q
            OR r.name LIKE :q
            OR d.name LIKE :q
         ORDER BY p.surname, p.first_name
         LIMIT ' . (int) $limit
    );
    $stmt->execute(['q' => '%' . $q . '%']);

    return array_map(static function (array $p): array {
        return [
            'id'          => (int) $p['id'],
            'f_ap_number' => $p['f_ap_number'],
            'name'        => trim($p['first_name'] . ' ' . ($p['middle_name'] ? $p['middle_name'] . ' ' : '') . $p['surname']),
            'rank'        => $p['rank_name'],
            'department'  => $p['department_name'],
            'status'      => $p['status'],
        ];
    }, $stmt->fetchAll());
}

function search_personnel(string $q, int $limit = 20): array
{
    $q = trim($q);

    if (mb_strlen($q) < 2) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT p.id, p.f_ap_number, p.first_name, p.middle_name, p.surname,
                r.name AS rank_name, d.name AS department_name, p.status
         FROM personnel p
         JOIN ranks r ON r.id = p.rank_id
         JOIN departments d ON d.id = p.department_id
         WHERE p.f_ap_number LIKE :q1
            OR p.first_name LIKE :q2
            OR p.surname LIKE :q3
            OR r.name LIKE :q4
            OR d.name LIKE :q5
         ORDER BY p.surname, p.first_name
         LIMIT ' . (int) $limit
    );

    $search = '%' . $q . '%';

    $stmt->execute([
        'q1' => $search,
        'q2' => $search,
        'q3' => $search,
        'q4' => $search,
        'q5' => $search,
    ]);

    return array_map(static function (array $p): array {
        return [
            'id'          => (int) $p['id'],
            'f_ap_number' => $p['f_ap_number'],
            'name'        => trim(
                $p['first_name'] . ' ' .
                ($p['middle_name'] ? $p['middle_name'] . ' ' : '') .
                $p['surname']
            ),
            'rank'        => $p['rank_name'],
            'department'  => $p['department_name'],
            'status'      => $p['status'],
        ];
    }, $stmt->fetchAll());
}


/**
 * Queues an in-app notification for one user, and — if email
 * notifications are turned on (Administration, off by default) —
 * also emails them. Never throws; email delivery failure is logged
 * but never blocks the in-app notification or the action that
 * triggered it.
 */
function notify(int $userId, string $message, string $type = 'info'): void
{
    try {
        db()->prepare('INSERT INTO notifications (user_id, type, message) VALUES (:u, :t, :m)')
            ->execute(['u' => $userId, 't' => $type, 'm' => $message]);
    } catch (PDOException $e) {
        error_log('notify() failed: ' . $e->getMessage());
    }

    if (email_notifications_enabled()) {
        try {
            $stmt = db()->prepare('SELECT full_name, email FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);
            $recipient = $stmt->fetch();
            if ($recipient && $recipient['email']) {
                $subject = APP_SHORT_NAME . ': ' . mb_substr($message, 0, 60);
                $body = $message . "\n\n— " . APP_NAME . "\nThis is an automated message; please do not reply.";
                $sent = send_email($recipient['email'], $recipient['full_name'], $subject, $body);
                audit_log(null, $sent ? 'EMAIL_SENT' : 'EMAIL_FAILED', 'users', $userId, mb_substr($message, 0, 200));
            }
        } catch (PDOException $e) {
            error_log('notify() email lookup failed: ' . $e->getMessage());
        }
    }
}

/** Queues the same notification for every active user holding a given role. */
function notify_role(string $roleName, string $message, string $type = 'info'): void
{
    try {
        $stmt = db()->prepare(
            "SELECT u.id FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE r.name = :role AND u.status = 'active'"
        );
        $stmt->execute(['role' => $roleName]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) {
            notify((int) $userId, $message, $type);
        }
    } catch (PDOException $e) {
        error_log('notify_role() failed: ' . $e->getMessage());
    }
}

/**
 * Streams $rows as a CSV download and ends the request. Call only before
 * any HTML output. $columns maps output header => row array key.
 */
function export_csv(string $filename, array $columns, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, array_keys($columns));
    foreach ($rows as $row) {
        $line = [];
        foreach ($columns as $key) {
            $line[] = $row[$key] ?? '';
        }
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}
