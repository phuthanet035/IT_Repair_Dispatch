<?php
/**
 * RESTful API Gateway (api.php)
 * สื่อกลางสำหรับให้ Frontend (UI JavaScript / Fetch API) เรียกใช้งาน Backend OOP Services
 * (รองรับอัปโหลดรูปถ่ายอาการเสียก่อนซ่อม และ รูปถ่ายรายงานซ่อมเสร็จ)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/classes/ITAssetManager.php';
require_once __DIR__ . '/classes/RepairCostCalculator.php';
require_once __DIR__ . '/classes/RepairTicketController.php';
require_once __DIR__ . '/classes/UserManager.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper สำหรับจัดการอัปโหลดไฟล์รูปภาพ
function saveUploadedImage(string $fileKey, string $folderName): ?string {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $tmpName = $_FILES[$fileKey]['tmp_name'];
    $originalName = $_FILES[$fileKey]['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($ext, $allowedExts)) {
        return null;
    }

    $uploadDir = __DIR__ . '/uploads/' . $folderName;
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newFileName = $folderName . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $destination = $uploadDir . '/' . $newFileName;

    if (move_uploaded_file($tmpName, $destination)) {
        return 'uploads/' . $folderName . '/' . $newFileName;
    }
    return null;
}

try {
    $assetManager = new ITAssetManager();
    $costCalculator = new RepairCostCalculator();
    $ticketController = new RepairTicketController();

    switch ($action) {

        // 1. ดึงรายการอุปกรณ์ทั้งหมด
        case 'get_assets':
            $assets = $assetManager->getAllAssets();
            $result = array_map(fn($a) => $a->toArray(), $assets);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // 2. ดึงรายละเอียดอุปกรณ์ชิ้นเดียว
        case 'get_asset_details':
            $assetId = $_GET['asset_id'] ?? '';
            $asset = $assetManager->getAssetDetails($assetId);
            if ($asset) {
                echo json_encode(['success' => true, 'data' => $asset->toArray()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่พบอุปกรณ์ที่ระบุ']);
            }
            break;

        // 3. ประเมินราคาและเวลา real-time
        case 'preview_cost':
            $assetId = $_GET['asset_id'] ?? $_POST['asset_id'] ?? '';
            $severity = $_GET['severity'] ?? $_POST['severity'] ?? 'Medium';
            $priority = $_GET['priority'] ?? $_POST['priority'] ?? 'Normal';

            $asset = $assetManager->getAssetDetails($assetId);
            if (!$asset) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบอุปกรณ์']);
                break;
            }

            $cost = $costCalculator->calculateCost($asset->getDeviceType(), $severity, $asset->isWarranty());
            $time = $costCalculator->estimateCompletionTime($priority);

            echo json_encode([
                'success' => true,
                'data' => [
                    'asset' => $asset->toArray(),
                    'estimated_cost' => $cost,
                    'formatted_cost' => number_format($cost, 2) . ' บาท',
                    'estimated_completion_time' => $time
                ]
            ]);
            break;

        // 4. สร้างใบแจ้งซ่อม (รองรับแนบรูปถ่ายอาการเสียก่อนซ่อม และระบุช่าง)
        case 'create_ticket':
            $userName = $_POST['user_name'] ?? '';
            $department = $_POST['department'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $userEmail = $_POST['user_email'] ?? '';
            $assetId = $_POST['asset_id'] ?? '';
            $issueDescription = $_POST['issue_description'] ?? '';
            $severity = $_POST['severity'] ?? 'Medium';
            $priority = $_POST['priority'] ?? 'Normal';
            $assignedTechnician = $_POST['assigned_technician'] ?? '';

            // หากส่งมาแบบ JSON แทน Multipart
            if (empty($userName) && empty($_POST)) {
                $input = json_decode(file_get_contents('php://input'), true) ?? [];
                $userName = $input['user_name'] ?? '';
                $department = $input['department'] ?? '';
                $phone = $input['phone'] ?? '';
                $userEmail = $input['user_email'] ?? '';
                $assetId = $input['asset_id'] ?? '';
                $issueDescription = $input['issue_description'] ?? '';
                $severity = $input['severity'] ?? 'Medium';
                $priority = $input['priority'] ?? 'Normal';
                $assignedTechnician = $input['assigned_technician'] ?? '';
            }

            if (empty($userName) || empty($phone) || empty($assetId) || empty($issueDescription)) {
                echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
                break;
            }

            // บันทึกรูปถ่ายอาการเสีย (ถ้ามี)
            $issueImagePath = saveUploadedImage('issue_image', 'issues');

            $ticket = $ticketController->createTicket(
                $userName,
                $department,
                $phone,
                $assetId,
                $issueDescription,
                $severity,
                $priority,
                $userEmail,
                $issueImagePath,
                $assignedTechnician
            );

            echo json_encode([
                'success' => true,
                'message' => 'สร้างใบแจ้งซ่อมสำเร็จ!',
                'data' => $ticket->toArray()
            ]);
            break;

        // 5. เช็คสถานะด้วย Ticket ID หรือ เบอร์โทรศัพท์
        case 'check_status':
            $query = $_GET['query'] ?? $_POST['query'] ?? '';
            if (empty($query)) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุ Ticket ID หรือ เบอร์โทรศัพท์']);
                break;
            }

            $tickets = $ticketController->checkStatus($query);
            $result = array_map(fn($t) => $t->toArray(), $tickets);

            echo json_encode([
                'success' => true,
                'count' => count($result),
                'data' => $result
            ]);
            break;

        // 6. ดึงตั๋วแจ้งซ่อมทั้งหมด (พร้อมสถิติจำนวนงานที่ช่างแต่ละคนรับในเดือนนี้ สรุปภาระงานช่าง และสถานะพร้อม/พักงาน)
        case 'get_all_tickets':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userManager = new UserManager();
            $currentUser = $userManager->getCurrentUser();

            $tickets = $ticketController->getAllTickets();
            $result = array_map(fn($t) => $t->toArray(), $tickets);
            $stats = $ticketController->getTechnicianMonthlyStats();
            $workload = $ticketController->getTechnicianWorkloadSummary();
            $dutyStatuses = $userManager->getTechniciansWithDutyStatus();

            $myTechName = $currentUser ? $currentUser->getFullName() : ($_SESSION['user_name'] ?? '');
            $myUsername = $currentUser ? $currentUser->getUsername() : ($_SESSION['username'] ?? '');
            $myCount = $myTechName ? $ticketController->getTechnicianJobCountThisMonth($myTechName) : 0;

            echo json_encode([
                'success' => true,
                'data' => $result,
                'stats' => $stats,
                'workload' => $workload,
                'duty_statuses' => $dutyStatuses,
                'current_user' => $currentUser ? $currentUser->toArray() : null,
                'my_tech_name' => $myTechName,
                'my_username' => $myUsername,
                'my_job_count' => $myCount
            ]);
            break;

        // 6.0 ดึงสถิติภาพรวมแดชบอร์ดอย่างละเอียด (Dashboard Analytics & Dispatch Data)
        case 'get_dashboard_analytics':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userManager = new UserManager();
            $currentUser = $userManager->getCurrentUser();

            $analytics = $ticketController->getDashboardAnalytics();
            $analytics['current_user'] = $currentUser ? $currentUser->toArray() : null;

            echo json_encode([
                'success' => true,
                'data' => $analytics
            ]);
            break;

        // 6.0 ล็อกอินแอดมินแบบเร่งด่วน (Quick Admin Login)
        case 'quick_admin_login':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userManager = new UserManager();
            $user = $userManager->login('Admin', '1234') ?? $userManager->login('admin', '1234');
            if ($user && $user->isAdmin()) {
                echo json_encode([
                    'success' => true,
                    'message' => '👑 เข้าสู่ระบบในฐานะแอดมิน (Admin / 1234) สำเร็จแล้ว!',
                    'user' => $user->toArray()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถเข้าสู่ระบบแอดมินได้']);
            }
            break;

        // 6.1 แอดมินมอบหมายงานให้ช่าง หรือเปลี่ยนตัวช่าง (Assign / Reassign Technician)
        case 'assign_technician':
        case 'reassign_technician':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userManager = new UserManager();
            $currentUser = $userManager->getCurrentUser();

            $rawInput = file_get_contents('php://input');
            $json = !empty($rawInput) ? json_decode($rawInput, true) : null;
            $adminPass = $json['admin_password'] ?? $_POST['admin_password'] ?? $_GET['admin_password'] ?? '';
            $adminUserReq = $json['admin_username'] ?? $_POST['admin_username'] ?? $_GET['admin_username'] ?? 'Admin';

            // ตรวจสอบสิทธิ์ Admin หรือยืนยันด้วยรหัสแอดมิน 1234
            if (!$currentUser || !$currentUser->isAdmin()) {
                if ($adminPass === '1234') {
                    $matchedAdmin = (stripos($adminUserReq, 'Admin') !== false) ? $adminUserReq : 'Admin';
                    $currentUser = $userManager->login($matchedAdmin, '1234') ?? $userManager->login('Admin', '1234') ?? $userManager->login('admin', '1234');
                }
            }

            if (!$currentUser || !$currentUser->isAdmin()) {
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => '🔒 สิทธิ์ไม่เพียงพอ! เฉพาะแอดมิน (Admin) เท่านั้นที่สามารถมอบหมายงานช่างได้ กรุณากรอกรหัสแอดมิน 1234 หรือเข้าสู่ระบบ'
                ]);
                break;
            }

            $ticketId = $json['ticket_id'] ?? $_POST['ticket_id'] ?? $_GET['ticket_id'] ?? '';
            $techName = $json['assigned_technician'] ?? $_POST['assigned_technician'] ?? $_GET['assigned_technician'] ?? '';

            if (empty($ticketId) || empty($techName)) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุ Ticket ID และชื่อช่างที่รับงาน']);
                break;
            }

            $success = $ticketController->reassignTechnician($ticketId, $techName);
            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => "มอบหมายงาน $ticketId ให้ช่าง $techName เรียบร้อยแล้ว (สถานะปรับเป็น กำลังซ่อม)"
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถมอบหมายงานได้']);
            }
            break;

        // 6.1.1 แอดมินดึงงานกลับเข้ากองกลาง (Unassign Technician)
        case 'unassign_technician':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userManager = new UserManager();
            $currentUser = $userManager->getCurrentUser();

            $rawInput = file_get_contents('php://input');
            $json = !empty($rawInput) ? json_decode($rawInput, true) : null;
            $adminPass = $json['admin_password'] ?? $_POST['admin_password'] ?? $_GET['admin_password'] ?? '';

            if (!$currentUser || !$currentUser->isAdmin()) {
                if ($adminPass === '1234') {
                    $currentUser = $userManager->login('Admin', '1234') ?? $userManager->login('admin', '1234');
                }
            }

            if (!$currentUser || !$currentUser->isAdmin()) {
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => '🔒 สิทธิ์ไม่เพียงพอ! เฉพาะแอดมิน (Admin) เท่านั้นที่สามารถดึงงานกลับเข้ากองกลางได้'
                ]);
                break;
            }

            $ticketId = $json['ticket_id'] ?? $_POST['ticket_id'] ?? $_GET['ticket_id'] ?? '';

            if (empty($ticketId)) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุ Ticket ID']);
                break;
            }

            $success = $ticketController->unassignTechnician($ticketId);
            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => "ดึงงาน $ticketId กลับเข้ากองกลางเรียบร้อยแล้ว (สถานะปรับเป็น รอดำเนินการ)"
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถดึงงานกลับเข้ากองกลางได้']);
            }
            break;

        // 6.1.2 สุ่มแจกงานอัจฉริยะ (Auto-Dispatch Ticket)
        case 'auto_dispatch':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userManager = new UserManager();
            $currentUser = $userManager->getCurrentUser();

            $rawInput = file_get_contents('php://input');
            $json = !empty($rawInput) ? json_decode($rawInput, true) : null;
            $adminPass = $json['admin_password'] ?? $_POST['admin_password'] ?? $_GET['admin_password'] ?? '';

            if (!$currentUser || !$currentUser->isAdmin()) {
                if ($adminPass === '1234') {
                    $currentUser = $userManager->login('Admin', '1234') ?? $userManager->login('admin', '1234');
                }
            }

            if (!$currentUser || !$currentUser->isAdmin()) {
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => '🔒 สิทธิ์ไม่เพียงพอ! เฉพาะแอดมิน (Admin) เท่านั้นที่สามารถใช้งานระบบแจกงานอัจฉริยะได้'
                ]);
                break;
            }

            $ticketId = $json['ticket_id'] ?? $_POST['ticket_id'] ?? $_GET['ticket_id'] ?? '';

            if (empty($ticketId)) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุ Ticket ID']);
                break;
            }

            try {
                $dispatchResult = $ticketController->autoDispatchTicket($ticketId);
                echo json_encode([
                    'success' => true,
                    'message' => "🎯 ระบบแจกงาน $ticketId ให้ช่าง [{$dispatchResult['technician']}] เรียบร้อยแล้ว! (ภาระงานปัจจุบัน: {$dispatchResult['active_jobs']} งาน)",
                    'data' => $dispatchResult
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        // 6.2 ช่างกดรับงานเอง (Claim / Accept Job by logged-in technician)
        case 'accept_job':
        case 'claim_job':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userManager = new UserManager();
            $currentUser = $userManager->getCurrentUser();

            if ($currentUser && $currentUser->isUser()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '❌ ผู้ใช้งานทั่วไปไม่สามารถกดรับงานได้ (สิทธิ์เฉพาะช่างซ่อมเท่านั้น)']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $ticketId = $input['ticket_id'] ?? $_POST['ticket_id'] ?? '';
            
            // ใช้ชื่อช่างที่ล็อกอินอยู่ หรือจากพารามิเตอร์หากไม่มี
            $techName = $currentUser ? $currentUser->getFullName() : ($input['assigned_technician'] ?? $_POST['assigned_technician'] ?? '');

            if (empty($ticketId)) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุ Ticket ID ที่ต้องการรับงาน']);
                break;
            }

            if (empty($techName)) {
                echo json_encode(['success' => false, 'message' => 'กรุณาล็อกอินด้วยบัญชีช่างซ่อมก่อนกดรับงาน']);
                break;
            }

            try {
                $success = $ticketController->claimTicket($ticketId, $techName);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => "🎉 ช่าง [$techName] กดรับงาน $ticketId เรียบร้อยแล้ว! (สถานะ: กำลังซ่อม)"]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'ไม่สามารถกดรับงานได้']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        // 6.3 ปรับสถานะปฏิบัติงานของช่าง (พร้อมรับงาน / พักงาน)
        case 'toggle_duty_status':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userManager = new UserManager();
            $currentUser = $userManager->getCurrentUser();

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $username = $currentUser ? $currentUser->getUsername() : ($_SESSION['username'] ?? $input['username'] ?? $_POST['username'] ?? '');
            $newStatus = $input['status'] ?? $_POST['status'] ?? 'Available';

            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'กรุณาล็อกอินก่อน']);
                break;
            }

            $success = $userManager->updateDutyStatus($username, $newStatus);
            if ($success) {
                $statusText = $newStatus === 'Available' ? '🟢 พร้อมรับงาน (On Duty)' : '☕ พักงาน / ลาพัก (Resting)';
                echo json_encode(['success' => true, 'message' => "อัปเดตสถานะปฏิบัติงานเป็น $statusText เรียบร้อยแล้ว"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการปรับสถานะ']);
            }
            break;

        // 7. ช่างอัปเดตสถานะงานซ่อม (เฉพาะช่างผู้รับผิดชอบงานเท่านั้นที่กดซ่อมเสร็จได้)
        case 'update_status':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userManager = new UserManager();
            $currentUser = $userManager->getCurrentUser();

            if ($currentUser && $currentUser->isUser()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '❌ ผู้ใช้งานทั่วไปไม่สามารถเปลี่ยนสถานะงานซ่อมได้ (สิทธิ์เฉพาะช่างซ่อมเท่านั้น)']);
                break;
            }

            $ticketId = $_POST['ticket_id'] ?? '';
            $newStatus = $_POST['new_status'] ?? '';
            $techName = $currentUser ? $currentUser->getFullName() : ($_POST['assigned_technician'] ?? $_SESSION['user_name'] ?? '');

            if (empty($ticketId) && empty($_POST)) {
                $input = json_decode(file_get_contents('php://input'), true) ?? [];
                $ticketId = $input['ticket_id'] ?? '';
                $newStatus = $input['new_status'] ?? '';
            }

            if (empty($ticketId) || empty($newStatus)) {
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                break;
            }

            // ตรวจสอบสิทธิ์กรณีซ่อมเสร็จ (Completed): ต้องเป็นช่างผู้รับผิดชอบงานนั้นเท่านั้น
            $ticketList = $ticketController->checkStatus($ticketId);
            if (!empty($ticketList)) {
                $existingTicket = $ticketList[0];
                $assignedTech = $existingTicket->getAssignedTechnician();

                if ($newStatus === 'Completed') {
                    if (empty($assignedTech)) {
                        echo json_encode(['success' => false, 'message' => 'งานซ่อมนี้ยังไม่มีช่างรับงาน! ต้องมีช่างกดรับงานก่อนจึงจะบันทึกซ่อมเสร็จได้']);
                        break;
                    }

                    // หากผู้ล็อกอินไม่ใช่ช่างผู้รับงาน และไม่ใช่ Admin
                    if ($currentUser && $currentUser->isTechnician() && $assignedTech !== $currentUser->getFullName()) {
                        echo json_encode(['success' => false, 'message' => "❌ เฉพาะช่างผู้รับผิดชอบงานนี้ [$assignedTech] เท่านั้นที่สามารถกดซ่อมเสร็จได้!"]);
                        break;
                    }
                }
            }

            // บันทึกรูปถ่ายเมื่อซ่อมเสร็จ (ถ้ามี)
            $completionImagePath = saveUploadedImage('completion_image', 'completions');

            $success = $ticketController->updateTicketStatus($ticketId, $newStatus, $completionImagePath, $techName);

            if ($success) {
                echo json_encode(['success' => true, 'message' => "อัปเดตสถานะ $ticketId เป็น $newStatus โดยช่าง $techName เรียบร้อยแล้ว"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ']);
            }
            break;

        // 8. ลบรายการใบแจ้งซ่อม (สำหรับ Admin เท่านั้น)
        case 'delete_ticket':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userManager = new UserManager();
            $currentUser = $userManager->getCurrentUser();

            if (!$currentUser || !$currentUser->isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '🔒 สิทธิ์ไม่เพียงพอ! เฉพาะแอดมิน (Admin) เท่านั้นที่สามารถลบใบแจ้งซ่อมได้']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $ticketId = $_GET['ticket_id'] ?? $input['ticket_id'] ?? '';

            if (empty($ticketId)) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุ Ticket ID ที่ต้องการลบ']);
                break;
            }

            $deleted = $ticketController->deleteTicket($ticketId);
            if ($deleted) {
                echo json_encode(['success' => true, 'message' => "ลบใบแจ้งซ่อมรหัส $ticketId สำเร็จเรียบร้อยแล้ว"]);
            } else {
                echo json_encode(['success' => false, 'message' => "ไม่พบใบแจ้งซ่อมรหัส $ticketId"]);
            }
            break;

        // 9. ดึงคอนฟิกตั้งค่าการส่งอีเมล
        case 'get_mail_config':
            $configFile = __DIR__ . '/config/mail.php';
            $config = file_exists($configFile) ? include $configFile : ['smtp_active' => false, 'driver' => 'brevo_api'];
            if (!empty($config['password'])) {
                $config['password_masked'] = str_repeat('*', min(12, strlen($config['password'])));
            }
            if (!empty($config['brevo_api_key'])) {
                $keyLen = strlen($config['brevo_api_key']);
                if ($keyLen > 12) {
                    $config['brevo_api_key_masked'] = substr($config['brevo_api_key'], 0, 8) . '...' . substr($config['brevo_api_key'], -4);
                } else {
                    $config['brevo_api_key_masked'] = str_repeat('*', $keyLen);
                }
            }
            echo json_encode(['success' => true, 'data' => $config]);
            break;

        // 10. บันทึกคอนฟิกตั้งค่าการส่งอีเมล (สำหรับ Admin/ช่าง)
        case 'save_mail_config':
            $driver = trim($_POST['driver'] ?? 'brevo_api');
            $smtpActive = isset($_POST['smtp_active']) && ($_POST['smtp_active'] === 'true' || $_POST['smtp_active'] === '1' || $_POST['smtp_active'] === true);
            $brevoApiKey = trim($_POST['brevo_api_key'] ?? '');
            $host = trim($_POST['host'] ?? 'smtp-relay.brevo.com');
            $port = (int)($_POST['port'] ?? 587);
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $fromEmail = trim($_POST['from_email'] ?? 'no-reply@company.com');
            $fromName = trim($_POST['from_name'] ?? 'ระบบแจ้งซ่อมอุปกรณ์ IT');

            // หาก password / brevo_api_key เป็นค่าเดิมที่ถูก mask ไว้ ไม่ต้องเขียนทับ
            $oldConfig = file_exists(__DIR__ . '/config/mail.php') ? include __DIR__ . '/config/mail.php' : [];
            if (strpos($password, '*') !== false && !empty($oldConfig['password'])) {
                $password = $oldConfig['password'];
            }
            if ((strpos($brevoApiKey, '*') !== false || strpos($brevoApiKey, '...') !== false) && !empty($oldConfig['brevo_api_key'])) {
                $brevoApiKey = $oldConfig['brevo_api_key'];
            }

            $configContent = "<?php\n/**\n * ตั้งค่าระบบส่งอีเมลแจ้งเตือน (Brevo API / SMTP / Local Simulation)\n */\nreturn [\n";
            $configContent .= "    'driver'         => " . var_export($driver, true) . ",\n";
            $configContent .= "    'smtp_active'    => " . ($smtpActive ? 'true' : 'false') . ",\n";
            $configContent .= "    'brevo_api_key'  => " . var_export($brevoApiKey, true) . ",\n";
            $configContent .= "    'from_email'     => " . var_export($fromEmail, true) . ",\n";
            $configContent .= "    'from_name'      => " . var_export($fromName, true) . ",\n";
            $configContent .= "    'host'           => " . var_export($host, true) . ",\n";
            $configContent .= "    'port'           => " . var_export($port, true) . ",\n";
            $configContent .= "    'username'       => " . var_export($username, true) . ",\n";
            $configContent .= "    'password'       => " . var_export($password, true) . "\n";
            $configContent .= "];\n";

            file_put_contents(__DIR__ . '/config/mail.php', $configContent);
            echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่าระบบส่งอีเมลเรียบร้อยแล้ว']);
            break;

        // 11. ทดสอบการส่งอีเมล
        case 'test_send_email':
            $targetEmail = $_POST['test_email'] ?? $_GET['test_email'] ?? '';
            $notifier = new EmailNotifier();
            $result = $notifier->sendTestEmail($targetEmail);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid Action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
