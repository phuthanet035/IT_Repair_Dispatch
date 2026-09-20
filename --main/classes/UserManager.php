<?php
/**
 * Class: UserManager — ควบคุมระบบสมาชิกและการเข้าสู่ระบบ (Authentication & Authorization)
 * ฟังก์ชันหลัก:
 * 1. login($username, $password): ตรวจสอบชื่อผู้ใช้/รหัสผ่าน และเริ่ม Session
 * 2. logout(): ออกจากระบบ
 * 3. getCurrentUser(): คืนค่า User ที่ล็อกอินอยู่
 * 4. registerUser(...): สมัครสมาชิกใหม่
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class UserManager {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * ตรวจสอบการเข้าสู่ระบบ (Login)
     */
    public function login(string $username, string $password): ?User {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        // ตรวจสอบรหัสผ่าน (รองรับทั้ง password_verify และ plaintext เผื่อ seed data)
        $passwordMatches = password_verify($password, $row['password']) || ($password === $row['password']);

        if (!$passwordMatches) {
            return null;
        }

        $user = new User(
            (int)$row['id'],
            $row['username'],
            $row['full_name'],
            $row['department'],
            $row['phone'],
            $row['email'],
            $row['role']
        );

        // บันทึกลง PHP Session
        $_SESSION['user'] = $user->toArray();

        return $user;
    }

    /**
     * ออกจากระบบ (Logout)
     */
    public function logout(): void {
        unset($_SESSION['user']);
        session_destroy();
    }

    /**
     * ดึงข้อมูลผู้ใช้งานปัจจุบันที่กำลังล็อกอินอยู่ (ดึงสดจาก DB เพื่อความถูกต้องของสิทธิ์)
     */
    public function getCurrentUser(): ?User {
        if (!isset($_SESSION['user'])) {
            return null;
        }

        $sessionUser = $_SESSION['user'];
        $username = $sessionUser['username'] ?? '';
        $userId = (int)($sessionUser['id'] ?? 0);

        if (!$username && !$userId) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id OR username = :username LIMIT 1");
        $stmt->execute([':id' => $userId, ':username' => $username]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $user = new User(
            (int)$row['id'],
            $row['username'],
            $row['full_name'],
            $row['department'],
            $row['phone'],
            $row['email'],
            $row['role']
        );

        $_SESSION['user'] = $user->toArray();
        return $user;
    }

    /**
     * ตรวจสอบว่าชื่อผู้ใช้งาน (Username) มีในระบบแล้วหรือไม่
     */
    public function usernameExists(string $username): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return (int)$stmt->fetch()['cnt'] > 0;
    }

    /**
     * สมัครสมาชิกใหม่
     */
    public function registerUser(
        string $username,
        string $password,
        string $fullName,
        string $department,
        string $phone,
        string $email,
        string $role = 'User'
    ): bool {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, password, full_name, department, phone, email, role) 
            VALUES (:username, :password, :full_name, :department, :phone, :email, :role)");

        return $stmt->execute([
            ':username' => $username,
            ':password' => $hashedPassword,
            ':full_name' => $fullName,
            ':department' => $department,
            ':phone' => $phone,
            ':email' => $email,
            ':role' => $role
        ]);
    }

    /**
     * ดึงรายชื่อผู้ใช้งานทั้งหมดในระบบ
     */
    public function getAllUsers(): array {
        $stmt = $this->pdo->query("SELECT id, username, full_name, department, phone, email, role, COALESCE(duty_status, 'Available') as duty_status FROM users ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    /**
     * ดึงรายชื่อช่างทั้งหมดพร้อมสถานะปฏิบัติงาน (พร้อมรับงาน / พักงาน)
     */
    public function getTechniciansWithDutyStatus(): array {
        $stmt = $this->pdo->query("SELECT id, username, full_name, department, role, COALESCE(duty_status, 'Available') as duty_status FROM users WHERE role IN ('Technician', 'Admin') ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    /**
     * อัปเดตสถานะปฏิบัติงานของช่าง (Available = พร้อมรับงาน, Resting = พักงาน/ลาหยุด)
     */
    public function updateDutyStatus(string $username, string $status): bool {
        $validStatuses = ['Available', 'Resting'];
        if (!in_array($status, $validStatuses)) return false;

        $stmt = $this->pdo->prepare("UPDATE users SET duty_status = :status WHERE username = :username");
        return $stmt->execute([':status' => $status, ':username' => $username]);
    }
}
