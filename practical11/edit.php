<?php
/**
 * Practical 11 – Student Management CRUD with Search & Filter
 * edit.php  –  Edit an existing student record
 *
 * Security: ✅ MySQLi prepared SELECT + UPDATE  ✅ htmlspecialchars  ✅ server-side validation
 */

require_once __DIR__ . '/includes/db.php';

$db = get_db();

$courses = ['Computer Science', 'Information Technology', 'Electronics',
            'Mechanical Engineering', 'Civil Engineering'];

// ── Load existing record ────────────────────────────────────────
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$fetchStmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$fetchStmt->bind_param('i', $id);
$fetchStmt->execute();
$student = $fetchStmt->get_result()->fetch_assoc();
$fetchStmt->close();

if (!$student) {
    header('Location: index.php?msg=error');
    exit;
}

$errors = [];
$old    = $student;  // defaults from DB; overwritten by POST on error

// ── Handle form submission ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name'    => trim($_POST['name']    ?? ''),
        'email'   => trim($_POST['email']   ?? ''),
        'phone'   => trim($_POST['phone']   ?? ''),
        'course'  => trim($_POST['course']  ?? ''),
        'gender'  => trim($_POST['gender']  ?? ''),
        'dob'     => trim($_POST['dob']     ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'status'  => trim($_POST['status']  ?? 'active'),
    ];

    // Validate
    if ($old['name'] === '')                      $errors['name']   = 'Name is required.';
    elseif (strlen($old['name']) > 120)           $errors['name']   = 'Name must be ≤ 120 characters.';
    if ($old['email'] === '')                     $errors['email']  = 'Email is required.';
    elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address.';
    if ($old['phone'] !== '' && !preg_match('/^\+?[\d\s\-]{7,20}$/', $old['phone']))
                                                  $errors['phone']  = 'Invalid phone number.';
    if (!in_array($old['course'], $courses))      $errors['course'] = 'Select a valid course.';
    if (!in_array($old['gender'], ['Male','Female','Other'])) $errors['gender'] = 'Select a valid gender.';
    if (!in_array($old['status'], ['active','inactive']))     $errors['status'] = 'Select a valid status.';

    // Check duplicate email (exclude current student)
    if (!isset($errors['email'])) {
        $chk = $db->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
        $chk->bind_param('si', $old['email'], $id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) $errors['email'] = 'This email is already used by another student.';
        $chk->close();
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            "UPDATE students SET name=?, email=?, phone=?, course=?, gender=?, dob=?, address=?, status=?
             WHERE id=?"
        );
        $dob = $old['dob'] ?: null;
        $stmt->bind_param('ssssssssi',
            $old['name'], $old['email'], $old['phone'],
            $old['course'], $old['gender'], $dob,
            $old['address'], $old['status'], $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: index.php?msg=updated');
            exit;
        }
        $stmt->close();
        $errors['_global'] = 'Failed to update student. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Student – Practical 11</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <nav class="navbar">
    <a href="index.php" class="logo">🎓 StudentHub <span>Practical 11</span></a>
    <ul class="nav-links">
      <li><a href="index.php">📋 Students</a></li>
      <li><a href="add.php">➕ Add Student</a></li>
      <li><a href="export.php">📥 Export CSV</a></li>
    </ul>
  </nav>
</header>

