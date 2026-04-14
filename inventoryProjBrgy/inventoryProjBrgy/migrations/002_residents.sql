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
