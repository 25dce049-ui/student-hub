/**
 * search.js – Practical 6
 * Responsible for: Reading UI controls (search input, filter dropdowns,
 * sort select) and returning a processed + paginated slice of data.
 *
 * Acts as a bridge between util.js (pure functions) and app.js (event wiring).
 */

'use strict';

import {
  filterBySearch,
  filterByField,
  filterByRange,
  sortBy,
  paginate,
} from './util.js';

/* ======================================================
   EVENTS PIPELINE
   ====================================================== */

/**
 * Apply all active filters/sort/pagination to the events dataset.
 * @param {Array}  allEvents    - raw full dataset
 * @param {Object} state        - current UI state
 * @returns {{ items, totalPages, totalItems, currentPage }}
 */
export function processEvents(allEvents, state) {
  let data = allEvents;

  // 1. Search
  data = filterBySearch(data, state.query);

  // 2. Filter by category
  data = filterByField(data, 'category', state.category);

  // 3. Filter by status
  data = filterByField(data, 'status', state.status);

  // 4. Filter by fee (free / paid)
  if (state.fee === 'free') {
    data = data.filter(e => e.fee === 0);
  } else if (state.fee === 'paid') {
    data = data.filter(e => e.fee > 0);
  }

  // 5. Sort
  data = sortBy(data, state.sortField, state.sortDir);

  // 6. Paginate
  return paginate(data, state.page, state.pageSize);
}

/* ======================================================
   STUDENTS PIPELINE
   ====================================================== */

/**
 * Apply all active filters/sort/pagination to the students dataset.
 * @param {Array}  allStudents
 * @param {Object} state
 * @returns {{ items, totalPages, totalItems, currentPage }}
 */
export function processStudents(allStudents, state) {
  let data = allStudents;

  // 1. Search
  data = filterBySearch(data, state.query);

  // 2. Filter by course
  data = filterByField(data, 'course', state.course);

  // 3. Filter by year
  data = filterByField(data, 'year', state.year);

  // 4. Filter by status
  data = filterByField(data, 'status', state.status);

  // 5. Filter by state (Intermediate: dependent dropdown)
  data = filterByField(data, 'state', state.state);

  // 6. Filter by city (depends on state selection)
  data = filterByField(data, 'city', state.city);

  // 7. CGPA range filter
  if (state.cgpaMin !== null || state.cgpaMax !== null) {
    data = filterByRange(data, 'cgpa', state.cgpaMin, state.cgpaMax);
  }

  // 8. Sort
  data = sortBy(data, state.sortField, state.sortDir);

  // 9. Paginate
  return paginate(data, state.page, state.pageSize);
}

/* ======================================================
   FAQS PIPELINE
   ====================================================== */

/**
 * Apply all active filters/sort/pagination to the FAQs dataset.
 * @param {Array}  allFAQs
 * @param {Object} state
 * @returns {{ items, totalPages, totalItems, currentPage }}
 */
export function processFAQs(allFAQs, state) {
  let data = allFAQs;

  // 1. Search
  data = filterBySearch(data, state.query);

  // 2. Filter by category
  data = filterByField(data, 'category', state.category);

  // 3. Sort (e.g. by helpful count)
  data = sortBy(data, state.sortField, state.sortDir);

  // 4. Paginate
  return paginate(data, state.page, state.pageSize);
}

/* ======================================================
   DEPENDENT DROPDOWN HELPER  (Intermediate Extension)
   Country → State → City cascade
   ====================================================== */

/**
 * Build state options filtered by selected country/region
 * (here: states of students filtered by a given state to get cities).
 *
 * @param {Array}  allStudents
 * @param {string} selectedState  - '' means all
 * @returns {string[]}  unique city names within that state
 */
export function getCitiesForState(allStudents, selectedState) {
  if (!selectedState || selectedState === 'all') {
    return [...new Set(allStudents.map(s => s.city).filter(Boolean))].sort();
  }
  return [
    ...new Set(
      allStudents
        .filter(s => s.state.toLowerCase() === selectedState.toLowerCase())
        .map(s => s.city)
        .filter(Boolean)
    )
  ].sort();
}

/**
 * Get unique states from the students dataset.
 * @param {Array} allStudents
 * @returns {string[]}
 */
export function getStates(allStudents) {
  return [...new Set(allStudents.map(s => s.state).filter(Boolean))].sort();
}
