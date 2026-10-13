<?php
/**
 * Practical 11 – Student Management CRUD with Search & Filter
 * add.php  –  Add a new student record
 *
 * Security: ✅ MySQLi prepared INSERT  ✅ htmlspecialchars  ✅ server-side validation
 */

require_once __DIR__ . '/includes/db.php';

$db     = get_db();
$errors = [];
$old    = [];   // repopulate form on error

$courses = ['Computer Science', 'Information Technology', 'Electronics',
            'Mechanical Engineering', 'Civil Engineering'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitise inputs
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

    // Check duplicate email
    if (!isset($errors['email'])) {
        $chk = $db->prepare("SELECT id FROM students WHERE email = ?");
        $chk->bind_param('s', $old['email']);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) $errors['email'] = 'This email is already registered.';
        $chk->close();
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            "INSERT INTO students (name, email, phone, course, gender, dob, address, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $dob = $old['dob'] ?: null;
        $stmt->bind_param('ssssssss',
            $old['name'], $old['email'], $old['phone'],
            $old['course'], $old['gender'], $dob,
            $old['address'], $old['status']
        );

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: index.php?msg=added');
            exit;
        }
        $stmt->close();
        $errors['_global'] = 'Failed to add student. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Student – Practical 11</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <nav class="navbar">
    <a href="index.php" class="logo">🎓 StudentHub <span>Practical 11</span></a>
    <ul class="nav-links">
      <li><a href="index.php">📋 Students</a></li>
      <li><a href="add.php" class="active">➕ Add Student</a></li>
      <li><a href="export.php">📥 Export CSV</a></li>
    </ul>
  </nav>
</header>

<main>
  <div class="page-header">
    <div>
      <h1>➕ Add New Student</h1>
      <p>Fill in the form below to register a new student.</p>
    </div>
    <a href="index.php" class="btn btn-outline">← Back to List</a>
  </div>

  <?php if (!empty($errors['_global'])): ?>
    <div class="alert alert-danger">❌ <?= htmlspecialchars($errors['_global']) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" action="add.php" novalidate>
      <div class="form-grid">

        <!-- Name -->
        <div class="form-group">
          <label for="name">Full Name <span class="required">*</span></label>
          <input type="text" id="name" name="name" maxlength="120"
                 value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                 placeholder="e.g. Aarav Sharma"
                 style="<?= isset($errors['name']) ? 'border-color:var(--danger)' : '' ?>">
          <?php if (isset($errors['name'])): ?>
            <span class="hint" style="color:var(--danger)"><?= $errors['name'] ?></span>
          <?php endif; ?>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label for="email">Email Address <span class="required">*</span></label>
          <input type="email" id="email" name="email" maxlength="120"
                 value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                 placeholder="e.g. aarav@example.com"
                 style="<?= isset($errors['email']) ? 'border-color:var(--danger)' : '' ?>">
          <?php if (isset($errors['email'])): ?>
            <span class="hint" style="color:var(--danger)"><?= $errors['email'] ?></span>
          <?php endif; ?>
        </div>

        <!-- Phone -->
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone" maxlength="20"
                 value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                 placeholder="e.g. 9876543210"
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
            <option value="">Select Course</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= $c ?>" <?= ($old['course'] ?? '') === $c ? 'selected' : '' ?>>
                <?= $c ?>
              </option>
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
            <option value="">Select Gender</option>
            <option value="Male"   <?= ($old['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($old['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other"  <?= ($old['gender'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other</option>
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
            <option value="active"   <?= ($old['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($old['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <!-- Address -->
        <div class="form-group" style="grid-column: 1 / -1">
          <label for="address">Address</label>
          <textarea id="address" name="address" maxlength="255"
                    placeholder="City, State"><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
        </div>

      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Save Student</button>
        <a href="index.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</main>

<footer class="site-footer">
  Practical 11 – Student Management CRUD &nbsp;|&nbsp; PHP + MySQLi + XAMPP
</footer>

</body>
</html>
