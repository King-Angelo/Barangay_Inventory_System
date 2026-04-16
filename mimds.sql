-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2026 at 03:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mimds`
--

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `brgy` varchar(50) NOT NULL,
  `n` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`brgy`, `n`) VALUES
('Agus-OS', 1),
('Alulod', 2),
('Banaba Cerca', 3),
('Banaba Lejos', 4),
('Bancod', 5),
('Buna Cerca', 6),
('Buna Lejos 1', 7),
('Buna Lejos 2', 8),
('Calumpang Cerca', 9),
('Calumpang Lejos', 10),
('Carasuchi', 11),
('Daine 1', 12),
('Daine 2', 13),
('Guyanm Malaki', 14),
('Guyam Munti', 15),
('Harasan', 16),
('Kayquit 1', 17),
('Kayquit 2', 18),
('Kayquit 3', 19),
('Kaytambog', 20),
('Kaytapos', 21),
('Limbon', 22),
('Lumampong Balagbag', 23),
('Lumampong Halayhay', 24),
('Mahabang Kahoy Cerca', 25),
('Mahabang Kahoy Lejos', 26),
('Mataas na Lupa', 27),
('Pulo', 28),
('Tambo Balagtag', 29),
('Tambog Ilaya', 30),
('Tambo kulit', 31),
('Tambo Malaki', 32),
('Barangay 1 (Poblacion)', 33),
('Barangay 2(Poblacion)', 34),
('Barangay 3 (Poblacion)', 35),
('Barangay 4 (Poblacion)', 36);

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `n` int(11) NOT NULL,
  `itemcode` varchar(50) NOT NULL,
  `itemname` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `transaction` text NOT NULL,
  `receiver` varchar(50) NOT NULL,
  `transactionD` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`n`, `itemcode`, `itemname`, `quantity`, `transaction`, `receiver`, `transactionD`) VALUES
(1, '4', 'chair', 0, '', 'Trisha', '2019-05-07'),
(2, '4', 'chair', 1, '', 'Trisha', '2019-05-07'),
(3, '1', 'biogesic', 1, '', 'Trisha', '2019-05-07'),
(4, '2', 'bioflu', 2, '', 'Trisha', '2019-05-07'),
(15, '1', 'biogesic', 1, '', 'Trisha', '2019-05-07'),
(16, '2', 'bioflu', 2, '', 'Trisha', '2019-05-07'),
(17, '1', 'biogesic', 5, '', 'Trisha', '2019-05-13'),
(18, '2', 'bioflu', 1, '', 'Cyrill', '2019-05-13'),
(19, '2', 'bioflu', 1, '', 'Trisha', '2019-05-13'),
(20, '5', 'Paracetamol', 100, '', 'Cyrill', '2019-05-13');

-- --------------------------------------------------------

--
-- Table structure for table `medication`
--

CREATE TABLE `medication` (
  `beneficiary` varchar(50) NOT NULL,
  `iname` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `n` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medsupply`
--

