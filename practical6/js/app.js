/**
 * app.js – Practical 6  (Entry Point)
 * Responsible for:
 *   - Initialising all three tabs (Events / Students / FAQs)
 *   - Wiring up all UI controls to the data pipeline
 *   - Delegating data fetch → api.js
 *   - Delegating processing → search.js
 *   - Delegating rendering → render.js
 *   - localStorage cache usage via api.js (Advanced Extension)
 */

'use strict';

import { fetchJSON, clearAllCache } from './api.js';
import {
  processEvents,
  processStudents,
  processFAQs,
  getCitiesForState,
  getStates,
} from './search.js';
import {
  showLoading, showError,
  renderEvents, renderStudents, renderFAQs,
  renderPagination, renderResultsInfo,
  renderStats,
  populateSelect,
} from './render.js';
import {
  uniqueValues, average, countWhere, debounce, formatDate,
} from './util.js';

/* ======================================================
   CONFIGURATION
   ====================================================== */
const JSON_BASE   = './json/';
const PAGE_SIZE   = 6;

/* ======================================================
   GLOBAL DATA STORE
   ====================================================== */
const store = {
  events:   [],
  students: [],
  faqs:     [],
};

/* ======================================================
   PER-TAB STATE
   ====================================================== */
const eventsState = {
  query: '', category: 'all', status: 'all', fee: 'all',
  sortField: 'date', sortDir: 'asc',
  page: 1, pageSize: PAGE_SIZE,
};

const studentsState = {
  query: '', course: 'all', year: 'all', status: 'all',
  state: 'all', city: 'all',
  cgpaMin: null, cgpaMax: null,
  sortField: 'name', sortDir: 'asc',
  page: 1, pageSize: PAGE_SIZE,
};

const faqsState = {
  query: '', category: 'all',
  sortField: 'id', sortDir: 'asc',
  page: 1, pageSize: 8,
};

/* ======================================================
   TAB SWITCHING
   ====================================================== */
function initTabs() {
  const tabs    = document.querySelectorAll('.tab-btn');
  const panels  = document.querySelectorAll('.tab-panel');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t  => { t.classList.remove('active'); t.setAttribute('aria-selected','false'); });
      panels.forEach(p => p.classList.remove('active'));

      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');
      const target = document.getElementById(tab.dataset.tab);
      if (target) target.classList.add('active');
    });
  });
}

/* ======================================================
   EVENTS TAB
   ====================================================== */
async function initEvents() {
  showLoading('events-grid');

  try {
    store.events = await fetchJSON(`${JSON_BASE}events.json`, 'events');
  } catch (err) {
    showError('events-grid', err.message);
    return;
  }

  // Populate filter dropdowns
  populateSelect('ev-filter-category', uniqueValues(store.events, 'category'), 'All Categories');
  populateSelect('ev-filter-status',   ['Open','Almost Full','Full'],           'All Statuses');
  populateSelect('ev-sort',            ['date','title','seats','fee'],          'Sort by…');

  // Render stats strip
  renderStats('events-stats', [
    { icon: '📅', value: store.events.length,                              label: 'Total Events' },
    { icon: '🟢', value: countWhere(store.events, 'status', 'Open'),       label: 'Open' },
    { icon: '🔴', value: countWhere(store.events, 'status', 'Full'),       label: 'Full' },
    { icon: '🆓', value: store.events.filter(e => e.fee === 0).length,     label: 'Free Entry' },
  ]);

  renderEvents_();

  // Wire search (debounced)
  const searchEl = document.getElementById('ev-search');
  if (searchEl) {
    searchEl.addEventListener('input', debounce(e => {
      eventsState.query = e.target.value;
      eventsState.page  = 1;
      renderEvents_();
    }, 300));
  }

  // Wire filters
  ['ev-filter-category','ev-filter-status','ev-filter-fee'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('change', () => {
      if (id === 'ev-filter-category') eventsState.category = el.value;
      if (id === 'ev-filter-status')   eventsState.status   = el.value;
      if (id === 'ev-filter-fee')      eventsState.fee      = el.value;
      eventsState.page = 1;
      renderEvents_();
    });
  });

  // Wire sort
  const sortEl    = document.getElementById('ev-sort');
  const sortDirEl = document.getElementById('ev-sort-dir');
  if (sortEl) sortEl.addEventListener('change', () => {
    eventsState.sortField = sortEl.value;
    eventsState.page = 1;
    renderEvents_();
  });
  if (sortDirEl) sortDirEl.addEventListener('change', () => {
    eventsState.sortDir = sortDirEl.value;
    eventsState.page = 1;
    renderEvents_();
  });
}

