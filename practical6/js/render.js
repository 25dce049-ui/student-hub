/**
 * render.js – Practical 6
 * Responsible for: Building HTML strings and injecting them into the DOM.
 * All render functions receive data + the current search query
 * (for highlight support) and return/insert HTML.
 */

'use strict';

import { formatDate, highlight, escapeHTML } from './util.js';

/* ======================================================
   LOADING / ERROR / EMPTY STATES
   ====================================================== */

export function showLoading(containerId) {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.innerHTML = `
    <div class="state-box" role="status" aria-live="polite">
      <div class="spinner" aria-hidden="true"></div>
      <p>Loading data…</p>
    </div>`;
}

export function showError(containerId, message) {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.innerHTML = `
    <div class="state-box state-error" role="alert">
      <span class="state-icon" aria-hidden="true">⚠️</span>
      <p><strong>Error:</strong> ${escapeHTML(message)}</p>
      <button class="btn-retry" onclick="location.reload()">Retry</button>
    </div>`;
}

export function showEmpty(containerId, message = 'No results found.') {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.innerHTML = `
    <div class="state-box state-empty" role="status">
      <span class="state-icon" aria-hidden="true">🔍</span>
      <p>${escapeHTML(message)}</p>
    </div>`;
}

/* ======================================================
   RESULTS COUNT
   ====================================================== */

export function renderResultsInfo(containerId, current, total) {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.textContent = total === 0
    ? 'No results'
    : `Showing ${current} of ${total} record${total !== 1 ? 's' : ''}`;
}

/* ======================================================
   PAGINATION CONTROLS
   ====================================================== */

/**
 * Render numbered pagination buttons.
 * @param {string}   containerId
 * @param {number}   currentPage
 * @param {number}   totalPages
 * @param {Function} onPageChange  callback(pageNumber)
 */
export function renderPagination(containerId, currentPage, totalPages, onPageChange) {
  const el = document.getElementById(containerId);
  if (!el) return;

  if (totalPages <= 1) { el.innerHTML = ''; return; }

  const pages = buildPageRange(currentPage, totalPages);
  const btns  = pages.map(p => {
    if (p === '…') return `<span class="page-ellipsis" aria-hidden="true">…</span>`;
    const active = p === currentPage ? ' active' : '';
    const aria   = p === currentPage ? ` aria-current="page"` : '';
    return `<button class="page-btn${active}"${aria} data-page="${p}"
              aria-label="Go to page ${p}">${p}</button>`;
  }).join('');

  el.innerHTML = `
    <nav class="pagination" aria-label="Pagination">
      <button class="page-btn page-prev" data-page="${currentPage - 1}"
        ${currentPage <= 1 ? 'disabled aria-disabled="true"' : ''}
        aria-label="Previous page">&#8592; Prev</button>
      ${btns}
      <button class="page-btn page-next" data-page="${currentPage + 1}"
        ${currentPage >= totalPages ? 'disabled aria-disabled="true"' : ''}
        aria-label="Next page">Next &#8594;</button>
    </nav>`;

  el.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
    btn.addEventListener('click', () => {
      const p = parseInt(btn.dataset.page, 10);
      if (!isNaN(p)) onPageChange(p);
    });
  });
}

/** Build compact page range with ellipsis. */
function buildPageRange(current, total) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const pages = new Set([1, total, current, current - 1, current + 1]
    .filter(p => p >= 1 && p <= total));
  const sorted = [...pages].sort((a, b) => a - b);
  const result = [];
  let prev = 0;
  for (const p of sorted) {
    if (p - prev > 1) result.push('…');
    result.push(p);
    prev = p;
  }
  return result;
}

/* ======================================================
   EVENTS RENDERER
   ====================================================== */

const STATUS_BADGE = {
  'Open':        'badge-success',
  'Almost Full': 'badge-warning',
  'Full':        'badge-danger',
};

/**
 * Render event cards into a grid container.
 * @param {string} containerId
 * @param {Array}  events
 * @param {string} [query]   current search query for highlight
 */
