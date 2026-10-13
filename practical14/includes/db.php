<?php
// Practical 14 – Admin Dashboard RBAC + Audit Log
// includes/db.php – MySQLi singleton

require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function get_db(): mysqli
{
    static $conn = null;
    if ($conn !== null) return $conn;
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset(DB_CHARSET);
    } catch (mysqli_sql_exception $e) {
        error_log('[P14 DB] ' . $e->getMessage());
        die('<div style="font-family:sans-serif;padding:2rem;color:#991b1b;background:#fee2e2;border-radius:8px;max-width:600px;margin:2rem auto">
              <strong>Database connection failed.</strong> Import <code>sql/schema.sql</code> and ensure XAMPP MySQL is running.
             </div>');
    }
    return $conn;
}
