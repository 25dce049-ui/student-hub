<?php
/**
 * Practical 7 (Advanced extension) - CSRF token generation and validation.
 * Tokens are per-session and single-use; they are compared with hash_equals()
 * so the check cannot be timed.
 */

declare(strict_types=1);

final class Csrf
{
    private const SESSION_KEY = 'practical7_csrf_token';

    public static function startSession(): void
    {
        if (PHP_SAPI === 'cli') {
            // CLI (test runs) has no cookies/headers, so use a plain array.
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }

            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /** Return the current token, generating one on first use. */
    public static function token(): string
    {
        self::startSession();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /** Hidden <input> for the form. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Compare the submitted token with the session token.
     * The token is rotated after a successful check to stop replay attacks.
     */
    public static function validate(?string $submitted): bool
    {
        self::startSession();

        $expected = $_SESSION[self::SESSION_KEY] ?? '';

        if ($expected === '' || !is_string($submitted) || $submitted === '') {
            return false;
        }

        if (hash_equals($expected, $submitted)) {
            unset($_SESSION[self::SESSION_KEY]);

            return true;
        }

        return false;
    }
}
