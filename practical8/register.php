<?php
/**
 * Practical 8 – StudentHub
 * register.php  –  Event registration form + processing
 *
 * Demonstrates:
 *  • PDO prepared statement for INSERT with named placeholders
 *  • UNIQUE constraint violation catch (duplicate registration)
 *  • Server-side validation before any DB write
 *  • CSRF token protection
 *  • Redirect-After-Post (PRG pattern) to avoid double submission
 */

require_once __DIR__ . '/includes/db.php';
session_start();

// ---------- CSRF Token ----------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ---------- Load dropdown data ----------
$error   = '';
$students = [];
$events   = [];
$success  = $_SESSION['reg_success'] ?? '';
$form_err = $_SESSION['reg_errors']  ?? [];
$old      = $_SESSION['reg_old']     ?? [];
unset($_SESSION['reg_success'], $_SESSION['reg_errors'], $_SESSION['reg_old']);

// Pre-fill event from query string (coming from events.php)
if (empty($old['event_id']) && isset($_GET['event_id'])) {
    $old['event_id'] = (int)$_GET['event_id'];
}

try {
    $students = db_fetch_all("SELECT student_id, full_name, course FROM students ORDER BY full_name");
    $events   = db_fetch_all("SELECT event_id, title, event_date, category FROM events ORDER BY event_date");
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $student_id = (int)($_POST['student_id'] ?? 0);
    $event_id   = (int)($_POST['event_id']   ?? 0);
    $status     = trim($_POST['status']      ?? 'Pending');
    $allowed_statuses = ['Pending', 'Confirmed', 'Cancelled'];

    $errors = [];
    if ($student_id <= 0) $errors[] = 'Please select a student.';
    if ($event_id   <= 0) $errors[] = 'Please select an event.';
    if (!in_array($status, $allowed_statuses, true)) $errors[] = 'Invalid status value.';

    if (empty($errors)) {
        try {
            // ✅ Prepared INSERT – named placeholders, no string interpolation
            db_execute(
                "INSERT INTO registrations (student_id, event_id, status)
                 VALUES (:sid, :eid, :status)",
                [':sid' => $student_id, ':eid' => $event_id, ':status' => $status]
            );
            $_SESSION['reg_success'] = "Registration saved successfully (Student #$student_id → Event #$event_id).";

        } catch (PDOException $e) {
            // Catch duplicate entry (UNIQUE constraint: uq_reg_student_event)
            if ($e->getCode() === '23000') {
                $errors[] = 'This student is already registered for that event.';
            } else {
                error_log('[Register Error] ' . $e->getMessage());
                $errors[] = 'Database error. Please try again.';
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['reg_errors'] = $errors;
        $_SESSION['reg_old']    = ['student_id' => $student_id, 'event_id' => $event_id, 'status' => $status];
    }

    // PRG: always redirect after POST
    header('Location: register.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Register for an event – Practical 8 StudentHub" />
  <title>Register – Practical 8 | StudentHub</title>
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
          <li><a href="students.php">Students</a></li>
          <li><a href="events.php">Events</a></li>
          <li><a href="register.php" class="active">Register</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main-content">

    <div class="page-header">
      <h1>➕ New Registration</h1>
      <p>Register a student for an event using a PDO prepared INSERT statement.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert">
        <span class="alert-icon">❌</span>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success" role="alert" aria-live="polite">
        <span class="alert-icon">✅</span>
        <div><strong>Success!</strong> <?= htmlspecialchars($success) ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($form_err)): ?>
      <div class="alert alert-danger" role="alert" aria-live="polite">
        <span class="alert-icon">❌</span>
        <div>
          <strong>Please fix the following:</strong>
          <ul><?php foreach ($form_err as $fe): ?><li><?= htmlspecialchars($fe) ?></li><?php endforeach; ?></ul>
        </div>
      </div>
    <?php endif; ?>

    <!-- Registration Form -->
    <div class="card">
      <h2>📋 Registration Form</h2>
      <form method="POST" action="register.php" novalidate aria-label="Event registration form">

        <!-- CSRF -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />

        <div class="form-grid">

          <!-- Student -->
          <div class="form-group">
            <label for="student_id">Student <span class="required" aria-hidden="true">*</span></label>
            <select id="student_id" name="student_id"
                    aria-required="true"
                    class="<?= !empty($form_err) && empty($old['student_id']) ? 'error' : '' ?>">
              <option value="">-- Select Student --</option>
              <?php foreach ($students as $s): ?>
                <option value="<?= (int)$s['student_id'] ?>"
                        <?= ((int)($old['student_id'] ?? 0)) === (int)$s['student_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['course']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Event -->
          <div class="form-group">
            <label for="event_id">Event <span class="required" aria-hidden="true">*</span></label>
            <select id="event_id" name="event_id"
                    aria-required="true"
                    class="<?= !empty($form_err) && empty($old['event_id']) ? 'error' : '' ?>">
              <option value="">-- Select Event --</option>
              <?php foreach ($events as $ev): ?>
                <option value="<?= (int)$ev['event_id'] ?>"
                        <?= ((int)($old['event_id'] ?? 0)) === (int)$ev['event_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ev['title']) ?>
                  (<?= htmlspecialchars(date('d M Y', strtotime($ev['event_date']))) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Status -->
          <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
              <?php foreach (['Pending','Confirmed','Cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= ($old['status'] ?? 'Pending') === $st ? 'selected' : '' ?>><?= $st ?></option>
              <?php endforeach; ?>
            </select>
          </div>

        </div>

        <div class="form-actions">
          <button type="submit" class="btn-primary">🚀 Submit Registration</button>
          <a href="events.php" class="btn-secondary">← Back to Events</a>
        </div>

      </form>
    </div>

    <!-- Prepared Statement Info -->
    <div class="card">
      <h2>🔐 Prepared Statement Used</h2>
      <pre class="code-block">// Prepared INSERT – named placeholders prevent SQL injection
db_execute(
    "INSERT INTO registrations (student_id, event_id, status)
     VALUES (:sid, :eid, :status)",
    [':sid' => $student_id, ':eid' => $event_id, ':status' => $status]
);</pre>
      <div class="stats-strip" style="margin-top:1rem;">
        <span class="stat-pill">✅ Named Placeholders</span>
        <span class="stat-pill">✅ CSRF Protected</span>
        <span class="stat-pill">✅ PRG Pattern</span>
        <span class="stat-pill">✅ Duplicate Guard (UNIQUE key)</span>
        <span class="stat-pill">✅ Allowlist Validation</span>
      </div>
    </div>

    <!-- Registrations Table -->
    <?php
    $all_regs = [];
    try {
        $all_regs = db_fetch_all("
            SELECT r.reg_id, s.full_name, s.course, e.title AS event_title,
                   r.status, r.registered_at
            FROM registrations r
            JOIN students s ON s.student_id = r.student_id
            JOIN events   e ON e.event_id   = r.event_id
            ORDER BY r.registered_at DESC
            LIMIT 20
        ");
    } catch (RuntimeException) {}
    ?>
    <?php if (!empty($all_regs)): ?>
    <div class="card">
      <h2>📋 Recent Registrations (latest 20)</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Student</th><th>Course</th>
              <th>Event</th><th>Status</th><th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($all_regs as $r): ?>
            <tr>
              <td><?= (int)$r['reg_id'] ?></td>
              <td><?= htmlspecialchars($r['full_name']) ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($r['course']) ?></span></td>
              <td><?= htmlspecialchars($r['event_title']) ?></td>
              <td>
                <?php
                  $bc = ['Confirmed'=>'badge-green','Pending'=>'badge-yellow','Cancelled'=>'badge-red'];
                  $cls = $bc[$r['status']] ?? 'badge-blue';
                ?>
                <span class="badge <?= $cls ?>"><?= htmlspecialchars($r['status']) ?></span>
              </td>
              <td><?= htmlspecialchars(date('d M Y', strtotime($r['registered_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </main>

  <footer class="site-footer" role="contentinfo">
    <p>&copy; 2026 StudentHub – Practical 8 &nbsp;|&nbsp;
      <a href="index.php">Dashboard</a> &nbsp;|&nbsp;
      <a href="students.php">Students</a> &nbsp;|&nbsp;
      <a href="events.php">Events</a>
    </p>
  </footer>

</body>
</html>
