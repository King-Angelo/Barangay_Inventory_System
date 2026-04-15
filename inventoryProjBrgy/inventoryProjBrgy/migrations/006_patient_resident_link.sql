-- Migration 006: link legacy `patient` to `residents` (optional, nullable)
-- Context: migrations/LEGACY_AND_RESIDENTS.md (patient vs medsupply vs residents).
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
