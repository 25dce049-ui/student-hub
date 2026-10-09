<?php
/**
 * Practical 10 – Secure Login/Logout with Sessions & RBAC
 * unauthorized.php  –  Access denied page
 *
 * Shown when:
 *  - A student tries to access dashboard_admin.php
 *  - An admin tries to access dashboard_student.php
 *  - Any future page that calls require_role() with an unmatched role
 *
 * Security:
 *  ✅ Sets HTTP 403 Forbidden status code
 *  ✅ Does NOT expose which page was attempted (no referrer leakage)
 *  ✅ Offers the user their correct dashboard link based on role
 */

require_once __DIR__ . '/includes/auth.php';

// Soft boot – no require_login() here; guests can also land here
session_boot();

// Return proper HTTP status
http_response_code(403);

$is_logged_in = is_logged_in();
$role         = current_role();
$full_name    = htmlspecialchars($_SESSION['full_name'] ?? '');

// Where should this user actually go?
$back_url   = LOGIN_URL;
$back_label = 'Go to Login';
if ($is_logged_in) {
    if ($role === ROLE_ADMIN) {
        $back_url   = ADMIN_DASH_URL;
        $back_label = 'Go to Admin Dashboard';
    } else {
        $back_url   = STUDENT_DASH_URL;
        $back_label = 'Go to My Dashboard';
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Access Denied – Practical 10 | StudentHub" />
  <title>403 Unauthorized – Practical 10 | StudentHub</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <a class="skip-link" href="#main-content">Skip to main content</a>

  <?php if ($is_logged_in): ?>
  <!-- Minimal nav for logged-in users -->
  <header>
    <div class="navbar" role="banner">
      <a href="login.php" class="logo" aria-label="StudentHub Home">🎓 StudentHub</a>
      <div class="nav-user">
        <?php if ($full_name): ?>
          <span>👤 <?= $full_name ?></span>
          <span class="role-pill <?= $role === ROLE_ADMIN ? 'role-admin' : 'role-student' ?>">
            <?= htmlspecialchars($role) ?>
          </span>
        <?php endif; ?>
        <form method="POST" action="logout.php" style="margin:0;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
          <button type="submit" class="btn btn-sm btn-danger">🚪 Logout</button>
        </form>
      </div>
    </div>
  </header>
  <?php endif; ?>

  <main id="main-content" class="unauth-wrapper">
    <div class="unauth-card" role="main" aria-labelledby="unauth-title">

      <div class="big-icon" aria-hidden="true">🚫</div>

      <h1 id="unauth-title">403 – Access Denied</h1>

      <p>
        <?php if ($is_logged_in && $role === ROLE_STUDENT): ?>
          This page requires <strong>Admin</strong> privileges.<br>
          Your account <em>(<?= $full_name ?>)</em> has the <strong>Student</strong> role.
        <?php elseif ($is_logged_in && $role === ROLE_ADMIN): ?>
          This page is restricted to <strong>Students</strong>.<br>
          Your account <em>(<?= $full_name ?>)</em> has the <strong>Admin</strong> role.
        <?php else: ?>
          You do not have permission to access this page.
          Please log in with an account that has the required role.
        <?php endif; ?>
      </p>

      <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-primary">
        <?= htmlspecialchars($back_label) ?>
      </a>

      <!-- Educational note -->
      <div class="alert alert-info" style="margin-top:2rem; text-align:left;">
        <span aria-hidden="true">ℹ️</span>
        <div>
          <strong>How RBAC works here:</strong><br>
          Each protected page calls <code>require_role('admin')</code> or
          <code>require_role('student')</code> at the top. If your session's
          <code>$_SESSION['role']</code> doesn't match, PHP immediately
          redirects here with a <strong>HTTP 403</strong> status — no page
          content is ever rendered for the unauthorised user.
        </div>
      </div>

    </div>
  </main>

  <footer class="site-footer" role="contentinfo">
    <p>&copy; 2026 StudentHub – Practical 10: Secure Sessions &amp; RBAC</p>
  </footer>

</body>
</html>
