/**
 * Practical 4 – JavaScript DOM Manipulation,
 * Event Handling & UI Interactivity
 * StudentHub
 *
 * Features implemented:
 *  1. Notification banner (dismiss + auto-hide)
 *  2. Hamburger / mobile nav toggle
 *  3. Light / Dark theme switcher (localStorage)
 *  4. FAQ accordion (collapsible, keyboard-friendly)
 *  5. Modal popup (open / close / Escape key / focus trap)
 *  6. Content / image slider (prev/next/dots/auto-play)
 *  7. Transition effects on cards (hover via CSS, click via JS)
 *  8. localStorage – restore theme preference on page load
 */

'use strict';

/* ======================================================
   1. THEME SWITCHER  (localStorage)
   ====================================================== */
const THEME_KEY = 'sh_theme';
const themeBtn  = document.getElementById('theme-btn');
const body      = document.body;

/**
 * Apply the given theme ('dark' | 'light') and persist it.
 * @param {string} theme
 */
function applyTheme(theme) {
  if (theme === 'dark') {
    body.classList.add('dark-mode');
    if (themeBtn) themeBtn.textContent = '☀️ Light Mode';
    if (themeBtn) themeBtn.setAttribute('aria-label', 'Switch to light mode');
  } else {
    body.classList.remove('dark-mode');
    if (themeBtn) themeBtn.textContent = '🌙 Dark Mode';
    if (themeBtn) themeBtn.setAttribute('aria-label', 'Switch to dark mode');
  }
  localStorage.setItem(THEME_KEY, theme);
}

// Restore saved preference on load
(function restoreTheme() {
  const saved = localStorage.getItem(THEME_KEY) || 'light';
  applyTheme(saved);
})();

// Toggle on button click
if (themeBtn) {
  themeBtn.addEventListener('click', () => {
    const current = localStorage.getItem(THEME_KEY) || 'light';
    applyTheme(current === 'dark' ? 'light' : 'dark');
  });
}

/* ======================================================
   2. NOTIFICATION BANNER
   ====================================================== */
const banner      = document.getElementById('notification-banner');
const closeBtn    = document.getElementById('close-banner');
const BANNER_KEY  = 'sh_banner_dismissed';

// If already dismissed in this session, hide immediately
if (banner && sessionStorage.getItem(BANNER_KEY)) {
  banner.classList.add('hidden');
}

if (closeBtn) {
  closeBtn.addEventListener('click', () => {
    banner.classList.add('hidden');
    sessionStorage.setItem(BANNER_KEY, '1');
  });
}

/* ======================================================
   3. HAMBURGER NAV
   ====================================================== */
const hamburger = document.getElementById('hamburger-btn');
const navLinks  = document.getElementById('main-nav');

if (hamburger && navLinks) {
  hamburger.addEventListener('click', () => {
    const expanded = hamburger.getAttribute('aria-expanded') === 'true';
    hamburger.setAttribute('aria-expanded', String(!expanded));
    navLinks.classList.toggle('open');
  });

  // Close nav on outside click
  document.addEventListener('click', (e) => {
    if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
      hamburger.setAttribute('aria-expanded', 'false');
      navLinks.classList.remove('open');
    }
  });
}

/* ======================================================
   4. FAQ ACCORDION
   ====================================================== */
const faqBtns = document.querySelectorAll('.faq-btn');

faqBtns.forEach((btn) => {
  btn.addEventListener('click', () => {
    const expanded = btn.getAttribute('aria-expanded') === 'true';
    const answer   = document.getElementById(btn.getAttribute('aria-controls'));

    // Close all others (single-open accordion)
    faqBtns.forEach((b) => {
      if (b !== btn) {
        b.setAttribute('aria-expanded', 'false');
        const a = document.getElementById(b.getAttribute('aria-controls'));
        if (a) a.classList.remove('open');
      }
    });

    // Toggle current
    btn.setAttribute('aria-expanded', String(!expanded));
    if (answer) answer.classList.toggle('open', !expanded);
  });

  // Keyboard: Space / Enter already fire click on <button>
  // Arrow Up/Down to navigate between FAQ items
  btn.addEventListener('keydown', (e) => {
    const items = [...faqBtns];
    const idx   = items.indexOf(btn);
    if (e.key === 'ArrowDown') { e.preventDefault(); items[Math.min(idx + 1, items.length - 1)].focus(); }
    if (e.key === 'ArrowUp')   { e.preventDefault(); items[Math.max(idx - 1, 0)].focus(); }
  });
});

