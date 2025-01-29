-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 29, 2025 at 06:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sigma2k25`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `eventName` varchar(200) NOT NULL,
  `playerRegno` varchar(50) NOT NULL,
  `credits` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `played` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `eventName`, `playerRegno`, `credits`, `score`, `played`) VALUES
(1, 'TempleRun', '22B91A6206', 2, 2, 2),
(2, 'WW', 'WW', 2, 2, 1),
(3, 'f', 'f', 2, 2, 4),
(4, 'ss', 'ss', 2, 2, 2),
(5, 'Free Fire', '22B91A6206', 100, 0, 1),
(9, 'Squid', '22B91A6206', 150, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `email` text NOT NULL,
  `phoneNumber` bigint(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `regNo` varchar(50) NOT NULL,
  `branch` varchar(10) NOT NULL,
  `year` int(11) NOT NULL,
  `credits` int(11) DEFAULT NULL,
  `eventsPlayed` int(11) DEFAULT NULL,
  `uniqueId` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `email`, `phoneNumber`, `name`, `regNo`, `branch`, `year`, `credits`, `eventsPlayed`, `uniqueId`) VALUES
(2, 'bvst27@gmail.com', 9347923953, 'Teja', '22B91A6206', 'CSD', 3, 350, 0, '22B91A6206SIGMA2K25CSD&CSIT'),
(7, 'bvst271@gmail.com', 1234567890, 'Budde Venkata Satya tejesh', '22B91A6203', 'eluru', 3, 0, 0, '22B91A6203SIGMA2K25No980'),
(8, 'e@gmail.com', 111, 'e', 'w', 'w', 1, 0, 0, 'wSIGMA2K25No207'),
(9, 'e1@gmail.com', 1111, 'e', 'w1', 'w', 1, 111, 0, 'w1SIGMA2K25No278'),
(10, 's@gmail.com', 11, 's', '2222', '11', 1, 2, 0, '2222SIGMA2K25No846'),
(11, 's1@gmail.com', 11, 's', '22221', '11', 1, 2, 0, '22221SIGMA2K25No250'),
(12, 's11@gmail.com', 111, 's', '222211', '11', 1, 2, 0, '222211SIGMA2K25No076'),
(13, 'afzalpurkar@gmail.com', 1234567890, 'Budde Venkata Satya tejesh', '22B91A6201', 'CSD', 1, 1, 0, '22B91A6201SIGMA2K25No304'),
(14, 'afzalpurk1ar@gmail.com', 1234567890, 'Budde Venkata Satya tejesh', '22B91A611', 'CSD', 1, 1, 0, '22B91A611SIGMA2K25No492'),
(15, 'afzalpaurk1ar@gmail.com', 1234567890, 'Budde Venkata Satya tejesh', '22B91A6111', 'CSD', 1, 1, 0, '22B91A6111SIGMA2K25No769'),
(16, 'bvst2117@gmail.com', 111, 'Budde Venkata Satya tejesh', '111', '11', 1, 1, 0, '111SIGMA2K25No847'),
(17, 'afzalapurkar@gmail.com', 1234567890, 'Budde Venkata Satya tejesh', '22B9111', 'CSD', 3, 1, 0, '22B9111SIGMA2K25No949'),
(18, 'venkatasatyatejeshbudde@gmail.com', 9347923953, 'Budde Venkata Satya tejesh', '22B91A62qw', 'CSD', 3, 3, 0, '22B91A62qwSIGMA2K25No204'),
(19, 'bvst211117@gmail.com', 111, 'Budde Venkata Satya tejesh', '1111', '1', 1, 1, 0, '1111SIGMA2K25No968'),
(20, 'bvst27@gmail.com2', 1234567890, 'Budde Venkata Satya tejesh', '22B91A62012', 'CSD', 3, 3, 0, '22B91A62012SIGMA2K25No547'),
(21, '11@gmail.com', 12345123, 'Budde Venkata Satya tejesh', '22B111', 'CSD', 1, 1, 0, '22B111SIGMA2K25No303'),
(22, '111@gmail.com', 12345123, 'Budde Venkata Satya tejesh', '22B1111', 'CSD', 1, 1, 0, '22B1111SIGMA2K25No146'),
(23, '123@gmail.com', 1234, 'Budde Venkata Satya tejesh', '2222111', '3', 1, 1, 0, '2222111SIGMA2K25No298'),
(24, 'bvst27123@gmail.com', 1234567890, 'Budde Venkata Satya tejesh', '22B91A620612', 'CSD', 2, 200, 0, '22B91A620612SIGMA2K25No335'),
(25, 'bvst271234@gmail.com', 1234567890, 'Budde Venkata Satya tejesh', 'bvst271234', 'CSD', 2, 200, 0, 'bvst271234SIGMA2K25No875'),
(26, 'bvs12t27@gmail.com', 1234567890, 'Budde Venkata Satya tejesh', '22B91A6212', 'CSD', 3, 333, 0, '22B91A6212SIGMA2K25No077'),
(27, 'bvst227@gmail.com', 9347923953, 'teja', '22b921a6206', 'cssd', 2, 0, 0, '22b921a6206SIGMA2K25No133'),
(28, 'bv13st27@gmail.com', 9347923953, 'teja', '22b91a6213', 'cssd', 2, 0, 0, '22b91a6213SIGMA2K25No279'),
(29, 'bvssst27@gmail.com', 9347923953, 'teja', '22b91a6sss', 'cssd', 2, 0, 0, '22b91a6sssSIGMA2K25No758'),
(30, 'bvst2aaaa7@gmail.com', 9347923953, 'teja', '22b91aaaaa', 'cssd', 2, 0, 0, '22b91aaaaaSIGMA2K25No709'),
(31, 'bvsasast27@gmail.com', 9347923953, 'teja', '22b91sasa', 'cssd', 2, 0, 0, '22b91sasaSIGMA2K25No500');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `regNo` (`regNo`),
  ADD UNIQUE KEY `uniqueId` (`uniqueId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
