<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/xlsx_writer.php';
start_secure_session();
require_login();

$pdo = db();
$where = ['1=1'];
$params = [];
if (!empty($_GET['section'])) { $where[] = 'a.section = ?'; $params[] = $_GET['section']; }
if (!empty($_GET['bank_id'])) { $where[] = 'a.bank_id = ?'; $params[] = $_GET['bank_id']; }
if (!empty($_GET['rank_id'])) { $where[] = 'a.rank_id = ?'; $params[] = $_GET['rank_id']; }
if (!empty($_GET['status'])) { $where[] = 'a.status = ?'; $params[] = $_GET['status']; }

$sql = "SELECT a.fap_number, r.name AS rank_name, a.full_name, a.account_number, b.name AS bank_name,
               a.employee_code, a.section, a.status
        FROM monthly_allowance_personnel a
        LEFT JOIN ranks r ON r.id = a.rank_id
        LEFT JOIN banks b ON b.id = a.bank_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY a.section, a.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

audit_log('ALLOWANCE_REPORT_EXPORTED_XLSX', 'monthly_allowance_personnel', null, 'Filters: ' . json_encode($_GET));

$writer = new XlsxWriter();
$writer->addRow(['F-AP Number', 'Rank', 'Name', 'Account Number', 'Bank Name', 'Employee Code', 'Section', 'Status']);
foreach ($rows as $row) {
    $writer->addRow([
        $row['fap_number'], $row['rank_name'], $row['full_name'], $row['account_number'],
        $row['bank_name'], $row['employee_code'], $row['section'], $row['status'],
    ]);
}

$filename = 'monthly_allowance_' . date('Y-m-d_His') . '.xlsx';
$writer->output($filename);
exit;
