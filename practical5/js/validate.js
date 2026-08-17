/**
 * Practical 5 – Registration Form Validation
 * Features:
 *  - Regex validation for all fields
 *  - Real-time keyup/change validation (Intermediate)
 *  - Password strength meter
 *  - Show/hide password toggle
 *  - Custom Canvas CAPTCHA (Advanced)
 *  - Accessible error messages (aria-describedby)
 *  - Live summary sidebar update
 *  - Step progress indicator
 *  - Form submission prevention until valid
 */

'use strict';

/* ======================================================
   REGEX PATTERNS
   ====================================================== */
const PATTERNS = {
  firstName:  /^[A-Za-z][A-Za-z\s'-]{1,49}$/,
  lastName:   /^[A-Za-z][A-Za-z\s'-]{1,49}$/,
  email:      /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/,
  mobile:     /^[6-9]\d{9}$/,                      // Indian 10-digit
  dob:        /^\d{4}-\d{2}-\d{2}$/,
  password:   /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_#^])[A-Za-z\d@$!%*?&_#^]{8,}$/,
  rollNumber: /^[A-Z]{2,5}\/\d{4}\/\d{3,6}$/i,    // e.g. BCA/2024/021
};

const ERROR_MSGS = {
  firstName:  'First name must be 2–50 letters (hyphens and apostrophes allowed).',
  lastName:   'Last name must be 2–50 letters (hyphens and apostrophes allowed).',
  email:      'Enter a valid email address (e.g. student@example.com).',
  mobile:     'Enter a valid 10-digit Indian mobile number starting with 6–9.',
  dob:        'Date of birth is required. You must be at least 15 years old.',
  course:     'Please select a course.',
  year:       'Please select your year of study.',
  gender:     'Please select a gender.',
  password:   'Min 8 chars with uppercase, lowercase, number, and special character (@$!%*?&_#^).',
  confirmPwd: 'Passwords do not match.',
  rollNumber: 'Format: BCA/2024/021 (Course/Year/Number).',
  terms:      'You must accept the Terms & Conditions to register.',
  captcha:    'Incorrect CAPTCHA. Click the image to refresh and try again.',
};

/* ======================================================
   UTILITY HELPERS
   ====================================================== */
function $(id) { return document.getElementById(id); }

/**
 * Mark a field as valid or invalid.
 * @param {HTMLElement} group  - .field-group element
 * @param {boolean}     isOk
 * @param {string}      [msg]  - Error message when invalid
 */
function setValidity(group, isOk, msg = '') {
  const errEl  = group.querySelector('.err-msg');
  const icon   = group.querySelector('.val-icon');

  group.classList.toggle('valid',   isOk);
  group.classList.toggle('invalid', !isOk);

  const input = group.querySelector('input, select, textarea');
  if (input) {
    input.setAttribute('aria-invalid', String(!isOk));
  }

  if (errEl) {
    errEl.textContent = isOk ? '' : msg;
    errEl.classList.toggle('show', !isOk);
  }
  if (icon) {
    icon.textContent = isOk ? '✅' : '❌';
  }
}

/** Clear validation state (neutral) */
function clearValidity(group) {
  group.classList.remove('valid', 'invalid');
  const errEl = group.querySelector('.err-msg');
  const icon  = group.querySelector('.val-icon');
  if (errEl) { errEl.textContent = ''; errEl.classList.remove('show'); }
  if (icon)  { icon.textContent = ''; }
  const input = group.querySelector('input, select, textarea');
  if (input) input.removeAttribute('aria-invalid');
}

/** Get .field-group ancestor of an element */
function getGroup(el) { return el.closest('.field-group'); }

/* ======================================================
   INDIVIDUAL FIELD VALIDATORS
   ====================================================== */
function validateFirstName() {
  const el    = $('first-name');
  const group = getGroup(el);
  const v     = el.value.trim();
  const ok    = PATTERNS.firstName.test(v);
  setValidity(group, ok, ERROR_MSGS.firstName);
  updateSummary('s-fname', v || '—');
  return ok;
}

function validateLastName() {
  const el    = $('last-name');
  const group = getGroup(el);
  const v     = el.value.trim();
  const ok    = PATTERNS.lastName.test(v);
  setValidity(group, ok, ERROR_MSGS.lastName);
  updateSummary('s-lname', v || '—');
  return ok;
}

function validateEmail() {
  const el    = $('email');
  const group = getGroup(el);
  const v     = el.value.trim();
  const ok    = PATTERNS.email.test(v);
  setValidity(group, ok, ERROR_MSGS.email);
  updateSummary('s-email', v || '—');
  return ok;
}

function validateMobile() {
  const el    = $('mobile');
  const group = getGroup(el);
  const v     = el.value.trim();
  const ok    = PATTERNS.mobile.test(v);
  setValidity(group, ok, ERROR_MSGS.mobile);
  return ok;
}

function validateDOB() {
  const el    = $('dob');
  const group = getGroup(el);
  const v     = el.value;
  if (!v) { setValidity(group, false, ERROR_MSGS.dob); return false; }
  const dob   = new Date(v);
  const today = new Date();
  const age   = (today - dob) / (365.25 * 24 * 3600 * 1000);
  const ok    = !isNaN(dob) && age >= 15;
  setValidity(group, ok, 'You must be at least 15 years old.');
  return ok;
}

function validateCourse() {
  const el    = $('course');
  const group = getGroup(el);
  const ok    = el.value !== '';
  setValidity(group, ok, ERROR_MSGS.course);
  updateSummary('s-course', ok ? el.options[el.selectedIndex].text : '—');
  return ok;
}

function validateYear() {
  const el    = $('year');
  const group = getGroup(el);
  const ok    = el.value !== '';
  setValidity(group, ok, ERROR_MSGS.year);
  return ok;
}

function validateGender() {
  const selected = document.querySelector('input[name="gender"]:checked');
  const group    = document.getElementById('gender-group-wrap');
  const ok       = !!selected;
  if (group) {
    group.classList.toggle('invalid', !ok);
    const errEl = group.querySelector('.err-msg');
    if (errEl) { errEl.textContent = ok ? '' : ERROR_MSGS.gender; errEl.classList.toggle('show', !ok); }
  }
  return ok;
}

function validateRollNumber() {
  const el    = $('roll-number');
  const group = getGroup(el);
  const v     = el.value.trim();
  const ok    = v === '' || PATTERNS.rollNumber.test(v); // optional field
  setValidity(group, ok, ERROR_MSGS.rollNumber);
  return ok;
}

/* ---- Password Strength ---- */
/**
 * Calculate password strength score (0–4).
 * @param {string} pwd
 * @returns {{ score: number, label: string }}
 */
function getPasswordStrength(pwd) {
  let score = 0;
  if (pwd.length >= 8)             score++;
  if (/[A-Z]/.test(pwd))          score++;
  if (/\d/.test(pwd))             score++;
  if (/[@$!%*?&_#^]/.test(pwd))   score++;

  const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
  return { score, label: labels[score] || '' };
}

function updateStrengthMeter(pwd) {
  const wrap = document.getElementById('strength-wrap');
  if (!wrap) return;
  const { score, label } = getPasswordStrength(pwd);
  wrap.dataset.strength = score;
  const labelEl = document.getElementById('strength-label');
  if (labelEl) labelEl.textContent = pwd ? `Strength: ${label}` : '';

  // Update password requirements checklist
  updateReqItem('req-len',     pwd.length >= 8);
  updateReqItem('req-upper',   /[A-Z]/.test(pwd));
  updateReqItem('req-lower',   /[a-z]/.test(pwd));
  updateReqItem('req-num',     /\d/.test(pwd));
  updateReqItem('req-special', /[@$!%*?&_#^]/.test(pwd));
}

function updateReqItem(id, met) {
  const el = document.getElementById(id);
  if (!el) return;
  const icon = el.querySelector('.req-icon');
  el.classList.toggle('req-met',   met);
  el.classList.toggle('req-unmet', !met);
  if (icon) icon.textContent = met ? '✅' : '○';
}

function validatePassword() {
  const el    = $('password');
  const group = getGroup(el);
  const v     = el.value;
  const ok    = PATTERNS.password.test(v);
  setValidity(group, ok, ERROR_MSGS.password);
  updateStrengthMeter(v);
  // Re-validate confirm if already touched
  if ($('confirm-pwd').value) validateConfirmPwd();
  return ok;
}

function validateConfirmPwd() {
  const el    = $('confirm-pwd');
  const group = getGroup(el);
  const ok    = el.value === $('password').value && el.value !== '';
  setValidity(group, ok, ERROR_MSGS.confirmPwd);
  return ok;
}

function validateTerms() {
  const el    = $('terms');
  const group = getGroup(el);
  const ok    = el.checked;
  group.classList.toggle('invalid', !ok);
  const errEl = group.querySelector('.err-msg');
  if (errEl) { errEl.textContent = ok ? '' : ERROR_MSGS.terms; errEl.classList.toggle('show', !ok); }
  return ok;
}

/* ======================================================
   CAPTCHA  (Advanced Extension – Canvas-based)
   ====================================================== */
let captchaCode = '';

function generateCaptcha() {
  const canvas  = $('captcha-canvas');
  if (!canvas) return;
  const ctx     = canvas.getContext('2d');
  const chars   = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  captchaCode   = Array.from({ length: 6 }, () => chars[Math.floor(Math.random() * chars.length)]).join('');

  const W = canvas.width, H = canvas.height;
  ctx.clearRect(0, 0, W, H);

  // Background gradient
  const grad = ctx.createLinearGradient(0, 0, W, H);
  grad.addColorStop(0, '#e0e7ff'); grad.addColorStop(1, '#f0f4f9');
  ctx.fillStyle = grad;
  ctx.fillRect(0, 0, W, H);

  // Noise lines
  for (let i = 0; i < 6; i++) {
    ctx.strokeStyle = `hsla(${Math.random()*360},60%,65%,.5)`;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(Math.random() * W, Math.random() * H);
    ctx.lineTo(Math.random() * W, Math.random() * H);
    ctx.stroke();
  }

  // Noise dots
  for (let i = 0; i < 40; i++) {
    ctx.fillStyle = `hsla(${Math.random()*360},60%,65%,.4)`;
    ctx.beginPath();
    ctx.arc(Math.random() * W, Math.random() * H, 1.5, 0, Math.PI * 2);
    ctx.fill();
  }

  // Draw characters with slight rotation
  const fonts  = ['bold 22px Georgia', 'bold 22px Arial', 'bold 22px Courier New'];
  const colors = ['#1a237e', '#b71c1c', '#1b5e20', '#4a148c', '#e65100', '#006064'];
  captchaCode.split('').forEach((ch, i) => {
    ctx.save();
    ctx.font      = fonts[i % fonts.length];
    ctx.fillStyle = colors[i % colors.length];
    const x = 14 + i * 26;
    const y = H / 2 + 8;
    ctx.translate(x, y);
    ctx.rotate((Math.random() - 0.5) * 0.5);
    ctx.fillText(ch, 0, 0);
    ctx.restore();
  });

  // Update aria-label for screen readers (provide alternative accessible code)
  canvas.setAttribute('aria-label', `CAPTCHA image. The code is: ${captchaCode.split('').join(' ')}`);
}

function validateCaptcha() {
  const el    = $('captcha-input');
  const group = getGroup(el);
  const ok    = el.value.trim().toUpperCase() === captchaCode;
  setValidity(group, ok, ERROR_MSGS.captcha);
  return ok;
}

/* ======================================================
   SHOW / HIDE PASSWORD TOGGLE
   ====================================================== */
function setupPasswordToggle(inputId, btnId) {
  const input = $(inputId);
  const btn   = $(btnId);
  if (!input || !btn) return;
  btn.addEventListener('click', () => {
    const isText = input.type === 'text';
    input.type   = isText ? 'password' : 'text';
    btn.textContent = isText ? '👁' : '🙈';
    btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
  });
}

/* ======================================================
   SUMMARY SIDEBAR UPDATE
   ====================================================== */
function updateSummary(id, value) {
  const el = $(id);
  if (el) el.textContent = value;
}

/* ======================================================
   STEP PROGRESS INDICATOR
   ====================================================== */
const fieldStepMap = {
  'first-name': 1, 'last-name': 1, 'email': 1, 'mobile': 1, 'dob': 1,
  'course': 2, 'year': 2, 'roll-number': 2,
  'password': 3, 'confirm-pwd': 3,
};

function updateSteps() {
  const steps   = document.querySelectorAll('.step');
  const allOk   = [
    validateFirstName, validateLastName, validateEmail, validateMobile, validateDOB,
    validateCourse, validateYear, validateGender,
    validatePassword, validateConfirmPwd,
    validateTerms, validateCaptcha
  ];
  // Just use visual indication based on filled fields (non-destructive)
  const step1Fields = ['first-name', 'last-name', 'email', 'mobile', 'dob'];
  const step2Fields = ['course', 'year'];
  const step3Fields = ['password', 'confirm-pwd'];

  function allFilled(ids) { return ids.every(id => { const el = $(id); return el && el.value.trim(); }); }

  if (steps[0]) steps[0].classList.toggle('completed', allFilled(step1Fields));
  if (steps[1]) steps[1].classList.toggle('completed', allFilled(step1Fields) && allFilled(step2Fields));
  if (steps[2]) steps[2].classList.toggle('completed', allFilled(step1Fields) && allFilled(step2Fields) && allFilled(step3Fields) && $('terms')?.checked);

  // Active step
  if (!allFilled(step1Fields)) {
    steps.forEach((s, i) => s.classList.toggle('active', i === 0));
  } else if (!allFilled(step2Fields)) {
    steps.forEach((s, i) => s.classList.toggle('active', i === 1));
  } else {
    steps.forEach((s, i) => s.classList.toggle('active', i === 2));
  }
}

/* ======================================================
   REAL-TIME VALIDATION  (Intermediate Extension)
   ====================================================== */
function attachRealTimeValidation() {

  const map = [
    { id: 'first-name',  fn: validateFirstName,  events: ['input', 'blur'] },
    { id: 'last-name',   fn: validateLastName,   events: ['input', 'blur'] },
    { id: 'email',       fn: validateEmail,      events: ['input', 'blur'] },
    { id: 'mobile',      fn: validateMobile,     events: ['input', 'blur'] },
    { id: 'dob',         fn: validateDOB,        events: ['change', 'blur'] },
    { id: 'course',      fn: validateCourse,     events: ['change'] },
    { id: 'year',        fn: validateYear,       events: ['change'] },
    { id: 'roll-number', fn: validateRollNumber, events: ['input', 'blur'] },
    { id: 'password',    fn: validatePassword,   events: ['input', 'blur'] },
    { id: 'confirm-pwd', fn: validateConfirmPwd, events: ['input', 'blur'] },
    { id: 'captcha-input', fn: validateCaptcha,  events: ['input', 'blur'] },
  ];

  map.forEach(({ id, fn, events }) => {
    const el = $(id);
    if (!el) return;
    events.forEach(ev => {
      el.addEventListener(ev, () => { fn(); updateSteps(); });
    });
  });

  // Gender radios
  document.querySelectorAll('input[name="gender"]').forEach(r => {
    r.addEventListener('change', () => { validateGender(); updateSteps(); });
  });

  // Terms
  const termsEl = $('terms');
  if (termsEl) termsEl.addEventListener('change', () => { validateTerms(); updateSteps(); });
}

/* ======================================================
   FULL FORM VALIDATION (on submit)
   ====================================================== */
function validateAll() {
  const results = [
    validateFirstName(),
    validateLastName(),
    validateEmail(),
    validateMobile(),
    validateDOB(),
    validateCourse(),
    validateYear(),
    validateGender(),
    validateRollNumber(),
    validatePassword(),
    validateConfirmPwd(),
    validateTerms(),
    validateCaptcha(),
  ];
  return results.every(Boolean);
}

/* ======================================================
   FORM SUBMIT HANDLER
   ====================================================== */
function handleSubmit(e) {
  e.preventDefault();
  if (validateAll()) {
    // Show success panel
    document.getElementById('form-section').style.display = 'none';
    const panel = document.getElementById('success-panel');
    if (panel) {
      panel.style.display = 'block';
      panel.focus();
    }
  } else {
    // Scroll to first invalid field
    const firstInvalid = document.querySelector('.field-group.invalid input, .field-group.invalid select');
    if (firstInvalid) {
      firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      firstInvalid.focus();
    }
  }
}

/* ======================================================
   INIT
   ====================================================== */
document.addEventListener('DOMContentLoaded', () => {

  // Captcha
  generateCaptcha();
  const refreshBtn = $('captcha-refresh');
  const canvas     = $('captcha-canvas');
  if (refreshBtn) refreshBtn.addEventListener('click', () => { generateCaptcha(); if ($('captcha-input')) $('captcha-input').value = ''; clearValidity(getGroup($('captcha-input'))); });
  if (canvas)     canvas.addEventListener('click',    () => { generateCaptcha(); if ($('captcha-input')) $('captcha-input').value = ''; clearValidity(getGroup($('captcha-input'))); });

  // Show/hide password toggles
  setupPasswordToggle('password',    'pwd-toggle-1');
  setupPasswordToggle('confirm-pwd', 'pwd-toggle-2');

  // Real-time validation
  attachRealTimeValidation();

  // Form submit
  const form = $('registration-form');
  if (form) form.addEventListener('submit', handleSubmit);

  // Reset button
  const resetBtn = $('reset-btn');
  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      document.querySelectorAll('.field-group').forEach(g => clearValidity(g));
      updateStrengthMeter('');
      generateCaptcha();
      updateSteps();
      // Clear summary
      ['s-fname','s-lname','s-email','s-course'].forEach(id => updateSummary(id, '—'));
    });
  }
});
