<?php
/**
 * Practical 9 – Secure User Registration
 * index.php  –  Registration form with CSRF token injection
 *
 * This page:
 *  • Starts the session and generates a CSRF token
 *  • Renders the registration form with frontend validation
 *  • Repopulates fields on validation failure (values from session flash)
 */

session_start();
require_once __DIR__ . '/includes/csrf.php';

$token  = csrf_generate_token();

// Flash data from a failed registration attempt
$old    = $_SESSION['old']    ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old'], $_SESSION['errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Practical 9 – Secure User Registration | StudentHub" />
  <title>Practical 9 – Register | StudentHub</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <a class="skip-link" href="#main-content">Skip to main content</a>

  <!-- NAVBAR -->
  <header>
    <div class="navbar" role="banner">
      <a href="../new/index.html" class="logo" aria-label="StudentHub Home">🎓 StudentHub</a>
      <nav aria-label="Main navigation">
        <ul class="nav-links">
          <li><a href="index.php" class="active">Register</a></li>
          <li><a href="users.php">All Users</a></li>
          <li><a href="../practical8/index.php">← Practical 8</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main-content">

    <div class="page-header">
      <h1>Practical 9 – Secure User Registration</h1>
      <p>
        MySQLi · password_hash() · Duplicate Email/Username Check · Prepared Statements · CSRF Protection
      </p>
    </div>

    <div class="register-wrap">

      <!-- Global error banner (repopulated after failed submit) -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert" aria-live="polite">
          <span class="alert-icon">❌</span>
          <div>
            <strong>Please fix the following errors:</strong>
            <ul>
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <!-- Registration form -->
      <div class="card">
        <h2>📝 Create Your Account</h2>

        <form
          id="registerForm"
          action="register.php"
          method="POST"
          novalidate
          aria-label="User registration form"
        >
          <!-- CSRF hidden field -->
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>" />

          <div class="form-grid">

            <!-- Full Name -->
            <div class="form-group full-width">
              <label for="full_name">
                Full Name <span class="required" aria-hidden="true">*</span>
              </label>
              <input
                type="text"
                id="full_name"
                name="full_name"
                value="<?= htmlspecialchars($old['full_name'] ?? '') ?>"
                placeholder="e.g. Ishal Patel"
                autocomplete="name"
                required
                minlength="3"
                maxlength="100"
                aria-required="true"
                aria-describedby="full_name_err"
              />
              <span class="field-error" id="full_name_err" role="alert" aria-live="polite"></span>
            </div>

            <!-- Username -->
            <div class="form-group">
              <label for="username">
                Username <span class="required" aria-hidden="true">*</span>
              </label>
              <input
                type="text"
                id="username"
                name="username"
                value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                placeholder="e.g. ishal99"
                autocomplete="username"
                required
                minlength="3"
                maxlength="50"
                pattern="^[a-zA-Z0-9_]+$"
                aria-required="true"
                aria-describedby="username_err"
              />
              <span class="field-error" id="username_err" role="alert" aria-live="polite"></span>
            </div>

            <!-- Email -->
            <div class="form-group">
              <label for="email">
                Email Address <span class="required" aria-hidden="true">*</span>
              </label>
              <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                placeholder="e.g. ishal@example.com"
                autocomplete="email"
                required
                maxlength="100"
                aria-required="true"
                aria-describedby="email_err"
              />
              <span class="field-error" id="email_err" role="alert" aria-live="polite"></span>
            </div>

            <!-- Password -->
            <div class="form-group">
              <label for="password">
                Password <span class="required" aria-hidden="true">*</span>
              </label>
              <div class="input-with-toggle">
                <input
                  type="password"
                  id="password"
                  name="password"
                  placeholder="Min 8 chars"
                  autocomplete="new-password"
                  required
                  minlength="8"
                  aria-required="true"
                  aria-describedby="password_err password_strength_label"
                />
                <button
                  type="button"
                  class="toggle-pwd"
                  aria-label="Toggle password visibility"
                  data-target="password"
                >👁️</button>
              </div>
              <div class="strength-bar-wrap" aria-hidden="true">
                <div class="strength-bar" id="strengthBar"></div>
              </div>
              <span class="strength-label" id="password_strength_label" aria-live="polite"></span>
              <span class="field-error" id="password_err" role="alert" aria-live="polite"></span>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
              <label for="confirm_password">
                Confirm Password <span class="required" aria-hidden="true">*</span>
              </label>
              <div class="input-with-toggle">
                <input
                  type="password"
                  id="confirm_password"
                  name="confirm_password"
                  placeholder="Re-enter password"
                  autocomplete="new-password"
                  required
                  aria-required="true"
                  aria-describedby="confirm_password_err"
                />
                <button
                  type="button"
                  class="toggle-pwd"
                  aria-label="Toggle confirm password visibility"
                  data-target="confirm_password"
                >👁️</button>
              </div>
              <span class="field-error" id="confirm_password_err" role="alert" aria-live="polite"></span>
            </div>

          </div><!-- /form-grid -->

          <div class="form-actions">
            <button type="submit" class="btn-primary" id="submitBtn">
              🔒 Register Securely
            </button>
            <button type="reset" class="btn-secondary">↺ Reset</button>
          </div>

        </form>

        <p style="margin-top:1.25rem; font-size:.88rem; color:var(--muted);">
          Already have an account? <a href="users.php">View registered users →</a>
        </p>

      </div><!-- /card -->

      <!-- Security info -->
      <div class="card">
        <h2>🔐 Security Features</h2>
        <div class="stats-strip">
          <span class="stat-pill">✅ password_hash() – bcrypt</span>
          <span class="stat-pill">✅ Duplicate Email Check</span>
          <span class="stat-pill">✅ Duplicate Username Check</span>
          <span class="stat-pill">✅ MySQLi Prepared Statements</span>
          <span class="stat-pill">✅ CSRF Token</span>
          <span class="stat-pill">✅ Frontend Validation</span>
          <span class="stat-pill">✅ Backend Validation</span>
          <span class="stat-pill">✅ Audit Log</span>
        </div>
        <div class="info-grid" style="margin-top:1rem;">
          <div class="info-item">
            <strong>Password Hashing</strong>
            <code>password_hash($pwd, PASSWORD_BCRYPT)</code>
            <p>Passwords are never stored as plain text. bcrypt automatically applies a salt.</p>
          </div>
          <div class="info-item">
            <strong>Duplicate Check</strong>
            <code>UNIQUE KEY on email &amp; username</code>
            <p>Both DB-level unique constraints and PHP pre-check prevent duplicate accounts.</p>
          </div>
          <div class="info-item">
            <strong>CSRF Protection</strong>
            <code>bin2hex(random_bytes(32))</code>
            <p>A one-time token is embedded in the form and validated before any DB operation.</p>
          </div>
        </div>
      </div>

    </div><!-- /register-wrap -->

  </main>

  <footer class="site-footer" role="contentinfo">
    <p>
      &copy; 2026 StudentHub – Practical 9: Secure User Registration &nbsp;|&nbsp;
      <a href="index.php">Register</a> &nbsp;|&nbsp;
      <a href="users.php">All Users</a>
    </p>
    <p style="margin-top:.4rem; font-size:.78rem;">
      PHP · MySQLi · password_hash() · Prepared Statements · CSRF Protection · XAMPP
    </p>
  </footer>

  <script src="js/validate.js"></script>
</body>
</html>
