-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jul 16, 2026 at 01:26 AM
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
-- Database: `dental_portal_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','staff') DEFAULT 'admin',
  `avatar_path` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `full_name`, `email`, `username`, `password_hash`, `role`, `avatar_path`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'Administrator', 'admin@dentalportal.com', 'admin', '$2b$10$xMA/58bxEg0VO6zXCvzadujmkLZkns55kXNA1NazOVjORMdDqXTFO', 'super_admin', NULL, 1, '2026-07-16 06:42:25', '2026-07-16 06:04:11');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `appointment_type` enum('trial','follow_up','final','consultation') DEFAULT 'trial',
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('scheduled','done','cancelled','rescheduled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `customer_id`, `service_id`, `appointment_type`, `appointment_date`, `appointment_time`, `status`, `notes`, `date_created`) VALUES
(1, 1, 1, 'trial', '2026-04-05', '10:00:00', 'scheduled', NULL, '2026-07-16 06:04:06'),
(2, 2, 2, 'final', '2026-04-08', '14:00:00', 'done', NULL, '2026-07-16 06:04:06'),
(3, 3, 3, 'consultation', '2026-04-10', '09:00:00', 'scheduled', NULL, '2026-07-16 06:04:06'),
(4, 3, 3, 'trial', '2026-07-17', '10:00:00', 'scheduled', '', '2026-07-16 06:52:49');

-- --------------------------------------------------------

--
-- Table structure for table `clinic_settings`
--

CREATE TABLE `clinic_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clinic_settings`
--

INSERT INTO `clinic_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'clinic_name', 'DentalCare Clinic', '2026-07-16 06:04:11'),
(2, 'clinic_address', '123 Dental St., Your City', '2026-07-16 06:04:11'),
(3, 'clinic_phone', '09XX-XXX-XXXX', '2026-07-16 06:04:11'),
(4, 'clinic_email', 'clinic@email.com', '2026-07-16 06:04:11'),
(5, 'clinic_logo_path', '', '2026-07-16 06:04:11'),
(6, 'invoice_prefix', 'INV', '2026-07-16 06:04:11'),
(7, 'invoice_footer', 'Thank you for choosing DentalCare Clinic. We look forward to seeing you again!', '2026-07-16 06:04:11'),
(8, 'smtp_host', '', '2026-07-16 06:04:11'),
(9, 'smtp_port', '587', '2026-07-16 06:04:11'),
(10, 'smtp_username', '', '2026-07-16 06:04:11'),
(11, 'smtp_password', '', '2026-07-16 06:04:11'),
(12, 'smtp_from_name', 'DentalCare Clinic', '2026-07-16 06:04:11'),
(13, 'smtp_from_email', '', '2026-07-16 06:04:11'),
(14, 'sms_api_key', '', '2026-07-16 06:04:11'),
(15, 'sms_sender_id', 'DentalCare', '2026-07-16 06:04:11'),
(16, 'primary_color', '#0f2d4a', '2026-07-16 06:04:11'),
(17, 'accent_color', '#0a8f8f', '2026-07-16 06:04:11');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_name`, `phone_number`, `email`, `address`, `date_created`, `notes`) VALUES
(1, 'John Doe', '09506574600', 'john.doe@email.com', NULL, '2026-07-16 06:04:06', NULL),
(2, 'Maria Santos', '09171234567', 'maria.santos@email.com', NULL, '2026-07-16 06:04:06', NULL),
(3, 'Carlos Reyes', '09281112222', NULL, NULL, '2026-07-16 06:04:06', NULL),
(4, 'Justine Villarosa', '09506579520', 'villarosajustine42@gmail.com', 'Zone 4, Santiago Pili CS', '2026-07-16 07:06:07', '');

-- --------------------------------------------------------

--
-- Table structure for table `dental_services`
--

CREATE TABLE `dental_services` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_label` varchar(100) DEFAULT 'Service 1',
  `service_number` int(11) DEFAULT 1,
  `tooth_upper` int(11) DEFAULT 0,
  `tooth_lower` int(11) DEFAULT 0,
  `tooth_shade` varchar(20) DEFAULT NULL,
  `tooth_size` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `total_bill` decimal(10,2) DEFAULT 0.00,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dental_services`
--

INSERT INTO `dental_services` (`id`, `customer_id`, `service_label`, `service_number`, `tooth_upper`, `tooth_lower`, `tooth_shade`, `tooth_size`, `description`, `total_bill`, `amount_paid`, `payment_status`, `date_created`) VALUES
(1, 1, 'Service 1', 1, 5, 2, 'A3', '64', NULL, 15000.00, 7000.00, 'partial', '2026-07-16 06:04:06'),
(2, 2, 'Service 1', 1, 3, 0, 'B2', '52', NULL, 9000.00, 9000.00, 'paid', '2026-07-16 06:04:06'),
(3, 3, 'Service 1', 1, 0, 4, 'A2', '44', NULL, 12000.00, 0.00, 'pending', '2026-07-16 06:04:06'),
(4, 4, 'Service 1', 1, 5, 0, '', '', 'Repair', 150.00, 0.00, 'pending', '2026-07-16 07:06:08');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('draft','sent','paid','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `issued_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sender` enum('admin','customer') DEFAULT 'admin',
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `date_sent` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `customer_id`, `sender`, `message`, `is_read`, `date_sent`) VALUES
(1, 1, 'admin', 'Hello John! Your dental case has been received. We will start with the trial fitting on April 5.', 1, '2026-07-16 06:04:06'),
(2, 1, 'customer', 'Thank you! Looking forward to it. Should I prepare anything?', 1, '2026-07-16 06:04:06'),
(3, 1, 'admin', 'No special preparation needed. Just come 10 minutes early for paperwork.', 0, '2026-07-16 06:04:06'),
(4, 2, 'admin', 'Hi Maria! Your dental work is now complete. Please come for your final fitting on April 8 at 2PM.', 1, '2026-07-16 06:04:06'),
(5, 2, 'customer', 'Perfect, I will be there!', 1, '2026-07-16 06:04:06');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `type` enum('sms','email') DEFAULT 'email',
  `event` enum('appointment_reminder','invoice_sent','service_update','custom') DEFAULT 'custom',
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_photos`
--

