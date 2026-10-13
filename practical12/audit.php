<?php
/**
 * Practical 12 – Event Management CRUD with Poster Upload
 * audit.php  –  Advanced Extension: View audit log of event changes
 */

require_once __DIR__ . '/includes/db.php';

$db   = get_db();
$page = max(1, intval($_GET['page'] ?? 1));
$pp   = 20;
$off  = ($page - 1) * $pp;

$total     = $db->query("SELECT COUNT(*) AS c FROM audit_log")->fetch_assoc()['c'];
$totalPgs  = (int)ceil($total / $pp);

$rows = $db->query("SELECT * FROM audit_log ORDER BY id DESC LIMIT $pp OFFSET $off")
           ->fetch_all(MYSQLI_ASSOC);

function actionBadge(string $a): string {
    return ['CREATE'=>'badge-green','UPDATE'=>'badge-blue','DELETE'=>'badge-red'][$a] ?? 'badge-gray';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Audit Log – Practical 12</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <nav class="navbar">
    <a href="index.php" class="logo">🗓️ EventHub <span>Practical 12</span></a>
    <ul class="nav-links">
      <li><a href="index.php">📋 Events</a></li>
      <li><a href="add.php">➕ Add Event</a></li>
      <li><a href="audit.php" class="active">📜 Audit Log</a></li>
    </ul>
  </nav>
</header>

<main>
  <div class="page-header">
    <div><h1>📜 Audit Log</h1><p>All create, update, and delete actions on events.</p></div>
    <a href="index.php" class="btn btn-outline">← Back to Events</a>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Timestamp</th>
            <th>Action</th>
            <th>Event ID</th>
            <th>Details</th>
            <th>IP Address</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">No audit records yet.</td></tr>
          <?php else: foreach ($rows as $i => $r): ?>
          <tr>
            <td style="color:var(--muted);font-size:.8rem"><?= $off+$i+1 ?></td>
            <td style="font-size:.82rem;color:var(--muted)"><?= $r['created_at'] ?></td>
            <td><span class="badge <?= actionBadge($r['action']) ?>"><?= $r['action'] ?></span></td>
            <td><?= $r['event_id'] ?? '<span style="color:var(--muted)">—</span>' ?></td>
            <td><?= htmlspecialchars($r['details']) ?></td>
            <td style="font-size:.82rem;color:var(--muted)"><?= htmlspecialchars($r['ip_address']) ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPgs > 1): ?>
    <nav class="pagination">
      <?php for ($p = max(1,$page-2); $p <= min($totalPgs,$page+2); $p++): ?>
        <a href="?page=<?= $p ?>" class="<?= $p===$page?'active':''?>"><?= $p ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
  </div>
</main>

<footer class="site-footer">Practical 12 – Event Management CRUD &nbsp;|&nbsp; PHP + MySQLi + XAMPP</footer>
</body>
</html>
