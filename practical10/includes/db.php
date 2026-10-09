<?php
/**
 * Practical 10 – Secure Login/Logout with Sessions & RBAC
 * includes/db.php  –  Singleton PDO connection
 *
 * Security practices applied:
 *  ✅ PDO with real prepared statements (ATTR_EMULATE_PREPARES = false)
 *  ✅ ERRMODE_EXCEPTION – errors throw, never silently fail
 *  ✅ FETCH_ASSOC default – no numeric index leakage
 *  ✅ utf8mb4 charset enforced in DSN and init command
 *  ✅ Connection error logged server-side, generic message to caller
 *  ✅ Singleton pattern – one connection per request
 */

require_once __DIR__ . '/config.php';

/**
 * Returns the singleton PDO instance.
 *
 * @return PDO
 * @throws RuntimeException on connection failure
 */
function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log('[Practical10 DB] ' . $e->getMessage());
        throw new RuntimeException(
            'Database connection failed. Check XAMPP MySQL is running and ' .
            'the database "' . DB_NAME . '" exists (import sql/schema.sql first).'
        );
    }

    return $pdo;
}

/**
 * Fetch a single row.
 *
 * @param  string $sql    SQL with named placeholders
 * @param  array  $params [':key' => value, ...]
 * @return array|false
 */
function db_fetch_one(string $sql, array $params = []): array|false
{
    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Fetch all rows.
 *
 * @param  string $sql
 * @param  array  $params
 * @return array
 */
function db_fetch_all(string $sql, array $params = []): array
{
    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute an INSERT / UPDATE / DELETE.
 *
 * @param  string $sql
 * @param  array  $params
 * @return int  Number of affected rows
 */
function db_execute(string $sql, array $params = []): int
{
    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