/* ======================================================
   5. MODAL POPUP
   ====================================================== */
const modalOverlay   = document.getElementById('modal-overlay');
const modalCloseBtn  = document.getElementById('modal-close');
const openModalBtns  = document.querySelectorAll('.open-modal-btn');

// All focusable elements inside modal
function getFocusable(container) {
  return [...container.querySelectorAll(
    'a[href], button:not([disabled]), input, textarea, select, [tabindex]:not([tabindex="-1"])'
  )];
}

function openModal() {
  if (!modalOverlay) return;
  modalOverlay.classList.add('open');
  modalOverlay.removeAttribute('hidden');
  // Move focus to first focusable inside modal
  const focusable = getFocusable(modalOverlay);
  if (focusable.length) focusable[0].focus();
}

function closeModal() {
  if (!modalOverlay) return;
  modalOverlay.classList.remove('open');
  modalOverlay.setAttribute('hidden', '');
  // Return focus to the button that opened the modal
  if (lastFocused) lastFocused.focus();
}

let lastFocused = null;

openModalBtns.forEach((btn) => {
  btn.addEventListener('click', () => {
    lastFocused = btn;
    openModal();
  });
});

if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);

// Close on overlay background click
if (modalOverlay) {
  modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
  });
}

// Close on Escape; trap Tab inside modal
document.addEventListener('keydown', (e) => {
  if (!modalOverlay || !modalOverlay.classList.contains('open')) return;

  if (e.key === 'Escape') {
    closeModal();
    return;
  }

  // Focus trap
  if (e.key === 'Tab') {
    const focusable = getFocusable(modalOverlay);
    if (!focusable.length) return;
    const first = focusable[0];
    const last  = focusable[focusable.length - 1];

    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault(); last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault(); first.focus();
    }
  }
});

/* ======================================================
   6. CONTENT SLIDER
   ====================================================== */
const sliderTrack = document.getElementById('slider-track');
const prevBtn     = document.getElementById('slider-prev');
const nextBtn     = document.getElementById('slider-next');
const dotsWrap    = document.getElementById('slider-dots');

let currentSlide = 0;
let autoPlayTimer = null;
const AUTOPLAY_DELAY = 4000;

function getSlides() {
  return sliderTrack ? [...sliderTrack.querySelectorAll('.slide')] : [];
}

function getDots() {
  return dotsWrap ? [...dotsWrap.querySelectorAll('.dot')] : [];
}

function goToSlide(index) {
  const slides = getSlides();
  if (!slides.length || !sliderTrack) return;

  currentSlide = (index + slides.length) % slides.length;
  sliderTrack.style.transform = `translateX(-${currentSlide * 100}%)`;

  // Update dots
  getDots().forEach((d, i) => {
    d.classList.toggle('active', i === currentSlide);
    d.setAttribute('aria-selected', String(i === currentSlide));
  });

  // Update live region for screen readers
  const liveEl = document.getElementById('slider-live');
  if (liveEl) liveEl.textContent = `Slide ${currentSlide + 1} of ${slides.length}`;
}

function startAutoPlay() {
  stopAutoPlay();
  autoPlayTimer = setInterval(() => goToSlide(currentSlide + 1), AUTOPLAY_DELAY);
}

function stopAutoPlay() {
  if (autoPlayTimer) { clearInterval(autoPlayTimer); autoPlayTimer = null; }
}

if (prevBtn) prevBtn.addEventListener('click', () => { stopAutoPlay(); goToSlide(currentSlide - 1); startAutoPlay(); });
if (nextBtn) nextBtn.addEventListener('click', () => { stopAutoPlay(); goToSlide(currentSlide + 1); startAutoPlay(); });