CREATE TABLE `medsupply` (
  `n` int(11) NOT NULL,
  `iname` varchar(50) NOT NULL,
  `category` text NOT NULL,
  `quantity` int(11) NOT NULL,
  `expdate` date NOT NULL,
  `datereceived` date NOT NULL,
  `itemcode` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `medsupply`
--

INSERT INTO `medsupply` (`n`, `iname`, `category`, `quantity`, `expdate`, `datereceived`, `itemcode`) VALUES
(1, 'biogesic', 'medicine', 500, '2020-05-23', '2019-05-04', 'med1212'),
(2, 'bioflu', 'medicine', 800, '2020-05-31', '2019-05-04', 'med12212'),
(3, 'Paracetamol', 'Medicine', 100, '2020-05-09', '2019-05-08', ''),
(4, 'Wheel Chair', 'Supply', 100, '2020-05-01', '2019-04-30', ''),
(5, 'Paracetamol', 'Medicine', 100, '2021-01-01', '2019-01-01', '');

-- --------------------------------------------------------

--
-- Table structure for table `medsuppy0`
--

CREATE TABLE `medsuppy0` (
  `n` int(11) NOT NULL,
  `iname` int(11) NOT NULL,
  `category` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `Lname` varchar(50) NOT NULL,
  `Fname` varchar(50) NOT NULL,
  `Mname` varchar(50) NOT NULL,
  `addres` varchar(100) NOT NULL,
  `Birthdate` date NOT NULL,
  `age` int(11) NOT NULL,
  `Gender` varchar(2) NOT NULL,
  `Medication` text NOT NULL,
  `n` int(11) NOT NULL,
  `brgy` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`Lname`, `Fname`, `Mname`, `addres`, `Birthdate`, `age`, `Gender`, `Medication`, `n`, `brgy`) VALUES
('Mendoza', 'Alexis', 'Garcia', '', '1996-01-01', 23, 'M', '', 1, 'Alulod'),
('Mendoza', 'Trisha', 'Garcia', '', '1997-05-01', 22, 'F', '', 2, 'Alulod'),
('Mendoza', 'Cyrill John', 'Garcia', '', '1998-05-02', 21, '', '', 3, ''),
('Tongson', 'Leo John', 'Garcia', '', '1998-07-21', 20, 'M', '', 7, 'Alulod'),
('Son', 'Lalaine', 'Mendoza', '', '1999-05-03', 20, 'F', '', 8, 'Alulod'),
('Galang', 'Mark', 'John', 'Blk3 lot3', '1999-05-01', 20, 'M', '', 9, '');

-- --------------------------------------------------------

--
-- Table structure for table `permits`
--

CREATE TABLE `permits` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `permit_type_id` int(11) NOT NULL,
  `reference_no` varchar(50) NOT NULL COMMENT 'Human-readable permit reference (search target)',
  `status` enum('draft','submitted','approved','rejected','ready_for_payment','paid','issued') NOT NULL DEFAULT 'draft',
  `submitted_by` int(11) DEFAULT NULL COMMENT 'FK → users.id (staff who submitted)',
  `approved_by` int(11) DEFAULT NULL COMMENT 'FK → users.id (admin who approved/rejected)',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permit/clearance applications. Staff submits; admin approves/rejects.';

--
-- Dumping data for table `permits`
--

INSERT INTO `permits` (`id`, `resident_id`, `permit_type_id`, `reference_no`, `status`, `submitted_by`, `approved_by`, `submitted_at`, `approved_at`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'REF-55A4205D', 'submitted', 5, NULL, '2026-04-12 13:13:50', NULL, NULL, '2026-04-12 13:13:32', '2026-04-12 13:13:50');

-- --------------------------------------------------------

--
-- Table structure for table `permit_types`
--

CREATE TABLE `permit_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lookup table for permit/clearance types. v1: Barangay Clearance only.';

--
-- Dumping data for table `permit_types`
--

INSERT INTO `permit_types` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Barangay Clearance', 'General-purpose barangay clearance certificate issued to residents.', 1, '2026-04-12 12:53:54');

-- --------------------------------------------------------

--
-- Table structure for table `released`
--

CREATE TABLE `released` (
  `n` int(11) NOT NULL,
  `ben` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `released`
--

INSERT INTO `released` (`n`, `ben`) VALUES
(1, '2');

-- --------------------------------------------------------

--
-- Table structure for table `releaselogs`
--

CREATE TABLE `releaselogs` (
  `bname` varchar(50) NOT NULL,
  `medicine` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `DateRec` date NOT NULL,
  `n` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `releaselogs`
--

INSERT INTO `releaselogs` (`bname`, `medicine`, `quantity`, `DateRec`, `n`) VALUES
('2', '2', 1, '2019-05-13', 1);

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL COMMENT 'Required for SMTP notifications',
  `phone` varchar(30) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `address_line` text DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_by_user_id` int(11) NOT NULL COMMENT 'FK → users.id (staff who created)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master resident record. Admin-only archive. No hard delete in v1.';

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `barangay_id`, `last_name`, `first_name`, `middle_name`, `email`, `phone`, `birthdate`, `gender`, `address_line`, `status`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'vicente', 'clark', '', 'clark@gmail.com', '1123123123131', '0000-00-00', 'Male', '', 'archived', 5, '2026-04-12 13:10:28', '2026-04-12 13:11:33'),
(2, 1, 'clark', 'vicente', '', 'clarkk@gmail.com', '', '0000-00-00', 'Male', '', 'active', 5, '2026-04-12 13:12:36', '2026-04-12 13:12:36');

-- --------------------------------------------------------

--
-- Table structure for table `rhusupply`
--

