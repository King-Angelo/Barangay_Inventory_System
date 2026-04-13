-- Migration 001: upgrade users table
-- Safe to run on top of existing mimds.sql import.
-- Adds auto-increment surrogate PK, role enum, password_hash,
-- barangay_id FK, and timestamps. Keeps legacy UserName / PaSS
-- columns until passwords are migrated, then they can be dropped.

SET NAMES utf8mb4;

-- 1. Convert table charset (utf8mb4 for Unicode names / emojis)
ALTER TABLE `users`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Add surrogate PK (UserName stays UNIQUE for now)
ALTER TABLE `users`
  ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_username` (`UserName`);

-- 3. Add role, secure password column, barangay FK, timestamps
ALTER TABLE `users`
  ADD COLUMN `role` ENUM('staff','admin') NOT NULL DEFAULT 'staff' AFTER `PaSS`,
  ADD COLUMN `password_hash` VARCHAR(255) NULL COMMENT 'bcrypt via password_hash(). Migrate from PaSS.' AFTER `role`,
  ADD COLUMN `barangay_id` INT NULL AFTER `password_hash`,
  ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `barangay_id`,
  ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- 4. FK to barangays (nullable — super-admin may span all barangays)
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_barangay`
    FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`n`)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- Note: Drop PaSS column AFTER migrating plaintext passwords to password_hash.
-- ALTER TABLE `users` DROP COLUMN `PaSS`;
