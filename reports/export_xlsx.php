<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/xlsx_writer.php';
start_secure_session();
require_login();

$pdo = db();
$where = ["p.status != 'Archived'"];
$params = [];
if (!empty($_GET['department_id'])) { $where[] = 'p.department_id = ?'; $params[] = $_GET['department_id']; }
if (!empty($_GET['gender'])) { $where[] = 'p.gender = ?'; $params[] = $_GET['gender']; }
if (!empty($_GET['state_id'])) { $where[] = 'p.state_id = ?'; $params[] = $_GET['state_id']; }
if (!empty($_GET['rank_id'])) { $where[] = 'p.rank_id = ?'; $params[] = $_GET['rank_id']; }
if (!empty($_GET['zone_id'])) { $where[] = 'p.zone_id = ?'; $params[] = $_GET['zone_id']; }
if (!empty($_GET['educational_qualification'])) { $where[] = 'p.educational_qualification LIKE ?'; $params[] = '%' . $_GET['educational_qualification'] . '%'; }
if (!empty($_GET['retirement_from'])) { $where[] = 'p.date_of_retirement >= ?'; $params[] = $_GET['retirement_from']; }
if (!empty($_GET['retirement_to'])) { $where[] = 'p.date_of_retirement <= ?'; $params[] = $_GET['retirement_to']; }

$sql = "SELECT p.ap_number, p.full_name, r.name AS rank_name, d.name AS dept_name, p.gender,
               s.name AS state_name, z.name AS zone_name, l.name AS lga_name, p.phone_number, p.email_address,
               p.date_of_enlistment, p.date_of_last_promotion, p.date_of_retirement,
               p.employee_code, p.ippis_number, p.status
        FROM personnel p
        LEFT JOIN ranks r ON r.id = p.rank_id
        LEFT JOIN departments d ON d.id = p.department_id
        LEFT JOIN states s ON s.id = p.state_id
        LEFT JOIN geo_political_zones z ON z.id = p.zone_id
        LEFT JOIN local_governments l ON l.id = p.lga_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY d.name, p.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

audit_log('REPORT_EXPORTED_XLSX', 'nominal_roll', null, 'Filters: ' . json_encode($_GET));

$writer = new XlsxWriter();
$writer->addRow(['AP Number','Full Name','Rank','Department','Gender','State of Origin','Geopolitical Zone','LGA','Phone',
                  'Email','Date of Enlistment','Last Promotion','Date of Retirement','Employee Code','IPPIS Number','Status']);

foreach ($rows as $row) {
    $writer->addRow([
        $row['ap_number'], $row['full_name'], $row['rank_name'], $row['dept_name'], $row['gender'],
        $row['state_name'], $row['zone_name'], $row['lga_name'], $row['phone_number'], $row['email_address'],
        $row['date_of_enlistment'], $row['date_of_last_promotion'], $row['date_of_retirement'],
        $row['employee_code'], $row['ippis_number'], $row['status'],
    ]);
}

$filename = 'nominal_roll_' . date('Y-m-d_His') . '.xlsx';
$writer->output($filename);
exit;
