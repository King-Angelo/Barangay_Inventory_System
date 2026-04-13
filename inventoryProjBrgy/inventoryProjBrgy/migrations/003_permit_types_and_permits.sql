-- Migration 003: permit_types and permits
-- v1 permit type: Barangay Clearance only (seeded in 007_seed.sql)
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
