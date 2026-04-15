-- Migration 007: seeds
-- Seeds one admin user and the v1 Barangay Clearance permit type.
--
-- IMPORTANT: Change the admin password before any real use.
-- The hash below is bcrypt for the placeholder 'ChangeMe2026!'
-- Generate your own: php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT);"
--
-- The legacy `UserName` / `PaSS` columns are kept until you drop them
-- (see migration 001 note). password_hash is what the new auth uses.

SET NAMES utf8mb4;

-- Seed admin user
-- barangay_id NULL → super-admin (spans all barangays)
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

-- Seed v1 permit type: Barangay Clearance
INSERT INTO `permit_types` (`name`, `description`, `is_active`)
VALUES (
  'Barangay Clearance',
  'General-purpose barangay clearance certificate issued to residents.',
  1
)
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `is_active`   = VALUES(`is_active`);

-- To add a second permit type later (example):
-- INSERT INTO `permit_types` (`name`, `description`, `is_active`)
-- VALUES ('Barangay Business Clearance', 'Clearance for business registration.', 1)
-- ON DUPLICATE KEY UPDATE `is_active` = VALUES(`is_active`);
