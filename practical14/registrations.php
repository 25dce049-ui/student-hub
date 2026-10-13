<?php
// Practical 14 – registrations.php  (admin + moderator)

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_role([ROLE_ADMIN, ROLE_MODERATOR]);

$db = get_db();
$rows = $db->query(
    "SELECT r.id, s.name AS student, s.course, e.title AS event, e.category, r.reg_date
     FROM registrations r
     JOIN students s ON s.id=r.student_id
     JOIN events   e ON e.id=r.event_id
     ORDER BY r.id DESC LIMIT 50"
)->fetch_all(MYSQLI_ASSOC);

page_header('Registrations', __FILE__);
?>
<div class="page-header">
  <div><h1>📋 Registrations</h1><p>Latest 50 registrations.</p></div>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Student</th><th>Course</th><th>Event</th><th>Category</th><th>Reg Date</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td style="color:var(--muted);font-size:.8rem"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($r['student']) ?></strong></td>
          <td style="font-size:.82rem;color:var(--muted)"><?= htmlspecialchars($r['course']) ?></td>
          <td><?= htmlspecialchars($r['event']) ?></td>
          <td><span class="badge badge-blue"><?= htmlspecialchars($r['category']) ?></span></td>
          <td style="color:var(--muted);font-size:.82rem"><?= $r['reg_date'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php page_footer(); ?>
