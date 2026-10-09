-- =============================================================
-- Practical 10 – Secure Login/Logout with Sessions & RBAC
-- sql/schema.sql  –  Database schema + seeded test users
--
-- Run this in phpMyAdmin or MySQL CLI before testing.
-- =============================================================

CREATE DATABASE IF NOT EXISTS practical10_auth
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE practical10_auth;

-- ---------------------------------------------------------------
-- Table: users
--   Stores login credentials, role, and remember-me token.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    username        VARCHAR(60)      NOT NULL,
    email           VARCHAR(120)     NOT NULL,
    password_hash   VARCHAR(255)     NOT NULL,          -- bcrypt via password_hash()
    role            ENUM('admin','student') NOT NULL DEFAULT 'student',
    full_name       VARCHAR(120)     NOT NULL,
    is_active       TINYINT(1)       NOT NULL DEFAULT 1,
    remember_token  VARCHAR(100)     NULL DEFAULT NULL, -- SHA-256 hash of the raw token
    token_expires   DATETIME         NULL DEFAULT NULL,
    last_login      DATETIME         NULL DEFAULT NULL,
    created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username),
    UNIQUE KEY uq_email    (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Seeded users (passwords hashed with PASSWORD_BCRYPT, cost 12)
--
-- admin123   → $2y$12$...  (run seed.php to generate fresh hashes)
-- student123 → $2y$12$...
--
-- These are placeholder hashes – run seed.php to insert real ones.
-- ---------------------------------------------------------------

-- Delete existing seed rows so re-running is safe
DELETE FROM users WHERE username IN ('admin', 'alice', 'bob');

-- NOTE: The actual INSERT with real hashes is done by seed.php.
--       The rows below use a static hash for documentation only.
--       Password: admin123
INSERT INTO users (username, email, password_hash, role, full_name) VALUES
(
    'admin',
    'admin@studenthub.local',
    '$2y$12$exampleHashAdminPlaceholderXXXXXXXXXXXXXXXXXXXXX',
    'admin',
    'Administrator'
),
(
    'alice',
    'alice@studenthub.local',
    '$2y$12$exampleHashAlicePlaceholderXXXXXXXXXXXXXXXXXXXXX',
    'student',
    'Alice Johnson'
),
(
    'bob',
    'bob@studenthub.local',
    '$2y$12$exampleHashBobPlaceholderXXXXXXXXXXXXXXXXXXXXXXX',
    'student',
    'Bob Smith'
);

-- ---------------------------------------------------------------
-- View: v_user_summary  (useful for admin dashboard queries)
-- ---------------------------------------------------------------
CREATE OR REPLACE VIEW v_user_summary AS
SELECT
    id,
    username,
    email,
    role,
    full_name,
    is_active,
    last_login,
    created_at
FROM users;
