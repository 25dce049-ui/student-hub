<?php
/**
 * Practical 14 – seed.php
 * Run once via browser: http://localhost/student analysis/practical14/seed.php
 * Inserts users with real bcrypt hashes. Safe to re-run.
 */

require_once __DIR__ . '/includes/db.php';

$users = [
    ['Super Admin',   'admin@hub.local',   'admin123',   'admin',     'active'],
    ['Moderator One', 'mod@hub.local',     'mod123',     'moderator', 'active'],
    ['Alice Johnson', 'alice@hub.local',   'student123', 'student',   'active'],
    ['Bob Smith',     'bob@hub.local',     'student123', 'student',   'inactive'],
    ['Carol White',   'carol@hub.local',   'student123', 'student',   'active'],
    ['David Brown',   'david@hub.local',   'student123', 'student',   'active'],
    ['Eva Green',     'eva@hub.local',     'student123', 'student',   'inactive'],
    ['Frank Lee',     'frank@hub.local',   'mod123',     'moderator', 'active'],
];

$db = get_db();
$db->query("DELETE FROM users");

$stmt = $db->prepare(
    "INSERT INTO users (name, email, password_hash, role, status) VALUES (?,?,?,?,?)"
);

foreach ($users as [$name, $email, $pass, $role, $status]) {
    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt->bind_param('sssss', $name, $email, $hash, $role, $status);
    $stmt->execute();
}
$stmt->close();

echo '<pre style="font-family:monospace;padding:2rem">✅ Users seeded successfully!

Credentials:
┌─────────────────────────────┬─────────────┬───────────┐
│ Email                       │ Password    │ Role      │
├─────────────────────────────┼─────────────┼───────────┤
│ admin@hub.local             │ admin123    │ admin     │
│ mod@hub.local               │ mod123      │ moderator │
│ frank@hub.local             │ mod123      │ moderator │
│ alice@hub.local             │ student123  │ student   │
│ carol@hub.local             │ student123  │ student   │
└─────────────────────────────┴─────────────┴───────────┘

<a href="login.php">→ Go to Login</a>
</pre>';
