-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2026 at 04:06 PM
-- Server version: 10.1.25-MariaDB
-- PHP Version: 7.1.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
-- Table structure for table `released`
--

CREATE TABLE `released` (
  `n` int(11) NOT NULL,
  `ben` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `releaselogs`
--

INSERT INTO `releaselogs` (`bname`, `medicine`, `quantity`, `DateRec`, `n`) VALUES
('2', '2', 1, '2019-05-13', 1);

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `supply2`
--

CREATE TABLE `supply2` (
  `beneficiary` varchar(50) NOT NULL,
  `medicine` varchar(50) NOT NULL,
  `quanti` int(11) NOT NULL,
  `n` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `UserName` varchar(50) NOT NULL,
  `PaSS` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserName`, `PaSS`) VALUES
('Cj233', 'Cj23'),
('qwertyyyy', '');

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
  ADD PRIMARY KEY (`UserName`);

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
-- AUTO_INCREMENT for table `supply2`
--
ALTER TABLE `supply2`
  MODIFY `n` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
