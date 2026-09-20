-- SQL Migration Script for IT Repair System (it_repair_db)
CREATE DATABASE IF NOT EXISTS `it_repair_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `it_repair_db`;

-- Table 0: Users Table (User Management & Authentication)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `role` ENUM('User', 'Technician', 'Admin') NOT NULL DEFAULT 'User',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table 1: Assets (Managed by ITAssetManager)
CREATE TABLE IF NOT EXISTS `assets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `device_type` ENUM('Computer', 'Printer', 'Monitor', 'Network', 'Other') NOT NULL,
    `is_warranty` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('Normal', 'Repairing', 'Damaged') NOT NULL DEFAULT 'Normal',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table 2: Repair Tickets (Managed by RepairTicketController)
CREATE TABLE IF NOT EXISTS `repair_tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` VARCHAR(50) NOT NULL UNIQUE,
    `user_name` VARCHAR(100) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `user_email` VARCHAR(100) DEFAULT NULL,
    `asset_id` VARCHAR(50) NOT NULL,
    `device_type` VARCHAR(50) NOT NULL,
    `issue_description` TEXT NOT NULL,
    `issue_severity` ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
    `priority` ENUM('Low', 'Normal', 'High', 'Urgent') NOT NULL DEFAULT 'Normal',
    `estimated_cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `estimated_completion_time` VARCHAR(50) NOT NULL,
    `status` ENUM('Pending', 'In Progress', 'Completed') NOT NULL DEFAULT 'Pending',
    `issue_image` VARCHAR(255) DEFAULT NULL,
    `completion_image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (`phone`),
    INDEX idx_ticket_id (`ticket_id`),
    INDEX idx_asset_id (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Auto Alter Table if columns missing
ALTER TABLE `repair_tickets` ADD COLUMN IF NOT EXISTS `user_email` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `repair_tickets` ADD COLUMN IF NOT EXISTS `issue_image` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `repair_tickets` ADD COLUMN IF NOT EXISTS `completion_image` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `repair_tickets` ADD COLUMN IF NOT EXISTS `assigned_technician` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `duty_status` VARCHAR(20) DEFAULT 'Available';

-- Seed Data: Sample Users (Admin, 3 Technicians, and Normal User)
INSERT INTO `users` (`username`, `password`, `full_name`, `department`, `phone`, `email`, `role`) VALUES
('admin', 'admin123', 'สมศักดิ์ หัวหน้าแอดมิน IT (Admin)', 'ฝ่าย IT / เทคโนโลยีสารสนเทศ', '0899999999', 'admin@company.com', 'Admin'),
('tech1', 'tech123', 'วิชัย ช่างคอมพิวเตอร์', 'ฝ่าย IT / ซ่อมบำรุงคอมพิวเตอร์', '0821111111', 'wichai@company.com', 'Technician'),
('tech2', 'tech123', 'กิตติ ช่างเน็ตเวิร์ก', 'ฝ่าย IT / โครงข่ายและเครือข่าย', '0822222222', 'kitti@company.com', 'Technician'),
('tech3', 'tech123', 'ธีระ ช่างอุปกรณ์ทั่วไป', 'ฝ่าย IT / ฮาร์ดแวร์และพริ้นเตอร์', '0823333333', 'theera@company.com', 'Technician'),
('user1', 'user123', 'นายสมชาย ใจดี (ผู้ใช้ทั่วไป)', 'ฝ่ายการเงินและบัญชี', '0812345678', 'somchai@company.com', 'User')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`), `role` = VALUES(`role`);

-- Seed Data: Sample IT Assets
INSERT INTO `assets` (`asset_id`, `name`, `device_type`, `is_warranty`, `status`) VALUES
('AST-1001', 'PC Workstation i7 (การเงิน)', 'Computer', 1, 'Normal'),
('AST-1002', 'Laptop Dell XPS 15 (ผู้บริหาร)', 'Computer', 1, 'Normal'),
('AST-1003', 'PC All-in-One HP (ต้อนรับ)', 'Computer', 0, 'Normal'),
('AST-2001', 'Printer HP LaserJet Pro (สำนักงานกลาง)', 'Printer', 1, 'Normal'),
('AST-2002', 'Printer Epson Dot Matrix (บัญชี)', 'Printer', 0, 'Normal'),
('AST-3001', 'Monitor LG 27 นิ้ว 4K (กราฟิก)', 'Monitor', 1, 'Normal'),
('AST-3002', 'Monitor Samsung 24 นิ้ว (บุคคล)', 'Monitor', 0, 'Normal'),
('AST-4001', 'Cisco Wi-Fi Router (ชั้น 2)', 'Network', 1, 'Normal')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
