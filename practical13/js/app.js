/**
 * Practical 13 – PHP JSON API & AJAX/Fetch CRUD
 * js/app.js  –  Main application logic (no page reload)
 *
 * Features:
 *  ✅ List with pagination
 *  ✅ AJAX search with debounce (Intermediate Extension)
 *  ✅ Add / Edit (shared modal form)
 *  ✅ Delete with confirmation modal
 *  ✅ Loading, success, and error toast notifications
 *  ✅ Centralised error handling
 */

import { StudentAPI } from './api.js';

// ── State ─────────────────────────────────────────────────────
const state = {
  page: 1,
  limit: 8,
  search: '',
  course: '',
  status: '',
  editingId: null,
  debounceTimer: null,
};

// ── DOM refs ─────────────────────────────────────────────────
const $ = id => document.getElementById(id);

const tableBody   = $('tableBody');
const pagination  = $('pagination');
const statsTotal  = $('statsTotal');
const statsActive = $('statsActive');
const statsInact  = $('statsInact');

const searchInput  = $('searchInput');
const courseFilter = $('courseFilter');
const statusFilter = $('statusFilter');

const formModal    = $('formModal');
const formTitle    = $('formTitle');
const studentForm  = $('studentForm');
const formError    = $('formError');
const saveBtn      = $('saveBtn');

const delModal   = $('delModal');
const delName    = $('delName');
const confirmDel = $('confirmDel');

const loadingOverlay = $('loadingOverlay');
const toastEl        = $('toast');

let pendingDeleteId = null;
let toastTimer      = null;

// ── Boot ──────────────────────────────────────────────────────
loadStudents();
loadStats();

// ── Event listeners ───────────────────────────────────────────

// Search with debounce (Intermediate Extension)
searchInput.addEventListener('input', () => {
  clearTimeout(state.debounceTimer);
  state.debounceTimer = setTimeout(() => {
    state.search = searchInput.value.trim();
    state.page   = 1;
    loadStudents();
  }, 350);
});

courseFilter.addEventListener('change', () => {
  state.course = courseFilter.value;
  state.page   = 1;
  loadStudents();
});

statusFilter.addEventListener('change', () => {
  state.status = statusFilter.value;
  state.page   = 1;
  loadStudents();
});

$('clearBtn').addEventListener('click', () => {
  searchInput.value   = '';
  courseFilter.value  = '';
  statusFilter.value  = '';
  state.search = state.course = state.status = '';
  state.page = 1;
  loadStudents();
});

$('addBtn').addEventListener('click', openAddModal);

studentForm.addEventListener('submit', handleFormSubmit);

// Modal close buttons
document.querySelectorAll('[data-close-modal]').forEach(btn =>
  btn.addEventListener('click', () => closeModal(btn.dataset.closeModal))
);

// Close modals on overlay click
[formModal, delModal].forEach(modal => {
  modal.addEventListener('click', e => {
    if (e.target === modal) closeModal(modal.id);
  });
});

// Escape key
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeModal('formModal');
    closeModal('delModal');
  }
});

confirmDel.addEventListener('click', handleDelete);

// ── Data loaders ──────────────────────────────────────────────

async function loadStudents() {
  showLoading(true);
  try {
    const res = await StudentAPI.list({
      search: state.search,
      course: state.course,
      status: state.status,
      page:   state.page,
      limit:  state.limit,
    });
    renderTable(res.data.students);
    renderPagination(res.data.totalPages, res.data.total);
  } catch (err) {
    showToast('❌ ' + err.message, 'error');
    tableBody.innerHTML = `<tr><td colspan="7" class="empty-cell">Failed to load data.</td></tr>`;
  } finally {
    showLoading(false);
  }
}

async function loadStats() {
  try {
    const all      = await StudentAPI.list({ limit: 1000 });
    const active   = await StudentAPI.list({ status: 'active',   limit: 1 });
    const inactive = await StudentAPI.list({ status: 'inactive', limit: 1 });
    statsTotal.textContent  = all.data.total;
    statsActive.textContent = active.data.total;
    statsInact.textContent  = inactive.data.total;
  } catch (_) { /* stats are non-critical */ }
}

// ── Render ────────────────────────────────────────────────────

function renderTable(students) {
  if (!students.length) {
    tableBody.innerHTML = `<tr><td colspan="7" class="empty-cell">
      No students found. <button class="link-btn" onclick="document.getElementById('addBtn').click()">Add one?</button>
    </td></tr>`;
    return;
  }

  const courseBadge = {
    'Computer Science':        'badge-blue',
    'Information Technology':  'badge-purple',
    'Electronics':             'badge-yellow',
    'Mechanical Engineering':  'badge-gray',
    'Civil Engineering':       'badge-green',
  };

  tableBody.innerHTML = students.map((s, i) => `
    <tr data-id="${s.id}">
      <td class="muted">${(state.page - 1) * state.limit + i + 1}</td>
      <td><strong>${esc(s.name)}</strong></td>
      <td>${esc(s.email)}</td>
      <td>${esc(s.phone || '—')}</td>
      <td><span class="badge ${courseBadge[s.course] ?? 'badge-gray'}">${esc(s.course)}</span></td>
      <td><span class="badge ${s.status === 'active' ? 'badge-green' : 'badge-red'}">${cap(s.status)}</span></td>
      <td>
        <div class="action-group">
          <button class="btn btn-warning btn-sm" onclick="editStudent(${s.id})">✏️ Edit</button>
          <button class="btn btn-danger btn-sm"  onclick="deleteStudent(${s.id},'${esc(s.name).replace(/'/g,"\\'")}')">🗑️ Delete</button>
        </div>
      </td>
    </tr>`).join('');
}

