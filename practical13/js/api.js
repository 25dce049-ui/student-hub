/**
 * Practical 13 – PHP JSON API & AJAX/Fetch CRUD
 * js/api.js  –  Centralised Fetch API wrapper
 *
 * Advanced Extension: centralised error handling + proper HTTP status codes
 */

const BASE_URL = 'api/students.php';

/**
 * Core fetch wrapper – always returns parsed JSON or throws.
 * @param {string} url
 * @param {RequestInit} options
 * @returns {Promise<object>}
 */
async function apiFetch(url, options = {}) {
  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json', ...options.headers },
    ...options,
  });

  let json;
  try { json = await res.json(); }
  catch { throw new Error(`Non-JSON response (HTTP ${res.status})`); }

  if (!json.success) {
    const err = new Error(json.message || 'API error');
    err.status = res.status;
    err.errors = json.data?.errors ?? null;
    throw err;
  }
  return json;
}

// ── Public API methods ────────────────────────────────────────

export const StudentAPI = {
  /** List students. Supports { search, course, status, page, limit } */
  list(params = {}) {
    const qs = new URLSearchParams(
      Object.entries(params).filter(([, v]) => v !== '' && v !== undefined)
    ).toString();
    return apiFetch(`${BASE_URL}${qs ? '?' + qs : ''}`);
  },

  /** Get single student by id */
  get(id) {
    return apiFetch(`${BASE_URL}?id=${id}`);
  },

  /** Create student */
  create(payload) {
    return apiFetch(BASE_URL, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  /** Update student */
  update(id, payload) {
    return apiFetch(`${BASE_URL}?id=${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
  },

  /** Delete student */
  delete(id) {
    return apiFetch(`${BASE_URL}?id=${id}`, { method: 'DELETE' });
  },
};