// Build dots dynamically
(function initSlider() {
  const slides = getSlides();
  if (!slides.length || !dotsWrap) return;

  slides.forEach((_, i) => {
    const dot = document.createElement('button');
    dot.className = 'dot' + (i === 0 ? ' active' : '');
    dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
    dot.setAttribute('aria-selected', String(i === 0));
    dot.addEventListener('click', () => { stopAutoPlay(); goToSlide(i); startAutoPlay(); });
    dotsWrap.appendChild(dot);
  });

  goToSlide(0);
  startAutoPlay();

  // Pause autoplay on hover / focus
  const wrap = document.querySelector('.slider-wrap');
  if (wrap) {
    wrap.addEventListener('mouseenter', stopAutoPlay);
    wrap.addEventListener('focusin',    stopAutoPlay);
    wrap.addEventListener('mouseleave', startAutoPlay);
    wrap.addEventListener('focusout',   startAutoPlay);
  }
})();

// Keyboard navigation on slider
if (sliderTrack) {
  sliderTrack.setAttribute('tabindex', '0');
  sliderTrack.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft')  { stopAutoPlay(); goToSlide(currentSlide - 1); startAutoPlay(); }
    if (e.key === 'ArrowRight') { stopAutoPlay(); goToSlide(currentSlide + 1); startAutoPlay(); }
  });
}

/* ======================================================
   7. TRANSITION CARD CLICK EFFECT  (Intermediate Ext.)
   ====================================================== */
document.querySelectorAll('.trans-card').forEach((card) => {
  card.addEventListener('click', () => {
    // Pulse animation via class
    card.classList.add('clicked');
    card.addEventListener('animationend', () => card.classList.remove('clicked'), { once: true });
    // Visual feedback: briefly highlight border
    card.style.borderColor = 'var(--accent)';
    setTimeout(() => { card.style.borderColor = ''; }, 600);
  });

  // Accessible keyboard activation
  card.setAttribute('tabindex', '0');
  card.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
  });
});

/* ======================================================
   8. RESTORE UI PREFERENCES ON PAGE LOAD  (Advanced Ext.)
      Theme already restored at top. Add any other prefs here.
   ====================================================== */
(function restoreUIPrefs() {
  // Example: restore font-size preference
  const fontSize = localStorage.getItem('sh_font_size');
  if (fontSize) document.documentElement.style.fontSize = fontSize;
})();

/* ======================================================
   HELPER: Show a toast-style notification
   ====================================================== */
/**
 * Display a temporary toast message.
 * @param {string} message
 * @param {'success'|'error'|'info'} type
 */
function showToast(message, type = 'info') {
  let toast = document.getElementById('toast-container');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast-container';
    toast.setAttribute('aria-live', 'polite');
    toast.setAttribute('role', 'status');
    Object.assign(toast.style, {
      position: 'fixed', bottom: '1.5rem', right: '1.5rem',
      zIndex: '2000', display: 'flex', flexDirection: 'column', gap: '.5rem'
    });
    document.body.appendChild(toast);
  }

  const colors = { success: '#16a34a', error: '#dc2626', info: '#1a73e8' };
  const msg = document.createElement('div');
  msg.textContent = message;
  Object.assign(msg.style, {
    background: colors[type] || colors.info,
    color: '#fff', padding: '.7rem 1.2rem',
    borderRadius: '8px', fontSize: '.9rem',
    boxShadow: '0 4px 12px rgba(0,0,0,.2)',
    opacity: '0', transition: 'opacity .3s ease',
    maxWidth: '320px'
  });
  toast.appendChild(msg);
  requestAnimationFrame(() => { msg.style.opacity = '1'; });
  setTimeout(() => {
    msg.style.opacity = '0';
    msg.addEventListener('transitionend', () => msg.remove(), { once: true });
  }, 3500);
}

// Expose showToast globally for inline use
window.showToast = showToast;
