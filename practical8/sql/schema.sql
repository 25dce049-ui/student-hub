-- ============================================================
--  Practical 8 – StudentHub Database Schema
--  File   : schema.sql
--  Tools  : MySQL 8.x / phpMyAdmin / XAMPP
-- ============================================================

-- Create and select the database
CREATE DATABASE IF NOT EXISTS studenthub
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE studenthub;

-- ============================================================
--  TABLE 1 : students
--  Stores core student information (1NF, 2NF, 3NF compliant)
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
    student_id   INT             UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name    VARCHAR(100)    NOT NULL,
    email        VARCHAR(150)    NOT NULL,
    phone        VARCHAR(15)     NOT NULL,
    course       ENUM('BCA','BCS','BIT','MCA','MCS','B.Tech') NOT NULL,
    year         ENUM('1st Year','2nd Year','3rd Year','4th Year') NOT NULL,
    gender       ENUM('Male','Female','Other') NOT NULL,
    city         VARCHAR(100)    DEFAULT NULL,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (student_id),
    UNIQUE KEY uq_students_email (email),
    INDEX idx_students_course (course),
    INDEX idx_students_year   (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  TABLE 2 : events
--  Stores campus / online events that students can register for
-- ============================================================
CREATE TABLE IF NOT EXISTS events (
    event_id     INT             UNSIGNED NOT NULL AUTO_INCREMENT,
    title        VARCHAR(200)    NOT NULL,
    description  TEXT            DEFAULT NULL,
    category     ENUM('Workshop','Seminar','Hackathon','Cultural','Sports','Other') NOT NULL DEFAULT 'Other',
    event_date   DATE            NOT NULL,
    venue        VARCHAR(200)    DEFAULT NULL,
    max_seats    SMALLINT        UNSIGNED NOT NULL DEFAULT 50,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (event_id),
    INDEX idx_events_date     (event_date),
    INDEX idx_events_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  TABLE 3 : registrations
--  Junction table – links students ↔ events (many-to-many)
--  Foreign keys enforce referential integrity.
-- ============================================================
CREATE TABLE IF NOT EXISTS registrations (
    reg_id       INT             UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id   INT             UNSIGNED NOT NULL,
    event_id     INT             UNSIGNED NOT NULL,
    status       ENUM('Pending','Confirmed','Cancelled') NOT NULL DEFAULT 'Pending',
    registered_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (reg_id),
    -- Prevent a student from registering for the same event twice
    UNIQUE KEY uq_reg_student_event (student_id, event_id),
    -- Foreign keys with cascading rules
    CONSTRAINT fk_reg_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reg_event
        FOREIGN KEY (event_id)   REFERENCES events   (event_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_reg_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  SEED DATA – Students
-- ============================================================
INSERT INTO students (full_name, email, phone, course, year, gender, city) VALUES
('Aarav Sharma',    'aarav.sharma@example.com',    '9876543210', 'BCA',    '1st Year', 'Male',   'Mumbai'),
('Priya Patel',     'priya.patel@example.com',     '9823456781', 'BCS',    '2nd Year', 'Female', 'Ahmedabad'),
('Rahul Mehta',     'rahul.mehta@example.com',     '9812345678', 'MCA',    '1st Year', 'Male',   'Pune'),
('Sneha Joshi',     'sneha.joshi@example.com',     '9801234567', 'BIT',    '3rd Year', 'Female', 'Surat'),
('Karan Verma',     'karan.verma@example.com',     '9790123456', 'B.Tech', '2nd Year', 'Male',   'Delhi'),
('Ananya Singh',    'ananya.singh@example.com',    '9789012345', 'MCS',    '1st Year', 'Female', 'Jaipur'),
('Vikram Nair',     'vikram.nair@example.com',     '9778901234', 'BCA',    '4th Year', 'Male',   'Kochi'),
('Divya Reddy',     'divya.reddy@example.com',     '9767890123', 'BCS',    '2nd Year', 'Female', 'Hyderabad'),
('Arjun Kumar',     'arjun.kumar@example.com',     '9756789012', 'B.Tech', '3rd Year', 'Male',   'Chennai'),
('Pooja Desai',     'pooja.desai@example.com',     '9745678901', 'MCA',    '2nd Year', 'Female', 'Vadodara');


-- ============================================================
--  SEED DATA – Events
-- ============================================================
INSERT INTO events (title, description, category, event_date, venue, max_seats) VALUES
('Web Dev Bootcamp',        'Full-stack development using HTML, CSS, JS & PHP.',    'Workshop',  '2026-11-05', 'Lab 101, Block A',       30),
('AI & ML Seminar',         'Introduction to machine learning algorithms.',          'Seminar',   '2026-11-12', 'Auditorium, Main Block',  80),
('Hackathon 2026',          '24-hour coding competition – open to all.',             'Hackathon', '2026-11-20', 'Innovation Hub',          60),
('Cultural Fest',           'Annual inter-college cultural extravaganza.',           'Cultural',  '2026-12-01', 'Open Ground',            200),
('Cybersecurity Workshop',  'Hands-on session on ethical hacking & pen testing.',   'Workshop',  '2026-11-28', 'Lab 204, Block B',        25),
('Sports Day 2026',         'Athletic events including track & field.',              'Sports',    '2026-12-10', 'Sports Ground',          150),
('Cloud Computing Talk',    'AWS & Azure fundamentals for beginners.',               'Seminar',   '2026-11-18', 'Seminar Hall 1',          50);


-- ============================================================
--  SEED DATA – Registrations
-- ============================================================
INSERT INTO registrations (student_id, event_id, status) VALUES
(1, 1, 'Confirmed'),
(1, 2, 'Confirmed'),
(2, 1, 'Confirmed'),
(2, 3, 'Pending'),
(3, 2, 'Confirmed'),
(3, 4, 'Confirmed'),
(4, 3, 'Cancelled'),
(4, 5, 'Confirmed'),
(5, 1, 'Confirmed'),
(5, 6, 'Confirmed'),
(6, 2, 'Pending'),
(6, 7, 'Confirmed'),
(7, 3, 'Confirmed'),
(7, 4, 'Confirmed'),
(8, 5, 'Confirmed'),
(9, 6, 'Pending'),
(10, 7, 'Confirmed'),
(10, 1, 'Confirmed');
