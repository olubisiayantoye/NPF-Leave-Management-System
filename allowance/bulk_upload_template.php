<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/xlsx_writer.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$columns = ['F-AP Number', 'Rank', 'Name', 'Account Number', 'Bank Name', 'Employee Code', 'Section'];
$exampleRow = ['FAP12345', 'Inspector of Police', 'John Ade Doe', '0123456789', 'Access Bank', 'EMP001', 'IGP Secretariat'];

$writer = new XlsxWriter();
$writer->addRow($columns);
$writer->addRow($exampleRow);
$writer->output('nrms_allowance_bulk_upload_template.xlsx');
exit;
