<?php
// Practical 14 – events.php  (all roles)

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_role();

$db     = get_db();
$events = $db->query("SELECT e.*, COUNT(r.id) AS reg_count
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.id
    GROUP BY e.id ORDER BY e.event_date ASC")->fetch_all(MYSQLI_ASSOC);

page_header('Events', __FILE__);
?>
<div class="page-header">
  <div><h1>🗓️ Events</h1><p><?= count($events) ?> event(s) in the system.</p></div>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Date</th><th>Status</th><th>Registrations</th></tr></thead>
      <tbody>
        <?php foreach ($events as $i => $e): ?>
        <tr>
          <td style="color:var(--muted);font-size:.8rem"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
          <td><span class="badge badge-blue"><?= htmlspecialchars($e['category']) ?></span></td>
          <td><?= date('d M Y', strtotime($e['event_date'])) ?></td>
          <td><span class="badge <?= ['open'=>'badge-green','closed'=>'badge-yellow','cancelled'=>'badge-red'][$e['status']]??'badge-gray' ?>">
            <?= ucfirst($e['status']) ?></span>
          </td>
          <td><strong><?= $e['reg_count'] ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php page_footer(); ?>
