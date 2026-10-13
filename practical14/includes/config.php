<?php
// Practical 14 – Admin Dashboard RBAC + Audit Log
// includes/config.php

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'practical14_admin');
define('DB_CHARSET', 'utf8mb4');

define('SESSION_NAME',    'P14_ADMIN');
define('SESSION_TIMEOUT', 1800);      // 30 minutes

// Roles
define('ROLE_ADMIN',     'admin');
define('ROLE_MODERATOR', 'moderator');
define('ROLE_STUDENT',   'student');

// Dynamic menu items per role
define('MENU', [
    ROLE_ADMIN => [
        ['icon'=>'🏠','label'=>'Dashboard',   'href'=>'dashboard.php'],
        ['icon'=>'👥','label'=>'Users',        'href'=>'users.php'],
        ['icon'=>'🎓','label'=>'Students',     'href'=>'students.php'],
        ['icon'=>'🗓️','label'=>'Events',       'href'=>'events.php'],
        ['icon'=>'📋','label'=>'Registrations','href'=>'registrations.php'],
        ['icon'=>'📜','label'=>'Audit Log',    'href'=>'audit.php'],
    ],
    ROLE_MODERATOR => [
        ['icon'=>'🏠','label'=>'Dashboard',   'href'=>'dashboard.php'],
        ['icon'=>'🎓','label'=>'Students',     'href'=>'students.php'],
        ['icon'=>'🗓️','label'=>'Events',       'href'=>'events.php'],
        ['icon'=>'📋','label'=>'Registrations','href'=>'registrations.php'],
    ],
    ROLE_STUDENT => [
        ['icon'=>'🏠','label'=>'Dashboard',   'href'=>'dashboard.php'],
        ['icon'=>'🗓️','label'=>'Events',       'href'=>'events.php'],
    ],
]);

ini_set('session.use_strict_mode',  '1');
ini_set('session.cookie_httponly',  '1');
ini_set('session.cookie_samesite',  'Strict');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime',   (string)SESSION_TIMEOUT);