<main>
  <div class="page-header">
    <div>
      <h1>✏️ Edit Student</h1>
      <p>Updating record for <strong><?= htmlspecialchars($student['name']) ?></strong> (ID: <?= $id ?>)</p>
    </div>
    <a href="index.php" class="btn btn-outline">← Back to List</a>
  </div>

  <?php if (!empty($errors['_global'])): ?>
    <div class="alert alert-danger">❌ <?= htmlspecialchars($errors['_global']) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" action="edit.php?id=<?= $id ?>" novalidate>
      <div class="form-grid">

        <!-- Name -->
        <div class="form-group">
          <label for="name">Full Name <span class="required">*</span></label>
          <input type="text" id="name" name="name" maxlength="120"
                 value="<?= htmlspecialchars($old['name']) ?>"
                 style="<?= isset($errors['name']) ? 'border-color:var(--danger)' : '' ?>">
          <?php if (isset($errors['name'])): ?>
            <span class="hint" style="color:var(--danger)"><?= $errors['name'] ?></span>
          <?php endif; ?>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label for="email">Email Address <span class="required">*</span></label>
          <input type="email" id="email" name="email" maxlength="120"
                 value="<?= htmlspecialchars($old['email']) ?>"
                 style="<?= isset($errors['email']) ? 'border-color:var(--danger)' : '' ?>">
          <?php if (isset($errors['email'])): ?>
            <span class="hint" style="color:var(--danger)"><?= $errors['email'] ?></span>
          <?php endif; ?>
        </div>

        <!-- Phone -->
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone" maxlength="20"
                 value="<?= htmlspecialchars($old['phone']) ?>"
                 style="<?= isset($errors['phone']) ? 'border-color:var(--danger)' : '' ?>">
          <?php if (isset($errors['phone'])): ?>
            <span class="hint" style="color:var(--danger)"><?= $errors['phone'] ?></span>
          <?php endif; ?>
        </div>

        <!-- Course -->
        <div class="form-group">
          <label for="course">Course <span class="required">*</span></label>
          <select id="course" name="course"
                  style="<?= isset($errors['course']) ? 'border-color:var(--danger)' : '' ?>">
            <?php foreach ($courses as $c): ?>
              <option value="<?= $c ?>" <?= $old['course'] === $c ? 'selected' : '' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['course'])): ?>
            <span class="hint" style="color:var(--danger)"><?= $errors['course'] ?></span>
          <?php endif; ?>
        </div>

        <!-- Gender -->
        <div class="form-group">
          <label for="gender">Gender <span class="required">*</span></label>
          <select id="gender" name="gender"
                  style="<?= isset($errors['gender']) ? 'border-color:var(--danger)' : '' ?>">
            <option value="Male"   <?= $old['gender']==='Male'   ? 'selected':'' ?>>Male</option>
            <option value="Female" <?= $old['gender']==='Female' ? 'selected':'' ?>>Female</option>
            <option value="Other"  <?= $old['gender']==='Other'  ? 'selected':'' ?>>Other</option>
          </select>
          <?php if (isset($errors['gender'])): ?>
            <span class="hint" style="color:var(--danger)"><?= $errors['gender'] ?></span>
          <?php endif; ?>
        </div>

        <!-- Date of Birth -->
        <div class="form-group">
          <label for="dob">Date of Birth</label>
          <input type="date" id="dob" name="dob"
                 value="<?= htmlspecialchars($old['dob'] ?? '') ?>"
                 max="<?= date('Y-m-d') ?>">
        </div>

        <!-- Status -->
        <div class="form-group">
          <label for="status">Status <span class="required">*</span></label>
          <select id="status" name="status">
            <option value="active"   <?= $old['status']==='active'   ? 'selected':'' ?>>Active</option>
            <option value="inactive" <?= $old['status']==='inactive' ? 'selected':'' ?>>Inactive</option>
          </select>
        </div>

        <!-- Address -->
        <div class="form-group" style="grid-column: 1 / -1">
          <label for="address">Address</label>
          <textarea id="address" name="address" maxlength="255"><?= htmlspecialchars($old['address']) ?></textarea>
        </div>

      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Update Student</button>
        <a href="index.php" class="btn btn-outline">Cancel</a>
        <button type="button" class="btn btn-danger" style="margin-left:auto"
          onclick="confirmDelete(<?= $id ?>, '<?= htmlspecialchars(addslashes($student['name']), ENT_QUOTES) ?>')">
          🗑️ Delete This Student
        </button>
      </div>
    </form>
  </div>

  <!-- Last updated info -->
  <p style="color:var(--muted);font-size:.82rem;margin-top:-.5rem">
    Created: <?= $student['created_at'] ?> &nbsp;|&nbsp; Last updated: <?= $student['updated_at'] ?>
  </p>
</main>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="modal-icon">⚠️</div>
    <h2>Confirm Delete</h2>
    <p id="modalMsg"></p>
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
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});
</script>

</body>
</html>
