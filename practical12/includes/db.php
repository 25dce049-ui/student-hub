<?php
/**
 * Practical 12 – Event Management CRUD with Poster Upload
 * includes/db.php  –  MySQLi singleton connection
 *
 * Security: ✅ MySQLi prepared statements enforced via MYSQLI_REPORT_ERROR|STRICT
 *           ✅ utf8mb4 charset  ✅ error logged server-side only
 */

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
        error_log('[Practical12 DB] ' . $e->getMessage());
        die('<div style="font-family:sans-serif;padding:2rem;color:#991b1b;background:#fee2e2;border-radius:8px;max-width:600px;margin:2rem auto">
              <strong>Database connection failed.</strong><br>
              Make sure XAMPP MySQL is running and import <code>sql/schema.sql</code> first.
             </div>');
    }
    return $conn;
}

/**
 * Advanced Extension: write an audit log entry.
 */
function audit(int $eventId = null, string $action = 'CREATE', string $details = ''): void
{
    try {
        $db   = get_db();
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $db->prepare(
            "INSERT INTO audit_log (event_id, action, details, ip_address) VALUES (?,?,?,?)"
        );
        $stmt->bind_param('isss', $eventId, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('[Practical12 Audit] ' . $e->getMessage());
    }
}
