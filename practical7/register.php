<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/response.php';

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register | StudentHub – Practical 7</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<a class="skip-link" href="#main">Skip to main content</a>
<header class="navbar">
  <a href="index.php" class="logo">🎓 StudentHub</a>
  <nav aria-label="Primary navigation">
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="register.php" aria-current="page">Register</a></li>
      <li><a href="contact.php">Contact</a></li>
      <li><a href="view_records.php">View Records</a></li>
    </ul>
  </nav>
</header>

<main id="main" class="container">
  <div class="form-card">
    <h1>Student Registration</h1>
    <p class="subtitle">Practical 7 – PHP POST · Server-Side Validation · CSV/JSON Storage · CSRF</p>

    <?php if ($flash): echo render_flash($flash); endif; ?>

    <form action="register_process.php" method="POST" novalidate aria-label="Student registration form">
      <?= csrf_field() ?>

      <fieldset>
        <legend>Personal Information</legend>
        <div class="field-row">
          <div class="field-group">
            <label for="first_name">First Name <span class="req">*</span></label>
            <input type="text" id="first_name" name="first_name"
                   value="<?= old('first_name') ?>"
                   autocomplete="given-name" required aria-required="true"
                   aria-describedby="err_first_name" placeholder="e.g. Rahul"/>
            <?= field_error('first_name') ?>
          </div>
          <div class="field-group">
            <label for="last_name">Last Name <span class="req">*</span></label>
            <input type="text" id="last_name" name="last_name"
                   value="<?= old('last_name') ?>"
                   autocomplete="family-name" required aria-required="true"
                   placeholder="e.g. Verma"/>
            <?= field_error('last_name') ?>
          </div>
        </div>

        <div class="field-group">
          <label for="email">Email Address <span class="req">*</span></label>
          <input type="email" id="email" name="email"
                 value="<?= old('email') ?>"
                 autocomplete="email" required aria-required="true"
                 placeholder="student@example.com"/>
          <?= field_error('email') ?>
        </div>

        <div class="field-row">
          <div class="field-group">
            <label for="mobile">Mobile Number <span class="req">*</span></label>
            <input type="tel" id="mobile" name="mobile"
                   value="<?= old('mobile') ?>"
                   autocomplete="tel" required aria-required="true"
                   placeholder="10-digit number" maxlength="10"/>
            <?= field_error('mobile') ?>
          </div>
          <div class="field-group">
            <label for="dob">Date of Birth <span class="req">*</span></label>
            <input type="date" id="dob" name="dob"
                   value="<?= old('dob') ?>"
                   required aria-required="true" max="2011-12-31"/>
            <?= field_error('dob') ?>
          </div>
        </div>

        <div class="field-group">
          <fieldset class="inline-fieldset">
            <legend>Gender <span class="req">*</span></legend>
            <div class="radio-group">
              <?php foreach (['Male','Female','Other'] as $g): ?>
              <label class="radio-label">
                <input type="radio" name="gender" value="<?= $g ?>"
                       <?= old('gender') === $g ? 'checked' : '' ?> required/>
                <?= $g ?>
              </label>
              <?php endforeach; ?>
            </div>
          </fieldset>
          <?= field_error('gender') ?>
        </div>
      </fieldset>

      <fieldset>
        <legend>Academic Information</legend>
        <div class="field-row">
          <div class="field-group">
            <label for="course">Course <span class="req">*</span></label>
            <select id="course" name="course" required aria-required="true">
              <option value="">-- Select Course --</option>
              <?php foreach (ALLOWED_COURSES as $c): ?>
              <option value="<?= $c ?>" <?= old('course') === $c ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
            <?= field_error('course') ?>
          </div>
          <div class="field-group">
            <label for="year">Year of Study <span class="req">*</span></label>
            <select id="year" name="year" required aria-required="true">
              <option value="">-- Select Year --</option>
              <?php foreach (ALLOWED_YEARS as $y): ?>
              <option value="<?= $y ?>" <?= old('year') == $y ? 'selected' : '' ?>>Year <?= $y ?></option>
              <?php endforeach; ?>
            </select>
            <?= field_error('year') ?>
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Account Security</legend>
        <div class="field-row">
          <div class="field-group">
            <label for="password">Password <span class="req">*</span></label>
            <input type="password" id="password" name="password"
                   autocomplete="new-password" required aria-required="true"
                   aria-describedby="pwd_hint" placeholder="Min 8 chars"/>
            <small id="pwd_hint" class="hint">Uppercase + lowercase + number + special char</small>
            <?= field_error('password') ?>
          </div>
          <div class="field-group">
            <label for="confirm_password">Confirm Password <span class="req">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password"
                   autocomplete="new-password" required aria-required="true"/>
            <?= field_error('confirm_password') ?>
          </div>
        </div>
      </fieldset>

      <div class="field-group">
        <label class="check-label">
          <input type="checkbox" name="terms" value="1"
                 <?= old('terms') ? 'checked' : '' ?> required aria-required="true"/>
          I agree to the <a href="#">Terms &amp; Conditions</a> and <a href="#">Privacy Policy</a>.
        </label>
        <?= field_error('terms') ?>
      </div>

      <button type="submit" class="btn-submit">Register Now</button>
      <p class="mt-1">Already registered? <a href="index.php">Go to Home</a></p>
    </form>
  </div>
</main>

<footer class="site-footer" role="contentinfo">
  <p>&copy; 2026 StudentHub – Practical 7 | <a href="index.php">Home</a></p>
</footer>
</body>
</html>
