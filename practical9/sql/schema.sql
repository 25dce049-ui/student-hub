-- ============================================================
--  Practical 9 – Secure User Registration
--  sql/schema.sql  –  Database & Table setup
--
--  Run this in phpMyAdmin or MySQL CLI before testing.
--  Steps:
--    1. Open phpMyAdmin → New → create database "practical9_db"
--    2. Select "practical9_db" → SQL tab → paste & run this file
-- ============================================================

-- Create and select database
CREATE DATABASE IF NOT EXISTS `practical9_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `practical9_db`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(50)     NOT NULL,
    `email`         VARCHAR(100)    NOT NULL,
    `full_name`     VARCHAR(100)    NOT NULL,
    `password_hash` VARCHAR(255)    NOT NULL,           -- bcrypt via password_hash()
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_username` (`username`),              -- Duplicate username check
    UNIQUE KEY `uq_email`    (`email`)                  -- Duplicate email check
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Audit log table (Advanced extension)
CREATE TABLE IF NOT EXISTS `audit_log` (
    `log_id`     INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `action`     VARCHAR(50)     NOT NULL,              -- e.g. 'REGISTER_SUCCESS', 'REGISTER_FAIL_DUPLICATE'
    `username`   VARCHAR(50)     DEFAULT NULL,
    `email`      VARCHAR(100)    DEFAULT NULL,
    `ip_address` VARCHAR(45)     DEFAULT NULL,          -- Supports IPv6
    `user_agent` VARCHAR(255)    DEFAULT NULL,
    `logged_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
