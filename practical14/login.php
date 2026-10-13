<?php
// Practical 14 – login.php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if ($email === '' || $pass === '') {
        $error = 'Email and password are required.';
    } else {
        $db   = get_db();
        $stmt = $db->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $error = 'Your account is inactive. Contact an administrator.';
        } else {
            // Regenerate session ID on login
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['last_active']= time();

            // Update last_login
            $db->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");

            // Audit
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $stmt2 = $db->prepare("INSERT INTO audit_logs (user_id, user_name, action, module, details, ip_address) VALUES (?,?,'LOGIN','Auth',?,?)");
            $detail = "{$user['name']} ({$user['role']}) logged in.";
            $stmt2->bind_param('isss', $user['id'], $user['name'], $detail, $ip);
            $stmt2->execute();
            $stmt2->close();

            header('Location: dashboard.php'); exit;
        }
    }
}

$reason = $_GET['reason'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login – Practical 14</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-brand">
      <span class="auth-icon">🛡️</span>
      <h1>AdminHub</h1>
      <p>Practical 14 – Role-Based Admin Dashboard</p>
    </div>

    <?php if ($reason === 'timeout'): ?>
      <div class="alert alert-warning">⏰ Session expired. Please log in again.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="admin@hub.local"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary btn-block" style="margin-top:1rem">🔐 Login</button>
    </form>

    <div class="auth-hint">
      <details>
        <summary>Test credentials</summary>
        <table class="cred-table">
          <tr><th>Email</th><th>Password</th><th>Role</th></tr>
          <tr><td>admin@hub.local</td><td>admin123</td><td>Admin</td></tr>
          <tr><td>mod@hub.local</td><td>mod123</td><td>Moderator</td></tr>
          <tr><td>alice@hub.local</td><td>student123</td><td>Student</td></tr>
        </table>
        <p style="font-size:.78rem;margin-top:.5rem;color:var(--muted)">
          Run <a href="seed.php">seed.php</a> first to create real password hashes.
        </p>
      </details>
    </div>
  </div>
</div>
</body>
</html>
