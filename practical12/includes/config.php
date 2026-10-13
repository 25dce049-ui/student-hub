<?php
/**
 * Practical 12 – Event Management CRUD with Poster Upload
 * includes/config.php
 */

// ---------- Database ----------
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'practical12_events');
define('DB_CHARSET', 'utf8mb4');

// ---------- Pagination ----------
define('PER_PAGE', 8);

// ---------- File Upload ----------
define('UPLOAD_DIR',      __DIR__ . '/../uploads/');      // absolute path
define('UPLOAD_URL',      'uploads/');                    // relative URL
define('MAX_FILE_SIZE',   2 * 1024 * 1024);               // 2 MB
define('ALLOWED_TYPES',   ['image/jpeg','image/png','image/webp','image/gif']);
define('ALLOWED_EXTS',    ['jpg','jpeg','png','webp','gif']);

// ---------- Event categories ----------
define('CATEGORIES', ['Technical','Cultural','Sports','Academic','Workshop','Networking','General']);
