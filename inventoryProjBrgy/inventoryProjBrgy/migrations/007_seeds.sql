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
