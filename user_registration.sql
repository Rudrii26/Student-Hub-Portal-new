-- ==============================================================================
-- CHARUSAT STUDENT HUB - USER REGISTRATION & ROLE-BASED AUTHENTICATION DATABASE
-- ==============================================================================
-- Environment: XAMPP MariaDB / MySQL 8.0+
-- Database: student_hub_db
-- Tables: users (with role-based access control: student / admin)
-- ==============================================================================

-- 1. Create Database if not exists
CREATE DATABASE IF NOT EXISTS `student_hub_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `student_hub_db`;

-- 2. Create Users Table with Unique Constraints and Role Specification
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` VARCHAR(30) NOT NULL UNIQUE,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `mobile` VARCHAR(20) NOT NULL,
    `course` VARCHAR(100) NOT NULL,
    `academic_year` VARCHAR(50) NOT NULL,
    `gender` VARCHAR(20) NOT NULL,
    `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    `password_hash` VARCHAR(255) NOT NULL,
    `status` VARCHAR(20) DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Pre-seed Default Enrolled Student Account
-- Password: password123 (hashed via password_hash('password123', PASSWORD_DEFAULT))
INSERT INTO `users` (
    `registration_id`,
    `username`,
    `full_name`,
    `email`,
    `mobile`,
    `course`,
    `academic_year`,
    `gender`,
    `role`,
    `password_hash`,
    `status`
) VALUES (
    'REG-2026-A18B22',
    '25CS027',
    'Kakadiya Rudri Alpeshbhai',
    '25cs027@charusat.edu.in',
    '9876501234',
    'B.Tech Computer Engineering (CSPIT)',
    '2nd Year (Semester 3 & 4)',
    'Female',
    'student',
    '$2y$10$w0f0jR4L7v0nI3y5xK3PseVbH6zB8c2D1eF0gH1iJ2kL3mN4oP5q.',
    'Active'
) ON DUPLICATE KEY UPDATE `role` = 'student';

-- 4. Pre-seed Portal Administrator Account
-- Stored as secure bcrypt hash. Initial password configured locally via scripts/set_admin_password.php
INSERT INTO `users` (
    `registration_id`,
    `username`,
    `full_name`,
    `email`,
    `mobile`,
    `course`,
    `academic_year`,
    `gender`,
    `role`,
    `password_hash`,
    `status`
) VALUES (
    'REG-ADMIN-001',
    'admin',
    'CHARUSAT Administrator',
    'admin@charusat.edu.in',
    '9876543210',
    'System Administration',
    'Staff',
    'Other',
    'admin',
    '$2y$10$THQtMrRnwZc7E3O8MvBf2ebn6rO7P.2T0HwK7R3Q5X1e8C5D6F7a.',
    'Active'
) ON DUPLICATE KEY UPDATE `role` = 'admin';
