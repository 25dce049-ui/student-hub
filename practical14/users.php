<?php
// Practical 14 – users.php  (admin only)
// Intermediate Extension: active/inactive status toggle

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_role([ROLE_ADMIN]);

$db = get_db();

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $tid    = intval($_POST['toggle_id']);
    $newSt  = ($_POST['current_status'] === 'active') ? 'inactive' : 'active';

    // Prevent self-deactivation
    if ($tid === (int)current_user()['id'] && $newSt === 'inactive') {
        $flash = ['danger', 'You cannot deactivate your own account.'];
    } else {
        $stmt = $db->prepare("UPDATE users SET status=? WHERE id=?");
        $stmt->bind_param('si', $newSt, $tid);
        $stmt->execute();
        $stmt->close();

        // Fetch name for audit
        $uRow = $db->query("SELECT name FROM users WHERE id=$tid")->fetch_assoc();
        audit_log('STATUS_CHANGE', 'Users', "User '{$uRow['name']}' status changed to $newSt.");

        $flash = ['success', "User status updated to <strong>$newSt</strong>."];
    }
}

$users = $db->query("SELECT * FROM users ORDER BY role, name")->fetch_all(MYSQLI_ASSOC);

page_header('Users', __FILE__);
?>

<div class="page-header">
  <div><h1>👥 User Management</h1><p>Manage all system users and their status.</p></div>
</div>

<?php if (isset($flash)): ?>
  <div class="alert alert-<?= $flash[0] ?>"><?= $flash[1] ?></div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Toggle Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $i => $u): ?>
        <tr>
          <td style="color:var(--muted);font-size:.8rem"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge <?= roleBadge($u['role']) ?>"><?= ucfirst($u['role']) ?></span></td>
          <td><span class="badge <?= $u['status']==='active'?'badge-green':'badge-red' ?>">
            <?= ucfirst($u['status']) ?></span>
          </td>
          <td style="color:var(--muted);font-size:.82rem">
            <?= $u['last_login'] ? date('d M Y, H:i', strtotime($u['last_login'])) : 'Never' ?>
          </td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="toggle_id" value="<?= $u['id'] ?>">
              <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
              <button type="submit" class="btn btn-sm <?= $u['status']==='active' ? 'btn-warning' : 'btn-success' ?>">
                <?= $u['status']==='active' ? '🔒 Deactivate' : '🔓 Activate' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
page_footer();
function roleBadge(string $r): string {
    return ['admin'=>'badge-red','moderator'=>'badge-yellow','student'=>'badge-blue'][$r] ?? 'badge-gray';
}
?>
