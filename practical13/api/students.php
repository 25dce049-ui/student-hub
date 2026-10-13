<?php
/**
 * Practical 13 – PHP JSON API & AJAX/Fetch CRUD
 * api/students.php  –  RESTful JSON API for students
 *
 * Routes:
 *   GET    /api/students.php               → list (with optional ?search=&course=&status=)
 *   GET    /api/students.php?id=1          → single student
 *   POST   /api/students.php               → create  { name, email, phone, course, status }
 *   PUT    /api/students.php?id=1          → update  { name, email, phone, course, status }
 *   DELETE /api/students.php?id=1          → delete
 *
 * Advanced Extension: proper HTTP status codes (200/201/400/404/409/500)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/response.php';

// CORS headers (allow local dev / browser fetch)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$db     = get_db();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? intval($_GET['id']) : null;

// ─────────────────────────────────────────────────────────────
// GET – list or single
// ─────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        // Single record
        $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) api_error('Student not found.', 404);
        api_ok('Student fetched.', ['student' => $row]);
    }

    // List with search / filter
    $search = trim($_GET['search'] ?? '');
    $course = trim($_GET['course'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = min(50, max(1, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    $where = []; $params = []; $types = '';

    if ($search !== '') {
        $where[] = '(name LIKE ? OR email LIKE ? OR course LIKE ?)';
        $like = '%'.$search.'%';
        $params = array_merge($params, [$like, $like, $like]);
        $types .= 'sss';
    }
    if ($course !== '') { $where[] = 'course = ?'; $params[] = $course; $types .= 's'; }
    if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; $types .= 's'; }

    $whereSQL = $where ? 'WHERE '.implode(' AND ', $where) : '';

    // Total count
    $cStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM students $whereSQL");
    if ($types) $cStmt->bind_param($types, ...$params);
    $cStmt->execute();
    $total = $cStmt->get_result()->fetch_assoc()['cnt'];
    $cStmt->close();

    // Paginated rows
    $lStmt = $db->prepare("SELECT * FROM students $whereSQL ORDER BY id DESC LIMIT ? OFFSET ?");
    $lStmt->bind_param($types.'ii', ...[...$params, $limit, $offset]);
    $lStmt->execute();
    $students = $lStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $lStmt->close();

    api_ok('Students fetched.', [
        'students'   => $students,
        'total'      => (int)$total,
        'page'       => $page,
        'limit'      => $limit,
        'totalPages' => (int)ceil($total / $limit),
    ]);
}

// ─────────────────────────────────────────────────────────────
// POST – create
// ─────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = json_body();
    if (empty($body)) {
        // Also accept form-data
        $body = $_POST;
    }

    [$errors, $data] = validate_student($body, $db);
    if ($errors) api_error('Validation failed.', 422, ['errors' => $errors]);

    // Duplicate email check
    $chk = $db->prepare("SELECT id FROM students WHERE email = ?");
    $chk->bind_param('s', $data['email']);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) { $chk->close(); api_error('Email already exists.', 409); }
    $chk->close();

    $stmt = $db->prepare(
        "INSERT INTO students (name, email, phone, course, status) VALUES (?,?,?,?,?)"
    );
    $stmt->bind_param('sssss', $data['name'], $data['email'], $data['phone'], $data['course'], $data['status']);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    // Return the new record
    $row = $db->query("SELECT * FROM students WHERE id = $newId")->fetch_assoc();
    api_ok('Student added successfully.', ['student' => $row], 201);
}

// ─────────────────────────────────────────────────────────────
// PUT – update
// ─────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) api_error('ID is required for update.', 400);

    $body = json_body();
    [$errors, $data] = validate_student($body, $db);
    if ($errors) api_error('Validation failed.', 422, ['errors' => $errors]);

    // Duplicate email (exclude self)
    $chk = $db->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $chk->bind_param('si', $data['email'], $id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) { $chk->close(); api_error('Email already used by another student.', 409); }
    $chk->close();

    $stmt = $db->prepare(
        "UPDATE students SET name=?,email=?,phone=?,course=?,status=? WHERE id=?"
    );
    $stmt->bind_param('sssssi', $data['name'], $data['email'], $data['phone'], $data['course'], $data['status'], $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) { $stmt->close(); api_error('Student not found.', 404); }
    $stmt->close();

    $row = $db->query("SELECT * FROM students WHERE id = $id")->fetch_assoc();
    api_ok('Student updated successfully.', ['student' => $row]);
}

// ─────────────────────────────────────────────────────────────
// DELETE
// ─────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) api_error('ID is required for delete.', 400);

    $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) { $stmt->close(); api_error('Student not found.', 404); }
    $stmt->close();

    api_ok('Student deleted successfully.', ['id' => $id]);
}

api_error('Method not allowed.', 405);

// ─────────────────────────────────────────────────────────────
// Validation helper
// ─────────────────────────────────────────────────────────────
function validate_student(array $b, mysqli $db): array
{
    $errors = [];
    $data   = [
        'name'   => trim($b['name']   ?? ''),
        'email'  => trim($b['email']  ?? ''),
        'phone'  => trim($b['phone']  ?? ''),
        'course' => trim($b['course'] ?? ''),
        'status' => trim($b['status'] ?? 'active'),
    ];

    if ($data['name']  === '')                         $errors['name']   = 'Name is required.';
    if ($data['email'] === '')                         $errors['email']  = 'Email is required.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';
    if ($data['phone'] !== '' && !preg_match('/^\+?[\d\s\-]{7,20}$/', $data['phone']))
                                                       $errors['phone']  = 'Invalid phone number.';
    if (!in_array($data['course'], COURSES, true))     $errors['course'] = 'Invalid course.';
    if (!in_array($data['status'], ['active','inactive'], true)) $errors['status'] = 'Invalid status.';

    return [$errors, $data];
}
