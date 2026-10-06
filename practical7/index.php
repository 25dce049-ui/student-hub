<?php
/**
 * Practical 7 – PHP Form Processing
 * index.php  –  Registration form with CSRF token generation
 *
 * CSRF token is generated here and embedded in a hidden field.
 * All submission handling is done in process.php.
 */

session_start();

// ---------- Generate CSRF token ----------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ---------- Preserve old input & messages after redirect ----------
$old     = $_SESSION['old_input'] ?? [];
$errors  = $_SESSION['form_errors'] ?? [];
$success = $_SESSION['form_success'] ?? '';
unset($_SESSION['old_input'], $_SESSION['form_errors'], $_SESSION['form_success']);

// Courses list (reused for the checkbox group)
$courses = ['BCA', 'BCS', 'BIT', 'MCA', 'MCS', 'B.Tech'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description"
        content="Practical 7 – PHP server-side validation and CSV/JSON storage for StudentHub." />
  <title>Practical 7 – PHP Form Processing | StudentHub</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <!-- Skip to content -->
  <a class="skip-link" href="#main-content">Skip to main content</a>

  <!-- ================================================
       NAVBAR
       ================================================ -->
  <header>
    <div class="navbar" role="banner">
      <a href="../new/index.html" class="logo" aria-label="StudentHub Home">
        🎓 StudentHub
      </a>
      <nav aria-label="Main navigation">
        <ul class="nav-links">
          <li><a href="../new/index.html">Home</a></li>
          <li><a href="index.php" class="active">Register</a></li>
          <li><a href="records.php">View Records</a></li>
          <li><a href="../practical6/index.html">← Practical 6</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <!-- ================================================
       MAIN
       ================================================ -->
  <main id="main-content">

    <!-- Page Header -->
    <div class="page-header">
      <h1>Practical 7 – PHP Form Processing</h1>
      <p>
        Registration form with <strong>POST handling</strong> ·
        <strong>Server-side validation &amp; sanitization</strong> ·
        <strong>CSV / JSON file storage</strong> · CSRF protection
      </p>
    </div>

    <!-- ================================================
         Flash Messages
         ================================================ -->
    <?php if ($success): ?>
      <div class="alert alert-success" role="alert" aria-live="polite">
        <span class="alert-icon">✅</span>
        <div>
          <strong>Registration Successful!</strong><br>
          <?= htmlspecialchars($success) ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger" role="alert" aria-live="polite">
        <span class="alert-icon">❌</span>
        <div>
          <strong>Please fix the following errors:</strong>
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <!-- ================================================
         REGISTRATION FORM CARD
         ================================================ -->
    <div class="card">
      <h2>📋 Student Registration Form</h2>

      <!--
        action  → process.php handles all validation + storage
        method  → POST (never GET for form data)
        novalidate → disable browser HTML5 validation so PHP handles everything
      -->
      <form action="process.php" method="POST" novalidate
            aria-label="Student registration form">

        <!-- CSRF hidden field (Advanced: CSRF token validation) -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />

        <div class="form-grid">

          <!-- Full Name -->
          <div class="form-group">
            <label for="full_name">Full Name <span class="required" aria-hidden="true">*</span></label>
            <input type="text"
                   id="full_name"
                   name="full_name"
                   value="<?= htmlspecialchars($old['full_name'] ?? '') ?>"
                   placeholder="e.g. Rahul Sharma"
                   maxlength="100"
                   autocomplete="name"
                   aria-required="true"
                   aria-describedby="full_name_err"
                   class="<?= isset($errors['full_name']) ? 'error' : '' ?>">
            <span class="field-error <?= isset($errors['full_name']) ? 'show' : '' ?>"
                  id="full_name_err" role="alert">
              <?= htmlspecialchars($errors['full_name'] ?? '') ?>
            </span>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label for="email">Email Address <span class="required" aria-hidden="true">*</span></label>
            <input type="email"
                   id="email"
                   name="email"
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                   placeholder="e.g. rahul@example.com"
                   maxlength="150"
                   autocomplete="email"
                   aria-required="true"
                   aria-describedby="email_err"
                   class="<?= isset($errors['email']) ? 'error' : '' ?>">
            <span class="field-error <?= isset($errors['email']) ? 'show' : '' ?>"
                  id="email_err" role="alert">
              <?= htmlspecialchars($errors['email'] ?? '') ?>
            </span>
          </div>

          <!-- Phone -->
          <div class="form-group">
            <label for="phone">Phone Number <span class="required" aria-hidden="true">*</span></label>
            <input type="tel"
                   id="phone"
                   name="phone"
                   value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                   placeholder="10-digit number"
                   maxlength="15"
                   autocomplete="tel"
                   aria-required="true"
                   aria-describedby="phone_err"
                   class="<?= isset($errors['phone']) ? 'error' : '' ?>">
            <span class="field-error <?= isset($errors['phone']) ? 'show' : '' ?>"
                  id="phone_err" role="alert">
              <?= htmlspecialchars($errors['phone'] ?? '') ?>
            </span>
          </div>

          <!-- Age -->
          <div class="form-group">
            <label for="age">Age <span class="required" aria-hidden="true">*</span></label>
            <input type="number"
                   id="age"
                   name="age"
                   value="<?= htmlspecialchars($old['age'] ?? '') ?>"
                   min="15" max="60"
                   placeholder="15 – 60"
                   aria-required="true"
                   aria-describedby="age_err"
                   class="<?= isset($errors['age']) ? 'error' : '' ?>">
            <span class="field-error <?= isset($errors['age']) ? 'show' : '' ?>"
                  id="age_err" role="alert">
              <?= htmlspecialchars($errors['age'] ?? '') ?>
            </span>
          </div>

          <!-- Gender -->
          <div class="form-group">
            <label>Gender <span class="required" aria-hidden="true">*</span></label>
            <div class="radio-group" role="group" aria-required="true"
                 aria-describedby="gender_err">
              <?php foreach (['Male','Female','Other'] as $g): ?>
                <label>
                  <input type="radio" name="gender" value="<?= $g ?>"
                         <?= ($old['gender'] ?? '') === $g ? 'checked' : '' ?>>
                  <?= $g ?>
                </label>
              <?php endforeach; ?>
            </div>
            <span class="field-error <?= isset($errors['gender']) ? 'show' : '' ?>"
                  id="gender_err" role="alert">
              <?= htmlspecialchars($errors['gender'] ?? '') ?>
            </span>
          </div>

          <!-- Course (select) -->
          <div class="form-group">
            <label for="course">Course <span class="required" aria-hidden="true">*</span></label>
            <select id="course" name="course"
                    aria-required="true"
                    aria-describedby="course_err"
                    class="<?= isset($errors['course']) ? 'error' : '' ?>">
              <option value="">-- Select Course --</option>
              <?php foreach ($courses as $c): ?>
                <option value="<?= $c ?>"
                        <?= ($old['course'] ?? '') === $c ? 'selected' : '' ?>>
                  <?= $c ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="field-error <?= isset($errors['course']) ? 'show' : '' ?>"
                  id="course_err" role="alert">
              <?= htmlspecialchars($errors['course'] ?? '') ?>
            </span>
          </div>

          <!-- Year of Study -->
          <div class="form-group">
            <label for="year">Year of Study <span class="required" aria-hidden="true">*</span></label>
            <select id="year" name="year"
                    aria-required="true"
                    aria-describedby="year_err"
                    class="<?= isset($errors['year']) ? 'error' : '' ?>">
              <option value="">-- Select Year --</option>
              <?php foreach (['1st Year','2nd Year','3rd Year','4th Year'] as $y): ?>
                <option value="<?= $y ?>"
                        <?= ($old['year'] ?? '') === $y ? 'selected' : '' ?>>
                  <?= $y ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="field-error <?= isset($errors['year']) ? 'show' : '' ?>"
                  id="year_err" role="alert">
              <?= htmlspecialchars($errors['year'] ?? '') ?>
            </span>
          </div>

          <!-- City -->
          <div class="form-group">
            <label for="city">City</label>
            <input type="text"
                   id="city"
                   name="city"
                   value="<?= htmlspecialchars($old['city'] ?? '') ?>"
                   placeholder="e.g. Mumbai"
                   maxlength="100"
                   autocomplete="address-level2"
                   aria-describedby="city_err"
                   class="<?= isset($errors['city']) ? 'error' : '' ?>">
            <span class="field-error <?= isset($errors['city']) ? 'show' : '' ?>"
                  id="city_err" role="alert">
              <?= htmlspecialchars($errors['city'] ?? '') ?>
            </span>
          </div>

          <!-- Interests (checkboxes) -->
          <div class="form-group full">
            <label>Interests / Hobbies (optional)</label>
            <div class="checkbox-group" role="group" aria-label="Interests">
              <?php foreach (['Web Development','AI/ML','Data Science','Cybersecurity','Mobile Dev','Cloud Computing'] as $interest): ?>
                <label>
                  <input type="checkbox"
                         name="interests[]"
                         value="<?= $interest ?>"
                         <?= in_array($interest, $old['interests'] ?? []) ? 'checked' : '' ?>>
                  <?= $interest ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Message / About -->
          <div class="form-group full">
            <label for="message">About Yourself
              <span class="required" aria-hidden="true">*</span>
            </label>
            <textarea id="message"
                      name="message"
                      maxlength="500"
                      placeholder="Write a short bio (10–500 characters)…"
                      aria-required="true"
                      aria-describedby="message_err char_count"
                      class="<?= isset($errors['message']) ? 'error' : '' ?>"
                      rows="4"><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
            <span class="char-count" id="char_count" aria-live="polite">0 / 500</span>
            <span class="field-error <?= isset($errors['message']) ? 'show' : '' ?>"
                  id="message_err" role="alert">
              <?= htmlspecialchars($errors['message'] ?? '') ?>
            </span>
          </div>

          <!-- Storage Format -->
          <div class="form-group full">
            <label for="storage_format">Storage Format
              <span class="required" aria-hidden="true">*</span>
            </label>
            <select id="storage_format" name="storage_format"
                    aria-required="true"
                    aria-describedby="storage_fmt_err">
              <option value="csv"  <?= ($old['storage_format'] ?? 'csv') === 'csv'  ? 'selected' : '' ?>>
                CSV (registrations.csv)
              </option>
              <option value="json" <?= ($old['storage_format'] ?? '') === 'json' ? 'selected' : '' ?>>
                JSON (registrations.json)
              </option>
            </select>
            <span class="field-error <?= isset($errors['storage_format']) ? 'show' : '' ?>"
                  id="storage_fmt_err" role="alert">
              <?= htmlspecialchars($errors['storage_format'] ?? '') ?>
            </span>
            <div class="security-note" role="note">
              ⚠️ Records are stored in flat files on the server.
              CSV and JSON are both cumulative — new submissions are appended.
            </div>
          </div>

        </div><!-- /form-grid -->

        <!-- Form Actions -->
        <div class="form-actions">
          <button type="submit" class="btn-primary">
            🚀 Submit Registration
          </button>
          <button type="reset" class="btn-secondary"
                  aria-label="Reset all form fields">
            🔄 Reset
          </button>
          <a href="records.php" class="btn-secondary">
            📄 View Records
          </a>
        </div>

      </form>
    </div><!-- /card -->

    <!-- ================================================
         CSRF INFO CARD (educational)
         ================================================ -->
    <div class="card">
      <h2>🔐 Security Features</h2>
      <div class="stats-strip">
        <span class="stat-pill">✅ CSRF Token</span>
        <span class="stat-pill">✅ Server-side Validation</span>
        <span class="stat-pill">✅ Input Sanitization</span>
        <span class="stat-pill">✅ Safe File Writing</span>
        <span class="stat-pill">✅ XSS Prevention</span>
      </div>
      <p style="font-size:.9rem; color:var(--muted); line-height:1.6;">
        A <strong>CSRF token</strong> (<code><?= substr($csrf_token, 0, 16) ?>…</code>) is
        generated per session and validated on every POST to prevent cross-site request
        forgery attacks. All inputs are sanitised with
        <code>htmlspecialchars()</code> and <code>filter_var()</code> before being
        written to disk. File writes use exclusive locks (<code>LOCK_EX</code>)
        to prevent race conditions.
      </p>
    </div>

  </main>

  <!-- ================================================
       FOOTER
       ================================================ -->
  <footer class="site-footer" role="contentinfo">
    <p>
      &copy; 2026 StudentHub – Practical 7: PHP Form Processing &nbsp;|&nbsp;
      <a href="../new/index.html">Home</a> &nbsp;|&nbsp;
      <a href="records.php">View Records</a> &nbsp;|&nbsp;
      <a href="../practical6/index.html">← Practical 6</a>
    </p>
    <p style="margin-top:.4rem; font-size:.78rem;">
      PHP · POST Handling · Server-side Validation · CSV / JSON Storage · CSRF Protection
    </p>
  </footer>

  <!-- ================================================
       CLIENT-SIDE CHAR COUNTER (progressive enhancement)
       ================================================ -->
  <script>
    (function () {
      const ta = document.getElementById('message');
      const cc = document.getElementById('char_count');
      if (!ta || !cc) return;

      function update() {
        const len = ta.value.length;
        cc.textContent = len + ' / 500';
        cc.style.color = len > 450 ? '#dc2626' : len > 350 ? '#d97706' : '#64748b';
      }

      ta.addEventListener('input', update);
      update(); // initialise on page load (for old input)
    })();
  </script>

</body>
</html>
