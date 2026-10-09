<?php
/**
 * Practical 10 – Secure Login/Logout with Sessions & RBAC
 * includes/config.php  –  Application-wide constants and settings
 *
 * Security practices:
 *  ✅ Session cookie flags: HttpOnly, SameSite=Strict, Secure (in production)
 *  ✅ Session timeout configurable in one place
 *  ✅ Remember-me token lifetime configurable
 *  ✅ Credentials centralised (move to .env in production)
 */

// ---------- Database ----------
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'practical10_auth');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ---------- Session ----------
define('SESSION_TIMEOUT',   1800);          // 30 minutes of inactivity (seconds)
define('SESSION_NAME',      'P10_SESSION'); // Custom session name (avoid default PHPSESSID)

// ---------- Remember-Me ----------
define('REMEMBER_ME_DAYS',   30);           // Cookie + DB token lifetime in days
define('REMEMBER_COOKIE',   'p10_remember'); // Cookie name for remember-me token

// ---------- Roles ----------
define('ROLE_ADMIN',   'admin');
define('ROLE_STUDENT', 'student');

// ---------- Paths ----------
define('BASE_PATH', dirname(__DIR__));      // practical10/ root
define('LOGIN_URL',        'login.php');
define('ADMIN_DASH_URL',   'dashboard_admin.php');
define('STUDENT_DASH_URL', 'dashboard_student.php');
define('UNAUTH_URL',       'unauthorized.php');

// ---------- Secure session cookie configuration ----------
// Must be called BEFORE session_start() – done in auth.php
ini_set('session.use_strict_mode',    '1');
ini_set('session.cookie_httponly',    '1');
ini_set('session.cookie_samesite',    'Strict');
ini_set('session.use_only_cookies',   '1');
ini_set('session.cookie_secure',      '0'); // Set to '1' on HTTPS/production
ini_set('session.gc_maxlifetime',     (string) SESSION_TIMEOUT);
