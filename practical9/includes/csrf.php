<?php
/**
 * Practical 9 – Secure User Registration
 * includes/csrf.php  –  CSRF Token Management
 *
 * Functions:
 *  • csrf_generate_token()  –  Creates and stores a CSRF token in session
 *  • csrf_validate_token()  –  Validates the token from POST request
 */

/**
 * Generates a CSRF token and stores it in session.
 * 
 * @return string The generated token
 */
function csrf_generate_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Validates the CSRF token from POST data.
 * 
 * @return bool True if valid, false otherwise
 */
function csrf_validate_token(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || empty($_POST['csrf_token'])) {
        return false;
    }

    $valid = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    
    // Regenerate token after validation (one-time use)
    unset($_SESSION['csrf_token']);
    
    return $valid;
}
