<?php
/**
 * Practical 10 – Secure Login/Logout with Sessions & RBAC
 * includes/auth.php  –  Session management, authentication, RBAC, remember-me
 *
 * Key features demonstrated:
 *  ✅ session_regenerate_id(true) on login – prevents session fixation
 *  ✅ Session timeout check on every protected page
 *  ✅ password_verify() for bcrypt hash comparison
 *  ✅ Role-based access control helpers (require_role, require_login)
 *  ✅ Secure remember-me: raw random token sent in cookie,
 *     SHA-256 hash stored in DB (never store raw token)
 *  ✅ last_login timestamp updated on successful login
 *  ✅ CSRF token generation and verification
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------
// Boot – start session with custom name & secure settings
// ---------------------------------------------------------------

/**
 * Start the session (idempotent – safe to call multiple times).
 */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

// Auto-boot on every file that requires auth.php
session_boot();

// ---------------------------------------------------------------
// Timeout
// ---------------------------------------------------------------

/**
 * Check if the current session has exceeded SESSION_TIMEOUT.
 * Calls logout and redirects to login if timed out.
 */
function check_session_timeout(): void
{
    if (!isset($_SESSION['user_id'])) {
        return; // Not logged in – nothing to check
    }

    $last = $_SESSION['last_activity'] ?? 0;

    if ((time() - $last) > SESSION_TIMEOUT) {
        // Preserve timeout flag for login page message
        $username = $_SESSION['username'] ?? '';
        logout_user();
        header('Location: ' . LOGIN_URL . '?reason=timeout&user=' . urlencode($username));
        exit;
    }

    // Refresh activity timestamp
    $_SESSION['last_activity'] = time();
}

// ---------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------

/**
 * Generate a CSRF token and store it in the session.
 * Returns the token string for embedding in forms.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the submitted CSRF token matches the session token.
 * Terminates with 403 on mismatch.
 */
function csrf_verify(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        die('CSRF token mismatch. Please go back and try again.');
    }
}

// ---------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------

/**
 * Attempt to log in a user by username + password.
 *
 * @param  string $username
 * @param  string $password   Plain-text password from form
 * @param  bool   $remember   Whether to set a remember-me cookie
 * @return array{ok: bool, error: string, user: array|null}
 */
function login_user(string $username, string $password, bool $remember = false): array
{
    // Fetch user record (active users only)
    $user = db_fetch_one(
        "SELECT id, username, email, password_hash, role, full_name
           FROM users
          WHERE username = :u
            AND is_active = 1
          LIMIT 1",
        [':u' => $username]
    );

    if (!$user) {
        // Generic message – don't reveal whether username exists
        return ['ok' => false, 'error' => 'Invalid username or password.', 'user' => null];
    }

    // ✅ password_verify() – constant-time bcrypt comparison
    if (!password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid username or password.', 'user' => null];
    }

    // ✅ Regenerate session ID – prevents session fixation attacks
    session_regenerate_id(true);

    // Populate session
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['full_name']     = $user['full_name'];
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time']    = time();

    // Regenerate CSRF token on login
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Update last_login timestamp in DB
    db_execute(
        "UPDATE users SET last_login = NOW() WHERE id = :id",
        [':id' => $user['id']]
    );

    // Remember-me handling
    if ($remember) {
        set_remember_me_cookie((int) $user['id']);
    }

    return ['ok' => true, 'error' => '', 'user' => $user];
}

/**
 * Destroy the session, clear cookies, and optionally wipe the remember-me token.
 */
