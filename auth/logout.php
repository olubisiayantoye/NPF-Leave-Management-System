<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logout_user();

// A fresh session is needed so the landing page can set its own flash/CSRF.
session_start();
flash_set('info', 'You have been signed out.');
redirect('/');
