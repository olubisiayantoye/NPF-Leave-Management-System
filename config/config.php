<?php
/**
 * NPF Leave Management System
 * Global Configuration
 *
 * Every page includes this file first. BASE_URL is the single
 * source of truth for building links, form actions, and asset
 * paths, so the app can move between /, a subfolder, or a new
 * domain by changing ONE value.
 */

// ---------------------------------------------------------------
// Environment
// ---------------------------------------------------------------
define('APP_ENV', 'development'); // 'development' | 'production'

error_reporting(APP_ENV === 'development' ? E_ALL : 0);
ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');

// ---------------------------------------------------------------
// BASE_URL — global variable used across the entire application
// ---------------------------------------------------------------
// Auto-detects protocol + host + install folder so links/assets
// work in local dev (e.g. http://localhost/npf-leave-management)
// and in production (e.g. https://leave.npf.gov.ng) without any
// code changes elsewhere. Override manually for production if
// you prefer a fixed value (recommended once deployed).
//
// IMPORTANT: this must be derived from __DIR__ (the filesystem
// location of THIS file), not from $_SERVER['SCRIPT_NAME'] (the
// page that happened to include it). Every page except the root
// index.php includes config.php from one level down — auth/,
// personnel/, leave/, api/, etc. — so SCRIPT_NAME's directory is
// different on almost every request. Basing the install path on it (as
// an earlier version of this file did) computed a different, wrong
// BASE_URL depending on which page loaded, breaking asset
// links, navigation, and any JS that called back into api/ using
// that value — most visibly on pages nested in a subfolder.

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Filesystem path of the project root (parent of this config/ folder),
    // compared against the web server's document root to get the URL
    // prefix the app is installed under — correct no matter which page
    // triggered the include.
    $projectRoot   = str_replace('\\', '/', dirname(__DIR__));
    $documentRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $installPath   = '';

    if ($documentRoot !== '' && str_starts_with($projectRoot, $documentRoot)) {
        $installPath = substr($projectRoot, strlen($documentRoot));
    }

    // --- Manual override (recommended for production, and the only
    //     reliable option if DOCUMENT_ROOT isn't set by your server/CLI) ---
     define('BASE_URL', 'https://magnivoice.com/npf-leave-management');

    if (!defined('BASE_URL')) {
        define('BASE_URL', $protocol . $host . $installPath);
    }
}

// Convenience helper — never hand-build a path, always go through this.
function base_url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

// ---------------------------------------------------------------
// App identity
// ---------------------------------------------------------------
define('APP_NAME', 'NPF Leave Management System');
define('APP_SHORT_NAME', 'NPF-LMS');
define('APP_AGENCY', 'Nigeria Police Force');
define('APP_HQ', 'Force Headquarters, Abuja');
define('APP_VERSION', '0.1.0-phase1');

// ---------------------------------------------------------------
// Paths
// ---------------------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_PATH', ROOT_PATH . '/assets');

// ---------------------------------------------------------------
// Session
// ---------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// ---------------------------------------------------------------
// Database (used by db.php via PDO — included by pages that need it)
// ---------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'npf_leave_management');
define('DB_USER', 'user');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
