<?php
// Practical 14 – Admin Dashboard RBAC + Audit Log
// includes/layout.php – Shared header/footer helpers

function page_header(string $title, string $currentFile = ''): void
{
    $user = current_user();
    $nav  = render_nav($currentFile);
    $roleClass = match($user['role']) {
        'admin'     => 'role-admin',
        'moderator' => 'role-mod',
        default     => 'role-student',
    };
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title} – Practical 14</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<header>
  <nav class="navbar">
    <a href="dashboard.php" class="logo">🛡️ AdminHub <span>Practical 14</span></a>
    <ul class="nav-links" id="mainNav">
      {$nav}
    </ul>
    <div class="nav-user">
      <span class="role-pill {$roleClass}">{$user['role']}</span>
      <span>👤 {$user['name']}</span>
      <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
  </nav>
</header>
<main>
HTML;
}

function page_footer(): void
{
    echo <<<HTML
</main>
<footer class="site-footer">
  Practical 14 – Admin Dashboard RBAC &nbsp;|&nbsp; PHP + MySQLi + XAMPP
</footer>
</body>
</html>
HTML;
}
