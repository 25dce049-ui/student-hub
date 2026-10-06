<?php
/**
 * Practical 8 – StudentHub
 * index.php  –  Dashboard & PDO connection test
 *
 * Demonstrates:
 *  • Secure PDO connection via get_pdo()
 *  • Aggregate queries with prepared statements
 *  • Graceful error handling (try/catch RuntimeException)
 */

require_once __DIR__ . '/includes/db.php';

// ---------- Run connection test & fetch stats ----------
$error   = '';
$stats   = [];
$recent  = [];
$db_ok   = false;

try {
    $pdo   = get_pdo();
    $db_ok = true;

    // Total counts (aggregate – no user input, no params needed)
    $stats['students']      = db_fetch_one("SELECT COUNT(*) AS cnt FROM students")['cnt']      ?? 0;
    $stats['events']        = db_fetch_one("SELECT COUNT(*) AS cnt FROM events")['cnt']        ?? 0;
    $stats['registrations'] = db_fetch_one("SELECT COUNT(*) AS cnt FROM registrations")['cnt'] ?? 0;
    $stats['confirmed']     = db_fetch_one(
        "SELECT COUNT(*) AS cnt FROM registrations WHERE status = :s",
        [':s' => 'Confirmed']
    )['cnt'] ?? 0;

    // Recent 5 registrations – prepared statement with JOIN
    $recent = db_fetch_all("
        SELECT
            r.reg_id,
            s.full_name,
            s.course,
            e.title        AS event_title,
            e.category,
            r.status,
            r.registered_at
        FROM registrations r
        JOIN students s ON s.student_id = r.student_id
        JOIN events   e ON e.event_id   = r.event_id
        ORDER BY r.registered_at DESC
        LIMIT 5
    ");

} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

// ---------- Status badge helper ----------
function status_badge(string $status): string {
    $map = [
        'Confirmed' => 'badge-green',
        'Pending'   => 'badge-yellow',
        'Cancelled' => 'badge-red',
    ];
    $cls = $map[$status] ?? 'badge-blue';
    return "<span class=\"badge {$cls}\">{$status}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Practical 8 – MySQL Schema, PDO Connectivity & Prepared Statements | StudentHub" />
  <title>Practical 8 – Dashboard | StudentHub</title>
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
          <li><a href="index.php"    class="active">Dashboard</a></li>
          <li><a href="students.php">Students</a></li>
          <li><a href="events.php">Events</a></li>
          <li><a href="register.php">Register</a></li>
          <li><a href="../practical7/index.php">← Practical 7</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main-content">

    <!-- Page Header -->
    <div class="page-header">
      <h1>Practical 8 – MySQL &amp; PDO</h1>
      <p>
        Schema Design · ER Model · PDO Connectivity · Prepared Statements · Stored Procedures
      </p>
    </div>

    <!-- DB Connection Status -->
    <?php if ($db_ok): ?>
      <div class="alert alert-success" role="alert" aria-live="polite">
        <span class="alert-icon">✅</span>
        <div>
          <strong>Database Connected!</strong>
          Connected to <code><?= DB_NAME ?></code> on <code><?= DB_HOST ?>:<?= DB_PORT ?></code>
          using PDO with <code>ERRMODE_EXCEPTION</code> and real prepared statements.
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-danger" role="alert" aria-live="polite">
        <span class="alert-icon">❌</span>
        <div>
          <strong>Connection Failed</strong><br>
          <?= htmlspecialchars($error) ?><br>
          <small>Make sure XAMPP MySQL is running and the database <code><?= DB_NAME ?></code> exists.
          Import <code>sql/schema.sql</code> via phpMyAdmin first.</small>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($db_ok): ?>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">👩‍🎓</div>
        <div class="stat-value"><?= $stats['students'] ?></div>
        <div class="stat-label">Total Students</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-value"><?= $stats['events'] ?></div>
        <div class="stat-label">Total Events</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-value"><?= $stats['registrations'] ?></div>
        <div class="stat-label">Registrations</div>
      </div>
      <div class="stat-card stat-card--green">
        <div class="stat-icon">✅</div>
        <div class="stat-value"><?= $stats['confirmed'] ?></div>
        <div class="stat-label">Confirmed</div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <h2>🚀 Quick Actions</h2>
      <div class="action-grid">
        <a href="students.php" class="action-btn">
          <span class="action-icon">👩‍🎓</span>
          <span>View Students</span>
        </a>
        <a href="events.php" class="action-btn">
          <span class="action-icon">📅</span>
          <span>Browse Events</span>
        </a>
        <a href="register.php" class="action-btn action-btn--primary">
          <span class="action-icon">➕</span>
          <span>New Registration</span>
        </a>
      </div>
    </div>

    <!-- Recent Registrations -->
    <div class="card">
      <h2>🕐 Recent Registrations</h2>
      <?php if (empty($recent)): ?>
        <p class="muted-text">No registrations yet.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Student</th>
              <th>Course</th>
              <th>Event</th>
              <th>Category</th>
              <th>Status</th>
              <th>Registered At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $row): ?>
            <tr>
              <td><?= (int)$row['reg_id'] ?></td>
              <td><?= htmlspecialchars($row['full_name']) ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($row['course']) ?></span></td>
              <td><?= htmlspecialchars($row['event_title']) ?></td>
              <td><span class="badge badge-purple"><?= htmlspecialchars($row['category']) ?></span></td>
              <td><?= status_badge($row['status']) ?></td>
              <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($row['registered_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- PDO Info Card -->
    <div class="card">
      <h2>🔐 Security &amp; Implementation Notes</h2>
      <div class="stats-strip">
        <span class="stat-pill">✅ PDO Prepared Statements</span>
        <span class="stat-pill">✅ ERRMODE_EXCEPTION</span>
        <span class="stat-pill">✅ Emulated Prepares OFF</span>
        <span class="stat-pill">✅ utf8mb4 Charset</span>
        <span class="stat-pill">✅ Singleton Connection</span>
        <span class="stat-pill">✅ FK Constraints</span>
        <span class="stat-pill">✅ 3NF Normalized</span>
      </div>
      <div class="info-grid" style="margin-top:1rem;">
        <div class="info-item">
          <strong>Schema</strong>
          <code>sql/schema.sql</code>
          <p>3 tables: students, events, registrations. All in 3NF with PK/FK constraints.</p>
        </div>
        <div class="info-item">
          <strong>Connection</strong>
          <code>includes/db.php</code>
          <p>Singleton PDO with named placeholders, exception mode, real server-side prepares.</p>
        </div>
        <div class="info-item">
          <strong>Stored Procedures</strong>
          <code>sql/stored_procedures.sql</code>
          <p>Advanced: <code>sp_register_student</code>, <code>sp_event_summary</code>, <code>sp_student_history</code>.</p>
        </div>
      </div>
    </div>

    <?php endif; // db_ok ?>

  </main>

  <footer class="site-footer" role="contentinfo">
    <p>
      &copy; 2026 StudentHub – Practical 8: MySQL &amp; PDO &nbsp;|&nbsp;
      <a href="students.php">Students</a> &nbsp;|&nbsp;
      <a href="events.php">Events</a> &nbsp;|&nbsp;
      <a href="register.php">Register</a>
    </p>
    <p style="margin-top:.4rem; font-size:.78rem;">
      MySQL · phpMyAdmin · PHP PDO · Prepared Statements · Stored Procedures · XAMPP
    </p>
  </footer>

</body>
</html>
