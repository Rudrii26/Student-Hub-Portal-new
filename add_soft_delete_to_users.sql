-- ==============================================================================
-- CHARUSAT STUDENT HUB - DATABASE MIGRATION: ADD SOFT DELETE SUPPORT
-- ==============================================================================
-- Adds `is_deleted` and `deleted_at` columns to `users` table.
-- Safe to execute repeatedly (Idempotent for MySQL / MariaDB 10.2+).
-- ==============================================================================

USE `student_hub_db`;

-- 1. Add `is_deleted` column if it does not already exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;

-- 2. Add `deleted_at` column if it does not already exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL DEFAULT NULL AFTER `is_deleted`;

-- 3. Add index on `is_deleted` for high-performance filtering
ALTER TABLE `users` 
ADD INDEX IF NOT EXISTS `idx_is_deleted` (`is_deleted`);

-- 4. Ensure any existing record with status = 'Deleted' has is_deleted = 1
UPDATE `users` 
SET `is_deleted` = 1, `deleted_at` = NOW() 
WHERE `status` = 'Deleted' AND `is_deleted` = 0;
