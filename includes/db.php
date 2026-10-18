<?php
/**
 * PDO database connection.
 * Requires config/config.php to already be loaded (for DB_* constants).
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
            ]);
        } catch (PDOException $e) {
            // Never leak connection details to the browser.
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('A system error occurred. Please contact the system administrator.');
        }
    }

    return $pdo;
}
