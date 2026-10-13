<?php
// Practical 14 – dashboard.php  (all roles)

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_role();   // any logged-in user

$db   = get_db();
$user = current_user();

// ── Stats (admin sees all; others see limited) ─────────────────
$totalUsers    = $db->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$activeUsers   = $db->query("SELECT COUNT(*) AS c FROM users WHERE status='active'")->fetch_assoc()['c'];
$totalStudents = $db->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$activeStudents= $db->query("SELECT COUNT(*) AS c FROM students WHERE status='active'")->fetch_assoc()['c'];
$totalEvents   = $db->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc()['c'];
$openEvents    = $db->query("SELECT COUNT(*) AS c FROM events WHERE status='open'")->fetch_assoc()['c'];
$totalRegs     = $db->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'];

// ── Latest registrations ───────────────────────────────────────
$latestRegs = $db->query(
    "SELECT r.id, s.name AS student_name, e.title AS event_title,
            e.category, r.reg_date
     FROM registrations r
     JOIN students s ON s.id = r.student_id
     JOIN events   e ON e.id = r.event_id
     ORDER BY r.id DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

// ── Recent audit entries ───────────────────────────────────────
$recentAudit = $db->query(
    "SELECT * FROM audit_logs ORDER BY id DESC LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

// ── Chart data: registrations by month (Advanced extension) ───
$chartRows = $db->query(
    "SELECT DATE_FORMAT(reg_date,'%b %Y') AS month,
            DATE_FORMAT(reg_date,'%Y-%m') AS ym,
            COUNT(*) AS cnt
     FROM registrations
     GROUP BY ym
     ORDER BY ym ASC
     LIMIT 12"
)->fetch_all(MYSQLI_ASSOC);
$chartLabels = array_column($chartRows, 'month');
$chartData   = array_column($chartRows, 'cnt');

// ── Students by course (for doughnut) ─────────────────────────
$courseRows = $db->query(
    "SELECT course, COUNT(*) AS cnt FROM students GROUP BY course ORDER BY cnt DESC"
)->fetch_all(MYSQLI_ASSOC);
$courseLabels = array_column($courseRows, 'course');
$courseCounts = array_column($courseRows, 'cnt');

page_header('Dashboard', __FILE__);
?>

<div class="page-header">
  <div>
    <h1>📊 Dashboard</h1>
    <p>Welcome back, <strong><?= htmlspecialchars($user['name']) ?></strong> — <?= date('l, d M Y') ?></p>
  </div>
  <?php if (is_admin()): ?>
    <a href="audit.php" class="btn btn-outline">📜 View Audit Log</a>
  <?php endif; ?>
</div>

<!-- Stats grid -->
<div class="stats-grid">
  <?php if (is_admin()): ?>
  <div class="stat-card">
    <div class="stat-icon">👥</div>
    <div class="stat-value"><?= $totalUsers ?></div>
    <div class="stat-label">Total Users</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon">✅</div>
    <div class="stat-value"><?= $activeUsers ?></div>
    <div class="stat-label">Active Users</div>
  </div>
  <?php endif; ?>
  <div class="stat-card blue">
    <div class="stat-icon">🎓</div>
    <div class="stat-value"><?= $totalStudents ?></div>
    <div class="stat-label">Total Students</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon">✅</div>
    <div class="stat-value"><?= $activeStudents ?></div>
    <div class="stat-label">Active Students</div>
  </div>
  <div class="stat-card purple">
    <div class="stat-icon">🗓️</div>
    <div class="stat-value"><?= $totalEvents ?></div>
    <div class="stat-label">Total Events</div>
  </div>
  <div class="stat-card warn">
    <div class="stat-icon">🔓</div>
    <div class="stat-value"><?= $openEvents ?></div>
    <div class="stat-label">Open Events</div>
  </div>
  <div class="stat-card info">
    <div class="stat-icon">📋</div>
    <div class="stat-value"><?= $totalRegs ?></div>
    <div class="stat-label">Registrations</div>
  </div>
</div>

<!-- Charts row (Advanced Extension) -->
<div class="two-col">
  <div class="card">
    <h2>📈 Registrations by Month</h2>
    <canvas id="regChart" height="200"></canvas>
  </div>
  <div class="card">
    <h2>🎓 Students by Course</h2>
    <canvas id="courseChart" height="200"></canvas>
  </div>
</div>

<!-- Latest registrations + Recent audit -->
<div class="two-col">
  <div class="card">
    <h2>🕒 Latest Registrations</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Student</th><th>Event</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($latestRegs as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['student_name']) ?></td>
            <td>
              <span class="badge <?= catBadge($r['category']) ?>">
                <?= htmlspecialchars($r['event_title']) ?>
              </span>
            </td>
            <td style="color:var(--muted);font-size:.82rem"><?= $r['reg_date'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (is_moderator()): ?>
  <div class="card">
    <h2>📜 Recent Audit Activity</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Time</th></tr></thead>
        <tbody>
          <?php foreach ($recentAudit as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['user_name']) ?></td>
            <td><span class="badge <?= auditBadge($a['action']) ?>"><?= $a['action'] ?></span></td>
            <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($a['module']) ?></td>
            <td style="color:var(--muted);font-size:.8rem"><?= date('d M, H:i', strtotime($a['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (is_admin()): ?>
      <div style="margin-top:1rem"><a href="audit.php" class="btn btn-outline btn-sm">View Full Log →</a></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php
page_footer();

function catBadge(string $c): string {
    return ['Technical'=>'badge-blue','Cultural'=>'badge-purple','Sports'=>'badge-green',
            'Academic'=>'badge-teal','Workshop'=>'badge-orange','Networking'=>'badge-yellow',
            'General'=>'badge-gray'][$c] ?? 'badge-gray';
}
function auditBadge(string $a): string {
    return ['LOGIN'=>'badge-green','LOGOUT'=>'badge-gray','CREATE'=>'badge-blue',
            'UPDATE'=>'badge-yellow','DELETE'=>'badge-red','STATUS_CHANGE'=>'badge-purple'][$a] ?? 'badge-gray';
}
?>
<script>
// ── Bar chart: Registrations by Month ──
new Chart(document.getElementById('regChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [{
      label: 'Registrations',
      data: <?= json_encode(array_map('intval', $chartData)) ?>,
      backgroundColor: 'rgba(79,70,229,.75)',
      borderColor: '#4f46e5',
      borderWidth: 1,
      borderRadius: 4,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});

// ── Doughnut chart: Students by Course ──
new Chart(document.getElementById('courseChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($courseLabels) ?>,
    datasets: [{
      data: <?= json_encode(array_map('intval', $courseCounts)) ?>,
      backgroundColor: ['#4f46e5','#16a34a','#d97706','#dc2626','#0284c7'],
      borderWidth: 2,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>
