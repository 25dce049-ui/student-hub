<?php
/**
 * Practical 11 – Student Management CRUD with Search & Filter
 * index.php  –  List all students with search, filter, and pagination
 *
 * Security: ✅ MySQLi prepared statements  ✅ htmlspecialchars output  ✅ intval for pagination
 */

require_once __DIR__ . '/includes/db.php';

$db = get_db();

// ── Flash messages from redirects ──────────────────────────────
$flash = '';
if (!empty($_GET['msg'])) {
    $map = [
        'added'   => ['success', '✅ Student added successfully.'],
        'updated' => ['success', '✅ Student updated successfully.'],
        'deleted' => ['success', '🗑️ Student deleted successfully.'],
        'error'   => ['danger',  '❌ An error occurred. Please try again.'],
    ];
    $key = $_GET['msg'];
    if (isset($map[$key])) {
        [$type, $text] = $map[$key];
        $flash = "<div class='alert alert-{$type}' role='alert'><span>{$text}</span></div>";
    }
}

// ── Search & Filter inputs ──────────────────────────────────────
$search  = trim($_GET['search']  ?? '');
$course  = trim($_GET['course']  ?? '');
$gender  = trim($_GET['gender']  ?? '');
$status  = trim($_GET['status']  ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * PER_PAGE;

// Build WHERE clause
$where   = [];
$params  = [];
$types   = '';

if ($search !== '') {
    $where[]  = '(s.name LIKE ? OR s.email LIKE ? OR s.course LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like]);
    $types   .= 'sss';
}
if ($course !== '') {
    $where[]  = 's.course = ?';
    $params[] = $course;
    $types   .= 's';
}
if ($gender !== '') {
    $where[]  = 's.gender = ?';
    $params[] = $gender;
    $types   .= 's';
}
if ($status !== '') {
    $where[]  = 's.status = ?';
    $params[] = $status;
    $types   .= 's';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Total count ─────────────────────────────────────────────────
$countSQL  = "SELECT COUNT(*) AS cnt FROM students s $whereSQL";
$countStmt = $db->prepare($countSQL);
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total    = $countStmt->get_result()->fetch_assoc()['cnt'];
$countStmt->close();

$totalPages = (int) ceil($total / PER_PAGE);

// ── Fetch page ──────────────────────────────────────────────────
$listSQL  = "SELECT * FROM students s $whereSQL ORDER BY s.id DESC LIMIT ? OFFSET ?";
$listStmt = $db->prepare($listSQL);
$allTypes  = $types . 'ii';
$allParams = array_merge($params, [PER_PAGE, $offset]);
$listStmt->bind_param($allTypes, ...$allParams);
$listStmt->execute();
$students = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

// ── Stats ───────────────────────────────────────────────────────
$statsRow = $db->query("SELECT
    COUNT(*) AS total,
    SUM(status='active')   AS active,
    SUM(status='inactive') AS inactive
  FROM students")->fetch_assoc();

// ── Distinct courses for filter dropdown ────────────────────────
$courses = $db->query("SELECT DISTINCT course FROM students ORDER BY course")
              ->fetch_all(MYSQLI_ASSOC);

// ── Build query string helper (keeps existing filters) ──────────
function qstr(array $overrides = []): string {
    $base = ['search','course','gender','status','page'];
    $qs   = [];
    foreach ($base as $k) {
        $val = $overrides[$k] ?? ($_GET[$k] ?? '');
        if ($val !== '' && $val !== null) $qs[$k] = $val;
    }
    foreach ($overrides as $k => $v) {
        if ($v === '') unset($qs[$k]);
        else $qs[$k] = $v;
    }
    return $qs ? '?' . http_build_query($qs) : '?';
}

// ── Course badge colours ─────────────────────────────────────────
function courseBadge(string $c): string {
    $map = [
        'Computer Science'       => 'badge-blue',
        'Information Technology' => 'badge-purple',
        'Electronics'            => 'badge-yellow',
        'Mechanical Engineering' => 'badge-gray',
        'Civil Engineering'      => 'badge-green',
    ];
    return $map[$c] ?? 'badge-gray';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Management – Practical 11</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <nav class="navbar" aria-label="Main navigation">
    <a href="index.php" class="logo">🎓 StudentHub <span>Practical 11</span></a>
    <ul class="nav-links">
      <li><a href="index.php" class="active">📋 Students</a></li>
      <li><a href="add.php">➕ Add Student</a></li>
      <li><a href="export.php<?= qstr(['page'=>'']) ?>">📥 Export CSV</a></li>
    </ul>
  </nav>
</header>

<main>
  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-value"><?= $statsRow['total'] ?></div>
      <div class="stat-label">Total Students</div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon">✅</div>
      <div class="stat-value"><?= $statsRow['active'] ?></div>
      <div class="stat-label">Active</div>
    </div>
    <div class="stat-card red">
      <div class="stat-icon">🚫</div>
      <div class="stat-value"><?= $statsRow['inactive'] ?></div>
      <div class="stat-label">Inactive</div>
    </div>
    <div class="stat-card warn">
      <div class="stat-icon">📄</div>
      <div class="stat-value"><?= $totalPages ?></div>
      <div class="stat-label">Pages</div>
    </div>
  </div>

  <!-- Flash -->
  <?= $flash ?>

  <!-- Page header -->
  <div class="page-header">
    <div>
      <h1>Student Records</h1>
      <p>Showing <?= $total ?> result<?= $total !== 1 ? 's' : '' ?>
        <?= $search ? " for "<strong>" . htmlspecialchars($search) . "</strong>"" : '' ?>
      </p>
    </div>
    <a href="add.php" class="btn btn-primary">➕ Add Student</a>
  </div>

  <!-- Search & Filter -->
  <div class="card">
    <form method="GET" action="index.php">
      <div class="filter-bar">
        <div class="fg" style="flex:1;min-width:220px">
          <label for="search">Search</label>
          <input type="text" id="search" name="search"
                 placeholder="Name, email or course…"
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="fg">
          <label for="course">Course</label>
          <select id="course" name="course">
            <option value="">All Courses</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= htmlspecialchars($c['course']) ?>"
                <?= $course === $c['course'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['course']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label for="gender">Gender</label>
          <select id="gender" name="gender">
            <option value="">All Genders</option>
            <option value="Male"   <?= $gender==='Male'   ? 'selected':'' ?>>Male</option>
            <option value="Female" <?= $gender==='Female' ? 'selected':'' ?>>Female</option>
            <option value="Other"  <?= $gender==='Other'  ? 'selected':'' ?>>Other</option>
          </select>
        </div>
        <div class="fg">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="">All Status</option>
            <option value="active"   <?= $status==='active'   ? 'selected':'' ?>>Active</option>
            <option value="inactive" <?= $status==='inactive' ? 'selected':'' ?>>Inactive</option>
          </select>
        </div>
        <div class="fg" style="justify-content:flex-end;flex-direction:row;gap:.5rem;align-items:flex-end">
          <button type="submit" class="btn btn-primary">🔍 Search</button>
          <a href="index.php" class="btn btn-outline">✕ Clear</a>
        </div>
      </div>
    </form>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Course</th>
            <th>Gender</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr>
              <td colspan="8" style="text-align:center;padding:2rem;color:var(--muted)">
                No students found. <a href="index.php">Clear filters</a> or
                <a href="add.php">add a student</a>.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($students as $i => $s): ?>
            <tr>
              <td style="color:var(--muted);font-size:.8rem"><?= $offset + $i + 1 ?></td>
              <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
              <td><?= htmlspecialchars($s['email']) ?></td>
              <td><?= htmlspecialchars($s['phone']) ?></td>
              <td>
                <span class="badge <?= courseBadge($s['course']) ?>">
                  <?= htmlspecialchars($s['course']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($s['gender']) ?></td>
              <td>
                <span class="badge <?= $s['status']==='active' ? 'badge-green' : 'badge-red' ?>">
                  <?= ucfirst($s['status']) ?>
                </span>
              </td>
              <td>
                <div class="action-group">
                  <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                  <button class="btn btn-danger btn-sm"
                    onclick="confirmDelete(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name']), ENT_QUOTES) ?>')">
                    🗑️ Delete
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Pagination">
      <a href="<?= qstr(['page' => $page - 1]) ?>"
         class="<?= $page <= 1 ? 'disabled' : '' ?>" aria-label="Previous">‹</a>

      <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
        <a href="<?= qstr(['page' => $p]) ?>"
           class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>

      <a href="<?= qstr(['page' => $page + 1]) ?>"
         class="<?= $page >= $totalPages ? 'disabled' : '' ?>" aria-label="Next">›</a>
    </nav>
    <?php endif; ?>
  </div>
</main>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal">
    <div class="modal-icon">⚠️</div>
    <h2 id="modalTitle">Confirm Delete</h2>
    <p id="modalMsg">Are you sure you want to delete this student?<br>This action cannot be undone.</p>
    <div class="modal-actions">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <a href="#" id="confirmDeleteBtn" class="btn btn-danger">🗑️ Yes, Delete</a>
    </div>
  </div>
</div>

<footer class="site-footer">
  Practical 11 – Student Management CRUD &nbsp;|&nbsp; PHP + MySQLi + XAMPP
</footer>

<script>
function confirmDelete(id, name) {
  document.getElementById('modalMsg').innerHTML =
    'Are you sure you want to permanently delete <strong>' + name + '</strong>?<br>This action cannot be undone.';
  document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + id;
  document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
  document.getElementById('deleteModal').classList.remove('open');
}
// Close on overlay click
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
// Close on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});
</script>
</body>
</html>
