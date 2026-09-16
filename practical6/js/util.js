/**
 * util.js – Practical 6
 * Pure helper / utility functions used across modules.
 * No DOM access — fully testable in isolation.
 */

'use strict';

/* ======================================================
   SEARCH
   ====================================================== */

/**
 * Filter an array of objects by a search query.
 * Checks all string/number values recursively.
 * @param {Array}  data
 * @param {string} query
 * @returns {Array}
 */
export function filterBySearch(data, query) {
  if (!query || !query.trim()) return data;
  const q = query.trim().toLowerCase();
  return data.filter(item => matchesQuery(item, q));
}

function matchesQuery(item, q) {
  return Object.values(item).some(val => {
    if (Array.isArray(val))  return val.some(v => String(v).toLowerCase().includes(q));
    if (typeof val === 'object' && val !== null) return matchesQuery(val, q);
    return String(val).toLowerCase().includes(q);
  });
}

/* ======================================================
   FILTER (by a specific field value)
   ====================================================== */

/**
 * Filter array where item[field] === value.
 * Pass '' or 'all' to skip filtering.
 * @param {Array}  data
 * @param {string} field
 * @param {string} value
 * @returns {Array}
 */
export function filterByField(data, field, value) {
  if (!value || value === 'all' || value === '') return data;
  return data.filter(item =>
    String(item[field]).toLowerCase() === value.toLowerCase()
  );
}

/**
 * Filter array where item[field] >= min AND item[field] <= max.
 * Pass null to skip either bound.
 * @param {Array}  data
 * @param {string} field
 * @param {number|null} min
 * @param {number|null} max
 * @returns {Array}
 */
export function filterByRange(data, field, min, max) {
  return data.filter(item => {
    const val = Number(item[field]);
    if (min !== null && val < min) return false;
    if (max !== null && val > max) return false;
    return true;
  });
}

/* ======================================================
   SORT
   ====================================================== */

/**
 * Sort array by a given field.
 * @param {Array}   data
 * @param {string}  field
 * @param {'asc'|'desc'} direction
 * @returns {Array}   new sorted array (does not mutate original)
 */
export function sortBy(data, field, direction = 'asc') {
  if (!field) return data;
  return [...data].sort((a, b) => {
    let va = a[field];
    let vb = b[field];

    // Date comparison
    if (isDateString(va) && isDateString(vb)) {
      va = new Date(va).getTime();
      vb = new Date(vb).getTime();
    }

    // Numeric comparison
    if (typeof va === 'number' && typeof vb === 'number') {
      return direction === 'asc' ? va - vb : vb - va;
    }

    // String comparison
    return direction === 'asc'
      ? String(va).localeCompare(String(vb))
      : String(vb).localeCompare(String(va));
  });
}

function isDateString(val) {
  return typeof val === 'string' && /^\d{4}-\d{2}-\d{2}/.test(val);
}

/* ======================================================
   PAGINATION
   ====================================================== */

/**
 * Slice data for a given page.
 * @param {Array}  data
 * @param {number} page      1-indexed
 * @param {number} pageSize
 * @returns {{ items: Array, totalPages: number, totalItems: number }}
 */
export function paginate(data, page, pageSize) {
  const totalItems = data.length;
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  const safePage   = Math.min(Math.max(1, page), totalPages);
  const start      = (safePage - 1) * pageSize;
  const items      = data.slice(start, start + pageSize);
  return { items, totalPages, totalItems, currentPage: safePage };
}

/* ======================================================
   UNIQUE VALUES (for populating filter dropdowns)
   ====================================================== */

/**
 * Extract unique values of a field from an array.
 * @param {Array}  data
 * @param {string} field
 * @returns {Array}
 */
export function uniqueValues(data, field) {
  return [...new Set(data.map(item => item[field]).filter(Boolean))].sort();
}

/* ======================================================
   DATE FORMATTING
   ====================================================== */

/**
 * Format an ISO date string as a human-readable date.
 * @param {string} isoDate  e.g. "2026-09-10"
 * @returns {string}        e.g. "10 Sep 2026"
 */
export function formatDate(isoDate) {
  if (!isoDate) return '—';
  const d = new Date(isoDate);
  if (isNaN(d)) return isoDate;
  return d.toLocaleDateString('en-IN', {
    day: '2-digit', month: 'short', year: 'numeric'
  });
}

/* ======================================================
   DEBOUNCE  (for search input)
   ====================================================== */

/**
 * Debounce a function call.
 * @param {Function} fn
 * @param {number}   delay  ms
 * @returns {Function}
 */
export function debounce(fn, delay = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

/* ======================================================
   HIGHLIGHT SEARCH TERM in text
   ====================================================== */

/**
 * Wrap occurrences of query in <mark> tags.
 * @param {string} text
 * @param {string} query
 * @returns {string} HTML string
 */
export function highlight(text, query) {
  if (!query || !query.trim()) return escapeHTML(text);
  const escaped = escapeHTML(text);
  const pattern = new RegExp(`(${escapeRegex(query.trim())})`, 'gi');
  return escaped.replace(pattern, '<mark>$1</mark>');
}

export function escapeHTML(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function escapeRegex(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/* ======================================================
   STATS  (aggregate helpers)
   ====================================================== */

/**
 * Calculate average of a numeric field.
 * @param {Array}  data
 * @param {string} field
 * @returns {string}  rounded to 1 decimal place
 */
export function average(data, field) {
  if (!data.length) return '0';
  const sum = data.reduce((acc, item) => acc + Number(item[field] || 0), 0);
  return (sum / data.length).toFixed(1);
}

/**
 * Count records where item[field] === value.
 */
export function countWhere(data, field, value) {
  return data.filter(item =>
    String(item[field]).toLowerCase() === String(value).toLowerCase()
  ).length;
}
