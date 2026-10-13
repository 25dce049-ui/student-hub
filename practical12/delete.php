<?php
/**
 * Practical 12 – Event Management CRUD with Poster Upload
 * delete.php  –  Delete event + poster file + audit log entry
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/upload.php';

$db = get_db();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php?msg=error'); exit; }

// Fetch before deleting (need title + poster filename for audit/cleanup)
$chk = $db->prepare("SELECT id, title, poster FROM events WHERE id = ?");
$chk->bind_param('i', $id);
$chk->execute();
$row = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$row) { header('Location: index.php?msg=error'); exit; }

// Delete DB record
$del = $db->prepare("DELETE FROM events WHERE id = ?");
$del->bind_param('i', $id);
$del->execute();
$del->close();

// Remove poster from disk
delete_poster($row['poster']);

// Audit log
audit(null, 'DELETE', "Event ID {$id} '{$row['title']}' deleted.");

header('Location: index.php?msg=deleted');
exit;
