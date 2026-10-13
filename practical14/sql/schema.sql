-- =============================================================
-- Practical 14 – Admin Dashboard with RBAC, Dynamic Menu & Audit Log
-- sql/schema.sql
-- =============================================================

CREATE DATABASE IF NOT EXISTS practical14_admin
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE practical14_admin;

-- ---------------------------------------------------------------
-- users
-- ---------------------------------------------------------------
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','moderator','student') NOT NULL DEFAULT 'student',
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login    DATETIME NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- students
-- ---------------------------------------------------------------
CREATE TABLE students (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(120) NOT NULL UNIQUE,
    course     VARCHAR(100) NOT NULL,
    status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- events
-- ---------------------------------------------------------------
CREATE TABLE events (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title      VARCHAR(200) NOT NULL,
    category   VARCHAR(80)  NOT NULL DEFAULT 'General',
    event_date DATE         NOT NULL,
    status     ENUM('open','closed','cancelled') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- registrations
-- ---------------------------------------------------------------
CREATE TABLE registrations (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id INT UNSIGNED NOT NULL,
    event_id   INT UNSIGNED NOT NULL,
    reg_date   DATE         NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_event_id   (event_id),
    INDEX idx_student_id (student_id),
    INDEX idx_reg_date   (reg_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- audit_logs
-- ---------------------------------------------------------------
CREATE TABLE audit_logs (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NULL,
    user_name  VARCHAR(120) NOT NULL DEFAULT '',
    action     VARCHAR(80)  NOT NULL,
    module     VARCHAR(80)  NOT NULL DEFAULT '',
    details    TEXT         NULL,
    ip_address VARCHAR(45)  NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_user_id (user_id),
    INDEX idx_action  (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Seed: users (passwords = password_hash('admin123') etc.)
-- Run seed.php to generate real bcrypt hashes.
-- ---------------------------------------------------------------
INSERT INTO users (name, email, password_hash, role, status) VALUES
('Super Admin',    'admin@hub.local',      '$2y$12$placeholder_admin_hash_xxxxx', 'admin',     'active'),
('Moderator One',  'mod@hub.local',        '$2y$12$placeholder_mod_hash_xxxxxxx', 'moderator', 'active'),
('Alice Johnson',  'alice@hub.local',      '$2y$12$placeholder_std_hash_xxxxxxx', 'student',   'active'),
('Bob Smith',      'bob@hub.local',        '$2y$12$placeholder_std_hash_xxxxxxx', 'student',   'inactive'),
('Carol White',    'carol@hub.local',      '$2y$12$placeholder_std_hash_xxxxxxx', 'student',   'active'),
('David Brown',    'david@hub.local',      '$2y$12$placeholder_std_hash_xxxxxxx', 'student',   'active'),
('Eva Green',      'eva@hub.local',        '$2y$12$placeholder_std_hash_xxxxxxx', 'student',   'inactive'),
('Frank Lee',      'frank@hub.local',      '$2y$12$placeholder_std_hash_xxxxxxx', 'moderator', 'active');

-- Seed: students
INSERT INTO students (name, email, course, status) VALUES
('Aarav Sharma',   'aarav@hub.local',   'Computer Science',       'active'),
('Priya Patel',    'priya@hub.local',   'Information Technology', 'active'),
('Rohan Mehta',    'rohan@hub.local',   'Electronics',            'active'),
('Ananya Iyer',    'ananya@hub.local',  'Computer Science',       'active'),
('Karan Singh',    'karan@hub.local',   'Mechanical Engineering', 'inactive'),
('Sneha Verma',    'sneha@hub.local',   'Civil Engineering',      'active'),
('Arjun Nair',     'arjun@hub.local',   'Information Technology', 'active'),
('Pooja Gupta',    'pooja@hub.local',   'Computer Science',       'active'),
('Vikram Reddy',   'vikram@hub.local',  'Electronics',            'inactive'),
('Divya Krishnan', 'divya@hub.local',   'Mechanical Engineering', 'active'),
('Amit Joshi',     'amit@hub.local',    'Civil Engineering',      'active'),
('Ritika Agarwal', 'ritika@hub.local',  'Computer Science',       'active'),
('Siddharth Rao',  'siddharth@hub.local','Information Technology','active'),
('Meera Desai',    'meera@hub.local',   'Electronics',            'inactive'),
('Nikhil Patil',   'nikhil@hub.local',  'Computer Science',       'active');

-- Seed: events
INSERT INTO events (title, category, event_date, status) VALUES
('Annual Tech Fest 2026',        'Technical',  '2026-11-15', 'open'),
('Cultural Night 2026',          'Cultural',   '2026-11-20', 'open'),
('Alumni Meet 2026',             'Networking', '2026-12-05', 'open'),
('Sports Day 2026',              'Sports',     '2026-11-28', 'open'),
('Hackathon 3.0',                'Technical',  '2026-12-10', 'open'),
('Workshop: Web Development',    'Workshop',   '2026-11-18', 'open'),
('Debate Competition',           'Academic',   '2026-11-25', 'closed'),
('Photography Exhibition',       'Cultural',   '2026-12-01', 'open'),
('Career Fair 2026',             'Networking', '2026-12-08', 'open'),
('Science Quiz Competition',     'Academic',   '2026-11-22', 'open');

-- Seed: registrations (spread over 6 months for chart)
INSERT INTO registrations (student_id, event_id, reg_date) VALUES
(1,1,'2026-05-10'),(2,1,'2026-05-12'),(3,2,'2026-05-15'),
(4,3,'2026-06-01'),(5,3,'2026-06-05'),(6,4,'2026-06-20'),
(7,5,'2026-07-03'),(8,5,'2026-07-08'),(9,6,'2026-07-15'),
(10,7,'2026-08-02'),(11,7,'2026-08-10'),(12,8,'2026-08-18'),
(13,9,'2026-09-05'),(14,9,'2026-09-12'),(15,10,'2026-09-25'),
(1,2,'2026-10-01'),(2,3,'2026-10-05'),(3,4,'2026-10-10'),
(4,5,'2026-10-15'),(5,6,'2026-10-20');

-- Seed: audit_logs
INSERT INTO audit_logs (user_id, user_name, action, module, details, ip_address) VALUES
(1,'Super Admin','LOGIN',       'Auth',       'Admin logged in.',             '127.0.0.1'),
(1,'Super Admin','CREATE',      'Students',   'Added student Aarav Sharma.',  '127.0.0.1'),
(1,'Super Admin','CREATE',      'Events',     'Created Annual Tech Fest.',    '127.0.0.1'),
(2,'Moderator One','UPDATE',    'Events',     'Updated Cultural Night.',      '127.0.0.1'),
(1,'Super Admin','DELETE',      'Students',   'Removed inactive student.',    '127.0.0.1'),
(2,'Moderator One','LOGIN',     'Auth',       'Moderator logged in.',         '127.0.0.1'),
(1,'Super Admin','STATUS_CHANGE','Users',     'Deactivated user Bob Smith.',  '127.0.0.1'),
(1,'Super Admin','LOGOUT',      'Auth',       'Admin logged out.',            '127.0.0.1');
