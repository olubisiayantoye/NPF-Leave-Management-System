<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/xlsx_writer.php';
start_secure_session();
require_role(['Super Administrator', 'Administrator']);

$columns = [
    'AP Number','Rank','Name','Gender','State of Origin','LGA','Tribe','Geo Political Zone',
    'Date of Birth','Date of Enlistment','Date of Last Promotion','Date of Retirement',
    'Command Served Last','Date Transferred','Educational Qualification','GD/SP','Duty Post',
    'Grade Level','Phone Number','Email Address','Account Number','Bank Name','Employee Code',
    'IPPIS Number','PFA','PEN-PIN','NHF Number','MSS',
];

$exampleRow = [
    'AP12345','Inspector of Police','John Ade Doe','Male','Lagos','Ikeja','Yoruba','South West',
    '1985-04-12','2010-06-01','2020-01-15','2040-04-12','IGP Secretariat','2023-03-01',
    'HND Computer Science','GD','Force Headquarters','08','08012345678','john.doe@example.com',
    '0123456789','Access Bank','EMP001','IPPIS0001','ARM Pension','PEN0001','NHF0001','MSS01',
];

$writer = new XlsxWriter();
$writer->addRow($columns);
$writer->addRow($exampleRow);
$writer->output('nrms_bulk_upload_template.xlsx');
exit;