function renderPagination(totalPages, total) {
  if (totalPages <= 1) { pagination.innerHTML = ''; return; }
  const p = state.page;
  let html = `<span class="pg-info">Page ${p} / ${totalPages} &nbsp;(${total} records)</span>`;

  if (p > 1)          html += `<button class="pg-btn" onclick="goPage(${p-1})">‹ Prev</button>`;
  for (let x = Math.max(1,p-2); x <= Math.min(totalPages,p+2); x++)
    html += `<button class="pg-btn ${x===p?'active':''}" onclick="goPage(${x})">${x}</button>`;
  if (p < totalPages) html += `<button class="pg-btn" onclick="goPage(${p+1})">Next ›</button>`;

  pagination.innerHTML = html;
}

// ── CRUD handlers ─────────────────────────────────────────────

function openAddModal() {
  state.editingId = null;
  formTitle.textContent = '➕ Add Student';
  studentForm.reset();
  hideFormError();
  saveBtn.textContent = '💾 Save Student';
  openModal('formModal');
}

window.editStudent = async function(id) {
  try {
    showLoading(true);
    const res = await StudentAPI.get(id);
    const s   = res.data.student;
    state.editingId = id;
    formTitle.textContent = '✏️ Edit Student';
    saveBtn.textContent   = '💾 Update Student';
    studentForm.name.value   = s.name;
    studentForm.email.value  = s.email;
    studentForm.phone.value  = s.phone;
    studentForm.course.value = s.course;
    studentForm.status.value = s.status;
    hideFormError();
    openModal('formModal');
  } catch (err) {
    showToast('❌ ' + err.message, 'error');
  } finally {
    showLoading(false);
  }
};

async function handleFormSubmit(e) {
  e.preventDefault();
  hideFormError();

  const payload = {
    name:   studentForm.name.value.trim(),
    email:  studentForm.email.value.trim(),
    phone:  studentForm.phone.value.trim(),
    course: studentForm.course.value,
    status: studentForm.status.value,
  };

  saveBtn.disabled    = true;
  saveBtn.textContent = '⏳ Saving…';

  try {
    if (state.editingId) {
      await StudentAPI.update(state.editingId, payload);
      showToast('✅ Student updated!', 'success');
    } else {
      await StudentAPI.create(payload);
      showToast('✅ Student added!', 'success');
    }
    closeModal('formModal');
    loadStudents();
    loadStats();
  } catch (err) {
    if (err.errors) {
      showFormError(Object.values(err.errors).join('<br>'));
    } else {
      showFormError(err.message);
    }
  } finally {
    saveBtn.disabled    = false;
    saveBtn.textContent = state.editingId ? '💾 Update Student' : '💾 Save Student';
  }
}

window.deleteStudent = function(id, name) {
  pendingDeleteId  = id;
  delName.textContent = name;
  openModal('delModal');
};

async function handleDelete() {
  if (!pendingDeleteId) return;
  confirmDel.disabled    = true;
  confirmDel.textContent = '⏳ Deleting…';
  try {
    await StudentAPI.delete(pendingDeleteId);
    showToast('🗑️ Student deleted.', 'success');
    closeModal('delModal');
    loadStudents();
    loadStats();
  } catch (err) {
    showToast('❌ ' + err.message, 'error');
    closeModal('delModal');
  } finally {
    confirmDel.disabled    = false;
    confirmDel.textContent = '🗑️ Yes, Delete';
    pendingDeleteId = null;
  }
}

// ── Pagination ────────────────────────────────────────────────
window.goPage = function(p) {
  state.page = p;
  loadStudents();
};

// ── UI helpers ────────────────────────────────────────────────
function openModal(id)  { $(id).classList.add('open'); }
function closeModal(id) { if (id) $(id).classList.remove('open'); }

function showLoading(on)  { loadingOverlay.style.display = on ? 'flex' : 'none'; }

function showToast(msg, type = 'success') {
  clearTimeout(toastTimer);
  toastEl.className  = `toast toast-${type} show`;
  toastEl.innerHTML  = msg;
  toastTimer = setTimeout(() => toastEl.classList.remove('show'), 3500);
}

function showFormError(msg) {
  formError.innerHTML = msg;
  formError.style.display = 'block';
}
function hideFormError() {
  formError.innerHTML = '';
  formError.style.display = 'none';
}

function esc(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function cap(s) { return s ? s[0].toUpperCase() + s.slice(1) : ''; }
