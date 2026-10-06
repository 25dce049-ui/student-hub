<?php
/**
 * Practical 8 – StudentHub
 * events.php  –  Events listing with seat availability & registration count
 *
 * Demonstrates:
 *  • JOIN query via prepared statement
 *  • Aggregate subquery (COUNT of registrations per event)
 *  • GET-based filter with allowlist validation
 */

require_once __DIR__ . '/includes/db.php';

$error  = '';
$events = [];

$allowed_categories = ['Workshop','Seminar','Hackathon','Cultural','Sports','Other'];
$category = trim($_GET['category'] ?? '');
if ($category && !in_array($category, $allowed_categories, true)) $category = '';

$sql    = "
    SELECT
        e.*,
        COUNT(r.reg_id) AS registered_count
    FROM events e
    LEFT JOIN registrations r
           ON r.event_id = e.event_id
              AND r.status != 'Cancelled'
    WHERE 1=1
";
$params = [];

if ($category !== '') {
    $sql .= " AND e.category = :cat";
    $params[':cat'] = $category;
}

$sql .= " GROUP BY e.event_id ORDER BY e.event_date ASC";

try {
    $events = db_fetch_all($sql, $params);
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

// Seat availability helper
function seats_badge(int $max, int $taken): string {
    $free = $max - $taken;
    if ($free <= 0)  return '<span class="badge badge-red">Full</span>';
    if ($free <= 5)  return "<span class=\"badge badge-yellow\">{$free} left</span>";
    return "<span class=\"badge badge-green\">{$free} seats</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Events listing – Practical 8 StudentHub" />
  <title>Events – Practical 8 | StudentHub</title>
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
          <li><a href="events.php" class="active">Events</a></li>
          <li><a href="register.php">Register</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main-content">

    <div class="page-header">
      <h1>📅 Events</h1>
      <p>Browse upcoming events. Real-time seat availability is calculated via a JOIN query.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert">
        <span class="alert-icon">❌</span>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <!-- Category Filter -->
    <div class="card">
      <h2>🏷️ Filter by Category</h2>
      <form method="GET" action="events.php" aria-label="Event category filter">
        <div class="filter-grid">
          <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" aria-label="Filter by category">
              <option value="">All Categories</option>
              <?php foreach ($allowed_categories as $cat): ?>
                <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group form-group--actions">
            <button type="submit" class="btn-primary">Filter</button>
            <a href="events.php" class="btn-secondary">✖ Clear</a>
          </div>
        </div>
      </form>
    </div>

    <!-- Events Grid -->
    <?php if (empty($events) && !$error): ?>
      <div class="card"><p class="muted-text">No events found.</p></div>
    <?php else: ?>
    <div class="events-grid">
      <?php foreach ($events as $ev): ?>
      <?php
        $free    = (int)$ev['max_seats'] - (int)$ev['registered_count'];
        $pct     = $ev['max_seats'] > 0 ? round(($ev['registered_count'] / $ev['max_seats']) * 100) : 0;
        $is_full = $free <= 0;
      ?>
      <article class="event-card <?= $is_full ? 'event-card--full' : '' ?>" aria-label="<?= htmlspecialchars($ev['title']) ?>">
        <div class="event-card__header">
          <span class="badge badge-purple"><?= htmlspecialchars($ev['category']) ?></span>
          <?= seats_badge((int)$ev['max_seats'], (int)$ev['registered_count']) ?>
        </div>
        <h3 class="event-card__title"><?= htmlspecialchars($ev['title']) ?></h3>
        <p class="event-card__desc"><?= htmlspecialchars($ev['description'] ?? '') ?></p>
        <div class="event-card__meta">
          <span>📆 <?= htmlspecialchars(date('d M Y', strtotime($ev['event_date']))) ?></span>
          <span>📍 <?= htmlspecialchars($ev['venue'] ?? 'TBD') ?></span>
        </div>
        <!-- Seat occupancy bar -->
        <div class="seat-bar" role="progressbar"
             aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"
             aria-label="<?= $pct ?>% seats filled">
          <div class="seat-bar__fill <?= $pct >= 90 ? 'seat-bar__fill--danger' : ($pct >= 60 ? 'seat-bar__fill--warn' : '') ?>"
               style="width:<?= $pct ?>%"></div>
        </div>
        <p class="seat-bar__label"><?= (int)$ev['registered_count'] ?> / <?= (int)$ev['max_seats'] ?> registered</p>
        <?php if (!$is_full): ?>
        <a href="register.php?event_id=<?= (int)$ev['event_id'] ?>" class="btn-primary" style="margin-top:.75rem; display:inline-flex;">
          Register →
        </a>
        <?php else: ?>
        <button class="btn-secondary" disabled style="margin-top:.75rem; opacity:.5; cursor:not-allowed;">
          Event Full
        </button>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </main>

  <footer class="site-footer" role="contentinfo">
    <p>&copy; 2026 StudentHub – Practical 8 &nbsp;|&nbsp;
      <a href="index.php">Dashboard</a> &nbsp;|&nbsp;
      <a href="students.php">Students</a> &nbsp;|&nbsp;
      <a href="register.php">Register</a>
    </p>
  </footer>

</body>
</html>
