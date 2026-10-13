<?php
/**
 * Practical 11 – Student Management CRUD with Search & Filter
 * delete.php  –  Permanently delete a student (GET with confirm modal from index/edit)
 *
 * Security: ✅ MySQLi prepared DELETE  ✅ intval ID sanitisation
 *
 * Advanced extension: soft-delete via status='inactive' is also supported;
 * to use soft-delete instead, toggle SOFT_DELETE to true.
 */

require_once __DIR__ . '/includes/db.php';

define('SOFT_DELETE', false);   // set true for advanced soft-delete extension

$db = get_db();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Verify the record exists first
$check = $db->prepare("SELECT id, name FROM students WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
$row = $check->get_result()->fetch_assoc();
$check->close();

if (!$row) {
    header('Location: index.php?msg=error');
    exit;
}

if (SOFT_DELETE) {
    // Advanced extension: mark as inactive instead of hard-delete
    $stmt = $db->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param('i', $id);
} else {
    // Standard permanent delete
    $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param('i', $id);
}

if ($stmt->execute()) {
    $stmt->close();
    header('Location: index.php?msg=deleted');
    exit;
}

$stmt->close();
header('Location: index.php?msg=error');
exit;
