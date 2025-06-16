-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2025 at 08:35 PM
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
-- Database: `concert_tickets`
--

-- --------------------------------------------------------

--
-- Table structure for table `concert`
--

CREATE TABLE `concert` (
  `concertId` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `showTime` datetime NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `max_tickets` int(11) NOT NULL DEFAULT 100,
  `imageUrl` varchar(255) DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `concert`
--

INSERT INTO `concert` (`concertId`, `title`, `description`, `showTime`, `price`, `max_tickets`, `imageUrl`, `dateCreated`, `lastUpdated`) VALUES
(1, 'Rock Festival', 'An amazing rock music festival featuring top bands.', '2025-07-01 19:00:00', 2000.00, 100, 'images/rock.png', '2025-06-09 09:00:02', '2025-06-09 09:00:02'),
(2, 'Jazz Night', 'A smooth night of jazz music with renowned performers.', '2025-07-05 20:00:00', 2.00, 100, 'images/jazz.png', '2025-06-09 09:00:02', '2025-06-11 08:01:35'),
(3, 'Pop Concert', 'Enjoy the latest pop hits live on stage.', '2025-07-10 18:00:00', 1800.00, 100, 'images/pop.jpg', '2025-06-09 09:00:02', '2025-06-09 09:00:02'),
(4, 'Classical Evening', 'An evening of timeless classical music.', '2025-07-15 20:00:00', 1200.00, 100, 'images/classic.png', '2025-06-09 09:00:02', '2025-06-09 09:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `pushrequest`
--

CREATE TABLE `pushrequest` (
  `pushRequestId` int(11) NOT NULL,
  `ticketId` int(11) NOT NULL,
  `checkoutRequestId` varchar(255) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pushrequest`
--

INSERT INTO `pushrequest` (`pushRequestId`, `ticketId`, `checkoutRequestId`, `dateCreated`, `lastUpdated`) VALUES
(1, 1, 'ws_CO_10062025221956617706975340', '2025-06-10 19:19:59', '2025-06-10 19:19:59'),
(2, 2, 'ws_CO_11062025102103456751459129', '2025-06-11 07:18:55', '2025-06-11 07:18:55'),
(3, 3, 'ws_CO_11062025104940011706975340', '2025-06-11 07:47:32', '2025-06-11 07:47:32'),
(4, 4, 'ws_CO_11062025105015404724550426', '2025-06-11 07:50:17', '2025-06-11 07:50:17'),
(5, 5, 'ws_CO_11062025110217796706975340', '2025-06-11 08:02:19', '2025-06-11 08:02:19'),
(6, 6, 'ws_CO_11062025135329220718469746', '2025-06-11 10:51:21', '2025-06-11 10:51:21'),
(7, 15, 'ws_CO_14062025121607926706975340', '2025-06-14 09:16:17', '2025-06-14 09:16:17'),
(8, 16, 'ws_CO_14062025121743284700534098', '2025-06-14 09:17:53', '2025-06-14 09:17:53'),
(9, 17, 'ws_CO_14062025122429348720869261', '2025-06-14 09:24:39', '2025-06-14 09:24:39'),
(10, 18, 'ws_CO_14062025131410162706975340', '2025-06-14 10:12:01', '2025-06-14 10:12:01'),
(11, 19, 'ws_CO_16062025212840768706975340', '2025-06-16 18:28:47', '2025-06-16 18:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

CREATE TABLE `ticket` (
  `ticketId` int(11) NOT NULL,
  `concertId` int(11) NOT NULL,
  `customerName` varchar(255) NOT NULL,
  `phoneNumber` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `totalAmount` decimal(10,2) NOT NULL,
  `paymentStatus` enum('Pending','Paid','Failed') NOT NULL DEFAULT 'Pending',
  `mpesaReceiptNumber` varchar(100) DEFAULT NULL,
  `transactionDate` datetime DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket`
--

INSERT INTO `ticket` (`ticketId`, `concertId`, `customerName`, `phoneNumber`, `quantity`, `totalAmount`, `paymentStatus`, `mpesaReceiptNumber`, `transactionDate`, `dateCreated`, `lastUpdated`) VALUES
(1, 2, 'Brian', '254706975340', 3, 4500.00, 'Pending', NULL, NULL, '2025-06-10 19:19:55', '2025-06-10 19:19:55'),
(2, 2, 'Brian', '254751459129', 3, 4500.00, 'Pending', NULL, NULL, '2025-06-11 07:18:53', '2025-06-11 07:18:53'),
(3, 1, 'Brian', '254706975340', 1, 2000.00, 'Pending', NULL, NULL, '2025-06-11 07:47:30', '2025-06-11 07:47:30'),
(4, 1, 'Winnie', '254724550426', 1, 2000.00, 'Pending', NULL, NULL, '2025-06-11 07:50:15', '2025-06-11 07:50:15'),
(5, 2, 'Brian', '254706975340', 1, 2.00, 'Pending', NULL, NULL, '2025-06-11 08:02:17', '2025-06-11 08:02:17'),
(6, 2, 'Nick', '254718469746', 3, 6.00, 'Pending', NULL, NULL, '2025-06-11 10:51:19', '2025-06-11 10:51:19'),
(7, 2, 'Nick', '254707703492', 3, 6.00, 'Failed', NULL, NULL, '2025-06-11 15:29:39', '2025-06-11 15:29:39'),
(8, 2, 'Vincent', '254707703492', 3, 6.00, 'Failed', NULL, NULL, '2025-06-11 15:30:03', '2025-06-11 15:30:03'),
(9, 1, 'Vincent', '254707703492', 1, 2000.00, 'Failed', NULL, NULL, '2025-06-11 15:30:34', '2025-06-11 15:30:34'),
(10, 1, 'Vincent', '254707703492', 1, 2000.00, 'Failed', NULL, NULL, '2025-06-11 15:31:04', '2025-06-11 15:31:04'),
(11, 1, 'Vincent', '254707703492', 1, 2000.00, 'Failed', NULL, NULL, '2025-06-11 15:32:16', '2025-06-11 15:32:16'),
(12, 1, 'Vincent', '254707703492', 1, 2000.00, 'Failed', NULL, NULL, '2025-06-11 15:32:23', '2025-06-11 15:32:23'),
(13, 1, 'Vincent', '254707703492', 1, 2000.00, 'Failed', NULL, NULL, '2025-06-11 15:32:47', '2025-06-11 15:32:48'),
(14, 1, 'Brian', '254706975340', 1, 2000.00, 'Failed', NULL, NULL, '2025-06-11 15:33:29', '2025-06-11 15:33:29'),
(15, 1, 'Brian', '254706975340', 1, 2000.00, 'Pending', NULL, NULL, '2025-06-14 09:16:15', '2025-06-14 09:16:15'),
(16, 1, 'Faith', '254700534098', 1, 2000.00, 'Pending', NULL, NULL, '2025-06-14 09:17:50', '2025-06-14 09:17:50'),
(17, 2, 'Emmy', '254720869261', 1, 2.00, 'Pending', NULL, NULL, '2025-06-14 09:24:37', '2025-06-14 09:24:37'),
(18, 2, 'Brian', '254706975340', 1, 2.00, 'Pending', NULL, NULL, '2025-06-14 10:11:59', '2025-06-14 10:11:59'),
(19, 1, 'Brian', '254706975340', 1, 2000.00, 'Pending', NULL, NULL, '2025-06-16 18:28:43', '2025-06-16 18:28:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `concert`
--
ALTER TABLE `concert`
  ADD PRIMARY KEY (`concertId`);

--
-- Indexes for table `pushrequest`
--
ALTER TABLE `pushrequest`
  ADD PRIMARY KEY (`pushRequestId`),
  ADD KEY `fk_PushRequest_Ticket` (`ticketId`);

--
-- Indexes for table `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`ticketId`),
  ADD KEY `fk_Ticket_Concert` (`concertId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `concert`
--
ALTER TABLE `concert`
  MODIFY `concertId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pushrequest`
--
ALTER TABLE `pushrequest`
  MODIFY `pushRequestId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `ticket`
--
ALTER TABLE `ticket`
  MODIFY `ticketId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pushrequest`
--
ALTER TABLE `pushrequest`
  ADD CONSTRAINT `fk_PushRequest_Ticket` FOREIGN KEY (`ticketId`) REFERENCES `ticket` (`ticketId`) ON DELETE CASCADE;

--
-- Constraints for table `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `fk_Ticket_Concert` FOREIGN KEY (`concertId`) REFERENCES `concert` (`concertId`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
