<?php
/**
 * Practical 9 – Secure User Registration
 * includes/db.php  –  MySQLi database connection
 *
 * Security practices applied:
 *  ✅ MySQLi with prepared statements (no raw string interpolation)
 *  ✅ Credentials centralised in one place
 *  ✅ Connection failure handled gracefully – never leaks credentials
 *  ✅ Charset forced to utf8mb4
 *  ✅ Singleton pattern to avoid multiple connections
 */

// ---------- Database Configuration ----------
// In production: move these to environment variables or a config outside webroot.
define('DB_HOST',    'localhost');
define('DB_PORT',    3306);
define('DB_NAME',    'practical9_db');
define('DB_USER',    'root');        // Change for production
define('DB_PASS',    '');            // Change for production
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton MySQLi connection.
 *
 * @return mysqli
 * @throws RuntimeException if connection fails
 */
function get_db(): mysqli
{
    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            $conn->set_charset(DB_CHARSET);
        } catch (mysqli_sql_exception $e) {
            error_log('[Practical9 DB Error] ' . $e->getMessage());
            throw new RuntimeException(
                'Database connection failed. Make sure XAMPP MySQL is running and the database exists.'
            );
        }
    }

    return $conn;
}
