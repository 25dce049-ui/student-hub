<?php
/**
 * Practical 12 – Event Management CRUD with Poster Upload
 * index.php  –  List all events with search, category/status filter, pagination
 */

require_once __DIR__ . '/includes/db.php';

$db = get_db();

// Flash messages
$flash = '';
$msgMap = [
    'added'   => ['success', '✅ Event added successfully.'],
    'updated' => ['success', '✅ Event updated successfully.'],
    'deleted' => ['success', '🗑️ Event deleted successfully.'],
    'error'   => ['danger',  '❌ An error occurred. Please try again.'],
];
if (!empty($_GET['msg']) && isset($msgMap[$_GET['msg']])) {
    [$type, $text] = $msgMap[$_GET['msg']];
    $flash = "<div class='alert alert-{$type}'>$text</div>";
}

// Filters
$search   = trim($_GET['search']   ?? '');
$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status']   ?? '');
$page     = max(1, intval($_GET['page'] ?? 1));
$offset   = ($page - 1) * PER_PAGE;

$where = []; $params = []; $types = '';

if ($search !== '') {
    $where[] = '(title LIKE ? OR description LIKE ? OR venue LIKE ?)';
    $like = '%'.$search.'%';
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}
if ($category !== '') { $where[] = 'category = ?'; $params[] = $category; $types .= 's'; }
if ($status   !== '') { $where[] = 'status = ?';   $params[] = $status;   $types .= 's'; }

$whereSQL = $where ? 'WHERE '.implode(' AND ', $where) : '';

// Count
$cStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM events $whereSQL");
if ($types) $cStmt->bind_param($types, ...$params);
$cStmt->execute();
$total      = $cStmt->get_result()->fetch_assoc()['cnt'];
$totalPages = (int)ceil($total / PER_PAGE);
$cStmt->close();

// Fetch
$lStmt = $db->prepare("SELECT * FROM events $whereSQL ORDER BY event_date ASC, id DESC LIMIT ? OFFSET ?");
$lStmt->bind_param($types.'ii', ...[...$params, PER_PAGE, $offset]);
$lStmt->execute();
$events = $lStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lStmt->close();

