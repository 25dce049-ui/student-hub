<?php
/**
 * Practical 11 – Student Management CRUD with Search & Filter
 * includes/db.php  –  MySQLi connection (singleton)
 *
 * Security practices:
 *  ✅ MySQLi prepared statements used for all queries
 *  ✅ Errors throw exceptions (MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
 *  ✅ utf8mb4 charset enforced
 *  ✅ Credentials in config.php only
 */

require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * Returns the singleton MySQLi connection.
 *
 * @return mysqli
 */
function get_db(): mysqli
{
    static $conn = null;

    if ($conn !== null) {
        return $conn;
    }

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset(DB_CHARSET);
    } catch (mysqli_sql_exception $e) {
        error_log('[Practical11 DB] ' . $e->getMessage());
        die('<div style="font-family:sans-serif;padding:2rem;color:#991b1b;background:#fee2e2;border-radius:8px;max-width:600px;margin:2rem auto">
              <strong>Database connection failed.</strong><br>
              Make sure XAMPP MySQL is running and you have imported <code>sql/schema.sql</code>.
             </div>');
    }

    return $conn;
}
