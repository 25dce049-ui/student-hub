<?php
/**
 * Practical 9 – Secure User Registration
 * users.php  –  View all registered users (without exposing password hashes)
 *
 * Demonstrates:
 *  • SELECT with MySQLi prepared statement
 *  • Proper output escaping with htmlspecialchars()
 */

session_start();
require_once __DIR__ . '/includes/db.php';

$error = '';
$users = [];
$db_ok = false;

try {
    $db    = get_db();
    $db_ok = true;

    // Fetch all users – SELECT specific columns (never expose password_hash)
    $result = $db->query(
        "SELECT id, username, full_name, email, created_at
         FROM users
         ORDER BY created_at DESC"
    );
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();

} catch (RuntimeException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Practical 9 – All Registered Users | StudentHub" />
  <title>Practical 9 – Users | StudentHub</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <a class="skip-link" href="#main-content">Skip to main content</a>

  <header>
    <div class="navbar" role="banner">
      <a href="../new/index.html" class="logo" aria-label="StudentHub Home">🎓 StudentHub</a>
      <nav aria-label="Main navigation">
        <ul class="nav-links">
          <li><a href="index.php">Register</a></li>
          <li><a href="users.php" class="active">All Users</a></li>
          <li><a href="../practical8/index.php">← Practical 8</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main-content">

    <div class="page-header">
      <h1>Practical 9 – Registered Users</h1>
      <p>All users registered through the secure registration form.</p>
    </div>

    <!-- DB status -->
    <?php if (!$db_ok): ?>
      <div class="alert alert-danger" role="alert" aria-live="polite">
        <span class="alert-icon">❌</span>
        <div>
          <strong>Database Error</strong><br>
          <?= htmlspecialchars($error) ?><br>
          <small>Make sure XAMPP MySQL is running and you have imported <code>sql/schema.sql</code>.</small>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($db_ok): ?>
    <div class="card">
      <h2>👥 Users
        <span style="font-size:.85rem; font-weight:400; color:var(--muted); margin-left:.5rem;">
          (<?= count($users) ?> registered)
        </span>
      </h2>

      <?php if (empty($users)): ?>
        <p class="muted-text">No users registered yet. <a href="index.php">Register the first user →</a></p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Full Name</th>
                <th scope="col">Username</th>
                <th scope="col">Email</th>
                <th scope="col">Registered At</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td>
                  <span class="badge badge-blue"><?= htmlspecialchars($u['username']) ?></span>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($u['created_at']))) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Audit Log -->
    <?php
    $logs = [];
    try {
        $res = $db->query(
            "SELECT action, username, email, ip_address, logged_at
             FROM audit_log
             ORDER BY logged_at DESC
             LIMIT 20"
        );
        while ($row = $res->fetch_assoc()) {
            $logs[] = $row;
        }
        $res->free();
    } catch (Throwable $e) {
        // audit_log table may not exist yet – silently skip
    }
    ?>
    <?php if (!empty($logs)): ?>
    <div class="card">
      <h2>📋 Recent Audit Log <small style="font-size:.78rem; font-weight:400;">(last 20 entries)</small></h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th scope="col">Action</th>
              <th scope="col">Username</th>
              <th scope="col">Email</th>
              <th scope="col">IP Address</th>
              <th scope="col">Logged At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
              <td>
                <?php
                $cls = match(true) {
                    str_contains($log['action'], 'SUCCESS')   => 'badge-green',
                    str_contains($log['action'], 'DUPLICATE') => 'badge-yellow',
                    default                                   => 'badge-red',
                };
                ?>
                <span class="badge <?= $cls ?>"><?= htmlspecialchars($log['action']) ?></span>
              </td>
              <td><?= htmlspecialchars($log['username'] ?? '—') ?></td>
              <td><?= htmlspecialchars($log['email']    ?? '—') ?></td>
              <td><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
              <td><?= htmlspecialchars(date('d M Y, H:i:s', strtotime($log['logged_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; // db_ok ?>

    <div style="text-align:center; margin-top:1rem;">
      <a href="index.php" class="btn-primary">➕ Register New User</a>
    </div>

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
