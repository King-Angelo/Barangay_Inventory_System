-- Create permit type, permit, and integration event tables for the API.
-- Run this after creating the residents table.

CREATE TABLE IF NOT EXISTS permit_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permit_types (name, description)
SELECT 'Barangay Clearance', 'Barangay clearance permit type for v1'
WHERE NOT EXISTS (SELECT 1 FROM permit_types WHERE name = 'Barangay Clearance');

CREATE TABLE IF NOT EXISTS permits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resident_id INT NOT NULL,
  permit_type_id INT NOT NULL DEFAULT 1,
  reference_no VARCHAR(50) NOT NULL UNIQUE,
  status ENUM('draft','submitted','approved','rejected','ready_for_payment','paid','issued') NOT NULL DEFAULT 'draft',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_by_user VARCHAR(50) NULL,
  approved_by_user VARCHAR(50) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
  FOREIGN KEY (permit_type_id) REFERENCES permit_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS integration_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(100) NOT NULL,
  payload JSON NOT NULL,
  status ENUM('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