export function renderEvents(containerId, events, query = '') {
  const el = document.getElementById(containerId);
  if (!el) return;

  if (!events.length) { showEmpty(containerId, 'No events match your search.'); return; }

  el.innerHTML = events.map(ev => {
    const pct      = Math.round((ev.registered / ev.seats) * 100);
    const badge    = STATUS_BADGE[ev.status] || 'badge-info';
    const feeText  = ev.fee === 0 ? 'Free' : `₹${ev.fee}`;
    const tags     = (ev.tags || []).map(t =>
      `<span class="tag">${escapeHTML(t)}</span>`).join('');

    return `
      <article class="card event-card" data-id="${ev.id}"
               tabindex="0" role="article"
               aria-label="${escapeHTML(ev.title)} event card">
        <div class="card-header">
          <span class="card-category">${escapeHTML(ev.category)}</span>
          <span class="badge ${badge}">${escapeHTML(ev.status)}</span>
        </div>
        <div class="card-body">
          <h3 class="card-title">${highlight(ev.title, query)}</h3>
          <p class="card-meta">
            <span>📅 <time datetime="${ev.date}">${formatDate(ev.date)}</time></span>
            <span>📍 ${highlight(ev.venue, query)}</span>
          </p>
          <p class="card-desc">${highlight(ev.description, query)}</p>
          <div class="seat-bar-wrap" aria-label="Seats filled: ${pct}%">
            <div class="seat-bar-label">
              <span>${ev.registered} / ${ev.seats} seats</span>
              <span>${pct}%</span>
            </div>
            <div class="seat-bar" role="progressbar"
                 aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100">
              <div class="seat-bar-fill" style="width:${pct}%"></div>
            </div>
          </div>
        </div>
        <div class="card-footer">
          <div class="tag-list">${tags}</div>
          <span class="fee-badge ${ev.fee === 0 ? 'fee-free' : 'fee-paid'}">${feeText}</span>
        </div>
      </article>`;
  }).join('');
}

/* ======================================================
   STUDENTS RENDERER
   ====================================================== */

const CGPA_COLOR = cgpa =>
  cgpa >= 9.0 ? '#16a34a' :
  cgpa >= 7.5 ? '#1a73e8' :
  cgpa >= 6.0 ? '#d97706' : '#dc2626';

const STATUS_CLASS = { Active: 'badge-success', Warning: 'badge-danger', Inactive: 'badge-warning' };

/**
 * Render student profile cards.
 * @param {string} containerId
 * @param {Array}  students
 * @param {string} [query]
 */
export function renderStudents(containerId, students, query = '') {
  const el = document.getElementById(containerId);
  if (!el) return;

  if (!students.length) { showEmpty(containerId, 'No students match your search.'); return; }

  el.innerHTML = students.map(s => {
    const statusClass = STATUS_CLASS[s.status] || 'badge-info';
    const cgpaColor   = CGPA_COLOR(s.cgpa);
    const attColor    = s.attendance >= 85 ? '#16a34a' : s.attendance >= 75 ? '#d97706' : '#dc2626';

    return `
      <article class="card student-card" data-id="${s.id}"
               tabindex="0" role="article"
               aria-label="${escapeHTML(s.name)} student card">
        <div class="card-header student-card-header">
          <div class="avatar-circle" aria-hidden="true"
               style="background:${cgpaColor}">
            ${escapeHTML(s.avatar)}
          </div>
          <div>
            <h3 class="card-title" style="margin:0">${highlight(s.name, query)}</h3>
            <p class="card-meta" style="margin:0">
              ${highlight(s.rollNo, query)}
            </p>
          </div>
          <span class="badge ${statusClass}" style="margin-left:auto">
            ${escapeHTML(s.status)}
          </span>
        </div>
        <div class="card-body student-card-body">
          <div class="student-info-grid">
            <div><span class="info-label">Course</span>
                 <span class="info-val">${highlight(s.course, query)}</span></div>
            <div><span class="info-label">Year / Sem</span>
                 <span class="info-val">Year ${s.year} / Sem ${s.semester}</span></div>
            <div><span class="info-label">City</span>
                 <span class="info-val">${highlight(s.city, query)}</span></div>
            <div><span class="info-label">State</span>
                 <span class="info-val">${highlight(s.state, query)}</span></div>
          </div>
          <div class="student-stats">
            <div class="stat-pill" style="border-color:${cgpaColor}">
              <span class="stat-num" style="color:${cgpaColor}">${s.cgpa}</span>
              <span class="stat-lbl">CGPA</span>
            </div>
            <div class="stat-pill" style="border-color:${attColor}">
              <span class="stat-num" style="color:${attColor}">${s.attendance}%</span>
              <span class="stat-lbl">Attendance</span>
            </div>
          </div>
        </div>
        <div class="card-footer">
          <a href="mailto:${escapeHTML(s.email)}" class="student-email"
             aria-label="Email ${escapeHTML(s.name)}">
            ✉ ${highlight(s.email, query)}
          </a>
        </div>
      </article>`;
  }).join('');
}

