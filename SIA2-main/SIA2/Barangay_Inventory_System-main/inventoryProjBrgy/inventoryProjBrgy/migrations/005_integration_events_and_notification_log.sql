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
