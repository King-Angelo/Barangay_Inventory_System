-- Read-only sanity check after migrations (no DDL/DML).
-- Usage: mysql -u root -p mimds < migrations/verify_schema.sql

SET NAMES utf8mb4;

SELECT 'table: barangays' AS check_item, COUNT(*) AS row_count FROM `barangays`
UNION ALL SELECT 'table: users', COUNT(*) FROM `users`
UNION ALL SELECT 'table: residents', COUNT(*) FROM `residents`
UNION ALL SELECT 'table: permit_types', COUNT(*) FROM `permit_types`
UNION ALL SELECT 'table: permits', COUNT(*) FROM `permits`
UNION ALL SELECT 'table: payments', COUNT(*) FROM `payments`
UNION ALL SELECT 'table: integration_events', COUNT(*) FROM `integration_events`
UNION ALL SELECT 'table: notification_log', COUNT(*) FROM `notification_log`;

SELECT 'users.role' AS check_item, `role`, COUNT(*) AS n
FROM `users`
GROUP BY `role`
ORDER BY `role`;

SELECT
  'residents.uq_resident_brgy_email' AS check_item,
  CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'residents'
      AND index_name = 'uq_resident_brgy_email'
  ) THEN 'OK' ELSE 'MISSING' END AS status;
