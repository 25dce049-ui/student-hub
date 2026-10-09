<?php
/**
 * Practical 10 – Secure Login/Logout with Sessions & RBAC
 * login.php  –  Login form and authentication handler
 *
 * Security features demonstrated:
 *  ✅ CSRF token on every form submission
 *  ✅ password_verify() via login_user() in auth.php
 *  ✅ session_regenerate_id(true) on successful login
 *  ✅ Role-based redirection (admin → admin dash, student → student dash)
 *  ✅ Remember-me cookie (raw token in cookie, SHA-256 hash in DB)
 *  ✅ Auto-login via remember-me token on revisit
 *  ✅ Generic error message (no username/password hint leakage)
 *  ✅ Output buffering so headers work after HTML starts
 */

require_once __DIR__ . '/includes/auth.php';

// ── Auto-login via remember-me cookie ────────────────────────────
$remembered = false;
try {
    $remembered = try_remember_me_login();
} catch (RuntimeException $e) {
    // DB not ready – continue to show login form
}

if ($remembered || is_logged_in()) {
    redirect_to_dashboard();
}

// ── Handle POST submission ────────────────────────────────────────
$error   = '';
$success = '';

// Show contextual banner based on ?reason= query param
$reason = $_GET['reason'] ?? '';
if ($reason === 'timeout') {
    $user_hint = htmlspecialchars($_GET['user'] ?? '');
    $error = "Your session timed out after " . (SESSION_TIMEOUT / 60) . " minutes of inactivity"
           . ($user_hint ? " ($user_hint)" : '') . ". Please log in again.";
} elseif ($reason === 'unauthorized') {
    $error = 'You must be logged in to access that page.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Basic presence validation
    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $result = login_user($username, $password, $remember);

            if ($result['ok']) {
                // ✅ Role-based redirection
                redirect_to_dashboard();
            } else {
                $error = $result['error'];
            }
        } catch (RuntimeException $e) {
            $error = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Practical 10 – Secure Login | StudentHub" />
  <title>Login – Practical 10 | StudentHub</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="login-page">

  <a class="skip-link" href="#main-content">Skip to main content</a>

  <main id="main-content" class="auth-wrapper">

    <div class="auth-card">

      <!-- Brand -->
      <div class="auth-brand">
        <span class="auth-icon" aria-hidden="true">🔐</span>
        <h1>StudentHub</h1>
        <p>Practical 10 – Secure Login</p>
      </div>

      <!-- Alert Banner -->
      <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert" aria-live="assertive">
          <span aria-hidden="true">⚠️</span> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success !== ''): ?>
        <div class="alert alert-success" role="alert" aria-live="polite">
          <span aria-hidden="true">✅</span> <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="POST" action="login.php" novalidate aria-label="Login form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />

        <div class="form-group">
          <label for="username">Username</label>
          <input
            type="text"
            id="username"
            name="username"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            autocomplete="username"
            required
            aria-required="true"
            placeholder="Enter your username"
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input
              type="password"
              id="password"
              name="password"
              autocomplete="current-password"
              required
              aria-required="true"
              placeholder="Enter your password"
            />
            <button
              type="button"
              class="toggle-pw"
              aria-label="Toggle password visibility"
              onclick="togglePw()"
            >👁</button>
          </div>
        </div>

        <div class="form-check">
          <input
            type="checkbox"
            id="remember"
            name="remember"
            <?= isset($_POST['remember']) ? 'checked' : '' ?>
          />
          <label for="remember">Remember me for <?= REMEMBER_ME_DAYS ?> days</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          Sign In
        </button>
      </form>

      <!-- Test credentials hint (remove in production) -->
      <div class="auth-hint">
        <details>
          <summary>Test credentials (dev only)</summary>
          <table class="cred-table">
            <thead><tr><th>Username</th><th>Password</th><th>Role</th></tr></thead>
            <tbody>
              <tr><td>admin</td><td>admin123</td><td><span class="badge badge-red">Admin</span></td></tr>
              <tr><td>alice</td><td>student123</td><td><span class="badge badge-blue">Student</span></td></tr>
              <tr><td>bob</td><td>student123</td><td><span class="badge badge-blue">Student</span></td></tr>
            </tbody>
          </table>
          <p class="muted-text" style="margin-top:.5rem;">
            Run <code>seed.php</code> once to create these users with hashed passwords.
          </p>
        </details>
      </div>

    </div><!-- /.auth-card -->

    <!-- Feature badges -->
    <div class="feature-strip" aria-label="Security features">
      <span class="feat-pill">🔑 password_verify()</span>
      <span class="feat-pill">🔄 Session Regeneration</span>
      <span class="feat-pill">⏱ <?= SESSION_TIMEOUT / 60 ?>-min Timeout</span>
      <span class="feat-pill">👤 Role-Based Access</span>
      <span class="feat-pill">🍪 Secure Remember-Me</span>
      <span class="feat-pill">🛡 CSRF Protection</span>
    </div>

  </main>

  <footer class="site-footer" role="contentinfo">
    <p>
      &copy; 2026 StudentHub – Practical 10: Secure Sessions &amp; RBAC &nbsp;|&nbsp;
      PHP Sessions · password_verify() · CSRF · Role-Based Access Control
    </p>
  </footer>

  <script>
    function togglePw() {
      const pw  = document.getElementById('password');
      const btn = document.querySelector('.toggle-pw');
      if (pw.type === 'password') {
        pw.type = 'text';
        btn.textContent = '🙈';
        btn.setAttribute('aria-label', 'Hide password');
      } else {
        pw.type = 'password';
        btn.textContent = '👁';
        btn.setAttribute('aria-label', 'Show password');
      }
    }
  </script>

</body>
</html>
