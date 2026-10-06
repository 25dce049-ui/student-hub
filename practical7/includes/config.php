<?php
/**
 * config.php – Practical 7
 * Central configuration for paths, limits, and allowed values.
 */

// ── Prevent direct browser access ─────────────────────────────────────────
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    http_response_code(403);
    exit('Access denied.');
}

// ── Base paths ─────────────────────────────────────────────────────────────
define('BASE_DIR',    dirname(__DIR__));          // practical7/
define('DATA_DIR',    BASE_DIR . '/data/');       // practical7/data/
define('INCLUDES_DIR',BASE_DIR . '/includes/');   // practical7/includes/

// ── Storage files ──────────────────────────────────────────────────────────
define('REG_CSV',     DATA_DIR . 'registrations.csv');
define('REG_JSON',    DATA_DIR . 'registrations.json');
define('CONTACT_CSV', DATA_DIR . 'contacts.csv');
define('CONTACT_JSON',DATA_DIR . 'contacts.json');

// ── Application settings ───────────────────────────────────────────────────
define('APP_NAME',    'StudentHub');
define('APP_VERSION', '1.0');
define('TIMEZONE',    'Asia/Kolkata');
define('CSRF_TOKEN_LENGTH', 32);
define('CSRF_SESSION_KEY',  'csrf_token');

// ── Validation limits ──────────────────────────────────────────────────────
define('NAME_MIN',   2);
define('NAME_MAX',   80);
define('MSG_MIN',    10);
define('MSG_MAX',    1000);
define('PWD_MIN',    8);

// ── Allowed values ─────────────────────────────────────────────────────────
define('ALLOWED_COURSES', ['BCA', 'MCA', 'B.Sc. CS', 'M.Sc. CS', 'BBA', 'B.Com']);
define('ALLOWED_YEARS',   [1, 2, 3, 4]);
define('ALLOWED_GENDERS', ['Male', 'Female', 'Other']);

// ── Set timezone ───────────────────────────────────────────────────────────
date_default_timezone_set(TIMEZONE);

// ── Start session (only once) ──────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
