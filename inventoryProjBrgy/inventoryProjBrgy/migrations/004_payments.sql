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
