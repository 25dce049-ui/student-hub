<?php
/**
 * Practical 10 – Secure Login/Logout with Sessions & RBAC
 * seed.php  –  One-time utility to create/reset test users with hashed passwords
 *
 * ⚠️  DEVELOPMENT / SETUP ONLY – delete or move outside webroot in production.
 *
 * Run once in browser: http://localhost/practical10/seed.php
 * Or via CLI:          php seed.php
 *
 * What it does:
 *  1. Creates the database if it doesn't exist
 *  2. Creates the users table (via schema.sql or inline DDL)
 *  3. Inserts / updates test users with proper bcrypt hashes
 *     using password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])
 */

require_once __DIR__ . '/includes/db.php';

// ── Seed data ─────────────────────────────────────────────────────
$seed_users = [
    [
        'username'  => 'admin',
        'email'     => 'admin@studenthub.local',
        'password'  => 'admin123',
        'role'      => 'admin',
        'full_name' => 'Administrator',
    ],
    [
        'username'  => 'alice',
        'email'     => 'alice@studenthub.local',
        'password'  => 'student123',
        'role'      => 'student',
        'full_name' => 'Alice Johnson',
    ],
    [
        'username'  => 'bob',
        'email'     => 'bob@studenthub.local',
        'password'  => 'student123',
        'role'      => 'student',
        'full_name' => 'Bob Smith',
    ],
];

$results = [];
$error   = '';

try {
    $pdo = get_pdo();

    // Create table if it doesn't exist (in case schema.sql wasn't imported yet)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
            username        VARCHAR(60)      NOT NULL,
            email           VARCHAR(120)     NOT NULL,
            password_hash   VARCHAR(255)     NOT NULL,
            role            ENUM('admin','student') NOT NULL DEFAULT 'student',
            full_name       VARCHAR(120)     NOT NULL,
            is_active       TINYINT(1)       NOT NULL DEFAULT 1,
            remember_token  VARCHAR(100)     NULL DEFAULT NULL,
            token_expires   DATETIME         NULL DEFAULT NULL,
            last_login      DATETIME         NULL DEFAULT NULL,
            created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_username (username),
            UNIQUE KEY uq_email    (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // ✅ Hash each password with bcrypt cost 12
    foreach ($seed_users as $u) {
        $hash = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        // INSERT or UPDATE so seed.php is safe to re-run
        $affected = db_execute(
            "INSERT INTO users (username, email, password_hash, role, full_name)
             VALUES (:u, :e, :h, :r, :f)
             ON DUPLICATE KEY UPDATE
               password_hash = VALUES(password_hash),
               email         = VALUES(email),
               role          = VALUES(role),
               full_name     = VALUES(full_name),
               is_active     = 1",
            [
                ':u' => $u['username'],
                ':e' => $u['email'],
                ':h' => $hash,
                ':r' => $u['role'],
                ':f' => $u['full_name'],
            ]
        );

        $results[] = [
            'username'  => $u['username'],
            'password'  => $u['password'],
            'role'      => $u['role'],
            'full_name' => $u['full_name'],
            'hash'      => substr($hash, 0, 30) . '…',   // show prefix only
            'action'    => $affected === 1 ? 'Inserted' : 'Updated',
        ];
    }

} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

// ── Output ────────────────────────────────────────────────────────
// Support CLI output
$is_cli = PHP_SAPI === 'cli';

if ($is_cli) {
    if ($error) {
        echo "ERROR: $error\n";
        exit(1);
    }
    echo "Seed completed successfully.\n\n";
    foreach ($results as $r) {
        printf("  %-12s %-10s %-10s %s  %s\n",
            $r['username'], $r['password'], $r['role'], $r['action'], $r['hash']);
    }
    echo "\nYou can now log in at login.php\n";
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Seed Users – Practical 10 | StudentHub</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="login-page">

  <main class="auth-wrapper">
    <div class="auth-card" style="max-width:600px;">

      <div class="auth-brand">
        <span class="auth-icon">🌱</span>
        <h1>Database Seeder</h1>
        <p>Practical 10 – Creates hashed test users</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
          <span>❌</span>
          <div>
            <strong>Database Error</strong><br>
            <?= htmlspecialchars($error) ?><br>
            <small>Make sure XAMPP MySQL is running and the DB
              <code><?= DB_NAME ?></code> exists.</small>
          </div>
        </div>
      <?php else: ?>

        <div class="alert alert-success" role="alert">
          <span>✅</span>
          <strong><?= count($results) ?> users seeded successfully!</strong>
          Table <code>users</code> is ready in <code><?= DB_NAME ?></code>.
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Username</th>
                <th>Password</th>
                <th>Role</th>
                <th>Action</th>
                <th>Hash (prefix)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($results as $r): ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['username']) ?></strong></td>
                <td><code><?= htmlspecialchars($r['password']) ?></code></td>
                <td>
                  <?php if ($r['role'] === 'admin'): ?>
                    <span class="badge badge-red">Admin</span>
                  <?php else: ?>
                    <span class="badge badge-blue">Student</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $r['action'] === 'Inserted' ? 'badge-green' : 'badge-yellow' ?>">
                    <?= $r['action'] ?>
                  </span>
                </td>
                <td class="muted-text" style="font-size:.78rem;">
                  <code><?= htmlspecialchars($r['hash']) ?></code>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="alert alert-warning" style="margin-top:1.25rem;" role="note">
          <span>⚠️</span>
          <div>
            <strong>Security reminder:</strong> Delete or restrict
            <code>seed.php</code> in production. It inserts known credentials.
          </div>
        </div>

        <a href="login.php" class="btn btn-primary btn-block" style="margin-top:1rem;">
          → Proceed to Login
        </a>

      <?php endif; ?>

    </div>
  </main>

  <footer class="site-footer">
    <p>&copy; 2026 StudentHub – Practical 10</p>
  </footer>

</body>
</html>
