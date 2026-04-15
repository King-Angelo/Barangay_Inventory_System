-- Optional: one draft permit for API/UI regression tests (no PII).
-- Prerequisites: migrations 001–007 applied; demo resident from 007 (resident.demo@example.com).
-- Idempotent: skips if reference_no DEV-REF-SAMPLE-001 already exists.
-- Not part of the numbered migration chain — run manually when needed.

SET NAMES utf8mb4;

INSERT INTO `permits` (
  `resident_id`,
  `permit_type_id`,
  `reference_no`,
  `status`,
  `submitted_by`
)
SELECT
  r.`id`,
  pt.`id`,
  'DEV-REF-SAMPLE-001',
  'draft',
  u.`id`
FROM `residents` r
INNER JOIN `permit_types` pt ON pt.`name` = 'Barangay Clearance' AND pt.`is_active` = 1
INNER JOIN `users` u ON u.`UserName` = 'staff_dev'
WHERE r.`email` = 'resident.demo@example.com'
  AND r.`barangay_id` = 1
  AND NOT EXISTS (
    SELECT 1 FROM `permits` p WHERE p.`reference_no` = 'DEV-REF-SAMPLE-001'
  )
LIMIT 1;