CREATE TABLE `patient_photos` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `photo_type` enum('before','after','progress','other') DEFAULT 'before',
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_portal_users`
--

CREATE TABLE `patient_portal_users` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patient_portal_users`
--

INSERT INTO `patient_portal_users` (`id`, `customer_id`, `username`, `password_hash`, `is_active`, `last_login`, `created_at`) VALUES
(1, 1, 'johndoe', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL, '2026-07-16 06:04:11'),
(2, 2, 'mariasantos', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL, '2026-07-16 06:04:11');

-- --------------------------------------------------------

--
-- Table structure for table `patient_signatures`
--

CREATE TABLE `patient_signatures` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `signature_path` varchar(500) NOT NULL,
  `signed_at` datetime DEFAULT current_timestamp(),
  `consent_text` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `clinic_settings`
--
ALTER TABLE `clinic_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dental_services`
--
ALTER TABLE `dental_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `patient_photos`
--
ALTER TABLE `patient_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `patient_portal_users`
--
ALTER TABLE `patient_portal_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `patient_signatures`
--
ALTER TABLE `patient_signatures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `service_id` (`service_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `clinic_settings`
--
ALTER TABLE `clinic_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dental_services`
--
ALTER TABLE `dental_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_photos`
--
ALTER TABLE `patient_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_portal_users`
--
ALTER TABLE `patient_portal_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_signatures`
--
ALTER TABLE `patient_signatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `dental_services` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dental_services`
--
ALTER TABLE `dental_services`
  ADD CONSTRAINT `dental_services_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `dental_services` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_photos`
--
ALTER TABLE `patient_photos`
  ADD CONSTRAINT `patient_photos_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_photos_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `dental_services` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patient_portal_users`
--
ALTER TABLE `patient_portal_users`
  ADD CONSTRAINT `patient_portal_users_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_signatures`
--
ALTER TABLE `patient_signatures`
  ADD CONSTRAINT `patient_signatures_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_signatures_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `dental_services` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
