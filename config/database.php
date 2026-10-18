<?php
/**
 * NRMS Database Configuration
 * Update these constants for your environment, or better, set them as
 * real environment variables and read them with getenv().
 */

define('DB_HOST', getenv('NRMS_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('NRMS_DB_NAME') ?: 'berriezl_nrms_db');
define('DB_USER', getenv('NRMS_DB_USER') ?: 'berriezl_nrms_db');
define('DB_PASS', getenv('NRMS_DB_PASS') ?: 'TY0FX%dx.bHD;^A6');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO instance (singleton per request).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed. Please contact the system administrator.');
        }
    }
    return $pdo;
}
