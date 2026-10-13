<?php
/**
 * Practical 12 – Event Management CRUD with Poster Upload
 * edit.php  –  Edit an existing event + replace/remove poster
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/upload.php';

$db = get_db();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$event) { header('Location: index.php?msg=error'); exit; }

$errors = [];
$old    = $event;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'title'       => trim($_POST['title']       ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category'    => trim($_POST['category']    ?? ''),
        'venue'       => trim($_POST['venue']       ?? ''),
        'event_date'  => trim($_POST['event_date']  ?? ''),
        'event_time'  => trim($_POST['event_time']  ?? ''),
        'status'      => trim($_POST['status']      ?? 'open'),
    ];
    $removePoster = isset($_POST['remove_poster']);

    if ($old['title'] === '')                    $errors['title']       = 'Title is required.';
    if ($old['description'] === '')              $errors['description'] = 'Description is required.';
    if (!in_array($old['category'], CATEGORIES)) $errors['category']   = 'Select a valid category.';
    if ($old['venue'] === '')                    $errors['venue']       = 'Venue is required.';
    if ($old['event_date'] === '')               $errors['event_date']  = 'Event date is required.';
    if (!in_array($old['status'],['open','closed','cancelled'])) $errors['status'] = 'Invalid status.';

    // Upload new poster if provided
    $uploadResult = upload_poster(
        $_FILES['poster'] ?? ['error' => UPLOAD_ERR_NO_FILE],
        null  // delete old poster only if new one succeeds
    );
    if (!$uploadResult['ok']) $errors['poster'] = $uploadResult['error'];

    if (empty($errors)) {
        // Determine final poster filename
        if ($uploadResult['filename']) {
            // New poster uploaded – delete old one
            delete_poster($event['poster']);
            $newPoster = $uploadResult['filename'];
        } elseif ($removePoster) {
            // Admin explicitly removed poster
            delete_poster($event['poster']);
            $newPoster = null;
        } else {
            // Keep existing
            $newPoster = $event['poster'];
        }

        $time = $old['event_time'] ?: null;
        $upd  = $db->prepare(
            "UPDATE events SET title=?,description=?,category=?,venue=?,event_date=?,event_time=?,status=?,poster=?
             WHERE id=?"
        );
        $upd->bind_param('ssssssssi',
            $old['title'], $old['description'], $old['category'],
            $old['venue'], $old['event_date'], $time, $old['status'], $newPoster, $id
        );
        $upd->execute();
        $upd->close();

        audit($id, 'UPDATE', "Event '{$old['title']}' updated.");
        header('Location: index.php?msg=updated');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Event – Practical 12</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <nav class="navbar">
    <a href="index.php" class="logo">🗓️ EventHub <span>Practical 12</span></a>
    <ul class="nav-links">
      <li><a href="index.php">📋 Events</a></li>
      <li><a href="add.php">➕ Add Event</a></li>
      <li><a href="audit.php">📜 Audit Log</a></li>
    </ul>
  </nav>
</header>

<main>
  <div class="page-header">
    <div><h1>✏️ Edit Event</h1>
    <p>Editing: <strong><?= htmlspecialchars($event['title']) ?></strong></p></div>
    <a href="index.php" class="btn btn-outline">← Back</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">❌ Please fix the errors below.</div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" enctype="multipart/form-data" novalidate>

      <div class="form-grid">
        <div class="form-group" style="grid-column:1/-1">
          <label>Title <span class="required">*</span></label>
          <input type="text" name="title" maxlength="200"
                 value="<?= htmlspecialchars($old['title']) ?>"
                 style="<?= isset($errors['title'])?'border-color:var(--danger)':''?>">
          <?php if (isset($errors['title'])): ?><span class="err"><?= $errors['title'] ?></span><?php endif; ?>
        </div>

        <div class="form-group">
          <label>Category <span class="required">*</span></label>
          <select name="category" style="<?= isset($errors['category'])?'border-color:var(--danger)':''?>">
            <?php foreach (CATEGORIES as $c): ?>
              <option value="<?= $c ?>" <?= $old['category']===$c?'selected':''?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['category'])): ?><span class="err"><?= $errors['category'] ?></span><?php endif; ?>
        </div>

        <div class="form-group">
          <label>Status <span class="required">*</span></label>
          <select name="status">
            <option value="open"      <?= $old['status']==='open'     ?'selected':''?>>Open</option>
            <option value="closed"    <?= $old['status']==='closed'   ?'selected':''?>>Closed</option>
            <option value="cancelled" <?= $old['status']==='cancelled'?'selected':''?>>Cancelled</option>
          </select>
        </div>

        <div class="form-group">
          <label>Venue <span class="required">*</span></label>
          <input type="text" name="venue" maxlength="200"
                 value="<?= htmlspecialchars($old['venue']) ?>"
                 style="<?= isset($errors['venue'])?'border-color:var(--danger)':''?>">
          <?php if (isset($errors['venue'])): ?><span class="err"><?= $errors['venue'] ?></span><?php endif; ?>
        </div>

        <div class="form-group">
          <label>Event Date <span class="required">*</span></label>
          <input type="date" name="event_date" value="<?= htmlspecialchars($old['event_date']) ?>"
                 style="<?= isset($errors['event_date'])?'border-color:var(--danger)':''?>">
          <?php if (isset($errors['event_date'])): ?><span class="err"><?= $errors['event_date'] ?></span><?php endif; ?>
        </div>

        <div class="form-group">
          <label>Event Time</label>
          <input type="time" name="event_time" value="<?= htmlspecialchars($old['event_time'] ?? '') ?>">
        </div>

        <div class="form-group" style="grid-column:1/-1">
          <label>Description <span class="required">*</span></label>
          <textarea name="description" maxlength="2000"
                    style="<?= isset($errors['description'])?'border-color:var(--danger)':''?>"><?= htmlspecialchars($old['description']) ?></textarea>
          <?php if (isset($errors['description'])): ?><span class="err"><?= $errors['description'] ?></span><?php endif; ?>
        </div>

        <!-- Poster section -->
        <div class="form-group" style="grid-column:1/-1">
          <label>Event Poster</label>

          <?php if ($event['poster']): ?>
            <div style="margin-bottom:.75rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
              <img src="<?= UPLOAD_URL . htmlspecialchars($event['poster']) ?>"
                   alt="Current poster" style="max-height:120px;border-radius:8px;border:1px solid var(--border)">
              <div>
                <p style="font-size:.85rem;color:var(--muted);margin-bottom:.4rem">Current poster</p>
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;cursor:pointer">
                  <input type="checkbox" name="remove_poster" value="1">
                  Remove current poster
                </label>
              </div>
            </div>
          <?php endif; ?>

          <div class="upload-zone" id="uploadZone" onclick="document.getElementById('posterInput').click()">
            <span class="uz-icon">🖼️</span>
            <p><?= $event['poster'] ? 'Upload new poster (replaces current)' : 'Click or drag & drop an image' ?></p>
            <small>JPG, PNG, WEBP, GIF — max 2 MB</small>
            <input type="file" id="posterInput" name="poster" accept=".jpg,.jpeg,.png,.webp,.gif">
            <img id="previewImg" class="preview-img" src="#" alt="Preview" style="display:none">
          </div>
          <?php if (isset($errors['poster'])): ?>
            <span class="err">❌ <?= htmlspecialchars($errors['poster']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Update Event</button>
        <a href="index.php" class="btn btn-outline">Cancel</a>
        <button type="button" class="btn btn-danger" style="margin-left:auto"
          onclick="confirmDel(<?= $id ?>,'<?= htmlspecialchars(addslashes($event['title']),ENT_QUOTES) ?>')">
          🗑️ Delete Event
        </button>
      </div>
    </form>
  </div>
  <p style="color:var(--muted);font-size:.8rem">Created: <?= $event['created_at'] ?> &nbsp;|&nbsp; Updated: <?= $event['updated_at'] ?></p>
</main>

<!-- Delete Modal -->
<div class="modal-overlay" id="delModal" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="modal-icon">⚠️</div>
    <h2>Delete Event</h2>
    <p id="delMsg"></p>
    <div class="modal-actions">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <a href="#" id="delBtn" class="btn btn-danger">🗑️ Delete</a>
    </div>
  </div>
</div>

<footer class="site-footer">Practical 12 – Event Management CRUD &nbsp;|&nbsp; PHP + MySQLi + XAMPP</footer>

<script>
const zone = document.getElementById('uploadZone');
const input = document.getElementById('posterInput');
const preview = document.getElementById('previewImg');
input.addEventListener('change', () => showPreview(input.files[0]));
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
zone.addEventListener('drop', e => {
  e.preventDefault(); zone.classList.remove('drag');
  const f = e.dataTransfer.files[0];
  if (f) { input.files = e.dataTransfer.files; showPreview(f); }
});
function showPreview(file) {
  if (!file || !file.type.startsWith('image/')) return;
  const r = new FileReader();
  r.onload = ev => { preview.src = ev.target.result; preview.style.display = 'block'; };
  r.readAsDataURL(file);
}
function confirmDel(id, title) {
  document.getElementById('delMsg').innerHTML =
    'Permanently delete <strong>' + title + '</strong>?<br>The poster will also be removed.';
  document.getElementById('delBtn').href = 'delete.php?id=' + id;
  document.getElementById('delModal').classList.add('open');
}
function closeModal() { document.getElementById('delModal').classList.remove('open'); }
document.getElementById('delModal').addEventListener('click', e => { if(e.target===e.currentTarget)closeModal(); });
document.addEventListener('keydown', e => { if(e.key==='Escape')closeModal(); });
</script>
</body>
</html>
