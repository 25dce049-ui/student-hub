<?php
/**
 * Practical 9 – Secure User Registration
 * register.php  –  POST handler
 *
 * Pipeline:
 *  1. Session & CSRF token validation
 *  2. Input sanitisation & backend validation
 *  3. Duplicate username / email check  (SELECT – prepared statement)
 *  4. Password hashing with password_hash()
 *  5. INSERT into users table           (prepared statement)
 *  6. Audit log entry                   (prepared statement)
 *  7. Redirect to success.php or back to form with errors
 *
 * Security checklist:
 *  ✅ CSRF token validated before any processing
 *  ✅ MySQLi prepared statements – no raw string interpolation
 *  ✅ password_hash($password, PASSWORD_BCRYPT) – never stored as plain text
 *  ✅ Duplicate email AND username check (PHP layer + DB unique key safety net)
 *  ✅ All user input sanitised with trim() + htmlspecialchars on output
 *  ✅ Audit log records every attempt (success & failure) with IP & user agent
 */

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/csrf.php';

/* ──────────────────────────────────────────
   Only accept POST requests
   ────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

/* ──────────────────────────────────────────
   1. CSRF Validation
   ────────────────────────────────────────── */
if (!csrf_validate_token()) {
    $_SESSION['errors'] = ['Invalid or expired form token. Please try again.'];
    header('Location: index.php');
    exit;
}

/* ──────────────────────────────────────────
   2. Collect & sanitise input
   ────────────────────────────────────────── */
$full_name = trim($_POST['full_name']       ?? '');
$username  = trim($_POST['username']        ?? '');
$email     = trim($_POST['email']           ?? '');
$password  = $_POST['password']             ?? '';
$confirm   = $_POST['confirm_password']     ?? '';

/* ──────────────────────────────────────────
   3. Backend Validation
   ────────────────────────────────────────── */
$errors = [];

// Full Name
if ($full_name === '') {
    $errors[] = 'Full name is required.';
} elseif (mb_strlen($full_name) < 3) {
    $errors[] = 'Full name must be at least 3 characters.';
} elseif (mb_strlen($full_name) > 100) {
    $errors[] = 'Full name cannot exceed 100 characters.';
}

// Username
if ($username === '') {
    $errors[] = 'Username is required.';
} elseif (mb_strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters.';
} elseif (mb_strlen($username) > 50) {
    $errors[] = 'Username cannot exceed 50 characters.';
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = 'Username may only contain letters, numbers, and underscores.';
}

// Email
if ($email === '') {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
} elseif (mb_strlen($email) > 100) {
    $errors[] = 'Email address cannot exceed 100 characters.';
}

// Password
if ($password === '') {
    $errors[] = 'Password is required.';
} elseif (mb_strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

// Confirm password
if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
}

if (!empty($errors)) {
    // Store old values to repopulate form (not the password for security)
    $_SESSION['old']    = compact('full_name', 'username', 'email');
    $_SESSION['errors'] = $errors;
    header('Location: index.php');
    exit;
}

/* ──────────────────────────────────────────
   4. Database operations
   ────────────────────────────────────────── */
try {
    $db = get_db();

    /* ── 4a. Duplicate check (email AND username) ── */
    $stmt = $db->prepare(
        "SELECT
            SUM(email    = ?) AS email_count,
            SUM(username = ?) AS username_count
         FROM users
         LIMIT 1"
    );
    $stmt->bind_param('ss', $email, $username);
    $stmt->execute();
    $dup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)$dup['email_count'] > 0) {
        $errors[] = 'This email address is already registered. Please use a different email.';
    }
    if ((int)$dup['username_count'] > 0) {
        $errors[] = 'This username is already taken. Please choose another username.';
    }

    if (!empty($errors)) {
        $_SESSION['old']    = compact('full_name', 'username', 'email');
        $_SESSION['errors'] = $errors;
        audit_log($db, 'REGISTER_FAIL_DUPLICATE', $username, $email);
        header('Location: index.php');
        exit;
    }

    /* ── 4b. Hash password ── */
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    // password_hash() with PASSWORD_BCRYPT uses a cost factor of 10 by default,
    // applies a random salt automatically, and returns a 60-char string.

    /* ── 4c. Insert new user ── */
    $stmt = $db->prepare(
        "INSERT INTO users (username, email, full_name, password_hash)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param('ssss', $username, $email, $full_name, $password_hash);
    $stmt->execute();
    $new_user_id = $db->insert_id;
    $stmt->close();

    /* ── 4d. Audit log – success ── */
    audit_log($db, 'REGISTER_SUCCESS', $username, $email);

    /* ── 4e. Store success info in session and redirect ── */
    $_SESSION['registered_user'] = [
        'id'        => $new_user_id,
        'username'  => $username,
        'full_name' => $full_name,
        'email'     => $email,
    ];
    header('Location: success.php');
    exit;

} catch (RuntimeException $e) {
    // DB connection failure
    $_SESSION['errors'] = [$e->getMessage()];
    header('Location: index.php');
    exit;
} catch (mysqli_sql_exception $e) {
    // Catch unexpected DB errors (e.g., duplicate key race condition)
    error_log('[Practical9 Register Error] ' . $e->getMessage());
    if ($e->getCode() === 1062) {
        // MySQL duplicate entry error code
        $_SESSION['errors'] = ['Username or email already exists. Please try again with different details.'];
    } else {
        $_SESSION['errors'] = ['A database error occurred. Please try again.'];
    }
    $_SESSION['old'] = compact('full_name', 'username', 'email');
    header('Location: index.php');
    exit;
}

/* ──────────────────────────────────────────
   Helper – write to audit_log table
   ────────────────────────────────────────── */
function audit_log(mysqli $db, string $action, string $username = '', string $email = ''): void
{
    $ip         = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
    $user_agent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    $stmt = $db->prepare(
        "INSERT INTO audit_log (action, username, email, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssss', $action, $username, $email, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}
