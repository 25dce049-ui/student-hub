<?php
/**
 * sanitize.php – Practical 7
 * Pure sanitization helpers — no side effects.
 */

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    http_response_code(403); exit('Access denied.');
}

/**
 * Sanitize a plain text string.
 * Trims whitespace, strips HTML tags, encodes special chars.
 *
 * @param  string $value
 * @return string
 */
function sanitize_text(string $value): string {
    $value = trim($value);
    $value = strip_tags($value);
    $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $value;
}

/**
 * Sanitize an email address.
 *
 * @param  string $value
 * @return string
 */
function sanitize_email(string $value): string {
    $value = trim($value);
    $value = filter_var($value, FILTER_SANITIZE_EMAIL);
    return strtolower($value);
}

/**
 * Sanitize a mobile number — keep only digits.
 *
 * @param  string $value
 * @return string
 */
function sanitize_mobile(string $value): string {
    return preg_replace('/\D/', '', trim($value));
}

/**
 * Sanitize an integer — cast and clamp.
 *
 * @param  mixed $value
 * @param  int   $min
 * @param  int   $max
 * @return int
 */
function sanitize_int($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int {
    $int = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    return max($min, min($max, $int));
}

/**
 * Sanitize a date string — return valid Y-m-d or empty string.
 *
 * @param  string $value
 * @return string
 */
function sanitize_date(string $value): string {
    $value = trim($value);
    $dt    = DateTime::createFromFormat('Y-m-d', $value);
    return ($dt && $dt->format('Y-m-d') === $value) ? $value : '';
}

/**
 * Sanitize a value that must belong to an allowed list.
 * Returns empty string if not in list (to trigger validation error).
 *
 * @param  string $value
 * @param  array  $allowed
 * @return string
 */
function sanitize_enum(string $value, array $allowed): string {
    $value = trim($value);
    return in_array($value, $allowed, true) ? $value : '';
}

/**
 * Sanitize a textarea message — trim and strip dangerous tags
 * but preserve line breaks.
 *
 * @param  string $value
 * @return string
 */
function sanitize_message(string $value): string {
    $value = trim($value);
    $value = strip_tags($value);
    $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $value;
}
