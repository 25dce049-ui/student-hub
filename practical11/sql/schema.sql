-- =============================================================
-- Practical 11 – Student Management CRUD with Search & Filter
-- sql/schema.sql  –  Database schema + 20 seeded records
-- =============================================================

CREATE DATABASE IF NOT EXISTS practical11_students
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE practical11_students;

DROP TABLE IF EXISTS students;

CREATE TABLE students (
    id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120)     NOT NULL,
    email       VARCHAR(120)     NOT NULL UNIQUE,
    phone       VARCHAR(20)      NOT NULL DEFAULT '',
    course      VARCHAR(100)     NOT NULL,
    gender      ENUM('Male','Female','Other') NOT NULL DEFAULT 'Male',
    dob         DATE             NULL,
    address     VARCHAR(255)     NOT NULL DEFAULT '',
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_name   (name),
    INDEX idx_email  (email),
    INDEX idx_course (course),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 20 Seeded Records
-- ---------------------------------------------------------------
INSERT INTO students (name, email, phone, course, gender, dob, address, status) VALUES
('Aarav Sharma',    'aarav.sharma@example.com',    '9876543210', 'Computer Science',    'Male',   '2002-03-15', 'Mumbai, Maharashtra',      'active'),
('Priya Patel',     'priya.patel@example.com',     '9876543211', 'Information Technology','Female','2003-07-22', 'Ahmedabad, Gujarat',       'active'),
('Rohan Mehta',     'rohan.mehta@example.com',     '9876543212', 'Electronics',         'Male',   '2002-11-10', 'Pune, Maharashtra',         'active'),
('Ananya Iyer',     'ananya.iyer@example.com',     '9876543213', 'Computer Science',    'Female', '2003-01-05', 'Chennai, Tamil Nadu',       'active'),
('Karan Singh',     'karan.singh@example.com',     '9876543214', 'Mechanical Engineering','Male', '2001-09-28', 'Delhi, India',              'inactive'),
('Sneha Verma',     'sneha.verma@example.com',     '9876543215', 'Civil Engineering',   'Female', '2002-06-14', 'Jaipur, Rajasthan',         'active'),
('Arjun Nair',      'arjun.nair@example.com',      '9876543216', 'Information Technology','Male', '2003-04-19', 'Kochi, Kerala',             'active'),
('Pooja Gupta',     'pooja.gupta@example.com',     '9876543217', 'Computer Science',    'Female', '2002-08-30', 'Lucknow, Uttar Pradesh',    'active'),
('Vikram Reddy',    'vikram.reddy@example.com',    '9876543218', 'Electronics',         'Male',   '2001-12-02', 'Hyderabad, Telangana',      'inactive'),
('Divya Krishnan',  'divya.krishnan@example.com',  '9876543219', 'Mechanical Engineering','Female','2003-02-17','Bangalore, Karnataka',      'active'),
('Amit Joshi',      'amit.joshi@example.com',      '9876543220', 'Civil Engineering',   'Male',   '2002-05-08', 'Nagpur, Maharashtra',       'active'),
('Ritika Agarwal',  'ritika.agarwal@example.com',  '9876543221', 'Computer Science',    'Female', '2003-10-25', 'Bhopal, Madhya Pradesh',    'active'),
('Siddharth Rao',   'siddharth.rao@example.com',   '9876543222', 'Information Technology','Male', '2001-07-11', 'Visakhapatnam, AP',         'active'),
('Meera Desai',     'meera.desai@example.com',     '9876543223', 'Electronics',         'Female', '2002-03-29', 'Surat, Gujarat',            'inactive'),
('Nikhil Patil',    'nikhil.patil@example.com',    '9876543224', 'Computer Science',    'Male',   '2003-09-07', 'Kolhapur, Maharashtra',     'active'),
('Ishaan Kapoor',   'ishaan.kapoor@example.com',   '9876543225', 'Mechanical Engineering','Male', '2002-01-23', 'Chandigarh, Punjab',        'active'),
('Kavya Nambiar',   'kavya.nambiar@example.com',   '9876543226', 'Civil Engineering',   'Female', '2003-06-16', 'Thiruvananthapuram, Kerala','active'),
('Tanmay Shah',     'tanmay.shah@example.com',     '9876543227', 'Information Technology','Male', '2001-11-04', 'Vadodara, Gujarat',         'inactive'),
('Shreya Mishra',   'shreya.mishra@example.com',   '9876543228', 'Computer Science',    'Female', '2002-04-12', 'Varanasi, Uttar Pradesh',   'active'),
('Rahul Saxena',    'rahul.saxena@example.com',    '9876543229', 'Electronics',         'Male',   '2003-08-20', 'Allahabad, Uttar Pradesh',  'active');
