<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
if (is_logged_in()) {
    audit_log('LOGOUT', 'user', $_SESSION['user_id']);
}
session_unset();
session_destroy();
header('Location: ' . url('/login.php'));
exit;
