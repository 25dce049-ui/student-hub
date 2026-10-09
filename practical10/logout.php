<?php
/**
 * Practical 10 – Secure Login/Logout with Sessions & RBAC
 * logout.php  –  Secure logout handler
 *
 * Security features:
 *  ✅ CSRF token checked before destroying session
 *  ✅ session_destroy() + cookie expiry + session var wipe
 *  ✅ Remember-me cookie cleared and DB token nulled
 *  ✅ No GET-based logout (requires POST to prevent CSRF logout attacks)
 *  ✅ Redirects to login with ?reason=logout confirmation
 */

require_once __DIR__ . '/includes/auth.php';

// Only accept POST with valid CSRF to prevent logout CSRF attacks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    logout_user();
    header('Location: login.php?reason=logout');
    exit;
}

// If someone hits logout.php via GET (e.g. link), show a confirmation form
require_login(); // Must be logged in to see this page

$csrf      = csrf_token();
$full_name = htmlspecialchars($_SESSION['full_name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Logout – Practical 10 | StudentHub</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="login-page">

  <a class="skip-link" href="#main-content">Skip to main content</a>

  <main id="main-content" class="auth-wrapper">
    <div class="auth-card">

      <div class="auth-brand">
        <span class="auth-icon" aria-hidden="true">👋</span>
        <h1>Sign Out</h1>
        <p>Are you sure you want to log out, <strong><?= $full_name ?></strong>?</p>
      </div>

      <form method="POST" action="logout.php" aria-label="Logout confirmation">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />

        <div style="display:flex;gap:1rem;margin-top:1.5rem;">
          <button type="submit" class="btn btn-danger btn-block">
            Yes, Log Out
          </button>
          <a
            href="<?= current_role() === ROLE_ADMIN ? ADMIN_DASH_URL : STUDENT_DASH_URL ?>"
            class="btn btn-outline btn-block"
          >
            Cancel
          </a>
        </div>
      </form>

    </div>
  </main>

  <footer class="site-footer" role="contentinfo">
    <p>&copy; 2026 StudentHub – Practical 10</p>
  </footer>

</body>
</html>
