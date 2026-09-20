<?php
/**
 * Controller Class 3: RepairTicketController (คนที่ 3)
 * หน้าที่: ควบคุม Workflow งานซ่อม IT (สร้างใบแจ้งซ่อม, ค้นหาติดตามสถานะ, อัปเดตสถานะพร้อมแนบรูปซ่อมเสร็จ, ลบใบแจ้งซ่อม, มอบหมายช่าง)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RepairTicket.php';
require_once __DIR__ . '/ITAssetManager.php';
require_once __DIR__ . '/RepairCostCalculator.php';
require_once __DIR__ . '/EmailNotifier.php';

class RepairTicketController {
    private PDO $pdo;
    private ITAssetManager $assetManager;
    private RepairCostCalculator $costCalculator;
    private EmailNotifier $emailNotifier;

    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->assetManager = new ITAssetManager();
        $this->costCalculator = new RepairCostCalculator();
        $this->emailNotifier = new EmailNotifier();
    }

    /**
     * 1. สร้างใบแจ้งซ่อม (Create Repair Ticket)
     */
    public function createTicket(
        string $userName,
        string $department,
        string $phone,
        string $assetId,
        string $issueDescription,
        string $severity = 'Medium',
        string $priority = 'Normal',
        string $userEmail = '',
        ?string $issueImage = null,
        ?string $assignedTechnician = null
    ): ?RepairTicket {
        // 1. ตรวจสอบว่าอุปกรณ์มีอยู่จริงผ่าน Class 1 ITAssetManager
        $asset = $this->assetManager->getAssetDetails($assetId);
        if (!$asset) {
            throw new Exception("ไม่พบรหัสอุปกรณ์ IT [$assetId] ในระบบ!");
        }

        // 2. คำนวณราคาประเมินค่าซ่อมและเวลาผ่าน Class 2 RepairCostCalculator
        $estimatedCost = $this->costCalculator->calculateCost(
            $asset->getDeviceType(),
            $severity,
            $asset->isWarranty()
        );
        $completionTime = $this->costCalculator->estimateCompletionTime($priority);

        // 3. สุ่มสร้าง Ticket ID เช่น JOB-4821
        $ticketId = $this->generateTicketId();
        $now = date('Y-m-d H:i:s');
        $status = 'Pending';
        $assignedTechnician = null;

        // 4. บันทึกลง Database
        $stmt = $this->pdo->prepare("INSERT INTO repair_tickets 
            (ticket_id, user_name, department, phone, user_email, asset_id, device_type, issue_description, issue_severity, priority, estimated_cost, estimated_completion_time, status, issue_image, assigned_technician, created_at) 
            VALUES (:ticket_id, :user_name, :department, :phone, :user_email, :asset_id, :device_type, :issue_description, :issue_severity, :priority, :estimated_cost, :estimated_completion_time, :status, :issue_image, :assigned_technician, :created_at)");

        $stmt->execute([
            ':ticket_id' => $ticketId,
            ':user_name' => $userName,
            ':department' => $department,
            ':phone' => $phone,
            ':user_email' => $userEmail,
            ':asset_id' => $assetId,
            ':device_type' => $asset->getDeviceType(),
            ':issue_description' => $issueDescription,
            ':issue_severity' => $severity,
            ':priority' => $priority,
            ':estimated_cost' => $estimatedCost,
            ':estimated_completion_time' => $completionTime,
            ':status' => $status,
            ':issue_image' => $issueImage,
            ':assigned_technician' => null,
            ':created_at' => $now
        ]);

        // 5. ปรับสถานะอุปกรณ์เป็น Repairing
        $this->assetManager->updateAssetStatus($assetId, 'Repairing');

        // สร้าง Object RepairTicket
        $ticket = new RepairTicket(
            $ticketId,
            $userName,
            $department,
            $phone,
            $assetId,
            $asset->getDeviceType(),
            $issueDescription,
            $severity,
            $priority,
            $estimatedCost,
            $completionTime,
            $status,
            $userEmail,
            $issueImage,
            null,
            null,
            $now
        );

        // 6. ส่งอีเมลแจ้งเตือนใบแจ้งซ่อมใหม่
        $this->emailNotifier->sendTicketCreatedNotification($ticket, $userEmail);

        return $ticket;
    }

    /**
     * 2. เช็คสถานะ (Check Status by Ticket ID or Phone Number)
     */
    public function checkStatus(string $searchQuery): array {
        $searchQuery = trim($searchQuery);
        $stmt = $this->pdo->prepare("SELECT * FROM repair_tickets 
            WHERE ticket_id = :ticket_id OR phone = :phone 
            ORDER BY id DESC");
        $stmt->execute([':ticket_id' => $searchQuery, ':phone' => $searchQuery]);
        $rows = $stmt->fetchAll();

        $tickets = [];
        foreach ($rows as $row) {
            $tickets[] = new RepairTicket(
                $row['ticket_id'],
                $row['user_name'],
                $row['department'],
                $row['phone'],
                $row['asset_id'],
                $row['device_type'],
                $row['issue_description'],
                $row['issue_severity'],
                $row['priority'],
                (float)$row['estimated_cost'],
                $row['estimated_completion_time'],
                $row['status'],
                $row['user_email'] ?? null,
                $row['issue_image'] ?? null,
                $row['completion_image'] ?? null,
                $row['assigned_technician'] ?? null,
                $row['created_at']
            );
        }

        return $tickets;
    }

    /**
     * 3. ช่างอัปเดตสถานะงานซ่อม และระบุช่างผู้รับงาน
     */
    public function updateTicketStatus(string $ticketId, string $newStatus, ?string $completionImage = null, ?string $technicianName = null): bool {
        $validStatuses = ['Pending', 'In Progress', 'Completed'];
        if (!in_array($newStatus, $validStatuses)) {
            return false;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM repair_tickets WHERE ticket_id = :ticket_id LIMIT 1");
        $stmt->execute([':ticket_id' => $ticketId]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        $assetId = $row['asset_id'];
        $userEmail = $row['user_email'] ?? '';
        $techToAssign = !empty($technicianName) ? $technicianName : ($row['assigned_technician'] ?? null);
        $now = date('Y-m-d H:i:s');

        // อัปเดตสถานะตั๋วแจ้งซ่อม และช่างผู้รับงาน
        if (!empty($completionImage)) {
            $updateStmt = $this->pdo->prepare("UPDATE repair_tickets SET status = :status, completion_image = :completion_image, assigned_technician = :technician, updated_at = :updated_at WHERE ticket_id = :ticket_id");
            $success = $updateStmt->execute([
                ':status' => $newStatus,
                ':completion_image' => $completionImage,
                ':technician' => $techToAssign,
                ':updated_at' => $now,
                ':ticket_id' => $ticketId
            ]);
        } else {
            $updateStmt = $this->pdo->prepare("UPDATE repair_tickets SET status = :status, assigned_technician = :technician, updated_at = :updated_at WHERE ticket_id = :ticket_id");
            $success = $updateStmt->execute([
                ':status' => $newStatus,
                ':technician' => $techToAssign,
                ':updated_at' => $now,
                ':ticket_id' => $ticketId
            ]);
        }

        if ($success) {
            if ($newStatus === 'Completed') {
                // เปลี่ยนสถานะอุปกรณ์เป็น Normal
                $this->assetManager->updateAssetStatus($assetId, 'Normal');

                // สร้าง Object Ticket เพื่อส่งอีเมลแจ้งเตือนซ่อมเสร็จ
                $ticketObj = new RepairTicket(
                    $row['ticket_id'],
                    $row['user_name'],
                    $row['department'],
                    $row['phone'],
                    $row['asset_id'],
                    $row['device_type'],
                    $row['issue_description'],
                    $row['issue_severity'],
                    $row['priority'],
                    (float)$row['estimated_cost'],
                    $row['estimated_completion_time'],
                    'Completed',
                    $userEmail,
                    $row['issue_image'] ?? null,
                    $completionImage ?? ($row['completion_image'] ?? null),
                    $techToAssign,
                    $row['created_at']
                );

                // 🔔 ส่งอีเมลแจ้งเตือนซ่อมเสร็จแล้ว ไปยังอีเมลของผู้แจ้งซ่อม
                if (!empty($userEmail)) {
                    $this->emailNotifier->sendCompletionNotification($ticketObj, $userEmail);
                }
            } elseif ($newStatus === 'In Progress') {
                $this->assetManager->updateAssetStatus($assetId, 'Repairing');
            }
        }

        return $success;
    }

    /**
     * 3.1 ช่างกดรับงานเอง (Claim Job)
     */
    public function claimTicket(string $ticketId, string $technicianName): bool {
        $stmt = $this->pdo->prepare("SELECT * FROM repair_tickets WHERE ticket_id = :ticket_id LIMIT 1");
        $stmt->execute([':ticket_id' => $ticketId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new Exception("ไม่พบใบแจ้งซ่อมรหัส [$ticketId]");
        }

        if (!empty($row['assigned_technician']) && $row['assigned_technician'] !== $technicianName) {
            throw new Exception("งานซ่อมรหัส $ticketId ถูกรับไปแล้วโดยช่าง [" . $row['assigned_technician'] . "]");
        }

        $now = date('Y-m-d H:i:s');
        $updateStmt = $this->pdo->prepare("UPDATE repair_tickets SET status = 'In Progress', assigned_technician = :tech, updated_at = :now WHERE ticket_id = :ticket_id");
        $success = $updateStmt->execute([
            ':tech' => $technicianName,
            ':now' => $now,
            ':ticket_id' => $ticketId
        ]);

        if ($success) {
            $this->assetManager->updateAssetStatus($row['asset_id'], 'Repairing');
        }

        return $success;
    }

    /**
     * ดึงรายการตั๋วแจ้งซ่อมทั้งหมด (สำหรับหน้า Dashboard ช่าง IT)
     */
    public function getAllTickets(): array {
        $stmt = $this->pdo->query("SELECT * FROM repair_tickets ORDER BY id DESC");
        $rows = $stmt->fetchAll();

        $tickets = [];
        foreach ($rows as $row) {
            $tickets[] = new RepairTicket(
                $row['ticket_id'],
                $row['user_name'],
                $row['department'],
                $row['phone'],
                $row['asset_id'],
                $row['device_type'],
                $row['issue_description'],
                $row['issue_severity'],
                $row['priority'],
                (float)$row['estimated_cost'],
                $row['estimated_completion_time'],
                $row['status'],
                $row['user_email'] ?? null,
                $row['issue_image'] ?? null,
                $row['completion_image'] ?? null,
                $row['assigned_technician'] ?? null,
                $row['created_at']
            );
        }

        return $tickets;
    }

    /**
     * ดึงสถิติจำนวนงานที่ช่างแต่ละคนรับไปในเดือนนี้
     */
    public function getTechnicianMonthlyStats(): array {
        $firstDayOfMonth = date('Y-m-01 00:00:00');
        $stmt = $this->pdo->prepare("SELECT assigned_technician, COUNT(*) as job_count 
            FROM repair_tickets 
            WHERE assigned_technician IS NOT NULL AND assigned_technician != '' AND updated_at >= :start_date 
            GROUP BY assigned_technician 
            ORDER BY job_count DESC");
        $stmt->execute([':start_date' => $firstDayOfMonth]);
        return $stmt->fetchAll();
    }

    /**
     * ดึงจำนวนงานที่ช่างคนระบุรับไปในเดือนนี้
     */
    public function getTechnicianJobCountThisMonth(string $technicianName): int {
        if (empty($technicianName)) return 0;
        $firstDayOfMonth = date('Y-m-01 00:00:00');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as cnt FROM repair_tickets WHERE assigned_technician = :tech AND updated_at >= :start_date");
        $stmt->execute([':tech' => $technicianName, ':start_date' => $firstDayOfMonth]);
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * ดึงสรุปภาระงานทั้งหมดของช่างแต่ละคน (รวมงานค้าง งานเสร็จ และงานทั้งหมด)
     */
    public function getTechnicianWorkloadSummary(): array {
        $stmt = $this->pdo->query("SELECT 
            assigned_technician,
            COUNT(*) as total_jobs,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_jobs,
            SUM(CASE WHEN status IN ('Pending', 'In Progress') THEN 1 ELSE 0 END) as pending_jobs
            FROM repair_tickets 
            WHERE assigned_technician IS NOT NULL AND assigned_technician != ''
            GROUP BY assigned_technician 
            ORDER BY total_jobs DESC");
        return $stmt->fetchAll();
    }

    /**
     * แอดมินมอบหมายช่างให้ใบแจ้งซ่อม (Assign Technician to Ticket)
     */
    public function assignTechnicianToTicket(string $ticketId, string $technicianName): bool {
        return $this->reassignTechnician($ticketId, $technicianName);
    }

    /**
     * มอบหมาย หรือ เปลี่ยนตัวช่างผู้รับผิดชอบงาน (Reassign Technician)
     */
    public function reassignTechnician(string $ticketId, string $technicianName): bool {
        $stmt = $this->pdo->prepare("SELECT * FROM repair_tickets WHERE ticket_id = :ticket_id LIMIT 1");
        $stmt->execute([':ticket_id' => $ticketId]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        // หากงานยังเป็น Pending ให้เปลี่ยนเป็น In Progress ทันทีเนื่องจากมีช่างรับมอบหมายแล้ว
        $newStatus = ($ticket['status'] === 'Pending') ? 'In Progress' : $ticket['status'];

        $updateStmt = $this->pdo->prepare("UPDATE repair_tickets 
            SET assigned_technician = :tech, status = :status, updated_at = :now 
            WHERE ticket_id = :ticket_id");
        $success = $updateStmt->execute([
            ':tech' => $technicianName,
            ':status' => $newStatus,
            ':now' => $now,
            ':ticket_id' => $ticketId
        ]);

        if ($success) {
            // อัปเดตสถานะอุปกรณ์เป็น Repairing
            $this->assetManager->updateAssetStatus($ticket['asset_id'], 'Repairing');

            // ส่งอีเมลแจ้งเตือนช่าง (ถ้ามีอีเมลในระบบ)
            try {
                $userStmt = $this->pdo->prepare("SELECT email FROM users WHERE full_name = :full_name LIMIT 1");
                $userStmt->execute([':full_name' => $technicianName]);
                $techUser = $userStmt->fetch();
                if ($techUser && !empty($techUser['email'])) {
                    $ticketObj = new RepairTicket(
                        $ticket['ticket_id'],
                        $ticket['user_name'],
                        $ticket['department'],
                        $ticket['phone'],
                        $ticket['asset_id'],
                        $ticket['device_type'],
                        $ticket['issue_description'],
                        $ticket['issue_severity'],
                        $ticket['priority'],
                        (float)$ticket['estimated_cost'],
                        $ticket['estimated_completion_time'],
                        $newStatus,
                        $ticket['user_email'] ?? null,
                        $ticket['issue_image'] ?? null,
                        $ticket['completion_image'] ?? null,
                        $technicianName,
                        $ticket['created_at']
                    );
                    $this->emailNotifier->sendJobAssignedNotification($ticketObj, $techUser['email'], $technicianName);
                }
            } catch (Exception $e) {
                // ข้ามหากส่งอีเมลไม่สำเร็จ เพื่อไม่ให้กระทบ workflow
            }
        }

        return $success;
    }

    /**
     * ถอดช่างผู้รับผิดชอบ และคืนงานกลับเข้ากองกลาง (Unassign Technician)
     */
    public function unassignTechnician(string $ticketId): bool {
        $stmt = $this->pdo->prepare("SELECT * FROM repair_tickets WHERE ticket_id = :ticket_id LIMIT 1");
        $stmt->execute([':ticket_id' => $ticketId]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $newStatus = ($ticket['status'] === 'In Progress') ? 'Pending' : $ticket['status'];

        $updateStmt = $this->pdo->prepare("UPDATE repair_tickets 
            SET assigned_technician = NULL, status = :status, updated_at = :now 
            WHERE ticket_id = :ticket_id");
        return $updateStmt->execute([
            ':status' => $newStatus,
            ':now' => $now,
            ':ticket_id' => $ticketId
        ]);
    }

    /**
     * ระบบสุ่มแจกงานอัจฉริยะ (Auto-Dispatch)
     * เลือกช่างที่มีสถานะ On-Duty (Available) และมีภาระงานค้างน้อยที่สุดให้อัตโนมัติ
     */
    public function autoDispatchTicket(string $ticketId): array {
        // ค้นหาช่างที่ Available และมีงาน In Progress น้อยที่สุด
        $sql = "SELECT u.full_name, u.email,
                    COUNT(t.id) as active_jobs
                FROM users u
                LEFT JOIN repair_tickets t ON t.assigned_technician = u.full_name AND t.status IN ('Pending', 'In Progress')
                WHERE u.role IN ('Technician', 'Admin') 
                  AND COALESCE(u.duty_status, 'Available') = 'Available'
                GROUP BY u.id, u.full_name, u.email
                ORDER BY active_jobs ASC, u.id ASC
                LIMIT 1";

        $stmt = $this->pdo->query($sql);
        $bestTech = $stmt->fetch();

        if (!$bestTech) {
            throw new Exception("ไม่พบช่างที่พร้อมรับงาน (On-Duty) ในขณะนี้");
        }

        $techName = $bestTech['full_name'];
        $assigned = $this->reassignTechnician($ticketId, $techName);

        if (!$assigned) {
            throw new Exception("ไม่สามารถมอบหมายงาน $ticketId ให้ช่าง $techName ได้");
        }

        return [
            'technician' => $techName,
            'active_jobs' => (int)$bestTech['active_jobs'] + 1
        ];
    }

    /**
     * ดึงข้อมูลสถิติภาพรวมแดชบอร์ดอย่างละเอียด (Full Dashboard Analytics)
     */
    public function getDashboardAnalytics(): array {
        // 1. KPI Counts
        $kpiSql = "SELECT 
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_count,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN (assigned_technician IS NULL OR assigned_technician = '') AND status != 'Completed' THEN 1 ELSE 0 END) as unassigned_count,
            SUM(CASE WHEN priority = 'Urgent' OR issue_severity = 'Critical' THEN 1 ELSE 0 END) as urgent_critical_count,
            COALESCE(SUM(estimated_cost), 0) as total_estimated_cost,
            COALESCE(SUM(CASE WHEN status = 'Completed' THEN estimated_cost ELSE 0 END), 0) as completed_cost
            FROM repair_tickets";
        $kpi = $this->pdo->query($kpiSql)->fetch();

        $totalTickets = (int)($kpi['total_tickets'] ?? 0);
        $completedCount = (int)($kpi['completed_count'] ?? 0);
        $completionRate = $totalTickets > 0 ? round(($completedCount / $totalTickets) * 100, 1) : 0;

        // 2. สัดส่วนสถานะงาน (Status Breakdown for Donut Chart)
        $statusBreakdown = [
            'Pending' => (int)($kpi['pending_count'] ?? 0),
            'In Progress' => (int)($kpi['in_progress_count'] ?? 0),
            'Completed' => $completedCount
        ];

        // 3. จำแนกตามประเภทอุปกรณ์ (Device Type Breakdown for Chart)
        $deviceSql = "SELECT device_type, COUNT(*) as count 
                      FROM repair_tickets 
                      GROUP BY device_type 
                      ORDER BY count DESC";
        $deviceBreakdown = $this->pdo->query($deviceSql)->fetchAll();

        // 4. จำแนกตามความเร่งด่วน (Priority Breakdown for Chart)
        $prioritySql = "SELECT priority, COUNT(*) as count 
                        FROM repair_tickets 
                        GROUP BY priority 
                        ORDER BY CASE priority 
                            WHEN 'Urgent' THEN 1 
                            WHEN 'High' THEN 2 
                            WHEN 'Normal' THEN 3 
                            WHEN 'Low' THEN 4 
                            ELSE 5 END ASC";
        $priorityBreakdown = $this->pdo->query($prioritySql)->fetchAll();

        // 5. รายชื่อช่างทั้งหมดพร้อมสถานะงานและรายละเอียดงานที่รับผิดชอบ
        $techSql = "SELECT 
                u.id,
                u.username,
                u.full_name,
                u.department,
                u.phone,
                u.email,
                u.role,
                COALESCE(u.duty_status, 'Available') as duty_status,
                SUM(CASE WHEN t.status IN ('Pending', 'In Progress') THEN 1 ELSE 0 END) as active_jobs,
                SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_jobs,
                COUNT(t.id) as total_jobs
            FROM users u
            LEFT JOIN repair_tickets t ON t.assigned_technician = u.full_name
            WHERE u.role IN ('Technician', 'Admin')
            GROUP BY u.id, u.username, u.full_name, u.department, u.phone, u.email, u.role, u.duty_status
            ORDER BY active_jobs DESC, u.full_name ASC";
        $technicians = $this->pdo->query($techSql)->fetchAll();

        // นับจำนวนช่างที่ On-Duty
        $availableTechCount = 0;
        foreach ($technicians as &$tech) {
            $tech['active_jobs'] = (int)($tech['active_jobs'] ?? 0);
            $tech['completed_jobs'] = (int)($tech['completed_jobs'] ?? 0);
            $tech['total_jobs'] = (int)($tech['total_jobs'] ?? 0);
            if ($tech['duty_status'] === 'Available') {
                $availableTechCount++;
            }

            // ดึงรายการตั๋วที่กำลังทำอยู่ของช่างคนนี้ (Active Tickets)
            $activeStmt = $this->pdo->prepare("SELECT ticket_id, asset_id, device_type, issue_description, priority, status, created_at 
                FROM repair_tickets 
                WHERE assigned_technician = :tech AND status IN ('Pending', 'In Progress')
                ORDER BY CASE priority 
                    WHEN 'Urgent' THEN 1 
                    WHEN 'High' THEN 2 
                    WHEN 'Normal' THEN 3 
                    WHEN 'Low' THEN 4 
                    ELSE 5 END ASC, id DESC");
            $activeStmt->execute([':tech' => $tech['full_name']]);
            $tech['active_tickets'] = $activeStmt->fetchAll();
        }
        unset($tech);

        // 6. รายการงานกองกลางที่ยังไม่มีช่างรับ (Unassigned Pool)
        $unassignedStmt = $this->pdo->query("SELECT * FROM repair_tickets 
            WHERE (assigned_technician IS NULL OR assigned_technician = '') 
              AND status != 'Completed'
            ORDER BY CASE priority 
                WHEN 'Urgent' THEN 1 
                WHEN 'High' THEN 2 
                WHEN 'Normal' THEN 3 
                WHEN 'Low' THEN 4 
                ELSE 5 END ASC, id DESC");
        $unassignedTickets = $unassignedStmt->fetchAll();

        // 7. แนวโน้มรายวัน/รายเดือน (Recent Activity Trends)
        $trendSql = "SELECT SUBSTR(created_at, 1, 10) as log_date, COUNT(*) as count 
                     FROM repair_tickets 
                     GROUP BY SUBSTR(created_at, 1, 10) 
                     ORDER BY log_date DESC 
                     LIMIT 7";
        $trendData = $this->pdo->query($trendSql)->fetchAll();

        return [
            'kpi' => [
                'total_tickets' => $totalTickets,
                'pending_count' => (int)($kpi['pending_count'] ?? 0),
                'in_progress_count' => (int)($kpi['in_progress_count'] ?? 0),
                'completed_count' => $completedCount,
                'unassigned_count' => (int)($kpi['unassigned_count'] ?? 0),
                'urgent_critical_count' => (int)($kpi['urgent_critical_count'] ?? 0),
                'total_estimated_cost' => (float)$kpi['total_estimated_cost'],
                'formatted_cost' => number_format((float)$kpi['total_estimated_cost'], 2) . ' ฿',
                'completion_rate' => $completionRate,
                'available_technicians' => $availableTechCount,
                'total_technicians' => count($technicians)
            ],
            'status_breakdown' => $statusBreakdown,
            'device_breakdown' => $deviceBreakdown,
            'priority_breakdown' => $priorityBreakdown,
            'technicians' => $technicians,
            'unassigned_tickets' => $unassignedTickets,
            'recent_trends' => $trendData
        ];
    }

    /**
     * ลบรายการใบแจ้งซ่อมออกจากระบบ (สำหรับช่าง IT / Admin)
     */
    public function deleteTicket(string $ticketId): bool {
        // ดึงข้อมูลรูปภาพที่เกี่ยวข้องเพื่อลบไฟล์ออกจากเครื่อง
        $stmt = $this->pdo->prepare("SELECT issue_image, completion_image FROM repair_tickets WHERE ticket_id = :ticket_id LIMIT 1");
        $stmt->execute([':ticket_id' => $ticketId]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        // ลบไฟล์รูปถ้ามี
        if (!empty($row['issue_image']) && file_exists(__DIR__ . '/../' . $row['issue_image'])) {
            @unlink(__DIR__ . '/../' . $row['issue_image']);
        }
        if (!empty($row['completion_image']) && file_exists(__DIR__ . '/../' . $row['completion_image'])) {
            @unlink(__DIR__ . '/../' . $row['completion_image']);
        }

        // ลบแถวข้อมูลในฐานข้อมูล
        $deleteStmt = $this->pdo->prepare("DELETE FROM repair_tickets WHERE ticket_id = :ticket_id");
        return $deleteStmt->execute([':ticket_id' => $ticketId]);
    }

    /**
     * สุ่มรหัส Ticket ID อัตโนมัติ (เช่น JOB-8801)
     */
    private function generateTicketId(): string {
        do {
            $randomNum = rand(1000, 9999);
            $ticketId = 'JOB-' . $randomNum;
            
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as cnt FROM repair_tickets WHERE ticket_id = :ticket_id");
            $stmt->execute([':ticket_id' => $ticketId]);
            $exists = $stmt->fetch()['cnt'] > 0;
        } while ($exists);

        return $ticketId;
    }
}
