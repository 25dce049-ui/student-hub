/**
 * Practical 9 – Secure User Registration
 * js/validate.js  –  Frontend validation & UX enhancements
 *
 * Features:
 *  • Real-time field validation with clear error messages
 *  • Password strength meter
 *  • Toggle password visibility
 *  • Prevent submission if validation fails
 *  • Consistent with backend rules (min lengths, patterns)
 */

(function () {
  'use strict';

  /* ── Helpers ── */
  const $ = (id) => document.getElementById(id);
  const setError = (fieldId, errId, msg) => {
    const field = $(fieldId);
    const err   = $(errId);
    if (!field || !err) return;
    err.textContent = msg;
    if (msg) {
      field.classList.add('is-invalid');
      field.classList.remove('is-valid');
      field.setAttribute('aria-invalid', 'true');
    } else {
      field.classList.remove('is-invalid');
      field.classList.add('is-valid');
      field.removeAttribute('aria-invalid');
    }
  };

  /* ── Validators ── */
  function validateFullName() {
    const val = $('full_name').value.trim();
    if (!val)           return setError('full_name', 'full_name_err', 'Full name is required.');
    if (val.length < 3) return setError('full_name', 'full_name_err', 'Name must be at least 3 characters.');
    if (val.length > 100) return setError('full_name', 'full_name_err', 'Name cannot exceed 100 characters.');
    setError('full_name', 'full_name_err', '');
    return true;
  }

  function validateUsername() {
    const val = $('username').value.trim();
    const pattern = /^[a-zA-Z0-9_]+$/;
    if (!val)              return setError('username', 'username_err', 'Username is required.');
    if (val.length < 3)    return setError('username', 'username_err', 'Username must be at least 3 characters.');
    if (val.length > 50)   return setError('username', 'username_err', 'Username cannot exceed 50 characters.');
    if (!pattern.test(val))return setError('username', 'username_err', 'Only letters, numbers, and underscores allowed.');
    setError('username', 'username_err', '');
    return true;
  }

  function validateEmail() {
    const val = $('email').value.trim();
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!val)               return setError('email', 'email_err', 'Email address is required.');
    if (!pattern.test(val)) return setError('email', 'email_err', 'Please enter a valid email address.');
    if (val.length > 100)   return setError('email', 'email_err', 'Email cannot exceed 100 characters.');
    setError('email', 'email_err', '');
    return true;
  }

  function validatePassword() {
    const val = $('password').value;
    if (!val)           return setError('password', 'password_err', 'Password is required.');
    if (val.length < 8) return setError('password', 'password_err', 'Password must be at least 8 characters.');
    setError('password', 'password_err', '');
    return true;
  }

  function validateConfirmPassword() {
    const pwd     = $('password').value;
    const confirm = $('confirm_password').value;
    if (!confirm)         return setError('confirm_password', 'confirm_password_err', 'Please confirm your password.');
    if (pwd !== confirm)  return setError('confirm_password', 'confirm_password_err', 'Passwords do not match.');
    setError('confirm_password', 'confirm_password_err', '');
    return true;
  }

  /* ── Password Strength Meter ── */
  function updateStrength(pwd) {
    const bar   = $('strengthBar');
    const label = $('password_strength_label');
    if (!bar || !label) return;

    let score = 0;
    if (pwd.length >= 8)                  score++;
    if (pwd.length >= 12)                 score++;
    if (/[A-Z]/.test(pwd))               score++;
    if (/[0-9]/.test(pwd))               score++;
    if (/[^A-Za-z0-9]/.test(pwd))        score++;

    const levels = [
      { pct: '0%',   color: 'transparent', text: ''           },
      { pct: '25%',  color: '#dc2626',     text: 'Weak'       },
      { pct: '50%',  color: '#d97706',     text: 'Fair'       },
      { pct: '75%',  color: '#2563eb',     text: 'Good'       },
      { pct: '100%', color: '#16a34a',     text: 'Strong ✓'   },
    ];

    const level = pwd.length === 0 ? levels[0] : levels[Math.min(score, 4)];
    bar.style.width      = level.pct;
    bar.style.background = level.color;
    label.textContent    = level.text;
    label.style.color    = level.color;
  }

  /* ── Event binding ── */
  const form = $('registerForm');
  if (!form) return;

  $('full_name')?.addEventListener('blur',  validateFullName);
  $('full_name')?.addEventListener('input', validateFullName);
  $('username')?.addEventListener('blur',  validateUsername);
  $('username')?.addEventListener('input', validateUsername);
  $('email')?.addEventListener('blur',    validateEmail);
  $('email')?.addEventListener('input',   validateEmail);
  $('password')?.addEventListener('blur',  validatePassword);
  $('password')?.addEventListener('input', function () {
    validatePassword();
    updateStrength(this.value);
    // Re-validate confirm if already touched
    if ($('confirm_password').value) validateConfirmPassword();
  });
  $('confirm_password')?.addEventListener('blur',  validateConfirmPassword);
  $('confirm_password')?.addEventListener('input', validateConfirmPassword);

  /* ── Toggle password visibility ── */
  document.querySelectorAll('.toggle-pwd').forEach((btn) => {
    btn.addEventListener('click', function () {
      const target = document.getElementById(this.dataset.target);
      if (!target) return;
      const isHidden = target.type === 'password';
      target.type    = isHidden ? 'text' : 'password';
      this.textContent = isHidden ? '🙈' : '👁️';
      this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  });

  /* ── Form submit: run all validators ── */
  form.addEventListener('submit', function (e) {
    const results = [
      validateFullName(),
      validateUsername(),
      validateEmail(),
      validatePassword(),
      validateConfirmPassword(),
    ];

    if (results.some((r) => r !== true)) {
      e.preventDefault();
      // Focus the first invalid field
      const firstInvalid = form.querySelector('.is-invalid');
      if (firstInvalid) firstInvalid.focus();
    }
  });

})();
