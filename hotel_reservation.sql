-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2025 at 03:50 AM
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
-- Database: `hotel_reservation`
--

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`id`, `first_name`, `last_name`, `email`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Edrei Josh', 'Cruz', 'ejcruz@gmail.com', '(095) 612-3451', 'Victorias City, Negros Occidental', '2025-12-16 15:42:44', '2025-12-16 15:42:44'),
(2, 'Trix Justin', 'Aguilar', 'vaguejusting@gmail.com', '(091) 124-1251', 'Victorias City, Negros Occidental', '2025-12-16 16:34:13', '2025-12-16 16:34:13'),
(3, 'Jeron', 'Opjer', 'kaitoshop@gmail.com', '(063) 312-2312', 'Victorias City, Negros Occidental', '2025-12-16 16:46:58', '2025-12-16 16:46:58'),
(4, 'Kheian', 'Roldan', 'kheian@gmail.com', '(239) 889-1958', 'Victorias City, Negros Occidental', '2025-12-17 00:25:09', '2025-12-17 02:49:15'),
(5, 'Francis', 'Sase', 'francis@gmail.com', '(111) 861-7961', 'Victorias City, Negros Occidental', '2025-12-17 00:48:27', '2025-12-17 00:48:27');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `number_of_guests` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `guest_id`, `room_id`, `check_in`, `check_out`, `number_of_guests`, `total_amount`, `status`, `special_requests`, `created_at`, `updated_at`) VALUES
(1, 1, 3, '2025-12-16', '2025-12-17', 1, 4500.00, 'checked_out', '', '2025-12-16 15:45:34', '2025-12-17 00:43:10'),
(2, 2, 6, '2025-12-16', '2025-12-17', 1, 12000.00, 'checked_out', '', '2025-12-16 16:35:05', '2025-12-17 00:43:13'),
(3, 3, 4, '2025-12-17', '2025-12-18', 2, 5500.00, 'checked_out', '', '2025-12-16 16:47:42', '2025-12-17 00:43:16'),
(4, 4, 5, '2025-12-19', '2025-12-20', 3, 7500.00, 'cancelled', '', '2025-12-17 00:25:48', '2025-12-17 00:28:17'),
(5, 4, 5, '2025-12-17', '2025-12-18', 2, 7500.00, 'checked_out', 'Room should have scented candles for the guest', '2025-12-17 00:32:17', '2025-12-17 00:43:19'),
(6, 5, 1, '2025-12-19', '2025-12-20', 1, 2500.00, 'cancelled', '', '2025-12-17 00:49:32', '2025-12-17 01:16:18'),
(7, 1, 4, '2025-12-17', '2025-12-18', 1, 5500.00, 'checked_out', '', '2025-12-17 00:50:57', '2025-12-17 01:29:56'),
(8, 1, 1, '2025-12-17', '2025-12-18', 2, 2500.00, 'checked_in', '', '2025-12-17 01:27:19', '2025-12-17 01:29:07'),
(9, 3, 2, '2025-12-17', '2025-12-18', 2, 2500.00, 'checked_in', '', '2025-12-17 01:28:17', '2025-12-17 01:29:10');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `floor` int(11) NOT NULL,
  `bed_type` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL,
  `amenities` text DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','maintenance','reserved') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `floor`, `bed_type`, `capacity`, `amenities`, `price_per_night`, `status`, `created_at`, `updated_at`) VALUES
(1, '101', 'Standard', 1, 'Double', 2, 'TV, WiFi, AC, Mini-fridge', 2500.00, 'occupied', '2025-12-16 14:56:01', '2025-12-17 01:29:07'),
(2, '102', 'Standard', 1, 'Double', 2, 'TV, WiFi, AC, Mini-fridge', 2500.00, 'occupied', '2025-12-16 14:56:01', '2025-12-17 01:29:10'),
(3, '201', 'Deluxe', 2, 'King', 3, 'TV, WiFi, AC, Mini-bar, Balcony', 4500.00, 'available', '2025-12-16 14:56:01', '2025-12-17 00:43:10'),
(4, '202', 'Deluxe', 2, 'Queen', 2, 'TV, WiFi, AC, Mini-bar, Jacuzzi', 5500.00, 'available', '2025-12-16 14:56:01', '2025-12-17 01:29:56'),
(5, '301', 'Suite', 3, 'King + Queen', 4, 'TV, WiFi, AC, Kitchenette, Living Room, Balcony', 7500.00, 'available', '2025-12-16 14:56:01', '2025-12-17 00:43:19'),
(6, '302', 'Presidential', 3, 'Super King', 2, 'TV, WiFi, AC, Kitchen, Living Room, Jacuzzi, Butler Service', 12000.00, 'available', '2025-12-16 14:56:01', '2025-12-17 00:43:13'),
(7, '312', 'Presidential', 3, 'King', 2, 'TV, WiFi, AC, Kitchenette, Living Room, Balcony', 12000.00, 'available', '2025-12-17 00:46:49', '2025-12-17 00:46:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_guests_email` (`email`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `idx_reservations_dates` (`check_in`,`check_out`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `idx_rooms_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