// Stats
$stats = $db->query("SELECT
    COUNT(*) AS total,
    SUM(status='open') AS open_cnt,
    SUM(status='closed') AS closed_cnt,
    SUM(status='cancelled') AS cancelled_cnt
  FROM events")->fetch_assoc();

// Build query string helper
function qstr(array $ov = []): string {
    $base = ['search','category','status','page'];
    $qs = [];
    foreach ($base as $k) { $v = $ov[$k] ?? ($_GET[$k] ?? ''); if ($v !== '') $qs[$k] = $v; }
    foreach ($ov as $k => $v) { if ($v === '') unset($qs[$k]); else $qs[$k] = $v; }
    return $qs ? '?'.http_build_query($qs) : '?';
}

// Category → badge class
function catBadge(string $c): string {
    return ['Technical'=>'badge-blue','Cultural'=>'badge-purple','Sports'=>'badge-green',
            'Academic'=>'badge-teal','Workshop'=>'badge-orange','Networking'=>'badge-yellow',
            'General'=>'badge-gray'][$c] ?? 'badge-gray';
}
// Status → badge class
function statusBadge(string $s): string {
    return ['open'=>'badge-green','closed'=>'badge-yellow','cancelled'=>'badge-red'][$s] ?? 'badge-gray';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Event Management – Practical 12</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <nav class="navbar">
    <a href="index.php" class="logo">🗓️ EventHub <span>Practical 12</span></a>
    <ul class="nav-links">
      <li><a href="index.php" class="active">📋 Events</a></li>
      <li><a href="add.php">➕ Add Event</a></li>
      <li><a href="audit.php">📜 Audit Log</a></li>
    </ul>
  </nav>
</header>

<main>
  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">🗓️</div>
      <div class="stat-value"><?= $stats['total'] ?></div>
      <div class="stat-label">Total Events</div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon">✅</div>
      <div class="stat-value"><?= $stats['open_cnt'] ?></div>
      <div class="stat-label">Open</div>
    </div>
    <div class="stat-card warn">
      <div class="stat-icon">🔒</div>
      <div class="stat-value"><?= $stats['closed_cnt'] ?></div>
      <div class="stat-label">Closed</div>
    </div>
    <div class="stat-card red">
      <div class="stat-icon">❌</div>
      <div class="stat-value"><?= $stats['cancelled_cnt'] ?></div>
      <div class="stat-label">Cancelled</div>
    </div>
  </div>

  <?= $flash ?>

  <div class="page-header">
    <div>
      <h1>Event Records</h1>
      <p>Showing <?= $total ?> event<?= $total != 1 ? 's' : '' ?></p>
    </div>
    <a href="add.php" class="btn btn-primary">➕ Add Event</a>
  </div>

  <!-- Filter -->
  <div class="card">
    <form method="GET">
      <div class="filter-bar">
        <div class="fg" style="flex:1;min-width:200px">
          <label>Search</label>
          <input type="text" name="search" placeholder="Title, venue…"
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="fg">
          <label>Category</label>
          <select name="category">
            <option value="">All Categories</option>
            <?php foreach (CATEGORIES as $c): ?>
              <option value="<?= $c ?>" <?= $category===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label>Status</label>
          <select name="status">
            <option value="">All Status</option>
            <option value="open"      <?= $status==='open'     ?'selected':''?>>Open</option>
            <option value="closed"    <?= $status==='closed'   ?'selected':''?>>Closed</option>
            <option value="cancelled" <?= $status==='cancelled'?'selected':''?>>Cancelled</option>
          </select>
        </div>
        <div class="fg" style="flex-direction:row;gap:.5rem;align-items:flex-end">
          <button type="submit" class="btn btn-primary">🔍 Filter</button>
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
            <th>Poster</th>
            <th>Title</th>
            <th>Category</th>
            <th>Date</th>
            <th>Venue</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($events)): ?>
          <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--muted)">
            No events found. <a href="index.php">Clear filters</a> or <a href="add.php">add an event</a>.
          </td></tr>
          <?php else: foreach ($events as $i => $e): ?>
          <tr>
            <td style="color:var(--muted);font-size:.8rem"><?= $offset+$i+1 ?></td>
            <td>
              <?php if ($e['poster']): ?>
                <img src="<?= UPLOAD_URL . htmlspecialchars($e['poster']) ?>"
                     alt="Poster" class="poster-thumb">
              <?php else: ?>
                <div class="no-poster">No img</div>
              <?php endif; ?>
            </td>
            <td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
            <td><span class="badge <?= catBadge($e['category']) ?>"><?= htmlspecialchars($e['category']) ?></span></td>
            <td><?= date('d M Y', strtotime($e['event_date'])) ?>
                <?php if ($e['event_time']): ?>
                  <br><small style="color:var(--muted)"><?= date('h:i A', strtotime($e['event_time'])) ?></small>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($e['venue']) ?></td>
            <td><span class="badge <?= statusBadge($e['status']) ?>"><?= ucfirst($e['status']) ?></span></td>
            <td>
              <div class="action-group">
                <a href="edit.php?id=<?= $e['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                <button class="btn btn-danger btn-sm"
                  onclick="confirmDel(<?= $e['id'] ?>,'<?= htmlspecialchars(addslashes($e['title']),ENT_QUOTES) ?>')">
                  🗑️ Delete
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination">
      <a href="<?= qstr(['page'=>$page-1]) ?>" class="<?= $page<=1?'disabled':'' ?>">‹</a>
      <?php for ($p=max(1,$page-2); $p<=min($totalPages,$page+2); $p++): ?>
        <a href="<?= qstr(['page'=>$p]) ?>" class="<?= $p===$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <a href="<?= qstr(['page'=>$page+1]) ?>" class="<?= $page>=$totalPages?'disabled':'' ?>">›</a>
    </nav>
    <?php endif; ?>
  </div>
</main>

<!-- Delete Modal -->
<div class="modal-overlay" id="delModal" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="modal-icon">⚠️</div>
    <h2>Delete Event</h2>
    <p id="delMsg">Are you sure?</p>
    <div class="modal-actions">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <a href="#" id="delBtn" class="btn btn-danger">🗑️ Delete</a>
    </div>
  </div>
</div>

<footer class="site-footer">Practical 12 – Event Management CRUD &nbsp;|&nbsp; PHP + MySQLi + XAMPP</footer>

<script>
function confirmDel(id, title) {
  document.getElementById('delMsg').innerHTML =
    'Permanently delete event <strong>' + title + '</strong>?<br>This also removes the poster image.';
  document.getElementById('delBtn').href = 'delete.php?id=' + id;
  document.getElementById('delModal').classList.add('open');
}
function closeModal() { document.getElementById('delModal').classList.remove('open'); }
document.getElementById('delModal').addEventListener('click', e => { if (e.target===e.currentTarget) closeModal(); });
document.addEventListener('keydown', e => { if (e.key==='Escape') closeModal(); });
</script>
</body>
</html>
