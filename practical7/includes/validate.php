<?php
/**
 * validate.php – Practical 7
 * Server-side validation functions.
 * Each function returns an error string on failure, or '' on success.
 */

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    http_response_code(403); exit('Access denied.');
}

/**
 * Validate a required text field.
 */
function validate_required(string $value, string $label): string {
    return ($value === '') ? "{$label} is required." : '';
}

/**
 * Validate string length.
 */
function validate_length(string $value, string $label, int $min, int $max): string {
    $len = mb_strlen($value);
    if ($len < $min) return "{$label} must be at least {$min} characters.";
    if ($len > $max) return "{$label} must not exceed {$max} characters.";
    return '';
}

/**
 * Validate a name (letters, spaces, hyphens, apostrophes only).
 */
function validate_name(string $value, string $label): string {
    if (!preg_match('/^[A-Za-z][A-Za-z\s\'\-]{1,79}$/u', $value)) {
        return "{$label} must contain only letters, spaces, hyphens or apostrophes.";
    }
    return '';
}

/**
 * Validate an email address.
 */
function validate_email(string $value): string {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return 'Enter a valid email address (e.g. student@example.com).';
    }
    return '';
}

/**
 * Validate an Indian mobile number (10 digits, starts with 6–9).
 */
function validate_mobile(string $value): string {
    if (!preg_match('/^[6-9]\d{9}$/', $value)) {
        return 'Enter a valid 10-digit Indian mobile number starting with 6–9.';
    }
    return '';
}

/**
 * Validate date of birth — must be at least $minAge years old.
 */
function validate_dob(string $value, int $minAge = 15): string {
    if ($value === '') return 'Date of birth is required.';
    $dob  = DateTime::createFromFormat('Y-m-d', $value);
    if (!$dob)  return 'Enter a valid date in YYYY-MM-DD format.';
    $now  = new DateTime();
    $age  = $now->diff($dob)->y;
    if ($age < $minAge) return "You must be at least {$minAge} years old to register.";
    if ($age > 100)     return 'Enter a valid date of birth.';
    return '';
}

/**
 * Validate a password — min 8 chars, upper, lower, digit, special.
 */
function validate_password(string $value): string {
    if (strlen($value) < PWD_MIN)
        return 'Password must be at least ' . PWD_MIN . ' characters long.';
    if (!preg_match('/[A-Z]/', $value))
        return 'Password must contain at least one uppercase letter.';
    if (!preg_match('/[a-z]/', $value))
        return 'Password must contain at least one lowercase letter.';
    if (!preg_match('/\d/', $value))
        return 'Password must contain at least one number.';
    if (!preg_match('/[@$!%*?&_#^]/', $value))
        return 'Password must contain at least one special character (@$!%*?&_#^).';
    return '';
}

/**
 * Validate password confirmation match.
 */
function validate_confirm_password(string $pwd, string $confirm): string {
    return ($pwd !== $confirm) ? 'Passwords do not match.' : '';
}

/**
 * Validate a value is in the allowed list.
 */
function validate_enum(string $value, array $allowed, string $label): string {
    return (!in_array($value, $allowed, true))
        ? "Please select a valid {$label}."
        : '';
}

/**
 * Validate a subject/message length.
 */
function validate_message(string $value, string $label, int $min, int $max): string {
    $len = mb_strlen($value);
    if ($len < $min) return "{$label} must be at least {$min} characters.";
    if ($len > $max) return "{$label} must not exceed {$max} characters.";
    return '';
}

/**
 * Collect all errors from an array of [field => error_string].
 * Returns only non-empty errors.
 */
function collect_errors(array $checks): array {
    return array_filter($checks, fn($e) => $e !== '');
}
