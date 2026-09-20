<?php
/**
 * Model Class: User
 * แทนข้อมูลผู้ใช้งานในระบบ (แยกสิทธิ์ User และ Admin/Staff)
 */

class User {
    private int $id;
    private string $username;
    private string $fullName;
    private string $department;
    private string $phone;
    private string $email;
    private string $role; // 'User' หรือ 'Admin'

    public function __construct(
        int $id,
        string $username,
        string $fullName,
        string $department,
        string $phone,
        string $email,
        string $role = 'User'
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->fullName = $fullName;
        $this->department = $department;
        $this->phone = $phone;
        $this->email = $email;
        $this->role = $role;
    }

    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getFullName(): string { return $this->fullName; }
    public function getDepartment(): string { return $this->department; }
    public function getPhone(): string { return $this->phone; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }
    
    public function isUser(): bool { return $this->role === 'User'; }
    public function isTechnician(): bool { return $this->role === 'Technician'; }
    public function isAdmin(): bool { return $this->role === 'Admin'; }
    public function isTechOrAdmin(): bool { return $this->role === 'Technician' || $this->role === 'Admin'; }

    public function getRoleLabel(): string {
        switch ($this->role) {
            case 'Technician': return 'ช่างซ่อม (Technician)';
            case 'Admin': return 'แอดมิน (Admin)';
            default: return 'ผู้ใช้งานทั่วไป (User)';
        }
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'full_name' => $this->fullName,
            'department' => $this->department,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->role,
            'role_label' => $this->getRoleLabel(),
            'is_user' => $this->isUser(),
            'is_technician' => $this->isTechnician(),
            'is_admin' => $this->isAdmin(),
            'is_tech_or_admin' => $this->isTechOrAdmin()
        ];
    }
}
