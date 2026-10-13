<?php
/**
 * Practical 11 – Student Management CRUD with Search & Filter
 * export.php  –  Export filtered student list to CSV
 *
 * Intermediate Extension: Export student list to CSV
 * Security: ✅ MySQLi prepared statements  ✅ fputcsv for safe CSV encoding
 */

require_once __DIR__ . '/includes/db.php';

$db = get_db();

// Accept same filters as index.php
$search = trim($_GET['search'] ?? '');
$course = trim($_GET['course'] ?? '');
$gender = trim($_GET['gender'] ?? '');
$status = trim($_GET['status'] ?? '');

$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(name LIKE ? OR email LIKE ? OR course LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like]);
    $types   .= 'sss';
}
if ($course !== '') { $where[] = 'course = ?'; $params[] = $course; $types .= 's'; }
if ($gender !== '') { $where[] = 'gender = ?'; $params[] = $gender; $types .= 's'; }
if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; $types .= 's'; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql      = "SELECT id, name, email, phone, course, gender, dob, address, status, created_at
             FROM students $whereSQL ORDER BY id ASC";

$stmt = $db->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ── Stream CSV to browser ────────────────────────────────────────
$filename = 'students_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// UTF-8 BOM so Excel opens it correctly
fputs($out, "\xEF\xBB\xBF");

// Header row
fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Course', 'Gender', 'Date of Birth', 'Address', 'Status', 'Created At']);

// Data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($out, [
        $row['id'],
        $row['name'],
        $row['email'],
        $row['phone'],
        $row['course'],
        $row['gender'],
        $row['dob'] ?? '',
        $row['address'],
        $row['status'],
        $row['created_at'],
    ]);
}

fclose($out);
$stmt->close();
exit;