function logout_user(): void
{
    // Clear remember-me if present
    if (isset($_COOKIE[REMEMBER_COOKIE])) {
        $raw = $_COOKIE[REMEMBER_COOKIE];
        clear_remember_me_token($raw);
        // Expire the cookie in the browser
        setcookie(REMEMBER_COOKIE, '', time() - 3600, '/', '', false, true);
    }

    // Unset all session variables
    $_SESSION = [];

    // Delete the session cookie from the browser
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

// ---------------------------------------------------------------
// Remember-Me
// ---------------------------------------------------------------

/**
 * Generate a cryptographically random token, store its SHA-256 hash
 * in the database, and set a cookie with the raw token.
 *
 * Security: raw token in cookie, hash in DB → even if DB is breached,
 * attacker cannot derive a valid cookie value.
 */
function set_remember_me_cookie(int $user_id): void
{
    $raw_token  = bin2hex(random_bytes(32));          // 64-char hex token
    $hashed     = hash('sha256', $raw_token);          // stored in DB
    $expires_at = date('Y-m-d H:i:s', time() + (REMEMBER_ME_DAYS * 86400));

    // Store hash + expiry in DB
    db_execute(
        "UPDATE users
            SET remember_token = :token,
                token_expires  = :exp
          WHERE id = :id",
        [
            ':token' => $hashed,
            ':exp'   => $expires_at,
            ':id'    => $user_id,
        ]
    );

    // Set HttpOnly cookie with raw token
    setcookie(
        REMEMBER_COOKIE,
        $raw_token,
        [
            'expires'  => time() + (REMEMBER_ME_DAYS * 86400),
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,   // true on HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]
    );
}

/**
 * Attempt to resume a session from the remember-me cookie.
 * Called at the top of login.php to auto-login returning users.
 *
 * @return bool  true if session was restored from cookie
 */
function try_remember_me_login(): bool
{
    // Already logged in
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    if (empty($_COOKIE[REMEMBER_COOKIE])) {
        return false;
    }

    $raw    = $_COOKIE[REMEMBER_COOKIE];
    $hashed = hash('sha256', $raw);

    // Look up unexpired, active user with this token hash
    $user = db_fetch_one(
        "SELECT id, username, role, full_name
           FROM users
          WHERE remember_token = :token
            AND token_expires  > NOW()
            AND is_active      = 1
          LIMIT 1",
        [':token' => $hashed]
    );

    if (!$user) {
        // Invalid / expired cookie – clear it
        setcookie(REMEMBER_COOKIE, '', time() - 3600, '/', '', false, true);
        return false;
    }

    // ✅ Regenerate session ID before populating new session
    session_regenerate_id(true);

    $_SESSION['user_id']       = $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['full_name']     = $user['full_name'];
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time']    = time();
    $_SESSION['via_remember']  = true;
    $_SESSION['csrf_token']    = bin2hex(random_bytes(32));

    // Rotate the remember-me token for security (token rotation)
    set_remember_me_cookie((int) $user['id']);

    db_execute(
        "UPDATE users SET last_login = NOW() WHERE id = :id",
        [':id' => $user['id']]
    );

    return true;
}

/**
 * Clear the remember-me token from the database for a given raw token.
 */
function clear_remember_me_token(string $raw_token): void
{
    $hashed = hash('sha256', $raw_token);
    db_execute(
        "UPDATE users
            SET remember_token = NULL,
                token_expires  = NULL
          WHERE remember_token = :token",
        [':token' => $hashed]
    );
}

// ---------------------------------------------------------------
// Role-Based Access Control (RBAC)
// ---------------------------------------------------------------

/**
 * Check if the current user is logged in.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Get current user's role.
 */
function current_role(): string
{
    return $_SESSION['role'] ?? '';
}

/**
 * Require the user to be logged in.
 * Redirects to login with reason=unauthorized if not.
 */
function require_login(): void
{
    check_session_timeout();

    if (!is_logged_in()) {
        header('Location: ' . LOGIN_URL . '?reason=unauthorized');
        exit;
    }
}

/**
 * Require the user to have a specific role.
 * Redirects to unauthorized.php if role doesn't match.
 *
 * @param string|array $roles  Allowed role(s): 'admin', 'student', or ['admin','student']
 */
function require_role(string|array $roles): void
{
    require_login();

    $allowed = is_array($roles) ? $roles : [$roles];

    if (!in_array(current_role(), $allowed, true)) {
        header('Location: ' . UNAUTH_URL);
        exit;
    }
}

/**
 * Redirect logged-in user to their appropriate dashboard.
 * Used in login.php to skip the form if already authenticated.
 */
function redirect_to_dashboard(): void
{
    $role = current_role();
    if ($role === ROLE_ADMIN) {
        header('Location: ' . ADMIN_DASH_URL);
    } else {
        header('Location: ' . STUDENT_DASH_URL);
    }
    exit;
}
