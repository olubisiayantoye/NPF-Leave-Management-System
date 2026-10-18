<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/leave_calc.php';

header('Content-Type: application/json');
require_login();

$proceedingDate = $_GET['proceeding_date'] ?? '';
$days = (int) ($_GET['days'] ?? 0);

$resumption = calculate_resumption_date($proceedingDate, $days);

echo json_encode(['expected_resumption_date' => $resumption]);
