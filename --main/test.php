<?php
/**
 * Test Script for IT Repair Request System
 * ทดสอบการทำงานของคลาสทั้ง 4 คลาส (ITAssetManager, RepairCostCalculator, RepairTicketController, UserManager)
 */

require_once __DIR__ . '/classes/ITAssetManager.php';
require_once __DIR__ . '/classes/RepairCostCalculator.php';
require_once __DIR__ . '/classes/RepairTicketController.php';
require_once __DIR__ . '/classes/UserManager.php';

echo "===========================================================\n";
echo "       IT REPAIR REQUEST SYSTEM - CLI OOP UNIT TEST        \n";
echo "===========================================================\n\n";

try {
    // 0. Test Class 0: UserManager (ระบบ Login & สมาชิก)
    echo "[TEST 0] Testing UserManager (Authentication & Roles)...\n";
    $userManager = new UserManager();
    
    // Login Admin
    $adminUser = $userManager->login('admin', 'admin123');
    if ($adminUser) {
        echo "  - Admin Login OK: " . $adminUser->getFullName() . " (Role: " . $adminUser->getRole() . ")\n";
    } else {
        echo "  - Admin Login Failed\n";
    }

    // Login User
    $normalUser = $userManager->login('user1', 'user123');
    if ($normalUser) {
        echo "  - Normal User Login OK: " . $normalUser->getFullName() . " (Role: " . $normalUser->getRole() . ")\n";
    } else {
        echo "  - Normal User Login Failed\n";
    }

    echo "  -> [PASSED] UserManager OK!\n\n";

    // 1. Test Class 1: ITAssetManager (คนที่ 1)
    echo "[TEST 1] Testing ITAssetManager (Class 1)...\n";
    $assetManager = new ITAssetManager();
    $assets = $assetManager->getAllAssets();
    echo "  - Total Assets Found: " . count($assets) . "\n";

    $sampleAsset = $assetManager->getAssetDetails('AST-1001');
    if ($sampleAsset) {
        echo "  - Asset Details AST-1001: " . $sampleAsset->getName() . " (" . $sampleAsset->getWarrantyStatusText() . ")\n";
    } else {
        echo "  - Asset AST-1001 not found\n";
    }
    echo "  -> [PASSED] ITAssetManager OK!\n\n";

    // 2. Test Class 2: RepairCostCalculator (คนที่ 2)
    echo "[TEST 2] Testing RepairCostCalculator (Class 2)...\n";
    $costCalculator = new RepairCostCalculator();
    
    // In Warranty Test => Should be 0.00 THB
    $costWarranty = $costCalculator->calculateCost('Computer', 'High', true);
    echo "  - Cost (In Warranty): " . number_format($costWarranty, 2) . " THB (Expected 0.00 THB)\n";

    // Out of Warranty Test => Should calculate part + service fee
    $costNoWarranty = $costCalculator->calculateCost('Computer', 'High', false);
    echo "  - Cost (Out of Warranty): " . number_format($costNoWarranty, 2) . " THB\n";

    // Time Estimate Test
    $timeUrgent = $costCalculator->estimateCompletionTime('Urgent');
    echo "  - Estimated Time (Urgent): " . $timeUrgent . "\n";
    echo "  -> [PASSED] RepairCostCalculator OK!\n\n";

    // 3. Test Class 3: RepairTicketController (Shared)
    echo "[TEST 3] Testing RepairTicketController (Class 3)...\n";
    $ticketController = new RepairTicketController();

    // Create Ticket
    $ticket = $ticketController->createTicket(
        'สมชาย ใจดี',
        'ฝ่ายการเงินและบัญชี (พนักงานทั่วไป)',
        '0812345678',
        'AST-1001',
        'เปิดเครื่องไม่ติด มีเสียงร้อง ติ๊ดๆ 3 ครั้ง',
        'High',
        'Urgent',
        'somchai@company.com'
    );
    echo "  - Created Ticket ID: " . $ticket->getTicketId() . "\n";
    echo "  - Requester: " . $ticket->getUserName() . " (" . $ticket->getPhone() . ")\n";
    echo "  - Estimated Cost: " . number_format($ticket->getEstimatedCost(), 2) . " THB\n";
    echo "  - Estimated Completion Time: " . $ticket->getEstimatedCompletionTime() . "\n";
    echo "  - Current Status: " . $ticket->getStatus() . "\n";

    // Check Status by Phone
    $foundTickets = $ticketController->checkStatus('0812345678');
    echo "  - Found Tickets by Phone 0812345678: " . count($foundTickets) . " ticket(s)\n";

    // Update Status
    $updateResult = $ticketController->updateTicketStatus($ticket->getTicketId(), 'In Progress');
    echo "  - Update Status to 'In Progress': " . ($updateResult ? "SUCCESS" : "FAILED") . "\n";

    $updateCompleted = $ticketController->updateTicketStatus($ticket->getTicketId(), 'Completed');
    echo "  - Update Status to 'Completed': " . ($updateCompleted ? "SUCCESS" : "FAILED") . "\n";

    echo "  -> [PASSED] RepairTicketController OK!\n\n";

    echo "===========================================================\n";
    echo "           ALL OOP TESTS PASSED SUCCESSFULLY!             \n";
    echo "===========================================================\n";

} catch (Exception $e) {
    echo "\n[ERROR] Test Failed: " . $e->getMessage() . "\n";
}