/* ======================================================
   FAQ RENDERER
   ====================================================== */

/**
 * Render FAQ accordion items.
 * @param {string} containerId
 * @param {Array}  faqs
 * @param {string} [query]
 */
export function renderFAQs(containerId, faqs, query = '') {
  const el = document.getElementById(containerId);
  if (!el) return;

  if (!faqs.length) { showEmpty(containerId, 'No FAQs match your search.'); return; }

  el.innerHTML = `<dl class="faq-list">` + faqs.map((f, i) => {
    const tags = (f.tags || []).map(t =>
      `<span class="tag">${escapeHTML(t)}</span>`).join('');
    return `
      <div class="faq-item" data-id="${f.id}">
        <dt>
          <button class="faq-btn" id="faq-btn-${f.id}"
                  aria-expanded="false"
                  aria-controls="faq-ans-${f.id}">
            <span class="faq-q">${highlight(f.question, query)}</span>
            <span class="faq-meta">
              <span class="badge badge-info">${escapeHTML(f.category)}</span>
              <span class="faq-icon" aria-hidden="true">＋</span>
            </span>
          </button>
        </dt>
        <dd class="faq-answer" id="faq-ans-${f.id}"
            role="region" aria-labelledby="faq-btn-${f.id}">
          <p>${highlight(f.answer, query)}</p>
          <div class="faq-footer">
            <div class="tag-list">${tags}</div>
            <span class="helpful-count">👍 ${f.helpful} found this helpful</span>
          </div>
        </dd>
      </div>`;
  }).join('') + `</dl>`;

  // Attach accordion behaviour
  el.querySelectorAll('.faq-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      // Close all
      el.querySelectorAll('.faq-btn').forEach(b => {
        b.setAttribute('aria-expanded', 'false');
        const ans = document.getElementById(b.getAttribute('aria-controls'));
        if (ans) ans.classList.remove('open');
      });
      // Toggle clicked
      if (!expanded) {
        btn.setAttribute('aria-expanded', 'true');
        const ans = document.getElementById(btn.getAttribute('aria-controls'));
        if (ans) ans.classList.add('open');
      }
    });

    // Arrow key navigation
    btn.addEventListener('keydown', e => {
      const btns = [...el.querySelectorAll('.faq-btn')];
      const idx  = btns.indexOf(btn);
      if (e.key === 'ArrowDown') { e.preventDefault(); btns[Math.min(idx+1, btns.length-1)].focus(); }
      if (e.key === 'ArrowUp')   { e.preventDefault(); btns[Math.max(idx-1, 0)].focus(); }
    });
  });
}

/* ======================================================
   STATS STRIP RENDERER
   ====================================================== */

/**
 * Render a summary stats strip.
 * @param {string} containerId
 * @param {Array}  stats  [{label, value, icon}]
 */
export function renderStats(containerId, stats) {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.innerHTML = stats.map(s => `
    <div class="stat-strip-item">
      <span class="stat-strip-icon" aria-hidden="true">${s.icon}</span>
      <span class="stat-strip-val">${escapeHTML(String(s.value))}</span>
      <span class="stat-strip-lbl">${escapeHTML(s.label)}</span>
    </div>`).join('');
}

/* ======================================================
   FILTER DROPDOWN BUILDER
   ====================================================== */

/**
 * Populate a <select> with options from an array of values.
 * @param {string} selectId
 * @param {Array}  values
 * @param {string} allLabel  label for the "show all" option
 */
export function populateSelect(selectId, values, allLabel = 'All') {
  const el = document.getElementById(selectId);
  if (!el) return;
  const current = el.value;
  el.innerHTML = `<option value="all">${escapeHTML(allLabel)}</option>` +
    values.map(v => `<option value="${escapeHTML(v)}"
      ${v === current ? 'selected' : ''}>${escapeHTML(v)}</option>`).join('');
}
