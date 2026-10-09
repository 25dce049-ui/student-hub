<?php
/**
 * Practical 10 – Secure Login/Logout with Sessions & RBAC
 * dashboard_student.php  –  Student dashboard (role-restricted)
 *
 * Security features:
 *  ✅ require_role(ROLE_STUDENT) – blocks admins attempting direct access
 *  ✅ check_session_timeout() called inside require_role → require_login
 *  ✅ All user output escaped with htmlspecialchars()
 *  ✅ POST-based logout with CSRF token
 *  ✅ Live session info displayed for educational/viva purposes
 */

require_once __DIR__ . '/includes/auth.php';

// ── Access control ────────────────────────────────────────────────
// Students only. Admin trying to access this → unauthorized.php
require_role(ROLE_STUDENT);

// ── Fetch this student's own record ──────────────────────────────
$student = null;
$db_err  = '';

try {
    $student = db_fetch_one(
        "SELECT id, username, email, full_name, last_login, created_at
           FROM users
          WHERE id = :id
          LIMIT 1",
        [':id' => (int)$_SESSION['user_id']]
    );
} catch (RuntimeException $e) {
    $db_err = $e->getMessage();
}

$csrf      = csrf_token();
$full_name = htmlspecialchars($_SESSION['full_name'] ?? 'Student');
$login_ts  = date('d M Y, H:i:s', $_SESSION['login_time']   ?? time());
$last_act  = date('d M Y, H:i:s', $_SESSION['last_activity'] ?? time());
$timeout_remaining = SESSION_TIMEOUT - (time() - ($_SESSION['last_activity'] ?? time()));
$timeout_pct = max(0, min(100, ($timeout_remaining / SESSION_TIMEOUT) * 100));
$session_id  = session_id();

// Sample course data (static, no extra DB table needed for this practical)
$courses = [
    ['name' => 'Web Technologies',       'code' => 'CS401', 'grade' => 'A',  'status' => 'Passed'],
    ['name' => 'Database Management',    'code' => 'CS402', 'grade' => 'B+', 'status' => 'Passed'],
    ['name' => 'Operating Systems',      'code' => 'CS403', 'grade' => 'A-', 'status' => 'Passed'],
    ['name' => 'Software Engineering',   'code' => 'CS404', 'grade' => 'B',  'status' => 'In Progress'],
    ['name' => 'Computer Networks',      'code' => 'CS405', 'grade' => '—',  'status' => 'Upcoming'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Student Dashboard – Practical 10 | StudentHub" />
  <title>Student Dashboard – Practical 10 | StudentHub</title>
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
          <li><a href="dashboard_student.php" class="active">My Dashboard</a></li>
          <li><a href="../practical8/index.php">← Practical 8</a></li>
        </ul>
      </nav>
      <div class="nav-user">
        <span>👤 <?= $full_name ?></span>
        <span class="role-pill role-student"><?= htmlspecialchars(current_role()) ?></span>
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
      <h1>🎓 Student Dashboard</h1>
      <p>Welcome back, <?= $full_name ?>! &nbsp;·&nbsp; Practical 10 – Secure Sessions &amp; RBAC</p>
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
          <span class="session-val"><span class="badge badge-blue">student</span></span>
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

    <!-- Profile + Last Login Card -->
    <?php if ($student): ?>
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">🆔</div>
        <div class="stat-value" style="font-size:1.3rem;"><?= htmlspecialchars($student['username']) ?></div>
        <div class="stat-label">Username</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📧</div>
        <div class="stat-value" style="font-size:1rem;"><?= htmlspecialchars($student['email']) ?></div>
        <div class="stat-label">Email</div>
      </div>
      <div class="stat-card stat-card--green">
        <div class="stat-icon">🕐</div>
        <div class="stat-value" style="font-size:.95rem;">
          <?= $student['last_login']
              ? htmlspecialchars(date('d M Y, H:i', strtotime($student['last_login'])))
              : 'First login' ?>
        </div>
        <div class="stat-label">Last Login</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-value" style="font-size:.95rem;">
          <?= htmlspecialchars(date('d M Y', strtotime($student['created_at']))) ?>
        </div>
        <div class="stat-label">Member Since</div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Courses Table -->
    <div class="card">
      <h2>📚 My Courses</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Code</th>
              <th>Course Name</th>
              <th>Grade</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($courses as $c): ?>
            <tr>
              <td><code><?= htmlspecialchars($c['code']) ?></code></td>
              <td><?= htmlspecialchars($c['name']) ?></td>
              <td><strong><?= htmlspecialchars($c['grade']) ?></strong></td>
              <td>
                <?php
                $badge = match($c['status']) {
                    'Passed'      => 'badge-green',
                    'In Progress' => 'badge-yellow',
                    'Upcoming'    => 'badge-blue',
                    default       => 'badge-purple',
                };
                ?>
                <span class="badge <?= $badge ?>"><?= htmlspecialchars($c['status']) ?></span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Access Control Info -->
    <div class="card">
      <h2>🛡 Your Access Level</h2>
      <div class="security-grid">
        <div class="sec-item">
          <div class="sec-icon">✅</div>
          <strong>This Page – Allowed</strong>
          <p><code>require_role('student')</code> – you are authenticated as a student.</p>
        </div>
        <div class="sec-item">
          <div class="sec-icon">🚫</div>
          <strong>Admin Dashboard – Blocked</strong>
          <p>Visiting <code>dashboard_admin.php</code> will redirect you to the Unauthorized page.</p>
        </div>
        <div class="sec-item">
          <div class="sec-icon">⏱</div>
          <strong>Session Timeout Active</strong>
          <p>You will be logged out automatically after <?= SESSION_TIMEOUT / 60 ?> minutes of inactivity.</p>
        </div>
        <div class="sec-item">
          <div class="sec-icon">🍪</div>
          <strong>Remember-Me</strong>
          <p>
            <?= !empty($_SESSION['via_remember'])
              ? 'You were auto-logged in via a secure remember-me cookie (token rotated).'
              : 'You logged in manually. Use "Remember me" on the login page for auto-login.' ?>
          </p>
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
