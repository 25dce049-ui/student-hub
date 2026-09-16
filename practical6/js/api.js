/**
 * api.js – Practical 6
 * Responsible for: Fetch API calls, error handling,
 * localStorage caching (Advanced Extension)
 */

'use strict';

const CACHE_TTL = 5 * 60 * 1000; // 5 minutes in ms

/**
 * Fetch JSON from a local path.
 * Strategy:
 *  1. Check localStorage for a fresh cached copy (Advanced Extension).
 *  2. If stale / missing → fetch from network, update cache.
 *  3. If network fails and stale cache exists → return stale data with warning.
 *  4. If network fails and no cache → throw error.
 *
 * @param {string} url          - Path to the JSON file
 * @param {string} cacheKey     - Key used to store data in localStorage
 * @returns {Promise<Array>}    - Parsed JSON array
 */
export async function fetchJSON(url, cacheKey) {
  // ── 1. Check localStorage cache ──────────────────────────────────────
  const cached = readCache(cacheKey);
  if (cached && !isCacheStale(cacheKey)) {
    console.info(`[API] Cache hit: ${cacheKey}`);
    return cached;
  }

  // ── 2. Fetch from network ─────────────────────────────────────────────
  try {
    const response = await fetch(url);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();

    if (!Array.isArray(data)) {
      throw new Error('Expected a JSON array but received a different type.');
    }

    // Write fresh data to cache
    writeCache(cacheKey, data);
    console.info(`[API] Fetched & cached: ${cacheKey} (${data.length} records)`);
    return data;

  } catch (networkError) {
    // ── 3. Network failed – try stale cache ───────────────────────────
    const stale = readCache(cacheKey);
    if (stale) {
      console.warn(`[API] Network error. Serving stale cache for: ${cacheKey}`);
      return stale;
    }

    // ── 4. No data at all ─────────────────────────────────────────────
    throw new Error(
      `Failed to load data from "${url}". ${networkError.message}`
    );
  }
}

/**
 * Read cached data from localStorage.
 * @param {string} key
 * @returns {Array|null}
 */
function readCache(key) {
  try {
    const raw = localStorage.getItem(`p6_${key}`);
    if (!raw) return null;
    const { data } = JSON.parse(raw);
    return data ?? null;
  } catch {
    return null;
  }
}

/**
 * Write data to localStorage with a timestamp.
 * @param {string} key
 * @param {Array}  data
 */
function writeCache(key, data) {
  try {
    localStorage.setItem(`p6_${key}`, JSON.stringify({
      data,
      timestamp: Date.now()
    }));
  } catch (e) {
    console.warn('[API] localStorage write failed:', e.message);
  }
}

/**
 * Check whether the cached entry has exceeded TTL.
 * @param {string} key
 * @returns {boolean}
 */
function isCacheStale(key) {
  try {
    const raw = localStorage.getItem(`p6_${key}`);
    if (!raw) return true;
    const { timestamp } = JSON.parse(raw);
    return (Date.now() - timestamp) > CACHE_TTL;
  } catch {
    return true;
  }
}

/**
 * Manually invalidate a cache entry (force re-fetch next time).
 * @param {string} key
 */
export function clearCache(key) {
  localStorage.removeItem(`p6_${key}`);
}

/**
 * Invalidate all Practical 6 cache entries.
 */
export function clearAllCache() {
  Object.keys(localStorage)
    .filter(k => k.startsWith('p6_'))
    .forEach(k => localStorage.removeItem(k));
  console.info('[API] All caches cleared.');
}