function renderEvents_() {
  const result = processEvents(store.events, eventsState);
  renderEvents('events-grid', result.items, eventsState.query);
  renderResultsInfo('events-info', result.items.length, result.totalItems);
  renderPagination('events-pagination', result.currentPage, result.totalPages, p => {
    eventsState.page = p;
    renderEvents_();
    document.getElementById('events-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

/* ======================================================
   STUDENTS TAB
   ====================================================== */
async function initStudents() {
  showLoading('students-grid');

  try {
    store.students = await fetchJSON(`${JSON_BASE}students.json`, 'students');
  } catch (err) {
    showError('students-grid', err.message);
    return;
  }

  // Populate dropdowns
  populateSelect('st-filter-course',  uniqueValues(store.students, 'course'), 'All Courses');
  populateSelect('st-filter-year',    uniqueValues(store.students, 'year').map(String), 'All Years');
  populateSelect('st-filter-status',  uniqueValues(store.students, 'status'), 'All Statuses');
  populateSelect('st-filter-state',   getStates(store.students),              'All States');
  populateSelect('st-filter-city',    getCitiesForState(store.students, ''),  'All Cities');

  // Stats
  const active  = countWhere(store.students, 'status', 'Active');
  const warning = countWhere(store.students, 'status', 'Warning');
  renderStats('students-stats', [
    { icon: '🎓', value: store.students.length,              label: 'Total Students' },
    { icon: '✅', value: active,                             label: 'Active' },
    { icon: '⚠️', value: warning,                            label: 'Low Attendance' },
    { icon: '📊', value: average(store.students, 'cgpa'),    label: 'Avg CGPA' },
    { icon: '🗓', value: average(store.students, 'attendance') + '%', label: 'Avg Attendance' },
  ]);

  renderStudents_();

  // Search
  const searchEl = document.getElementById('st-search');
  if (searchEl) {
    searchEl.addEventListener('input', debounce(e => {
      studentsState.query = e.target.value;
      studentsState.page  = 1;
      renderStudents_();
    }, 300));
  }

  // Filters
  const filterMap = {
    'st-filter-course': 'course',
    'st-filter-year':   'year',
    'st-filter-status': 'status',
  };
  Object.entries(filterMap).forEach(([id, field]) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('change', () => {
      studentsState[field] = el.value;
      studentsState.page   = 1;
      renderStudents_();
    });
  });

  // Dependent dropdowns: State → City  (Intermediate Extension)
  const stateEl = document.getElementById('st-filter-state');
  const cityEl  = document.getElementById('st-filter-city');
  if (stateEl) {
    stateEl.addEventListener('change', () => {
      studentsState.state = stateEl.value;
      studentsState.city  = 'all';
      studentsState.page  = 1;
      // Repopulate city dropdown based on selected state
      const cities = getCitiesForState(store.students, stateEl.value === 'all' ? '' : stateEl.value);
      populateSelect('st-filter-city', cities, 'All Cities');
      renderStudents_();
    });
  }
  if (cityEl) {
    cityEl.addEventListener('change', () => {
      studentsState.city = cityEl.value;
      studentsState.page = 1;
      renderStudents_();
    });
  }

  // CGPA range
  const cgpaMinEl = document.getElementById('st-cgpa-min');
  const cgpaMaxEl = document.getElementById('st-cgpa-max');
  [cgpaMinEl, cgpaMaxEl].forEach(el => {
    if (!el) return;
    el.addEventListener('input', debounce(() => {
      studentsState.cgpaMin = cgpaMinEl?.value ? parseFloat(cgpaMinEl.value) : null;
      studentsState.cgpaMax = cgpaMaxEl?.value ? parseFloat(cgpaMaxEl.value) : null;
      studentsState.page    = 1;
      renderStudents_();
    }, 400));
  });

  // Sort
  const sortEl    = document.getElementById('st-sort');
  const sortDirEl = document.getElementById('st-sort-dir');
  if (sortEl) sortEl.addEventListener('change', () => {
    studentsState.sortField = sortEl.value;
    studentsState.page = 1;
    renderStudents_();
  });
  if (sortDirEl) sortDirEl.addEventListener('change', () => {
    studentsState.sortDir = sortDirEl.value;
    studentsState.page = 1;
    renderStudents_();
  });
}

function renderStudents_() {
  const result = processStudents(store.students, studentsState);
  renderStudents('students-grid', result.items, studentsState.query);
  renderResultsInfo('students-info', result.items.length, result.totalItems);
  renderPagination('students-pagination', result.currentPage, result.totalPages, p => {
    studentsState.page = p;
    renderStudents_();
    document.getElementById('students-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

/* ======================================================
   FAQs TAB
   ====================================================== */
async function initFAQs() {
  showLoading('faqs-list');

  try {
    store.faqs = await fetchJSON(`${JSON_BASE}faqs.json`, 'faqs');
  } catch (err) {
    showError('faqs-list', err.message);
    return;
  }

  populateSelect('fq-filter-category', uniqueValues(store.faqs, 'category'), 'All Categories');

  renderStats('faqs-stats', [
    { icon: '❓', value: store.faqs.length,                             label: 'Total FAQs' },
    { icon: '📚', value: uniqueValues(store.faqs,'category').length,    label: 'Categories' },
    { icon: '👍', value: Math.max(...store.faqs.map(f => f.helpful)),   label: 'Most Helpful' },
  ]);

  renderFAQs_();

  const searchEl = document.getElementById('fq-search');
  if (searchEl) {
    searchEl.addEventListener('input', debounce(e => {
      faqsState.query = e.target.value;
      faqsState.page  = 1;
      renderFAQs_();
    }, 300));
  }

  const catEl = document.getElementById('fq-filter-category');
  if (catEl) {
    catEl.addEventListener('change', () => {
      faqsState.category = catEl.value;
      faqsState.page     = 1;
      renderFAQs_();
    });
  }

  const sortEl    = document.getElementById('fq-sort');
  const sortDirEl = document.getElementById('fq-sort-dir');
  if (sortEl) sortEl.addEventListener('change', () => {
    faqsState.sortField = sortEl.value;
    faqsState.page = 1;
    renderFAQs_();
  });
  if (sortDirEl) sortDirEl.addEventListener('change', () => {
    faqsState.sortDir = sortDirEl.value;
    faqsState.page = 1;
    renderFAQs_();
  });
}

function renderFAQs_() {
  const result = processFAQs(store.faqs, faqsState);
  renderFAQs('faqs-list', result.items, faqsState.query);
  renderResultsInfo('faqs-info', result.items.length, result.totalItems);
  renderPagination('faqs-pagination', result.currentPage, result.totalPages, p => {
    faqsState.page = p;
    renderFAQs_();
    document.getElementById('faqs-list')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

/* ======================================================
   CACHE CLEAR BUTTON
   ====================================================== */
function initCacheControls() {
  const btn = document.getElementById('clear-cache-btn');
  if (!btn) return;
  btn.addEventListener('click', () => {
    clearAllCache();
    btn.textContent = '✅ Cache Cleared!';
    setTimeout(() => { btn.textContent = '🗑 Clear Cache'; }, 2000);
  });
}

/* ======================================================
   BOOT
   ====================================================== */
document.addEventListener('DOMContentLoaded', () => {
  initTabs();
  initCacheControls();
  // Load all tabs in parallel — data is cached so switching is instant
  Promise.all([initEvents(), initStudents(), initFAQs()])
    .then(() => console.info('[App] All tabs loaded.'))
    .catch(err => console.error('[App] Boot error:', err));
});
