<?php
/**
 * Practical 8 – StudentHub
 * includes/db.php  –  Secure PDO database connection
 *
 * How to use:
 *   require_once __DIR__ . '/includes/db.php';
 *   $pdo = get_pdo();       // returns a singleton PDO instance
 *
 * Security practices applied:
 *  ✅ PDO with prepared statements (no raw string interpolation)
 *  ✅ Error mode: ERRMODE_EXCEPTION (safe error handling)
 *  ✅ Emulated prepares OFF (real server-side prepared statements)
 *  ✅ Default fetch mode: FETCH_ASSOC
 *  ✅ Charset forced to utf8mb4 in DSN
 *  ✅ Credentials kept in one place, not scattered across files
 *  ✅ Connection failure throws exception – never leaks credentials
 */

// ---------- Database Configuration ----------
// In production, move these to environment variables or a config outside webroot.
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'studenthub');
define('DB_USER',    'root');       // Change for production
define('DB_PASS',    '');           // Change for production
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO connection.
 *
 * @return PDO
 * @throws RuntimeException if the connection fails
 */
function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Return assoc arrays by default
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Use real prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose raw exception message in production – log it instead
            error_log('[StudentHub DB Error] ' . $e->getMessage());
            throw new RuntimeException(
                'Database connection failed. Please try again later.'
            );
        }
    }

    return $pdo;
}


/**
 * Helper – fetch all rows using a prepared statement.
 *
 * @param  string $sql    SQL with named/positional placeholders
 * @param  array  $params Bound parameters
 * @return array
 */
function db_fetch_all(string $sql, array $params = []): array
{
    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


/**
 * Helper – fetch a single row using a prepared statement.
 *
 * @param  string $sql
 * @param  array  $params
 * @return array|false
 */
function db_fetch_one(string $sql, array $params = []): array|false
{
    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}


/**
 * Helper – execute a write query (INSERT / UPDATE / DELETE).
 *
 * @param  string $sql
 * @param  array  $params
 * @return int    Number of affected rows
 */
function db_execute(string $sql, array $params = []): int
{
    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
