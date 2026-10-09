<?php
/**
 * Practical 10 – Secure Login/Logout with Sessions & RBAC
 * dashboard_admin.php  –  Admin-only dashboard
 *
 * Security features:
 *  ✅ require_role(ROLE_ADMIN) – blocks students + unauthenticated users
 *  ✅ check_session_timeout() called inside require_role → require_login
 *  ✅ Live session data displayed for inspection (educational)
 *  ✅ All user output escaped with htmlspecialchars()
 *  ✅ Logout uses POST + CSRF form (no GET logout)
 *  ✅ Last-login timestamp shown per user (intermediate extension)
 */

require_once __DIR__ . '/includes/auth.php';

// ── Access control ────────────────────────────────────────────────
// Redirects to unauthorized.php if role !== 'admin',
// redirects to login.php if not logged in at all.
require_role(ROLE_ADMIN);

// ── Fetch all users for admin table ──────────────────────────────
$users  = [];
$db_err = '';

try {
    $users = db_fetch_all(
        "SELECT id, username, email, role, full_name, is_active,
                last_login, created_at
           FROM users
          ORDER BY role ASC, username ASC"
    );
} catch (RuntimeException $e) {
    $db_err = $e->getMessage();
}

// ── Compute quick stats ───────────────────────────────────────────
$total_users   = count($users);
$total_admins  = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$total_students = $total_users - $total_admins;
$active_users  = count(array_filter($users, fn($u) => $u['is_active']));

