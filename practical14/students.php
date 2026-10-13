<?php
// Practical 14 – students.php  (admin + moderator)

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_role([ROLE_ADMIN, ROLE_MODERATOR]);

$db = get_db();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = []; $params = []; $types = '';
if ($search !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR course LIKE ?)';
    $like = '%'.$search.'%';
    $params = array_merge($params, [$like,$like,$like]);
    $types .= 'sss';
}
if ($status !== '') { $where[] = 'status=?'; $params[] = $status; $types .= 's'; }

$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';
$stmt = $db->prepare("SELECT * FROM students $whereSQL ORDER BY name");
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_header('Students', __FILE__);
?>
<div class="page-header">
  <div><h1>🎓 Students</h1><p><?= count($students) ?> record(s) found.</p></div>
</div>

<div class="card">
  <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
    <div style="display:flex;flex-direction:column;gap:.3rem;flex:1;min-width:180px">
      <label style="font-size:.78rem;font-weight:600;color:var(--muted)">Search</label>
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
             placeholder="Name, email, course…" class="filter-input">
    </div>
    <div style="display:flex;flex-direction:column;gap:.3rem">
      <label style="font-size:.78rem;font-weight:600;color:var(--muted)">Status</label>
      <select name="status" class="filter-input">
        <option value="">All</option>
        <option value="active"   <?= $status==='active'  ?'selected':''?>>Active</option>
        <option value="inactive" <?= $status==='inactive'?'selected':''?>>Inactive</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">🔍 Filter</button>
    <a href="students.php" class="btn btn-outline btn-sm">✕ Clear</a>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Course</th><th>Status</th><th>Joined</th></tr></thead>
      <tbody>
        <?php if (empty($students)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">No students found.</td></tr>
        <?php else: foreach ($students as $i => $s): ?>
        <tr>
          <td style="color:var(--muted);font-size:.8rem"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
          <td><?= htmlspecialchars($s['email']) ?></td>
          <td><?= htmlspecialchars($s['course']) ?></td>
          <td><span class="badge <?= $s['status']==='active'?'badge-green':'badge-red' ?>"><?= ucfirst($s['status']) ?></span></td>
          <td style="color:var(--muted);font-size:.82rem"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php page_footer(); ?>
