<?php
/**
 * Practical 9 – Secure User Registration
 * success.php  –  Registration success page
 *
 * Reads the registered user details from session flash data
 * and displays a confirmation. Clears the flash data after display.
 */

session_start();

// If no flash data, redirect back to form
if (empty($_SESSION['registered_user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['registered_user'];
unset($_SESSION['registered_user']); // Consume flash data
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Registration Successful | Practical 9 – StudentHub" />
  <title>Registration Successful | StudentHub</title>
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
          <li><a href="index.php">Register</a></li>
          <li><a href="users.php">All Users</a></li>
          <li><a href="../practical8/index.php">← Practical 8</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main-content">

    <div class="page-header">
      <h1>Practical 9 – Secure User Registration</h1>
      <p>Registration result</p>
    </div>

    <div class="register-wrap">

      <div class="card">
        <div class="success-box" role="status" aria-live="polite">
          <div class="success-icon" aria-hidden="true">🎉</div>
          <h2>Registration Successful!</h2>
          <p>
            Your account has been created securely. Your password was hashed using
            <strong>bcrypt</strong> before being stored in the database.
          </p>

          <!-- Registered user details -->
          <div class="user-details" aria-label="Registered user details">
            <p><strong>User ID:</strong>   #<?= (int)$user['id'] ?></p>
            <p><strong>Full Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
            <p><strong>Username:</strong>  <?= htmlspecialchars($user['username']) ?></p>
            <p><strong>Email:</strong>     <?= htmlspecialchars($user['email']) ?></p>
          </div>

          <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
            <a href="index.php" class="btn-primary">➕ Register Another</a>
            <a href="users.php" class="btn-secondary">👥 View All Users</a>
          </div>
        </div>
      </div>

      <!-- What happened under the hood -->
      <div class="card">
        <h2>🔍 What Happened Under the Hood</h2>
        <div class="info-grid">
          <div class="info-item">
            <strong>1. CSRF Validated</strong>
            <code>hash_equals()</code>
            <p>The one-time token was verified before any processing began, preventing cross-site request forgery.</p>
          </div>
          <div class="info-item">
            <strong>2. Input Validated</strong>
            <code>filter_var() + mb_strlen()</code>
            <p>All fields were checked on both frontend (JS) and backend (PHP) for length, format, and required rules.</p>
          </div>
          <div class="info-item">
            <strong>3. Duplicate Check</strong>
            <code>SELECT + prepared statement</code>
            <p>Email and username were checked against existing records using a MySQLi prepared statement.</p>
          </div>
          <div class="info-item">
            <strong>4. Password Hashed</strong>
            <code>password_hash($pwd, PASSWORD_BCRYPT)</code>
            <p>Plain-text password was never stored. bcrypt applied a random salt and 10 rounds of hashing.</p>
          </div>
          <div class="info-item">
            <strong>5. INSERT Executed</strong>
            <code>MySQLi prepared statement</code>
            <p>User data was inserted using a bound prepared statement — fully protected from SQL injection.</p>
          </div>
          <div class="info-item">
            <strong>6. Audit Logged</strong>
            <code>audit_log table</code>
            <p>The registration event, IP address, and user agent were recorded in the audit_log table.</p>
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

</body>
</html>
