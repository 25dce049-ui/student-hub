<?php
/**
 * Practical 8 – StudentHub
 * students.php  –  Student listing with search & filter via PDO prepared statements
 *
 * Demonstrates:
 *  • Named placeholders (:param) to prevent SQL injection
 *  • Dynamic WHERE clause building (safe, parameterised)
 *  • LIKE search with bound wildcard values
 */

require_once __DIR__ . '/includes/db.php';

$error    = '';
$students = [];

// ---------- Sanitise GET inputs ----------
$search  = trim($_GET['search']  ?? '');
$course  = trim($_GET['course']  ?? '');
$year    = trim($_GET['year']    ?? '');

$allowed_courses = ['BCA','BCS','BIT','MCA','MCS','B.Tech'];
$allowed_years   = ['1st Year','2nd Year','3rd Year','4th Year'];

// Reject any value not in allowlists
if ($course && !in_array($course, $allowed_courses, true)) $course = '';
if ($year   && !in_array($year,   $allowed_years,   true)) $year   = '';

// ---------- Build parameterised query ----------
$sql    = "SELECT * FROM students WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (full_name LIKE :search OR email LIKE :search2)";
    $params[':search']  = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
}
if ($course !== '') {
    $sql .= " AND course = :course";
    $params[':course'] = $course;
}
if ($year !== '') {
    $sql .= " AND year = :year";
    $params[':year'] = $year;
}
$sql .= " ORDER BY created_at DESC";

try {
    $students = db_fetch_all($sql, $params);
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Student listing – Practical 8 StudentHub" />
  <title>Students – Practical 8 | StudentHub</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <a class="skip-link" href="#main-content">Skip to main content</a>

  <header>
    <div class="navbar" role="banner">
      <a href="../new/index.html" class="logo" aria-label="StudentHub Home">🎓 StudentHub</a>
      <nav aria-label="Main navigation">
        <ul class="nav-links">
          <li><a href="index.php">Dashboard</a></li>
          <li><a href="students.php" class="active">Students</a></li>
          <li><a href="events.php">Events</a></li>
          <li><a href="register.php">Register</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main-content">

    <div class="page-header">
      <h1>👩‍🎓 Students</h1>
      <p>Search and filter all registered students. Queries use PDO prepared statements.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert">
        <span class="alert-icon">❌</span>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="card">
      <h2>🔍 Search &amp; Filter</h2>
      <form method="GET" action="students.php" aria-label="Student search filter">
        <div class="filter-grid">
          <div class="form-group">
            <label for="search">Name / Email</label>
            <input type="search"
                   id="search" name="search"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="e.g. Rahul or rahul@…"
                   aria-label="Search by name or email">
          </div>
          <div class="form-group">
            <label for="course">Course</label>
            <select id="course" name="course" aria-label="Filter by course">
              <option value="">All Courses</option>
              <?php foreach ($allowed_courses as $c): ?>
                <option value="<?= $c ?>" <?= $course === $c ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="year">Year</label>
            <select id="year" name="year" aria-label="Filter by year">
              <option value="">All Years</option>
              <?php foreach ($allowed_years as $y): ?>
                <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group form-group--actions">
            <button type="submit" class="btn-primary">🔍 Search</button>
            <a href="students.php" class="btn-secondary">✖ Clear</a>
          </div>
        </div>
      </form>

      <!-- SQL Preview (educational) -->
      <div class="security-note" style="margin-top:1rem;">
        <strong>Query executed (parameterised):</strong><br>
        <code><?= htmlspecialchars($sql) ?></code><br>
        <strong>Bound params:</strong>
        <code><?= htmlspecialchars(json_encode($params)) ?></code>
      </div>
    </div>

    <!-- Results Table -->
    <div class="card">
      <h2>📋 Results
        <span class="result-count"><?= count($students) ?> student<?= count($students) !== 1 ? 's' : '' ?></span>
      </h2>

      <?php if (empty($students)): ?>
        <p class="muted-text">No students match your criteria.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Course</th>
              <th>Year</th>
              <th>Gender</th>
              <th>City</th>
              <th>Joined</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
              <td><?= (int)$s['student_id'] ?></td>
              <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
              <td><?= htmlspecialchars($s['email']) ?></td>
              <td><?= htmlspecialchars($s['phone']) ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($s['course']) ?></span></td>
              <td><?= htmlspecialchars($s['year']) ?></td>
              <td><?= htmlspecialchars($s['gender']) ?></td>
              <td><?= htmlspecialchars($s['city'] ?? '—') ?></td>
              <td><?= htmlspecialchars(date('d M Y', strtotime($s['created_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </main>

  <footer class="site-footer" role="contentinfo">
    <p>&copy; 2026 StudentHub – Practical 8 &nbsp;|&nbsp;
      <a href="index.php">Dashboard</a> &nbsp;|&nbsp;
      <a href="events.php">Events</a> &nbsp;|&nbsp;
      <a href="register.php">Register</a>
    </p>
  </footer>

</body>
</html>
