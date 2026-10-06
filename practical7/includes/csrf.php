<?php
/**
 * csrf.php – Practical 7  (Advanced Extension)
 * CSRF token generation and validation.
 *
 * Usage:
 *   In a form page  : echo csrf_field();
 *   In a processor  : csrf_verify();   // dies on failure
 */

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    http_response_code(403); exit('Access denied.');
}

/**
 * Generate (or reuse existing) CSRF token and store in session.
 *
 * @return string  The token value
 */
function csrf_generate(): string {
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION[CSRF_SESSION_KEY];
}

/**
 * Return an HTML hidden input field containing the CSRF token.
 *
 * @return string  HTML string
 */
function csrf_field(): string {
    $token = csrf_generate();
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '" />';
}

/**
 * Verify the CSRF token submitted with the form.
 * Terminates with 403 if token is missing or invalid.
 * Regenerates token after successful validation.
 *
 * @return void
 */
function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION[CSRF_SESSION_KEY] ?? '';

    if (
        empty($submitted) ||
        empty($stored)    ||
        !hash_equals($stored, $submitted)
    ) {
        http_response_code(403);
        // Regenerate to prevent reuse
        unset($_SESSION[CSRF_SESSION_KEY]);
        die(json_encode([
            'success' => false,
            'message' => 'Invalid or expired security token. Please go back and try again.',
            'errors'  => ['csrf' => 'Security token mismatch.']
        ]));
    }

    // Rotate token after use (one-time-use pattern)
    unset($_SESSION[CSRF_SESSION_KEY]);
    csrf_generate();
}
