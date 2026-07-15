-- ============================================================
-- DENTAL SERVICE MANAGEMENT PORTAL - DATABASE SCHEMA
-- ============================================================

CREATE DATABASE IF NOT EXISTS dental_portal_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dental_portal_db;

-- ============================================================
-- TABLE: customers
-- ============================================================
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(30) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: dental_services
-- Each customer can have multiple service entries
-- ============================================================
CREATE TABLE IF NOT EXISTS dental_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    tooth_upper INT DEFAULT 0,
    tooth_lower INT DEFAULT 0,
    tooth_shade VARCHAR(20) DEFAULT NULL,
    tooth_size VARCHAR(20) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    total_bill DECIMAL(10,2) DEFAULT 0.00,
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('pending','partial','paid') DEFAULT 'pending',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: appointments
-- ============================================================
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT DEFAULT NULL,
    appointment_type ENUM('trial','follow_up','final','consultation') DEFAULT 'trial',
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled','done','cancelled','rescheduled') DEFAULT 'scheduled',
    notes TEXT DEFAULT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES dental_services(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: messages (Customer-Admin Conversation)
-- ============================================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    sender ENUM('admin','customer') DEFAULT 'admin',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    date_sent DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT INTO customers (customer_name, phone_number, email, date_created) VALUES
('John Doe', '09506574600', 'john.doe@email.com', NOW()),
('Maria Santos', '09171234567', 'maria.santos@email.com', NOW()),
('Carlos Reyes', '09281112222', NULL, NOW());

INSERT INTO dental_services (customer_id, tooth_upper, tooth_lower, tooth_shade, tooth_size, total_bill, amount_paid, payment_status) VALUES
(1, 5, 2, 'A3', '64', 15000.00, 7000.00, 'partial'),
(2, 3, 0, 'B2', '52', 9000.00, 9000.00, 'paid'),
(3, 0, 4, 'A2', '44', 12000.00, 0.00, 'pending');

INSERT INTO appointments (customer_id, service_id, appointment_type, appointment_date, appointment_time, status) VALUES
(1, 1, 'trial', '2026-04-05', '10:00:00', 'scheduled'),
(2, 2, 'final', '2026-04-08', '14:00:00', 'done'),
(3, 3, 'consultation', '2026-04-10', '09:00:00', 'scheduled');

INSERT INTO messages (customer_id, sender, message, is_read) VALUES
(1, 'admin', 'Hello John! Your dental case has been received. We will start with the trial fitting on April 5.', 1),
(1, 'customer', 'Thank you! Looking forward to it. Should I prepare anything?', 1),
(1, 'admin', 'No special preparation needed. Just come 10 minutes early for paperwork.', 0),
(2, 'admin', 'Hi Maria! Your dental work is now complete. Please come for your final fitting on April 8 at 2PM.', 1),
(2, 'customer', 'Perfect, I will be there!', 1);