$csrf      = csrf_token();
$full_name = htmlspecialchars($_SESSION['full_name'] ?? 'Admin');
$username  = htmlspecialchars($_SESSION['username']  ?? '');
$login_ts  = date('d M Y, H:i:s', $_SESSION['login_time']   ?? time());
$last_act  = date('d M Y, H:i:s', $_SESSION['last_activity'] ?? time());
$timeout_remaining = SESSION_TIMEOUT - (time() - ($_SESSION['last_activity'] ?? time()));
$timeout_pct = max(0, min(100, ($timeout_remaining / SESSION_TIMEOUT) * 100));
$session_id  = session_id();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Admin Dashboard – Practical 10 | StudentHub" />
  <title>Admin Dashboard – Practical 10 | StudentHub</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <a class="skip-link" href="#main-content">Skip to main content</a>

  <!-- NAVBAR -->
  <header>
    <div class="navbar" role="banner">
      <a href="login.php" class="logo" aria-label="StudentHub Home">🎓 StudentHub</a>
      <nav aria-label="Main navigation">
        <ul class="nav-links">
          <li><a href="dashboard_admin.php" class="active">Dashboard</a></li>
          <li><a href="../practical8/index.php">← Practical 8</a></li>
        </ul>
      </nav>
      <div class="nav-user">
        <span>👤 <?= $full_name ?></span>
        <span class="role-pill role-admin"><?= htmlspecialchars(current_role()) ?></span>
        <!-- POST-based logout to prevent CSRF logout attacks -->
        <form method="POST" action="logout.php" style="margin:0;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
          <button type="submit" class="btn btn-sm btn-danger" aria-label="Logout">
            🚪 Logout
          </button>
        </form>
      </div>
    </div>
  </header>

  <main id="main-content">

    <!-- Page Header -->
    <div class="page-header">
      <h1>⚙️ Admin Dashboard</h1>
      <p>Practical 10 – Secure Sessions · RBAC · Timeout · Remember-Me</p>
    </div>

    <?php if ($db_err): ?>
      <div class="alert alert-danger" role="alert">
        <span>❌</span> <?= htmlspecialchars($db_err) ?>
      </div>
    <?php endif; ?>

    <!-- Session Info Box -->
    <div class="session-box" role="region" aria-label="Current session information">
      <h3>🔍 Active Session Inspector</h3>
      <div class="session-grid">
        <div class="session-row">
          <span class="session-key">Session ID:</span>
          <span class="session-val"><code><?= htmlspecialchars(substr($session_id, 0, 20)) ?>…</code></span>
        </div>
        <div class="session-row">
          <span class="session-key">User ID:</span>
          <span class="session-val"><?= (int)($_SESSION['user_id'] ?? 0) ?></span>
        </div>
        <div class="session-row">
          <span class="session-key">Role:</span>
          <span class="session-val"><span class="badge badge-red">admin</span></span>
        </div>
        <div class="session-row">
          <span class="session-key">Login Time:</span>
          <span class="session-val"><?= $login_ts ?></span>
        </div>
        <div class="session-row">
          <span class="session-key">Last Activity:</span>
          <span class="session-val"><?= $last_act ?></span>
        </div>
        <div class="session-row">
          <span class="session-key">Via Remember-Me:</span>
          <span class="session-val">
            <?= !empty($_SESSION['via_remember'])
                  ? '<span class="badge badge-purple">Yes</span>'
                  : '<span class="badge badge-blue">No</span>' ?>
          </span>
        </div>
      </div>

      <!-- Timeout countdown bar -->
      <div style="margin-top:.9rem;">
        <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#166534;font-weight:600;">
          <span>⏱ Session Timeout</span>
          <span id="countdown"><?= gmdate('i:s', max(0, $timeout_remaining)) ?> remaining</span>
        </div>
        <div class="timeout-bar-wrap" aria-label="Session time remaining">
          <div class="timeout-bar" id="timeout-bar" style="width:<?= $timeout_pct ?>%;"></div>
        </div>
        <p class="muted-text" style="margin-top:.3rem;">
          Session expires after <?= SESSION_TIMEOUT / 60 ?> minutes of inactivity.
        </p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= $total_users ?></div>
        <div class="stat-label">Total Users</div>
      </div>
      <div class="stat-card stat-card--red">
        <div class="stat-icon">⚙️</div>
        <div class="stat-value"><?= $total_admins ?></div>
        <div class="stat-label">Admins</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🎓</div>
        <div class="stat-value"><?= $total_students ?></div>
        <div class="stat-label">Students</div>
      </div>
      <div class="stat-card stat-card--green">
        <div class="stat-icon">✅</div>
        <div class="stat-value"><?= $active_users ?></div>
        <div class="stat-label">Active Accounts</div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="card">
      <h2>👥 All Users</h2>
      <?php if (empty($users)): ?>
        <p class="muted-text">No users found. Run <code>seed.php</code> first.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Username</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Last Login</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
              <td><?= htmlspecialchars($u['full_name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <?php if ($u['role'] === 'admin'): ?>
                  <span class="badge badge-red">Admin</span>
                <?php else: ?>
                  <span class="badge badge-blue">Student</span>
                <?php endif; ?>
              </td>
              <td>
                <?= $u['is_active']
                    ? '<span class="badge badge-green">Active</span>'
                    : '<span class="badge badge-yellow">Inactive</span>' ?>
              </td>
              <td class="muted-text">
                <?= $u['last_login']
                    ? htmlspecialchars(date('d M Y, H:i', strtotime($u['last_login'])))
                    : '—' ?>
              </td>
              <td class="muted-text">
                <?= htmlspecialchars(date('d M Y', strtotime($u['created_at']))) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Security Implementation Notes -->
    <div class="card">
      <h2>🛡 Security Implementation – Practical 10</h2>
      <div class="security-grid">
        <div class="sec-item">
          <div class="sec-icon">🔑</div>
          <strong>password_verify()</strong>
          <p>Bcrypt hash comparison in constant time. Raw passwords never stored.</p>
        </div>
        <div class="sec-item">
          <div class="sec-icon">🔄</div>
          <strong>Session Regeneration</strong>
          <p><code>session_regenerate_id(true)</code> called on every login to prevent session fixation.</p>
        </div>
        <div class="sec-item">
          <div class="sec-icon">⏱</div>
          <strong>Session Timeout</strong>
          <p>Inactivity tracked via <code>$_SESSION['last_activity']</code>. Auto-logout after <?= SESSION_TIMEOUT / 60 ?> min.</p>
        </div>
        <div class="sec-item">
          <div class="sec-icon">👤</div>
          <strong>Role-Based Access</strong>
          <p><code>require_role('admin')</code> blocks this page for students. <code>require_login()</code> blocks guests.</p>
        </div>
        <div class="sec-item">
          <div class="sec-icon">🍪</div>
          <strong>Remember-Me Token</strong>
          <p>Random 32-byte token → raw in cookie, SHA-256 hash in DB. Token rotated on each use.</p>
        </div>
        <div class="sec-item">
          <div class="sec-icon">🛡</div>
          <strong>CSRF Protection</strong>
          <p><code>csrf_token()</code> embedded in every form. <code>csrf_verify()</code> checks on POST. Logout requires POST.</p>
        </div>
        <div class="sec-item">
          <div class="sec-icon">🍪</div>
          <strong>Secure Cookies</strong>
          <p>HttpOnly, SameSite=Strict, custom session name, <code>use_strict_mode=1</code>.</p>
        </div>
        <div class="sec-item">
          <div class="sec-icon">🕐</div>
          <strong>Last Login (Intermediate)</strong>
          <p>Timestamp updated in DB on every successful login. Displayed in the users table above.</p>
        </div>
      </div>
    </div>

  </main>

  <footer class="site-footer" role="contentinfo">
    <p>
      &copy; 2026 StudentHub – Practical 10: Secure Sessions &amp; RBAC &nbsp;|&nbsp;
      <a href="logout.php">Logout</a>
    </p>
    <p style="margin-top:.3rem; font-size:.75rem;">
      PHP Sessions · password_verify() · session_regenerate_id() · CSRF · RBAC · Remember-Me Token
    </p>
  </footer>

  <!-- Session timeout countdown script -->
  <script>
    (function () {
      let remaining = <?= max(0, $timeout_remaining) ?>;
      const bar     = document.getElementById('timeout-bar');
      const display = document.getElementById('countdown');
      const total   = <?= SESSION_TIMEOUT ?>;

      function fmt(s) {
        const m = String(Math.floor(s / 60)).padStart(2, '0');
        const sec = String(s % 60).padStart(2, '0');
        return m + ':' + sec;
      }

      function tick() {
        if (remaining <= 0) {
          display.textContent = '00:00 – redirecting…';
          window.location.href = 'login.php?reason=timeout';
          return;
        }
        remaining--;
        const pct = (remaining / total) * 100;
        bar.style.width = pct + '%';
        bar.style.background = pct > 50 ? '#16a34a' : pct > 20 ? '#d97706' : '#dc2626';
        display.textContent = fmt(remaining) + ' remaining';
        setTimeout(tick, 1000);
      }

      setTimeout(tick, 1000);
    })();
  </script>

</body>
</html>
