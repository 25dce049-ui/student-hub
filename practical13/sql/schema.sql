-- =============================================================
-- Practical 13 – PHP JSON API & AJAX/Fetch CRUD
-- sql/schema.sql
-- =============================================================

CREATE DATABASE IF NOT EXISTS practical13_api
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE practical13_api;

DROP TABLE IF EXISTS students;

CREATE TABLE students (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name       VARCHAR(120)  NOT NULL,
    email      VARCHAR(120)  NOT NULL UNIQUE,
    phone      VARCHAR(20)   NOT NULL DEFAULT '',
    course     VARCHAR(100)  NOT NULL,
    status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_name   (name),
    INDEX idx_course (course),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20 seeded records
INSERT INTO students (name, email, phone, course, status) VALUES
('Aarav Sharma',   'aarav.sharma@api.local',   '9876543210', 'Computer Science',        'active'),
('Priya Patel',    'priya.patel@api.local',    '9876543211', 'Information Technology',  'active'),
('Rohan Mehta',    'rohan.mehta@api.local',    '9876543212', 'Electronics',             'active'),
('Ananya Iyer',    'ananya.iyer@api.local',    '9876543213', 'Computer Science',        'active'),
('Karan Singh',    'karan.singh@api.local',    '9876543214', 'Mechanical Engineering',  'inactive'),
('Sneha Verma',    'sneha.verma@api.local',    '9876543215', 'Civil Engineering',       'active'),
('Arjun Nair',     'arjun.nair@api.local',     '9876543216', 'Information Technology',  'active'),
('Pooja Gupta',    'pooja.gupta@api.local',    '9876543217', 'Computer Science',        'active'),
('Vikram Reddy',   'vikram.reddy@api.local',   '9876543218', 'Electronics',             'inactive'),
('Divya Krishnan', 'divya.krishnan@api.local', '9876543219', 'Mechanical Engineering',  'active'),
('Amit Joshi',     'amit.joshi@api.local',     '9876543220', 'Civil Engineering',       'active'),
('Ritika Agarwal', 'ritika.agarwal@api.local', '9876543221', 'Computer Science',        'active'),
('Siddharth Rao',  'siddharth.rao@api.local',  '9876543222', 'Information Technology',  'active'),
('Meera Desai',    'meera.desai@api.local',    '9876543223', 'Electronics',             'inactive'),
('Nikhil Patil',   'nikhil.patil@api.local',   '9876543224', 'Computer Science',        'active'),
('Ishaan Kapoor',  'ishaan.kapoor@api.local',  '9876543225', 'Mechanical Engineering',  'active'),
('Kavya Nambiar',  'kavya.nambiar@api.local',  '9876543226', 'Civil Engineering',       'active'),
('Tanmay Shah',    'tanmay.shah@api.local',    '9876543227', 'Information Technology',  'inactive'),
('Shreya Mishra',  'shreya.mishra@api.local',  '9876543228', 'Computer Science',        'active'),
('Rahul Saxena',   'rahul.saxena@api.local',   '9876543229', 'Electronics',             'active');
