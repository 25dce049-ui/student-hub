<?php
/**
 * register_process.php – Practical 7
 * Handles POST from register.php
 * Steps: CSRF → Sanitize → Validate → Store CSV+JSON → Redirect
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/includes/validate.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/response.php';

// ── 1. Only accept POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// ── 2. CSRF verification (Advanced Extension) ───────────────────────────────
csrf_verify();

// ── 3. Sanitize all inputs ──────────────────────────────────────────────────
$data = [
    'first_name'       => sanitize_text($_POST['first_name']       ?? ''),
    'last_name'        => sanitize_text($_POST['last_name']        ?? ''),
    'email'            => sanitize_email($_POST['email']           ?? ''),
    'mobile'           => sanitize_mobile($_POST['mobile']         ?? ''),
    'dob'              => sanitize_date($_POST['dob']              ?? ''),
    'gender'           => sanitize_enum($_POST['gender']           ?? '', ALLOWED_GENDERS),
    'course'           => sanitize_enum($_POST['course']           ?? '', ALLOWED_COURSES),
    'year'             => sanitize_int($_POST['year']              ?? 0, 1, 4),
    'password'         => $_POST['password']                       ?? '',
    'confirm_password' => $_POST['confirm_password']               ?? '',
    'terms'            => isset($_POST['terms']) ? '1' : '',
];

// ── 4. Validate all fields ──────────────────────────────────────────────────
$errors = collect_errors([
    'first_name'      => validate_required($data['first_name'], 'First name')
                      ?: validate_name($data['first_name'], 'First name')
                      ?: validate_length($data['first_name'], 'First name', NAME_MIN, NAME_MAX),

    'last_name'       => validate_required($data['last_name'], 'Last name')
                      ?: validate_name($data['last_name'], 'Last name')
                      ?: validate_length($data['last_name'], 'Last name', NAME_MIN, NAME_MAX),

    'email'           => validate_required($data['email'], 'Email')
                      ?: validate_email($data['email']),

    'mobile'          => validate_required($data['mobile'], 'Mobile number')
                      ?: validate_mobile($data['mobile']),

    'dob'             => validate_dob($data['dob'], 15),

    'gender'          => validate_enum($data['gender'], ALLOWED_GENDERS, 'gender'),

    'course'          => validate_enum($data['course'], ALLOWED_COURSES, 'course'),

    'year'            => (in_array((int)$data['year'], ALLOWED_YEARS, true))
                         ? '' : 'Please select a valid year of study.',

    'password'        => validate_required($data['password'], 'Password')
                      ?: validate_password($data['password']),

    'confirm_password'=> validate_confirm_password($data['password'], $data['confirm_password']),

    'terms'           => ($data['terms'] !== '1') ? 'You must accept the Terms & Conditions.' : '',
]);

// ── 5. Return errors if validation failed ───────────────────────────────────
if (!empty($errors)) {
    // Don't send passwords back to the form
    $oldInput = $data;
    unset($oldInput['password'], $oldInput['confirm_password']);

    redirect_with_flash(
        'register.php',
        false,
        'Please fix the errors below and try again.',
        $errors,
        $oldInput
    );
}

// ── 6. Prepare clean record (never store plain-text password) ───────────────
$record = [
    'id'           => uniqid('REG_', true),
    'first_name'   => $data['first_name'],
    'last_name'    => $data['last_name'],
    'email'        => $data['email'],
    'mobile'       => $data['mobile'],
    'dob'          => $data['dob'],
    'gender'       => $data['gender'],
    'course'       => $data['course'],
    'year'         => $data['year'],
    'password_hash'=> password_hash($data['password'], PASSWORD_BCRYPT),
    'registered_at'=> date('Y-m-d H:i:s'),
    'ip'           => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
];

// ── 7. Ensure data directory is safe ───────────────────────────────────────
ensure_data_dir(DATA_DIR);

// ── 8. Write to CSV (safe record — no password hash in CSV for demo) ────────
$csvRecord = $record;
unset($csvRecord['password_hash']); // exclude hash from CSV for readability

$csvOk  = csv_append(REG_CSV, $csvRecord);

// ── 9. Write to JSON ───────────────────────────────────────────────────────
$jsonOk = json_append(REG_JSON, $record);

// ── 10. Handle storage errors ──────────────────────────────────────────────
if (!$csvOk || !$jsonOk) {
    redirect_with_flash(
        'register.php',
        false,
        'Registration failed due to a server storage error. Please try again.',
        ['storage' => 'Unable to save your registration. Contact support.'],
        array_diff_key($data, ['password' => '', 'confirm_password' => ''])
    );
}

// ── 11. Success ────────────────────────────────────────────────────────────
redirect_with_flash(
    'register.php',
    true,
    "✅ Registration successful! Welcome, {$data['first_name']} {$data['last_name']}. Your data has been saved."
);
