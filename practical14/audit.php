<?php
// Practical 14 – audit.php  (admin only)

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_role([ROLE_ADMIN]);

$db   = get_db();
$page = max(1, intval($_GET['page'] ?? 1));
$pp   = 20;
$off  = ($page - 1) * $pp;

$total    = $db->query("SELECT COUNT(*) AS c FROM audit_logs")->fetch_assoc()['c'];
$totalPgs = (int)ceil($total / $pp);

$rows = $db->query(
    "SELECT * FROM audit_logs ORDER BY id DESC LIMIT $pp OFFSET $off"
)->fetch_all(MYSQLI_ASSOC);

function auditBadge(string $a): string {
    return ['LOGIN'=>'badge-green','LOGOUT'=>'badge-gray','CREATE'=>'badge-blue',
            'UPDATE'=>'badge-yellow','DELETE'=>'badge-red','STATUS_CHANGE'=>'badge-purple'][$a] ?? 'badge-gray';
}

page_header('Audit Log', __FILE__);
?>
<div class="page-header">
  <div><h1>📜 Audit Log</h1><p>Full record of all admin actions (<?= $total ?> entries).</p></div>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted)">No audit entries yet.</td></tr>
        <?php else: foreach ($rows as $i => $r): ?>
        <tr>
          <td style="color:var(--muted);font-size:.78rem"><?= $off+$i+1 ?></td>
          <td style="color:var(--muted);font-size:.8rem;white-space:nowrap"><?= date('d M Y H:i:s', strtotime($r['created_at'])) ?></td>
          <td><?= htmlspecialchars($r['user_name']) ?></td>
          <td><span class="badge <?= auditBadge($r['action']) ?>"><?= htmlspecialchars($r['action']) ?></span></td>
          <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($r['module']) ?></td>
          <td style="font-size:.85rem"><?= htmlspecialchars($r['details']) ?></td>
          <td style="color:var(--muted);font-size:.8rem"><?= htmlspecialchars($r['ip_address']) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPgs > 1): ?>
  <nav class="pagination" style="margin-top:1rem">
    <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>" class="pg-btn">‹ Prev</a><?php endif; ?>
    <?php for ($p=max(1,$page-2); $p<=min($totalPgs,$page+2); $p++): ?>
      <a href="?page=<?= $p ?>" class="pg-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPgs): ?><a href="?page=<?= $page+1 ?>" class="pg-btn">Next ›</a><?php endif; ?>
  </nav>
  <?php endif; ?>
</div>
<?php page_footer(); ?>