CREATE TABLE `rhusupply` (
  `n` int(11) NOT NULL,
  `iname` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `expdate` date NOT NULL,
  `datereceived` date NOT NULL,
  `itemcode` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `rhusupply`
--

INSERT INTO `rhusupply` (`n`, `iname`, `category`, `quantity`, `expdate`, `datereceived`, `itemcode`) VALUES
(0, 'bioflu', 'medicine', 2, '2019-05-31', '2019-05-13', '2'),
(0, 'Paracetamol', 'Medicine', 100, '2021-01-01', '2019-05-13', '5');

-- --------------------------------------------------------

--
-- Table structure for table `rhusupply0`
--

CREATE TABLE `rhusupply0` (
  `n` int(11) NOT NULL,
  `iname` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supply2`
--

CREATE TABLE `supply2` (
  `beneficiary` varchar(50) NOT NULL,
  `medicine` varchar(50) NOT NULL,
  `quanti` int(11) NOT NULL,
  `n` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `supply2`
--

INSERT INTO `supply2` (`beneficiary`, `medicine`, `quanti`, `n`) VALUES
('2', '2', 1, 5);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `UserName` varchar(50) NOT NULL,
  `PaSS` varchar(50) NOT NULL,
  `role` enum('staff','admin') NOT NULL DEFAULT 'staff',
  `password_hash` varchar(255) DEFAULT NULL COMMENT 'bcrypt via password_hash(). Migrate from PaSS.',
  `barangay_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `UserName`, `PaSS`, `role`, `password_hash`, `barangay_id`, `created_at`, `updated_at`) VALUES
(1, 'Cj233', 'Cj23', 'staff', NULL, NULL, '2026-04-12 12:52:51', '2026-04-12 12:52:51'),
(2, 'clark', 'clark', 'staff', NULL, NULL, '2026-04-12 12:52:51', '2026-04-12 12:52:51'),
(3, 'qwertyyyy', '', 'staff', NULL, NULL, '2026-04-12 12:52:51', '2026-04-12 12:52:51'),
(4, 'admin', 'admin123', 'admin', '$2y$10$vl3mbjJbmxS51ZwKFjArlerRFtlIo41xhTf8vP6DaaBlye03MsWuy', NULL, '2026-04-12 12:53:54', '2026-04-12 13:02:50'),
(5, 'staff1', 'staff123', 'staff', NULL, NULL, '2026-04-12 12:55:03', '2026-04-12 12:55:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`n`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`n`);

--
-- Indexes for table `medication`
--
ALTER TABLE `medication`
  ADD PRIMARY KEY (`n`);

--
-- Indexes for table `medsupply`
--
ALTER TABLE `medsupply`
  ADD PRIMARY KEY (`n`);

--
-- Indexes for table `medsuppy0`
--
ALTER TABLE `medsuppy0`
  ADD PRIMARY KEY (`n`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`n`);

--
-- Indexes for table `permits`
--
ALTER TABLE `permits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reference_no` (`reference_no`),
  ADD KEY `idx_resident` (`resident_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_submitted_by` (`submitted_by`),
  ADD KEY `idx_approved_by` (`approved_by`),
  ADD KEY `fk_permits_type` (`permit_type_id`);

--
-- Indexes for table `permit_types`
--
ALTER TABLE `permit_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permit_type_name` (`name`);

--
-- Indexes for table `released`
--
ALTER TABLE `released`
  ADD PRIMARY KEY (`n`);

--
-- Indexes for table `releaselogs`
--
ALTER TABLE `releaselogs`
  ADD PRIMARY KEY (`n`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_resident_brgy_email` (`barangay_id`,`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_name` (`last_name`,`first_name`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by_user_id`);

--
-- Indexes for table `rhusupply0`
--
ALTER TABLE `rhusupply0`
  ADD PRIMARY KEY (`n`);

--
-- Indexes for table `supply2`
--
ALTER TABLE `supply2`
  ADD PRIMARY KEY (`n`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_username` (`UserName`),
  ADD KEY `fk_users_barangay` (`barangay_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `n` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `n` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `medication`
--
ALTER TABLE `medication`
  MODIFY `n` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medsupply`
--
ALTER TABLE `medsupply`
  MODIFY `n` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `medsuppy0`
--
ALTER TABLE `medsuppy0`
  MODIFY `n` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `n` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `permits`
--
ALTER TABLE `permits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permit_types`
--
ALTER TABLE `permit_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `released`
--
ALTER TABLE `released`
  MODIFY `n` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `releaselogs`
--
ALTER TABLE `releaselogs`
  MODIFY `n` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supply2`
--
ALTER TABLE `supply2`
  MODIFY `n` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `permits`
--
ALTER TABLE `permits`
  ADD CONSTRAINT `fk_permits_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_permits_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_permits_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_permits_type` FOREIGN KEY (`permit_type_id`) REFERENCES `permit_types` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `residents`
--
ALTER TABLE `residents`
  ADD CONSTRAINT `fk_residents_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`n`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_residents_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`n`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
