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
-- DENTALPORTAL — PHASE 2 DATABASE MIGRATION
-- Run this AFTER your existing database.sql is already imported
-- ============================================================

USE dental_portal_db;

-- ============================================================
-- TABLE: admin_users — Authentication
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin','staff') DEFAULT 'admin',
    avatar_path VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: clinic_settings — Branding & Config
-- ============================================================
CREATE TABLE IF NOT EXISTS clinic_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: patient_photos — Before/After Photos
-- ============================================================
CREATE TABLE IF NOT EXISTS patient_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT DEFAULT NULL,
    photo_type ENUM('before','after','progress','other') DEFAULT 'before',
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    caption TEXT DEFAULT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES dental_services(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: patient_signatures — Consent Forms
-- ============================================================
CREATE TABLE IF NOT EXISTS patient_signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT DEFAULT NULL,
    signature_path VARCHAR(500) NOT NULL,
    signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    consent_text TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES dental_services(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: invoices — Generated Invoices
-- ============================================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    service_id INT DEFAULT NULL,
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) DEFAULT 0.00,
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    balance DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('draft','sent','paid','cancelled') DEFAULT 'draft',
    notes TEXT DEFAULT NULL,
    issued_date DATE NOT NULL,
    due_date DATE DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES dental_services(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: notifications — Email/SMS Log
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    type ENUM('sms','email') DEFAULT 'email',
    event ENUM('appointment_reminder','invoice_sent','service_update','custom') DEFAULT 'custom',
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(500) DEFAULT NULL,
    body TEXT NOT NULL,
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    sent_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: patient_portal_users — Patient Login Accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS patient_portal_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ALTER: dental_services — Add service_label for multi-service
-- ============================================================
ALTER TABLE dental_services 
    ADD COLUMN IF NOT EXISTS service_label VARCHAR(100) DEFAULT 'Service 1' AFTER customer_id,
    ADD COLUMN IF NOT EXISTS service_number INT DEFAULT 1 AFTER service_label;

-- ============================================================
-- CLINIC SETTINGS DEFAULTS
-- ============================================================
INSERT IGNORE INTO clinic_settings (setting_key, setting_value) VALUES
('clinic_name',       'DentalCare Clinic'),
('clinic_address',    '123 Dental St., Your City'),
('clinic_phone',      '09XX-XXX-XXXX'),
('clinic_email',      'clinic@email.com'),
('clinic_logo_path',  ''),
('invoice_prefix',    'INV'),
('invoice_footer',    'Thank you for choosing DentalCare Clinic. We look forward to seeing you again!'),
('smtp_host',         ''),
('smtp_port',         '587'),
('smtp_username',     ''),
('smtp_password',     ''),
('smtp_from_name',    'DentalCare Clinic'),
('smtp_from_email',   ''),
('sms_api_key',       ''),
('sms_sender_id',     'DentalCare'),
('primary_color',     '#0f2d4a'),
('accent_color',      '#0a8f8f');

-- ============================================================
-- DEFAULT ADMIN USER (password: Admin@123)
-- Change this password immediately after first login!
-- ============================================================
INSERT IGNORE INTO admin_users (full_name, email, username, password_hash, role) VALUES
('Administrator', 'admin@dentalportal.com', 'admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
-- Note: default password hash above is 'password' — update via Change Password after login

-- ============================================================
-- SAMPLE PATIENT PORTAL USERS (password: Patient@123)
-- ============================================================
INSERT IGNORE INTO patient_portal_users (customer_id, username, password_hash) VALUES
(1, 'johndoe', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'mariasantos', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');