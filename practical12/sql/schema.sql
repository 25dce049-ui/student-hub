-- =============================================================
-- Practical 12 – Event Management CRUD with Poster Upload
-- sql/schema.sql  –  Database schema + sample records
-- =============================================================

CREATE DATABASE IF NOT EXISTS practical12_events
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE practical12_events;

DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS events;

-- ---------------------------------------------------------------
-- Table: events
-- ---------------------------------------------------------------
CREATE TABLE events (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    title        VARCHAR(200)  NOT NULL,
    description  TEXT          NOT NULL,
    category     VARCHAR(80)   NOT NULL DEFAULT 'General',
    venue        VARCHAR(200)  NOT NULL DEFAULT '',
    event_date   DATE          NOT NULL,
    event_time   TIME          NULL,
    status       ENUM('open','closed','cancelled') NOT NULL DEFAULT 'open',
    poster       VARCHAR(255)  NULL DEFAULT NULL,   -- stored filename
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_category   (category),
    INDEX idx_status     (status),
    INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Advanced Extension: audit_log table
-- ---------------------------------------------------------------
CREATE TABLE audit_log (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id   INT UNSIGNED NULL,
    action     ENUM('CREATE','UPDATE','DELETE') NOT NULL,
    details    TEXT         NULL,
    ip_address VARCHAR(45)  NOT NULL DEFAULT '',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Sample event records
-- ---------------------------------------------------------------
INSERT INTO events (title, description, category, venue, event_date, event_time, status) VALUES
('Annual Tech Fest 2026',         'A celebration of technology and innovation with workshops, hackathons, and keynotes.',    'Technical',    'Main Auditorium',         '2026-11-15', '09:00:00', 'open'),
('Cultural Night 2026',           'An evening of music, dance, and drama performances by students.',                        'Cultural',     'Open Air Theatre',        '2026-11-20', '18:30:00', 'open'),
('Alumni Meet 2026',              'Annual gathering of alumni for networking and panel discussions.',                        'Networking',   'Convention Hall',         '2026-12-05', '11:00:00', 'open'),
('Sports Day 2026',               'Inter-department sports competitions including cricket, football, and athletics.',        'Sports',       'Sports Ground',           '2026-11-28', '08:00:00', 'open'),
('Hackathon 3.0',                 '24-hour coding challenge for students to build real-world solutions.',                   'Technical',    'Computer Lab Block A',    '2026-12-10', '10:00:00', 'open'),
('Workshop: Web Development',     'Hands-on workshop on HTML, CSS, JavaScript, and PHP for beginners.',                     'Workshop',     'Seminar Hall 2',          '2026-11-18', '10:00:00', 'open'),
('Debate Competition',            'Inter-college debate on contemporary issues in science and society.',                    'Academic',     'Seminar Hall 1',          '2026-11-25', '14:00:00', 'open'),
('Photography Exhibition',        'Exhibition of photos captured by students across the academic year.',                    'Cultural',     'Gallery Hall',            '2026-12-01', '10:00:00', 'open'),
('Career Fair 2026',              'Meet recruiters from top companies and explore internship and job opportunities.',        'Networking',   'Exhibition Centre',       '2026-12-08', '09:30:00', 'open'),
('Science Quiz Competition',      'Annual science quiz open to all undergraduate students.',                                'Academic',     'Lecture Hall 3',          '2026-11-22', '15:00:00', 'open'),
('Business Plan Presentation',    'Students pitch their startup ideas to a panel of industry experts.',                     'Technical',    'Board Room',              '2026-12-03', '10:00:00', 'open'),
('Health & Wellness Camp',        'Free health check-ups, yoga sessions, and mental wellness workshops.',                   'General',      'College Ground',          '2026-11-30', '09:00:00', 'open'),
('AI & ML Summit',                'One-day summit featuring talks on artificial intelligence and machine learning.',         'Technical',    'Main Auditorium',         '2026-12-12', '09:00:00', 'open'),
('Environment Day Celebration',   'Tree planting drive and awareness rally for environmental conservation.',                'General',      'Campus Garden',           '2026-11-16', '07:30:00', 'closed'),
('Literary Festival',             'Poetry readings, short story contests, and creative writing workshops.',                 'Cultural',     'Library Hall',            '2026-11-19', '11:00:00', 'open'),
('Robotics Competition',          'Teams design and compete with autonomous robots in obstacle challenges.',                'Technical',    'Engineering Workshop',    '2026-12-06', '09:00:00', 'open'),
('Freshers Welcome Party',        'Welcome event for first-year students with games, music, and food.',                    'Cultural',     'Open Air Theatre',        '2026-11-17', '17:00:00', 'closed'),
('National Seminar on Cyber Security', 'Expert talks on ethical hacking, data privacy, and network defence.',             'Technical',    'Seminar Hall 1',          '2026-12-15', '09:30:00', 'open'),
('Inter-College Dance Competition','Competitive dance event open to teams from all affiliated colleges.',                   'Cultural',     'Main Auditorium',         '2026-12-18', '16:00:00', 'open'),
('Annual Prize Distribution',     'Ceremony to honour academic toppers, sports champions, and achievers.',                 'General',      'Main Auditorium',         '2026-12-20', '10:00:00', 'open');
