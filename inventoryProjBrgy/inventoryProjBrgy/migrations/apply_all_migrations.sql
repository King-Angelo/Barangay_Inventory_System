-- =============================================================================
-- ONE-FILE: run migrations 001-007 in order (phpMyAdmin: select DB mimds -> Import this file)
-- If tables already exist, errors may occur — use fresh DB or run individual files.
-- =============================================================================

USE `mimds`;

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
-- Migration 002: residents table
-- Frozen decisions (RESIDENT_ROADMAP.md):
--   • UNIQUE(barangay_id, email) — one record per person per barangay
--   • status: active | archived — admin-only archive, no hard delete in v1
--   • created_by_user_id → users.id (which staff filed the record)
--   • optional patient.resident_id link handled in migration 006

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `residents` (
  `id`                  INT           NOT NULL AUTO_INCREMENT,
  `barangay_id`         INT           NOT NULL,
  `last_name`           VARCHAR(100)  NOT NULL,
  `first_name`          VARCHAR(100)  NOT NULL,
  `middle_name`         VARCHAR(100)  NULL,
  `email`               VARCHAR(255)  NOT NULL COMMENT 'Required for SMTP notifications',
  `phone`               VARCHAR(30)   NULL,
  `birthdate`           DATE          NULL,
  `gender`              VARCHAR(20)   NULL,
  `address_line`        TEXT          NULL,
  `status`              ENUM('active','archived') NOT NULL DEFAULT 'active',
  `created_by_user_id`  INT           NOT NULL COMMENT 'FK → users.id (staff who created)',
  `created_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- Frozen rule Q1: same person cannot appear twice in the same barangay
  UNIQUE KEY `uq_resident_brgy_email` (`barangay_id`, `email`),

  -- Search indexes (RESIDENT_ROADMAP.md §2.3)
  INDEX `idx_email`           (`email`),
  INDEX `idx_name`            (`last_name`, `first_name`),
  INDEX `idx_status`          (`status`),
  INDEX `idx_created_by`      (`created_by_user_id`),

  CONSTRAINT `fk_residents_barangay`
    FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`n`)
    ON UPDATE CASCADE ON DELETE RESTRICT,

  CONSTRAINT `fk_residents_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Master resident record. Admin-only archive. No hard delete in v1.';
-- Migration 003: permit_types and permits
-- v1 permit type: Barangay Clearance only (seeded in 007_seeds.sql)
-- Status flow: draft → submitted (staff) → approved/rejected (admin only)
-- reference_no: unique, human-readable identifier for permit-ref search

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `permit_types` (
  `id`          INT           NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)  NOT NULL,
  `description` TEXT          NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permit_type_name` (`name`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Lookup table for permit/clearance types. v1: Barangay Clearance only.';


CREATE TABLE IF NOT EXISTS `permits` (
  `id`              INT           NOT NULL AUTO_INCREMENT,
  `resident_id`     INT           NOT NULL,
  `permit_type_id`  INT           NOT NULL,
  `reference_no`    VARCHAR(50)   NOT NULL COMMENT 'Human-readable permit reference (search target)',

  -- Frozen rule Q6: staff → submitted; admin → approved/rejected
  `status`          ENUM(
                      'draft',
                      'submitted',
                      'approved',
                      'rejected',
                      'ready_for_payment',
                      'paid',
                      'issued'
                    ) NOT NULL DEFAULT 'draft',

  `submitted_by`    INT           NULL COMMENT 'FK → users.id (staff who submitted)',
  `approved_by`     INT           NULL COMMENT 'FK → users.id (admin who approved/rejected)',
  `submitted_at`    TIMESTAMP     NULL,
  `approved_at`     TIMESTAMP     NULL,
  `remarks`         TEXT          NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reference_no` (`reference_no`),
  INDEX `idx_resident`        (`resident_id`),
  INDEX `idx_status`          (`status`),
  INDEX `idx_submitted_by`    (`submitted_by`),
  INDEX `idx_approved_by`     (`approved_by`),

  CONSTRAINT `fk_permits_resident`
    FOREIGN KEY (`resident_id`)    REFERENCES `residents` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,

  CONSTRAINT `fk_permits_type`
    FOREIGN KEY (`permit_type_id`) REFERENCES `permit_types` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,

  CONSTRAINT `fk_permits_submitted_by`
    FOREIGN KEY (`submitted_by`)   REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,

  CONSTRAINT `fk_permits_approved_by`
    FOREIGN KEY (`approved_by`)    REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Permit/clearance applications. Staff submits; admin approves/rejects.';
-- Migration 004: payments (mock payment provider)
-- One payment row per permit (1-to-1 once approved).
-- provider_ref + idempotency_key guard against double-charging.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `payments` (
  `id`              INT             NOT NULL AUTO_INCREMENT,
  `permit_id`       INT             NOT NULL,
  `amount`          DECIMAL(10,2)   NOT NULL,
  `currency`        CHAR(3)         NOT NULL DEFAULT 'PHP',
  `status`          ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `provider`        VARCHAR(50)     NOT NULL DEFAULT 'mock',
  `provider_ref`    VARCHAR(100)    NULL COMMENT 'Reference from payment provider (idempotency)',
  `idempotency_key` VARCHAR(100)    NULL COMMENT 'Client-generated key to prevent duplicate charges',
  `provider_payload` JSON           NULL COMMENT 'Full provider webhook payload (never log secrets)',
  `paid_at`         TIMESTAMP       NULL,
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permit_payment`   (`permit_id`),  -- one payment per permit
  UNIQUE KEY `uq_idempotency_key`  (`idempotency_key`),
  INDEX `idx_provider_ref`         (`provider_ref`),
  INDEX `idx_status`               (`status`),

  CONSTRAINT `fk_payments_permit`
    FOREIGN KEY (`permit_id`) REFERENCES `permits` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Mock payment records. idempotency_key prevents double-charging.';
-- Migration 005: integration_events (DB outbox) + notification_log
-- Outbox pattern: app inserts an event row, a worker script polls and sends.
-- Worker updates status → processed after sending email via SMTP.
-- Never log full email bodies or secrets in these tables.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `integration_events` (
  `id`              INT           NOT NULL AUTO_INCREMENT,
  `event_type`      VARCHAR(100)  NOT NULL COMMENT 'e.g. permit.approved, payment.paid',
  `aggregate_id`    INT           NOT NULL COMMENT 'e.g. permit_id or payment_id',
  `aggregate_type`  VARCHAR(50)   NOT NULL COMMENT 'e.g. permit, payment',
  `payload`         JSON          NOT NULL COMMENT 'Must include resident_id + permit_id for mailer',
  `status`          ENUM('pending','processing','processed','failed') NOT NULL DEFAULT 'pending',
  `attempts`        TINYINT       NOT NULL DEFAULT 0,
  `last_error`      TEXT          NULL,
  `scheduled_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at`    TIMESTAMP     NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  INDEX `idx_status_scheduled`  (`status`, `scheduled_at`),
  INDEX `idx_aggregate`         (`aggregate_type`, `aggregate_id`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='DB outbox. Worker polls pending rows, processes, marks processed.';


CREATE TABLE IF NOT EXISTS `notification_log` (
  `id`                    INT           NOT NULL AUTO_INCREMENT,
  `integration_event_id`  INT           NULL COMMENT 'Source event (nullable if sent ad-hoc)',
  `resident_id`           INT           NOT NULL,
  `recipient_email`       VARCHAR(255)  NOT NULL,
  `subject`               VARCHAR(255)  NOT NULL,
  `status`                ENUM('sent','failed') NOT NULL,
  `error_message`         TEXT          NULL COMMENT 'Truncated SMTP error; no secrets',
  `sent_at`               TIMESTAMP     NULL,
  `created_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  INDEX `idx_resident`    (`resident_id`),
  INDEX `idx_event`       (`integration_event_id`),

  CONSTRAINT `fk_notif_event`
    FOREIGN KEY (`integration_event_id`) REFERENCES `integration_events` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,

  CONSTRAINT `fk_notif_resident`
    FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit log of outbound emails. Never stores full body or SMTP secrets.';
-- Migration 006: link legacy `patient` to `residents` (optional, nullable)
-- RESIDENT_ROADMAP.md §2.2 / Q7: prefer patient.resident_id (nullable FK)
-- over merging tables. Same real person can exist in both modules.
-- Apply this migration once the residents table is populated and you are
-- ready to start linking patient rows to resident rows.

SET NAMES utf8mb4;

-- Convert patient table to utf8mb4 to support Unicode names
ALTER TABLE `patient`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Add nullable FK column
ALTER TABLE `patient`
  ADD COLUMN `resident_id` INT NULL
    COMMENT 'Optional link → residents.id. Null = not yet linked.'
    AFTER `brgy`,
  ADD INDEX `idx_patient_resident` (`resident_id`),
  ADD CONSTRAINT `fk_patient_resident`
    FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- Usage: to link a patient row manually:
--   UPDATE patient SET resident_id = <residents.id> WHERE n = <patient.n>;
--
-- To query across both contexts:
--   SELECT p.*, r.email FROM patient p
--   LEFT JOIN residents r ON r.id = p.resident_id
--   WHERE p.n = ?;
-- Migration 007: seeds
-- RESIDENT_ROADMAP.md: v1 auth = staff | admin only; UNIQUE(barangay_id, email) on residents;
-- permit flow: staff draft→submitted; admin approved|rejected (permit_types + users seeds below).
--
-- PASSWORD WORKFLOW (see migrations/SEEDS.md):
--   Default bcrypt below verifies to: ChangeMe2026!
--   New hash: php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT);"
--   Then UPDATE users SET password_hash = '...' WHERE UserName = 'admin';
--
-- Legacy `UserName` / `PaSS` columns remain until you migrate other rows (migration 001).

SET NAMES utf8mb4;

-- Seed admin user
-- barangay_id NULL → super-admin (spans all barangays; can approve permits per roadmap)
INSERT INTO `users` (`UserName`, `PaSS`, `role`, `password_hash`, `barangay_id`)
VALUES (
  'admin',
  '',   -- empty; login must use password_hash column
  'admin',
  '$2y$10$6fjLU5olcVT0HQ3G5CgwHORL75efEzYN8r8jCvkrNn89mHnO4/7Km',
  NULL
)
ON DUPLICATE KEY UPDATE
  `role`          = VALUES(`role`),
  `password_hash` = VALUES(`password_hash`);

-- Demo staff user (barangay 1) for RBAC / API tests — same bcrypt as admin placeholder `ChangeMe2026!`
-- Requires `barangays.n = 1` (included in base `mimds.sql`).
INSERT INTO `users` (`UserName`, `PaSS`, `role`, `password_hash`, `barangay_id`)
VALUES (
  'staff_dev',
  '',
  'staff',
  '$2y$10$6fjLU5olcVT0HQ3G5CgwHORL75efEzYN8r8jCvkrNn89mHnO4/7Km',
  1
)
ON DUPLICATE KEY UPDATE
  `role`          = VALUES(`role`),
  `password_hash` = VALUES(`password_hash`),
  `barangay_id`   = VALUES(`barangay_id`);

-- Seed v1 permit type: Barangay Clearance (only active type in v1 per roadmap)
INSERT INTO `permit_types` (`name`, `description`, `is_active`)
VALUES (
  'Barangay Clearance',
  'General-purpose barangay clearance certificate issued to residents.',
  1
)
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `is_active`   = VALUES(`is_active`);

-- Optional: demo resident for permit / API tests — respects UNIQUE(barangay_id, email) (Q1)
INSERT INTO `residents` (
  `barangay_id`, `last_name`, `first_name`, `middle_name`, `email`, `phone`, `status`, `created_by_user_id`
)
SELECT
  1,
  'Demo',
  'Resident',
  NULL,
  'resident.demo@example.com',
  NULL,
  'active',
  u.`id`
FROM `users` u
WHERE u.`UserName` = 'admin'
  AND NOT EXISTS (
    SELECT 1 FROM `residents` r
    WHERE r.`barangay_id` = 1 AND r.`email` = 'resident.demo@example.com'
  )
LIMIT 1;

-- To add a second permit type later (example):
-- INSERT INTO `permit_types` (`name`, `description`, `is_active`)
-- VALUES ('Barangay Business Clearance', 'Clearance for business registration.', 1)
-- ON DUPLICATE KEY UPDATE `is_active` = VALUES(`is_active`);
