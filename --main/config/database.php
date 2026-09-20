<?php
/**
 * Database Connection Configuration
 * รองรับทั้ง MySQL PDO (หลัก) และ SQLite (สำรองเมื่อไม่ได้เปิด MySQL บน XAMPP)
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'it_repair_db');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                // 1. ลองเชื่อมต่อ MySQL PDO
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::$driver = 'mysql';
            } catch (PDOException $e) {
                try {
                    $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                    $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    self::$driver = 'mysql';
                } catch (PDOException $ex) {
                    // 2. สำรอง: SQLite PDO
                    $sqliteDir = __DIR__ . '/../storage';
                    if (!file_exists($sqliteDir)) {
                        mkdir($sqliteDir, 0777, true);
                    }
                    $sqliteFile = $sqliteDir . '/database.sqlite';
                    self::$instance = new PDO("sqlite:" . $sqliteFile);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$driver = 'sqlite';
                }
            }
            self::initTables();
        }
        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    private static function initTables(): void {
        $pdo = self::$instance;
        if (self::$driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                full_name TEXT NOT NULL,
                department TEXT NOT NULL,
                phone TEXT NOT NULL,
                email TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'User',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS assets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                asset_id TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                device_type TEXT NOT NULL,
                is_warranty INTEGER NOT NULL DEFAULT 1,
                status TEXT NOT NULL DEFAULT 'Normal',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS repair_tickets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ticket_id TEXT UNIQUE NOT NULL,
                user_name TEXT NOT NULL,
                department TEXT NOT NULL,
                phone TEXT NOT NULL,
                user_email TEXT DEFAULT NULL,
                asset_id TEXT NOT NULL,
                device_type TEXT NOT NULL,
                issue_description TEXT NOT NULL,
                issue_severity TEXT NOT NULL DEFAULT 'Medium',
                priority TEXT NOT NULL DEFAULT 'Normal',
                estimated_cost REAL NOT NULL DEFAULT 0.0,
                estimated_completion_time TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'Pending',
                issue_image TEXT DEFAULT NULL,
                completion_image TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Auto alter sqlite if needed
            try { $pdo->exec("ALTER TABLE repair_tickets ADD COLUMN user_email TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE repair_tickets ADD COLUMN issue_image TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE repair_tickets ADD COLUMN completion_image TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE repair_tickets ADD COLUMN assigned_technician TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE users ADD COLUMN duty_status TEXT DEFAULT 'Available'"); } catch (Exception $e) {}

            // Seed sample users if empty
            $uStmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
            if ($uStmt->fetch()['cnt'] == 0) {
                $pdo->exec("INSERT INTO users (username, password, full_name, department, phone, email, role) VALUES
                    ('Admin', '1234', 'ผู้ดูแลระบบส่วนกลาง (Admin)', 'ฝ่าย IT / เทคโนโลยีสารสนเทศ', '0899999999', 'admin@company.com', 'Admin'),
                    ('admin', '1234', 'สมศักดิ์ ช่างไอที (Admin)', 'ฝ่าย IT / เทคโนโลยีสารสนเทศ', '0899999999', 'admin@company.com', 'Admin'),
                    ('user1', 'user123', 'นายสมชาย ใจดี (ผู้ใช้ทั่วไป)', 'ฝ่ายการเงินและบัญชี', '0812345678', 'somchai@company.com', 'User')");
            }

            // Seed sample assets if empty
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM assets");
            if ($stmt->fetch()['cnt'] == 0) {
                $pdo->exec("INSERT INTO assets (asset_id, name, device_type, is_warranty, status) VALUES
                    ('AST-1001', 'PC Workstation i7 (การเงิน)', 'Computer', 1, 'Normal'),
                    ('AST-1002', 'Laptop Dell XPS 15 (ผู้บริหาร)', 'Computer', 1, 'Normal'),
                    ('AST-1003', 'PC All-in-One HP (ต้อนรับ)', 'Computer', 0, 'Normal'),
                    ('AST-2001', 'Printer HP LaserJet Pro (สำนักงานกลาง)', 'Printer', 1, 'Normal'),
                    ('AST-2002', 'Printer Epson Dot Matrix (บัญชี)', 'Printer', 0, 'Normal'),
                    ('AST-3001', 'Monitor LG 27 นิ้ว 4K (กราฟิก)', 'Monitor', 1, 'Normal'),
                    ('AST-3002', 'Monitor Samsung 24 นิ้ว (บุคคล)', 'Monitor', 0, 'Normal'),
                    ('AST-4001', 'Cisco Wi-Fi Router (ชั้น 2)', 'Network', 1, 'Normal')");
            }
        } else {
            // MySQL Initialization
            $sqlScript = file_get_contents(__DIR__ . '/../schema.sql');
            if ($sqlScript) {
                $pdo->exec($sqlScript);
            }
        }
    }
}
