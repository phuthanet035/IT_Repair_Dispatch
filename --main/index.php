<?php
/**
 * Main Web Application (index.php)
 * ระบบแจ้งซ่อมอุปกรณ์ IT (แนบรูปถ่ายอาการเสีย และ รูปรายงานซ่อมเสร็จ)
 */

require_once __DIR__ . '/classes/UserManager.php';

$userManager = new UserManager();
$currentUser = $userManager->getCurrentUser();
$isUser = $currentUser ? $currentUser->isUser() : false;
$isTech = $currentUser ? $currentUser->isTechnician() : false;
$isAdmin = $currentUser ? $currentUser->isAdmin() : false;
$isTechOrAdmin = $currentUser ? $currentUser->isTechOrAdmin() : false;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบแจ้งซ่อมอุปกรณ์ IT (IT Repair Request System)</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Kanit -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Kanit', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

    <!-- Header / Navbar: Clean, Organized, Beautiful Two-Tier Design -->
    <header class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white shadow-xl sticky top-0 z-50 border-b border-indigo-700/60 backdrop-blur-md">
        <!-- Top Bar: Brand & User Profile -->
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <!-- Brand Logo & Title -->
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-amber-400 via-amber-300 to-amber-500 text-slate-950 flex items-center justify-center text-xl shadow-lg shadow-amber-500/25 border border-amber-200">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl md:text-2xl font-black tracking-wide text-white">ระบบแจ้งซ่อมอุปกรณ์ IT</h1>
                        <span class="hidden sm:inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full <?= $isAdmin ? 'bg-amber-400/20 text-amber-300 border border-amber-400/40' : ($isTech ? 'bg-blue-400/20 text-blue-300 border border-blue-400/40' : 'bg-emerald-400/20 text-emerald-300 border border-emerald-400/40') ?>">
                            <?= $isAdmin ? '👑 แอดมิน (Admin)' : ($isTech ? '👨‍🔧 ช่างซ่อม (Tech)' : '👤 ผู้ใช้งาน (User)') ?>
                        </span>
                    </div>
                    <p class="text-xs text-indigo-200">IT Service Desk &amp; Repair Management • OOP/OOAD Architecture</p>
                </div>
            </div>

            <!-- Right: User Profile & Actions -->
            <div class="flex items-center gap-3">
                <?php if ($currentUser): ?>
                    <div class="flex items-center gap-3 bg-white/10 hover:bg-white/15 backdrop-blur-md pl-3.5 pr-2 py-1.5 rounded-2xl border border-white/20 transition shadow-sm">
                        <div class="w-8 h-8 rounded-xl <?= $isAdmin ? 'bg-amber-400 text-slate-950' : ($isTech ? 'bg-blue-500 text-white' : 'bg-emerald-500 text-white') ?> flex items-center justify-center font-bold text-xs shadow-inner">
                            <?= $isAdmin ? '<i class="fa-solid fa-crown"></i>' : ($isTech ? '<i class="fa-solid fa-wrench"></i>' : '<i class="fa-solid fa-user"></i>') ?>
                        </div>
                        <div class="text-left leading-tight pr-1">
                            <span class="text-xs font-bold text-white block max-w-[140px] truncate"><?= htmlspecialchars($currentUser->getFullName()) ?></span>
                            <span class="text-[10px] <?= $isAdmin ? 'text-amber-300 font-bold' : ($isTech ? 'text-blue-300 font-bold' : 'text-emerald-300 font-bold') ?>">
                                <?= htmlspecialchars($currentUser->getRoleLabel()) ?>
                            </span>
                        </div>
                        <a href="logout.php" title="ออกจากระบบ (Logout)" class="w-7 h-7 rounded-lg bg-rose-500/20 hover:bg-rose-600 text-rose-300 hover:text-white flex items-center justify-center transition text-xs ml-1">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="bg-gradient-to-r from-amber-400 to-amber-300 hover:from-amber-300 hover:to-amber-200 text-slate-950 font-bold px-4 py-2 rounded-xl text-xs transition shadow-md flex items-center gap-1.5 border border-amber-300">
                        <i class="fa-solid fa-right-to-bracket"></i> เข้าสู่ระบบ (Login)
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bottom Row: Clean, Dedicated Horizontal Navigation Bar (จัดระเบียบเรียบร้อยไม่ซ้อนทับ) -->
        <div class="bg-indigo-950/50 border-t border-white/10 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between overflow-x-auto scrollbar-none">
                <nav class="flex items-center gap-2 text-xs font-medium whitespace-nowrap w-full">
                    <?php if (!$currentUser || $isUser): ?>
                        <!-- 1. ผู้ใช้งานทั่วไป (User): เพิ่มงาน และ ติดตามงานได้เท่านั้น -->
                        <button onclick="switchTab('create-ticket')" id="tab-btn-create-ticket" class="tab-btn px-4 py-2 rounded-xl bg-white text-indigo-950 font-bold shadow-md hover:bg-white/90 transition flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-plus-circle text-amber-500"></i> แจ้งซ่อม (เพิ่มงาน)
                        </button>
                        <button onclick="switchTab('check-status')" id="tab-btn-check-status" class="tab-btn px-4 py-2 rounded-xl bg-white/10 text-indigo-100 hover:bg-white/20 hover:text-white transition flex items-center gap-2 font-medium cursor-pointer">
                            <i class="fa-solid fa-magnifying-glass"></i> ติดตามสถานะ (ติดตามงาน)
                        </button>
                    <?php endif; ?>

                    <?php if ($isTech): ?>
                        <!-- 2. ช่างเทคนิค (Technician): กดรับงานเอง หรือ รับงานจากแอดมินได้แค่นั้น -->
                        <button onclick="switchTab('tech-dashboard')" id="tab-btn-tech-dashboard" class="tab-btn px-4 py-2 rounded-xl bg-white text-indigo-950 font-bold shadow-md hover:bg-white/90 transition flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-user-wrench text-blue-600"></i> หน้างานช่าง (งานที่ได้รับ &amp; กดรับงาน)
                        </button>
                        <button onclick="switchTab('check-status')" id="tab-btn-check-status" class="tab-btn px-4 py-2 rounded-xl bg-white/10 text-indigo-100 hover:bg-white/20 hover:text-white transition flex items-center gap-2 font-medium cursor-pointer">
                            <i class="fa-solid fa-magnifying-glass"></i> ตรวจสอบงานทั้งหมด
                        </button>
                    <?php endif; ?>

                    <?php if ($isAdmin): ?>
                        <!-- 3. ผู้ดูแลระบบ (Admin): รับงานจากผู้ใช้ ส่งงานให้ช่าง และ ติดตามงานได้เท่านั้น -->
                        <a href="dashboard.php" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-400 via-amber-300 to-amber-400 hover:from-amber-300 hover:to-amber-200 text-slate-950 font-black transition flex items-center gap-2 shadow-md shadow-amber-500/20 border border-amber-300">
                            <i class="fa-solid fa-chart-pie text-indigo-950"></i> 👑 ศูนย์แดชบอร์ด &amp; แจกงานช่าง
                        </a>
                        <button onclick="switchTab('tech-dashboard')" id="tab-btn-tech-dashboard" class="tab-btn px-4 py-2 rounded-xl bg-white text-indigo-950 font-bold shadow-md hover:bg-white/90 transition flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-clipboard-list text-indigo-600"></i> ติดตามงานซ่อมทั้งหมด (Admin Monitor)
                        </button>
                        <button onclick="switchTab('check-status')" id="tab-btn-check-status" class="tab-btn px-4 py-2 rounded-xl bg-white/10 text-indigo-100 hover:bg-white/20 hover:text-white transition flex items-center gap-2 font-medium cursor-pointer">
                            <i class="fa-solid fa-magnifying-glass"></i> ค้นหาสถานะงาน
                        </button>
                        <button onclick="switchTab('asset-manager')" id="tab-btn-asset-manager" class="tab-btn px-4 py-2 rounded-xl bg-white/10 text-indigo-100 hover:bg-white/20 hover:text-white transition flex items-center gap-2 font-medium cursor-pointer">
                            <i class="fa-solid fa-desktop"></i> คลังอุปกรณ์ IT
                        </button>
                        <button onclick="openMailConfigModal()" class="tab-btn px-3.5 py-2 rounded-xl bg-white/10 text-indigo-100 hover:bg-white/20 hover:text-white transition flex items-center gap-2 ml-auto cursor-pointer" title="ตั้งค่าระบบแจ้งเตือนอีเมล Brevo">
                            <i class="fa-solid fa-envelope"></i> ตั้งค่าอีเมล
                        </button>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 py-8 flex-grow w-full">

        <!-- ALERT NOTIFICATION BANNER -->
        <div id="toast-banner" class="hidden mb-6 p-4 rounded-xl shadow-md flex items-center justify-between border">
            <div class="flex items-center gap-3">
                <i id="toast-icon" class="text-2xl"></i>
                <span id="toast-message" class="font-medium"></span>
            </div>
            <button onclick="hideToast()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <!-- ================= TAB 1: CREATE TICKET (แจ้งซ่อมอุปกรณ์) ================= -->
        <section id="tab-create-ticket" class="tab-content">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Column -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">1</div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">กรอกข้อมูลแจ้งซ่อมอุปกรณ์ IT</h2>
                            <p class="text-sm text-slate-500">สามารถแนบรูปถ่ายอาการเสียเพื่อความรวดเร็วในการซ่อม</p>
                        </div>
                    </div>

                    <form id="create-ticket-form" onsubmit="handleCreateTicket(event)">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล ผู้แจ้งซ่อม <span class="text-red-500">*</span></label>
                                <input type="text" id="user_name" required value="<?= $currentUser ? htmlspecialchars($currentUser->getFullName()) : '' ?>" placeholder="เช่น นายสมชาย ใจดี" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">ตำแหน่ง (Position) <span class="text-red-500">*</span></label>
                                <select id="position" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                                    <option value="">-- เลือกตำแหน่ง --</option>
                                    <option value="พนักงานทั่วไป (Staff)">พนักงานทั่วไป (Staff)</option>
                                    <option value="หัวหน้างาน / ผู้จัดการ (Manager)">หัวหน้างาน / ผู้จัดการ (Manager)</option>
                                    <option value="เจ้าหน้าที่ IT / ช่าง (IT Staff)">เจ้าหน้าที่ IT / ช่าง (IT Staff)</option>
                                    <option value="อาจารย์ / บุคลากร (Faculty)">อาจารย์ / บุคลากร (Faculty)</option>
                                    <option value="นักเรียน / นักศึกษา (Student)">นักเรียน / นักศึกษา (Student)</option>
                                    <option value="ผู้บริหาร (Executive)">ผู้บริหาร (Executive)</option>
                                    <option value="อื่นๆ (Other)">อื่นๆ (Other)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">แผนก / ฝ่าย <span class="text-red-500">*</span></label>
                                <select id="department" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                                    <option value="">-- เลือกแผนก / ฝ่าย --</option>
                                    <option value="ฝ่ายการเงินและบัญชี">ฝ่ายการเงินและบัญชี</option>
                                    <option value="ฝ่ายบริหารทรัพยากรบุคคล (HR)">ฝ่ายบริหารทรัพยากรบุคคล (HR)</option>
                                    <option value="ฝ่าย IT / เทคโนโลยีสารสนเทศ">ฝ่าย IT / เทคโนโลยีสารสนเทศ</option>
                                    <option value="ฝ่ายการตลาดและขาย">ฝ่ายการตลาดและขาย</option>
                                    <option value="ฝ่ายการผลิต / ปฏิบัติการ">ฝ่ายการผลิต / ปฏิบัติการ</option>
                                    <option value="ฝ่ายบริการลูกค้า / ต้อนรับ">ฝ่ายบริการลูกค้า / ต้อนรับ</option>
                                    <option value="ฝ่ายวิชาการ / วิจัย">ฝ่ายวิชาการ / วิจัย</option>
                                    <option value="สำนักงานกลาง / ผู้บริหาร">สำนักงานกลาง / ผู้บริหาร</option>
                                    <option value="อื่นๆ">อื่นๆ</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                                <input type="tel" id="phone" required value="<?= $currentUser ? htmlspecialchars($currentUser->getPhone()) : '' ?>" placeholder="เช่น 0812345678" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">อีเมลผู้แจ้ง (สำหรับรับแจ้งเตือน)</label>
                                <input type="email" id="user_email" value="<?= $currentUser ? htmlspecialchars($currentUser->getEmail()) : '' ?>" placeholder="เช่น user@company.com" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">เลือกอุปกรณ์ IT ที่จะแจ้งซ่อม <span class="text-red-500">*</span></label>
                                <select id="asset_id" required onchange="handleAssetSelectChange()" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                                    <option value="">-- กำลังโหลดรายการอุปกรณ์... --</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">ระดับอาการเสีย (Issue Severity)</label>
                                <select id="severity" onchange="updatePreviewCost()" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                                    <option value="Low">Low - อาการเล็กน้อย (ลงโปรแกรม / สายหลุด)</option>
                                    <option value="Medium" selected>Medium - อาการปานกลาง (RAM / หัวพิมพ์)</option>
                                    <option value="High">High - อาการหนัก (เปิดไม่ติด / จอแตก)</option>
                                    <option value="Critical">Critical - ชำรุดวิกฤต (ต้องเปลี่ยนชิ้นส่วนหลัก)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">ระดับความสำคัญ (Priority)</label>
                                <select id="priority" onchange="updatePreviewCost()" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                                    <option value="Low">Low - ความสำคัญต่ำ (แก้ไขใน 48 ชม.)</option>
                                    <option value="Normal" selected>Normal - ปกติ (แก้ไขใน 24 ชม.)</option>
                                    <option value="High">High - สูง (แก้ไขใน 6 ชม.)</option>
                                    <option value="Urgent">Urgent - ด่วนมาก/กระทบงานหลัก (แก้ไขใน 2 ชม.)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-5 p-3.5 rounded-xl bg-indigo-50/80 border border-indigo-100 text-xs text-indigo-900 flex items-center gap-2.5">
                            <i class="fa-solid fa-circle-info text-indigo-600 text-lg"></i>
                            <div>
                                <span class="font-bold block">กระบวนการรับงานช่าง:</span>
                                <span class="text-indigo-700">ใบแจ้งซ่อมจะส่งเข้ากองกลาง (สถานะ: รอดำเนินการ) โดยช่างซ่อมจะเข้ามากดรับงานเอง</span>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="block text-sm font-medium text-slate-700 mb-1">รายละเอียดอาการเสีย <span class="text-red-500">*</span></label>
                            <textarea id="issue_description" required rows="3" placeholder="ระบุรายละเอียดอาการเสีย เช่น เครื่องเปิดไม่ติด มีเสียงดัง หรือ พิมพ์งานไม่ได้..." class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"></textarea>
                        </div>

                        <!-- 📸 Attach Photo Field (อาการเสีย) -->
                        <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200">
                            <label class="block text-sm font-bold text-slate-800 mb-1 flex items-center gap-2">
                                <i class="fa-solid fa-camera text-indigo-600"></i> แนบรูปถ่ายอาการเสียก่อนซ่อม (Issue Photo)
                            </label>
                            <p class="text-xs text-slate-500 mb-2">ถ่ายหรือแนบไฟล์รูปภาพหน้าจอ/ตัวเครื่องที่ชำรุด (รองรับ JPG, PNG, WEBP)</p>
                            <input type="file" id="issue_image_file" accept="image/*" class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition">
                        </div>

                        <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-lg shadow-lg hover:shadow-indigo-200 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-paper-plane"></i> ยืนยันส่งใบแจ้งซ่อม
                        </button>
                    </form>
                </div>

                <!-- Preview Column (RepairCostCalculator Output) -->
                <div class="bg-gradient-to-br from-slate-900 to-indigo-950 text-white rounded-2xl shadow-xl p-6 md:p-8 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-700">
                            <h3 class="text-lg font-bold flex items-center gap-2">
                                <i class="fa-solid fa-calculator text-amber-400"></i> ประเมินราคา & เวลา (Real-time)
                            </h3>
                            <span class="text-xs bg-indigo-500/30 text-indigo-300 border border-indigo-500/40 px-2.5 py-1 rounded-full">Class 2 Calculator</span>
                        </div>

                        <!-- Asset Selected Status -->
                        <div id="preview-asset-info" class="mb-6 p-4 rounded-xl bg-slate-800/80 border border-slate-700">
                            <p class="text-xs text-slate-400 mb-1">อุปกรณ์ที่เลือก:</p>
                            <h4 id="preview-asset-name" class="font-bold text-slate-200 text-base">กรุณาเลือกอุปกรณ์ในฟอร์ม</h4>
                            <div class="flex items-center gap-2 mt-2">
                                <span id="preview-warranty-badge" class="inline-block text-xs px-2.5 py-0.5 rounded-full bg-slate-700 text-slate-300">-</span>
                                <span id="preview-device-type" class="text-xs text-slate-400"></span>
                            </div>
                        </div>

                        <!-- Cost Estimation Box -->
                        <div class="mb-6">
                            <p class="text-xs text-slate-400 mb-1">ราคาประเมินค่าซ่อมเบื้องต้น:</p>
                            <div class="text-3xl font-extrabold text-amber-400 tracking-tight" id="preview-cost-display">0.00 บาท</div>
                            <p class="text-xs text-slate-400 mt-1" id="preview-cost-note">* อุปกรณ์ในประกัน ฟรีค่าอะไหล่และค่าบริการ 100%</p>
                        </div>

                        <!-- Time Estimation Box -->
                        <div class="mb-6 p-4 rounded-xl bg-indigo-900/40 border border-indigo-700/50">
                            <p class="text-xs text-indigo-300 mb-1"><i class="fa-solid fa-clock"></i> ระยะเวลาประเมินแก้ไขเสร็จ:</p>
                            <p class="text-lg font-bold text-indigo-100" id="preview-time-display">24 ชั่วโมง (Standard Repair)</p>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700 text-xs text-slate-400 leading-relaxed">
                        <i class="fa-solid fa-circle-info text-amber-400 mr-1"></i>
                        คำนวณผ่าน <strong>RepairCostCalculator (คนที่ 2)</strong> โดยคิดตามประกัน ประเภทอุปกรณ์ และระดับความสำคัญของงาน
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= TAB 2: CHECK STATUS (ติดตามสถานะ) ================= -->
        <section id="tab-check-status" class="tab-content hidden">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 mb-8">
                    <h2 class="text-xl font-bold text-slate-800 mb-2 text-center">ค้นหาและติดตามสถานะงานซ่อม</h2>
                    <p class="text-sm text-slate-500 mb-6 text-center">ระบุรหัส Ticket ID (เช่น JOB-8801) หรือ เบอร์โทรศัพท์ที่ใช้แจ้งซ่อม</p>

                    <form onsubmit="handleSearchStatus(event)" class="flex flex-col md:flex-row gap-3">
                        <div class="relative flex-grow">
                            <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400"></i>
                            <input type="text" id="search-query" required value="<?= $currentUser ? htmlspecialchars($currentUser->getPhone()) : '' ?>" placeholder="ป้อน Ticket ID (JOB-xxxx) หรือ เบอร์โทรศัพท์..." class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-base">
                        </div>
                        <button type="submit" class="py-3 px-8 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition shadow-md flex items-center justify-center gap-2">
                            <i class="fa-solid fa-search"></i> ค้นหาสถานะ
                        </button>
                    </form>
                </div>

                <!-- Search Results Container -->
                <div id="search-results-container" class="space-y-6">
                    <div class="text-center py-12 text-slate-400 bg-white rounded-2xl border border-slate-200">
                        <i class="fa-solid fa-clipboard-list text-5xl mb-3 text-slate-300"></i>
                        <p class="text-base font-medium">ป้อน Ticket ID หรือ เบอร์โทรศัพท์ เพื่อค้นหางานซ่อม</p>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($isTechOrAdmin): ?>
            <!-- ================= TAB 3: TECHNICIAN DASHBOARD / PERSONAL WORKSPACE ================= -->
            <section id="tab-tech-dashboard" class="tab-content hidden">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-user-gear text-amber-500"></i> <?= $isAdmin ? 'Admin Dashboard (ติดตามงานซ่อมและดูสรุปภาพรวม)' : 'พื้นที่ทำงานช่างซ่อมส่วนตัว (Technician Workspace)' ?>
                            </h2>
                            <p class="text-sm text-slate-500"><?= $isAdmin ? 'ตรวจสอบสถิติ ภาระงานช่าง และติดตามความคืบหน้าของงานซ่อมทั้งหมด' : 'เข้ามากดรับงานจากกองกลาง ดูแลงานซ่อมของคุณ และอัปเดตรายงานซ่อมเสร็จ' ?></p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if ($isTech): ?>
                                <!-- Duty Status Toggle for Technician -->
                                <div class="flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-xl border border-slate-200 text-xs">
                                    <span class="font-bold text-slate-600 ml-1">สถานะช่าง:</span>
                                    <button id="duty-btn-available" onclick="setMyDutyStatus('Available')" class="px-2.5 py-1 rounded-lg font-bold transition bg-emerald-600 text-white shadow-sm">
                                        🟢 พร้อมรับงาน
                                    </button>
                                    <button id="duty-btn-resting" onclick="setMyDutyStatus('Resting')" class="px-2.5 py-1 rounded-lg font-bold transition bg-slate-200 text-slate-600 hover:bg-amber-100 hover:text-amber-800">
                                        ☕ พักงาน
                                    </button>
                                </div>
                            <?php endif; ?>

                            <?php if ($isAdmin): ?>
                                <button onclick="openMailConfigModal()" class="px-3.5 py-2 rounded-xl bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 font-semibold text-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-envelope"></i> คอนฟิกอีเมล (SMTP)
                                </button>
                                <a href="dashboard.php" class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-900 font-bold text-xs transition flex items-center gap-1.5 shadow-sm border border-amber-300">
                                    <i class="fa-solid fa-chart-pie"></i> 👑 ศูนย์แดชบอร์ด &amp; แจกงานช่าง
                                </a>
                            <?php endif; ?>

                            <button onclick="loadTechnicianTickets()" class="px-3.5 py-2 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-semibold text-xs transition flex items-center gap-1.5 border border-indigo-100 cursor-pointer">
                                <i class="fa-solid fa-rotate"></i> รีเฟรชข้อมูล
                            </button>
                        </div>
                    </div>

                    <?php if ($isTech): ?>
                        <!-- 👨‍🔧 TECHNICIAN PERSONAL WORKSPACE SUB-NAV & BANNER -->
                        <div class="mb-6 p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-700 text-amber-300 flex items-center justify-center text-xl font-bold shadow-md border border-indigo-400/30">
                                    <i class="fa-solid fa-screwdriver-wrench"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">พื้นที่ทำงานช่างซ่อมประจำตัว</span>
                                    <h3 id="tech-personal-welcome" class="text-lg font-extrabold text-slate-800">
                                        ยินดีต้อนรับคุณ <?= htmlspecialchars($currentUser->getFullName()) ?>
                                    </h3>
                                    <p class="text-xs text-slate-500">เลือกเมนูด้านล่างเพื่อเข้ามารับงานหรือจัดการงานซ่อมของคุณ</p>
                                </div>
                            </div>
                            <div id="tech-personal-badge-count" class="flex gap-2 text-xs">
                                <span class="px-3 py-1.5 bg-white rounded-xl border border-indigo-200 text-indigo-800 font-bold shadow-sm">
                                    เดือนนี้รับไปแล้ว: <strong id="my-claimed-count-badge" class="text-amber-600 font-black text-sm">0</strong> งาน
                                </span>
                            </div>
                        </div>

                        <!-- 🗂️ TECHNICIAN SUB-TABS NAV -->
                        <div class="flex flex-wrap gap-2 mb-6 border-b border-slate-200 pb-3">
                            <button id="tech-subtab-pool" onclick="switchTechSubTab('pool')" class="px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 bg-indigo-600 text-white shadow">
                                <i class="fa-solid fa-inbox text-amber-300"></i> 📥 กองกลางงานซ่อม (รอรับงาน)
                                <span id="subtab-pool-count" class="px-2 py-0.5 rounded-full bg-amber-400 text-slate-900 text-[11px] font-black">0</span>
                            </button>
                            <button id="tech-subtab-my-active" onclick="switchTechSubTab('my-active')" class="px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 bg-slate-100 text-slate-600 hover:bg-slate-200">
                                <i class="fa-solid fa-wrench text-indigo-500"></i> 🛠️ งานของฉัน - กำลังซ่อม
                                <span id="subtab-my-active-count" class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-[11px] font-black">0</span>
                            </button>
                            <button id="tech-subtab-my-completed" onclick="switchTechSubTab('my-completed')" class="px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 bg-slate-100 text-slate-600 hover:bg-slate-200">
                                <i class="fa-solid fa-square-check text-emerald-500"></i> ✅ ประวัติงานซ่อมเสร็จของฉัน
                                <span id="subtab-my-completed-count" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-black">0</span>
                            </button>
                            <button id="tech-subtab-all" onclick="switchTechSubTab('all')" class="px-4 py-2.5 rounded-xl font-semibold text-xs transition flex items-center gap-2 bg-slate-100 text-slate-500 hover:bg-slate-200">
                                <i class="fa-solid fa-list"></i> แสดงรวมทั้งหมด
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($isAdmin): ?>
                        <!-- 📊 Monthly Technician Job Stats Banner -->
                        <div id="tech-monthly-stats-banner" class="mb-6 p-4 rounded-2xl bg-gradient-to-r from-amber-500/10 via-indigo-500/10 to-blue-500/10 border border-amber-200/80 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-lg shadow">
                                    <i class="fa-solid fa-user-check"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm">สถิติจำนวนงานที่ช่างแต่ละคนรับไปในเดือนนี้ (<?= date('m/Y') ?>)</h4>
                                    <div id="my-tech-job-badge" class="text-xs text-slate-600 font-medium mt-0.5">
                                        กำลังโหลดสถิติช่าง...
                                    </div>
                                </div>
                            </div>
                            <div id="all-tech-stats-badges" class="flex flex-wrap gap-2 text-xs">
                                <!-- JS populated -->
                            </div>
                        </div>

                        <!-- 👥 Technician Workload Overview Grid (การ์ดสรุปงานของช่าง) -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                    <i class="fa-solid fa-users-gear text-indigo-600"></i> สรุปภาระงาน & งานค้างของช่างแต่ละคน (Technician Summary)
                                </h4>
                                <span class="text-xs text-slate-500">คลิกการ์ดช่างเพื่อกรองตารางเฉพาะช่างคนนั้น</span>
                            </div>
                            <div id="tech-workload-cards-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                                <!-- JS Populated Workload Cards -->
                            </div>
                        </div>

                        <!-- 🔍 Admin Filter & Reassign Bar -->
                        <div class="mb-4 p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs">
                                <i class="fa-solid fa-filter text-indigo-600"></i>
                                <span class="font-bold text-slate-700">กรองตารางตามช่าง:</span>
                                <select id="tech-filter-select" onchange="filterTicketsByTech(this.value)" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white font-semibold text-slate-700 outline-none">
                                    <option value="ALL">-- แสดงงานของช่างทุกคน (ทั้งหมด) --</option>
                                    <option value="วิชัย ช่างคอมพิวเตอร์">👨‍🔧 วิชัย ช่างคอมพิวเตอร์</option>
                                    <option value="กิตติ ช่างเน็ตเวิร์ก">👨‍🔧 กิตติ ช่างเน็ตเวิร์ก</option>
                                    <option value="ธีระ ช่างอุปกรณ์ทั่วไป">👨‍🔧 ธีระ ช่างอุปกรณ์ทั่วไป</option>
                                    <option value="สมศักดิ์ หัวหน้าช่าง IT (Admin)">👑 สมศักดิ์ (Admin)</option>
                                    <option value="UNASSIGNED">⚠️ ยังไม่ได้ระบุช่าง (กองกลาง)</option>
                                </select>
                            </div>
                            <div id="tech-filter-status-note" class="text-xs font-bold text-indigo-700 bg-indigo-50 px-3 py-1 rounded-lg border border-indigo-100">
                                แสดงงานทั้งหมด (<?= date('Y-m-d') ?>)
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Table Container -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold tracking-wider">
                                    <th class="p-3.5 rounded-l-lg">Ticket ID</th>
                                    <th class="p-3.5">ผู้แจ้งซ่อม / แผนก</th>
                                    <th class="p-3.5">รหัสอุปกรณ์</th>
                                    <th class="p-3.5">อาการเสีย</th>
                                    <th class="p-3.5">ช่างผู้รับงาน</th>
                                    <th class="p-3.5">รูปก่อน/หลังซ่อม</th>
                                    <th class="p-3.5">สถานะปัจจุบัน</th>
                                    <th class="p-3.5 rounded-r-lg text-center">อัปเดตสถานะงาน</th>
                                </tr>
                            </thead>
                            <tbody id="tech-tickets-table-body" class="divide-y divide-slate-100 text-sm">
                                <tr><td colspan="8" class="text-center py-8 text-slate-400">กำลังโหลดรายการงานซ่อม...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ================= TAB 4: ASSET MANAGER (คลังอุปกรณ์ IT - เฉพาะ Admin) ================= -->
            <section id="tab-asset-manager" class="tab-content hidden">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">คลังข้อมูลอุปกรณ์ IT (IT Asset Manager)</h2>
                            <p class="text-sm text-slate-500">ควบคุมโดย <strong>ITAssetManager (คนที่ 1)</strong> เช็คสิทธิ์ประกันและสถานะอุปกรณ์</p>
                        </div>
                        <span class="bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1.5 rounded-full">Class 1 Asset Manager</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="assets-grid-container">
                        <div class="col-span-full text-center py-8 text-slate-400">กำลังโหลดรายการอุปกรณ์...</div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-6 text-center text-xs border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-2">
            <p>© 2026 IT Repair Request System • OOP / OOAD 3-Class Architecture</p>
            <p class="text-slate-500">รองรับแนบรูปอาการเสีย & รูปรายงานซ่อมเสร็จ • เชื่อมต่อ MySQL Database</p>
        </div>
    </footer>

    <!-- Ticket Success Modal -->
    <div id="ticket-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 text-center border border-slate-100">
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="fa-solid fa-check"></i>
            </div>
            <h3 class="text-2xl font-bold text-slate-800 mb-1">แจ้งซ่อมเรียบร้อยแล้ว!</h3>
            <p class="text-sm text-slate-500 mb-4">ระบบสร้างใบแจ้งซ่อมสำเร็จ พร้อมบันทึกรูปแนบเรียบร้อยแล้ว</p>

            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 mb-6">
                <span class="text-xs text-indigo-500 font-medium">รหัสใบแจ้งซ่อมของคุณ (Ticket ID)</span>
                <div id="modal-ticket-id" class="text-3xl font-black text-indigo-700 tracking-wider my-1">JOB-xxxx</div>
                <span id="modal-phone-note" class="text-xs text-slate-500">หรือใช้เบอร์โทรค้นหาในหน้าติดตามสถานะ</span>
            </div>

            <div class="text-left text-xs text-slate-600 space-y-2 mb-6 bg-slate-50 p-4 rounded-xl">
                <div class="flex justify-between"><span>ผู้แจ้งซ่อม:</span><span id="modal-user-name" class="font-bold"></span></div>
                <div class="flex justify-between"><span>อุปกรณ์:</span><span id="modal-asset-name" class="font-bold"></span></div>
                <div class="flex justify-between"><span>ราคาประเมิน:</span><span id="modal-cost" class="font-bold text-emerald-600"></span></div>
                <div class="flex justify-between"><span>เวลาประเมินเสร็จ:</span><span id="modal-time" class="font-bold text-indigo-600"></span></div>
            </div>

            <div class="flex gap-3">
                <button onclick="closeModal(); switchTab('check-status')" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition shadow">
                    ติดตามสถานะงานซ่อม
                </button>
                <button onclick="closeModal()" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-xl transition">
                    ปิด
                </button>
            </div>
        </div>
    </div>

    <!-- 📸 Technician Completion Photo Upload Modal -->
    <div id="completion-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 border border-slate-100">
            <h3 class="text-xl font-bold text-slate-800 mb-1 flex items-center gap-2">
                <i class="fa-solid fa-camera-retro text-emerald-600"></i> ถ่าย/แนบรูปรายงานซ่อมเสร็จ
            </h3>
            <p class="text-xs text-slate-500 mb-4">อัปเดตสถานะเป็น <strong class="text-emerald-600">Completed (ซ่อมเสร็จแล้ว)</strong> พร้อมแนบรูปถ่ายยืนยันงานซ่อม</p>
            
            <form onsubmit="submitCompletionWithImage(event)">
                <input type="hidden" id="modal-completion-ticket-id">
                <div class="mb-5 p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <label class="block text-xs font-bold text-slate-700 mb-1">เลือกรูปถ่ายผลงานซ่อมเสร็จ (ถ้ามี)</label>
                    <input type="file" id="modal-completion-file" accept="image/*" class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition shadow">
                        <i class="fa-solid fa-check"></i> บันทึกซ่อมเสร็จแล้ว
                    </button>
                    <button type="button" onclick="closeCompletionModal()" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 📧 SMTP Mail Settings Modal -->
    <div id="mail-config-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 md:p-8 border border-slate-100 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-gear text-amber-500"></i> ตั้งค่าการส่งอีเมลเข้า Inbox จริง (SMTP)
                </h3>
                <button type="button" onclick="closeMailConfigModal()" class="text-slate-400 hover:text-slate-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <p class="text-xs text-slate-500 mb-5">
                เมื่อเปิดใช้งาน และระบุ Gmail + App Password เรียบร้อยแล้ว ระบบจะส่งอีเมลแจ้งเตือนไปยังอีเมลของผู้แจ้งซ่อมจริงทันทีที่ช่างกดซ่อมเสร็จ
            </p>

            <form onsubmit="saveMailConfig(event)">
                <!-- SMTP Active Switch -->
                <div class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between">
                    <div>
                        <label for="mail_smtp_active" class="font-bold text-sm text-slate-800 block cursor-pointer">เปิดใช้งานการส่งอีเมลจริง (SMTP)</label>
                        <span class="text-xs text-slate-600">หากปิดไว้ ระบบจะบันทึกจำลองอีเมลลงไฟล์ storage/email_logs.txt</span>
                    </div>
                    <input type="checkbox" id="mail_smtp_active" class="w-6 h-6 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 cursor-pointer">
                </div>

                <div class="space-y-4 text-sm">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">SMTP Host</label>
                        <input type="text" id="mail_host" placeholder="ssl://smtp.gmail.com" value="ssl://smtp.gmail.com" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <span class="text-[11px] text-slate-400">สำหรับ Gmail ใช้ <code>ssl://smtp.gmail.com</code> | Outlook ใช้ <code>tls://smtp.office365.com</code></span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 mb-1">Port</label>
                            <input type="number" id="mail_port" value="465" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                            <span class="text-[11px] text-slate-400">SSL=465, TLS=587</span>
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700 mb-1">ชื่อผู้ส่ง (From Name)</label>
                            <input type="text" id="mail_from_name" value="ระบบแจ้งซ่อมอุปกรณ์ IT" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">อีเมลผู้ส่ง (Gmail Address)</label>
                        <input type="email" id="mail_username" placeholder="your_email@gmail.com" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">รหัสผ่านแอป (Gmail App Password 16 หลัก)</label>
                        <input type="password" id="mail_password" placeholder="xxxx xxxx xxxx xxxx" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <span class="text-[11px] text-amber-600 block mt-1">
                            ⚠️ คำเตือน: รหัสผ่านของ Gmail ต้องเป็น <strong>App Password 16 หลัก</strong> (สร้างจาก Google Account -> Security -> 2-Step Verification -> App Passwords)
                        </span>
                    </div>
                </div>

                <div class="flex gap-3 mt-6 pt-4 border-t border-slate-100">
                    <button type="submit" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm transition shadow">
                        <i class="fa-solid fa-floppy-disk"></i> บันทึกการตั้งค่า
                    </button>
                    <button type="button" onclick="closeMailConfigModal()" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 👨‍🔧 Select Technician Modal -->
    <div id="assign-tech-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 md:p-8 border border-slate-100">
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-gear text-indigo-600"></i> เลือกช่างผู้รับผิดชอบงานซ่อม
                </h3>
                <button type="button" onclick="closeAssignTechModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form onsubmit="submitAssignTechModal(event)">
                <input type="hidden" id="modal-assign-ticket-id">

                <div class="mb-5 bg-indigo-50 border border-indigo-100 p-4 rounded-2xl text-xs flex justify-between items-center">
                    <div>
                        <span class="text-indigo-500 font-medium block">รหัสใบแจ้งซ่อม:</span>
                        <strong id="modal-assign-ticket-id-display" class="text-indigo-800 text-lg font-black tracking-wide">JOB-xxxx</strong>
                    </div>
                    <span class="bg-indigo-600 text-white text-[11px] font-bold px-2.5 py-1 rounded-full">เลือกช่าง</span>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-700 mb-2">ระบุชื่อช่างผู้รับงาน <span class="text-rose-500">*</span></label>
                    <select id="modal-assign-tech-select" required class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 font-semibold text-slate-800 outline-none bg-white text-sm shadow-sm">
                        <option value="">-- กรุณาเลือกช่างผู้รับงาน --</option>
                        <option value="วิชัย ช่างคอมพิวเตอร์">👨‍🔧 วิชัย ช่างคอมพิวเตอร์ (ฝ่ายซ่อมบำรุงคอม)</option>
                        <option value="กิตติ ช่างเน็ตเวิร์ก">👨‍🔧 กิตติ ช่างเน็ตเวิร์ก (ฝ่ายเครือข่ายและระบบ)</option>
                        <option value="ธีระ ช่างอุปกรณ์ทั่วไป">👨‍🔧 ธีระ ช่างอุปกรณ์ทั่วไป (ฝ่ายฮาร์ดแวร์ & พริ้นเตอร์)</option>
                        <option value="สมศักดิ์ หัวหน้าช่าง IT (Admin)">👑 สมศักดิ์ หัวหน้าช่าง IT (Admin)</option>
                    </select>
                </div>

                <div class="flex gap-2.5">
                    <button type="submit" id="index-assign-confirm-btn" class="flex-1 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-extrabold rounded-xl text-sm transition shadow-md flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-circle-check"></i> <span>กดยืนยันการแจกงานให้ช่าง</span>
                    </button>
                    <button type="button" onclick="closeAssignTechModal()" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium cursor-pointer">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Frontend Application JavaScript -->
    <script>
        let allAssets = [];
        let allTechTicketsList = [];
        let currentDutyStatusesList = [];
        let currentUserData = null;
        let currentTechSubTab = 'pool';

        const isUser = <?= $isUser ? 'true' : 'false' ?>;
        const isTech = <?= $isTech ? 'true' : 'false' ?>;
        const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
        const isTechOrAdmin = <?= $isTechOrAdmin ? 'true' : 'false' ?>;

        async function openMailConfigModal() {
            try {
                const res = await fetch('api.php?action=get_mail_config');
                const json = await res.json();
                if (json.success && json.data) {
                    const d = json.data;
                    document.getElementById('mail_smtp_active').checked = !!d.smtp_active;
                    document.getElementById('mail_host').value = d.host || 'ssl://smtp.gmail.com';
                    document.getElementById('mail_port').value = d.port || 465;
                    document.getElementById('mail_username').value = d.username || '';
                    document.getElementById('mail_password').value = d.password_masked || d.password || '';
                    document.getElementById('mail_from_name').value = d.from_name || 'ระบบแจ้งซ่อมอุปกรณ์ IT';
                }
                document.getElementById('mail-config-modal').classList.remove('hidden');
            } catch (e) {
                alert('เกิดข้อผิดพลาดในการโหลดคอนฟิกอีเมล');
            }
        }

        function closeMailConfigModal() {
            document.getElementById('mail-config-modal').classList.add('hidden');
        }

        async function saveMailConfig(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('smtp_active', document.getElementById('mail_smtp_active').checked ? 'true' : 'false');
            formData.append('host', document.getElementById('mail_host').value);
            formData.append('port', document.getElementById('mail_port').value);
            formData.append('username', document.getElementById('mail_username').value);
            formData.append('password', document.getElementById('mail_password').value);
            formData.append('from_name', document.getElementById('mail_from_name').value);

            try {
                const res = await fetch('api.php?action=save_mail_config', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                if (json.success) {
                    alert('🎉 ' + json.message);
                    closeMailConfigModal();
                } else {
                    alert('❌ ' + (json.message || 'บันทึกไม่สำเร็จ'));
                }
            } catch (e) {
                alert('เกิดข้อผิดพลาดในการบันทึกการตั้งค่า');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadAssets();
            if (isTechOrAdmin) loadTechnicianTickets();

            // Check URL parameters for tab and permission denied alerts
            const urlParams = new URLSearchParams(window.location.search);
            const reqTab = urlParams.get('tab') || window.location.hash.replace('#', '');
            
            if (urlParams.get('denied') === 'admin_only') {
                showToast('🔒 สิทธิ์ไม่เพียงพอ! ฟังก์ชันดังกล่าวสงวนสิทธิ์เฉพาะผู้ดูแลระบบ (Admin) เท่านั้น', 'error');
            }

            if (isTech) {
                switchTab(reqTab === 'check-status' ? 'check-status' : 'tech-dashboard');
            } else if (isUser) {
                switchTab(reqTab === 'check-status' ? 'check-status' : 'create-ticket');
            } else if (isAdmin) {
                switchTab(reqTab || 'tech-dashboard');
            } else {
                switchTab('create-ticket');
            }
        });

        function switchTab(tabName) {
            // Guard role-based access
            if (isUser && (tabName === 'tech-dashboard' || tabName === 'asset-manager')) {
                showToast('🔒 สิทธิ์เฉพาะช่างและแอดมินเท่านั้น (ผู้ใช้งานทั่วไปสามารถเพิ่มงานและติดตามงานได้เท่านั้น)', 'error');
                return;
            }
            if (isTech && tabName === 'asset-manager') {
                showToast('🔒 สิทธิ์เฉพาะผู้ดูแลระบบ (Admin) เท่านั้น (ช่างสามารถกดรับงานเองและรับงานจากแอดมินได้แค่นั้น)', 'error');
                return;
            }

            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('bg-white', 'text-indigo-950', 'font-bold', 'shadow-md', 'bg-white/20', 'text-white');
                btn.classList.add('bg-white/10', 'text-indigo-100');
            });

            const target = document.getElementById(`tab-${tabName}`);
            if (target) target.classList.remove('hidden');

            const activeBtn = document.getElementById(`tab-btn-${tabName}`);
            if (activeBtn) {
                activeBtn.classList.remove('bg-white/10', 'text-indigo-100', 'bg-indigo-900/60', 'text-indigo-200');
                activeBtn.classList.add('bg-white', 'text-indigo-950', 'font-bold', 'shadow-md');
            }

            if (tabName === 'tech-dashboard' && isTechOrAdmin) loadTechnicianTickets();
            if (tabName === 'asset-manager' && isAdmin) renderAssetManagerGrid();
        }

        async function loadAssets() {
            try {
                const res = await fetch('api.php?action=get_assets');
                const data = await res.json();
                if (data.success) {
                    allAssets = data.data;
                    populateAssetSelectOptions();
                    if (isAdmin) renderAssetManagerGrid();
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อ API ได้: ' + err.message, 'error');
            }
        }

        function populateAssetSelectOptions() {
            const select = document.getElementById('asset_id');
            select.innerHTML = '<option value="">-- เลือกอุปกรณ์ IT --</option>';
            allAssets.forEach(asset => {
                const isW = asset.is_warranty ? '[ในประกัน]' : '[หมดประกัน]';
                const opt = document.createElement('option');
                opt.value = asset.asset_id;
                opt.textContent = `${asset.asset_id} - ${asset.name} (${asset.device_type}) ${isW}`;
                select.appendChild(opt);
            });
        }

        function handleAssetSelectChange() {
            updatePreviewCost();
        }

        async function updatePreviewCost() {
            const assetId = document.getElementById('asset_id').value;
            const severity = document.getElementById('severity').value;
            const priority = document.getElementById('priority').value;

            if (!assetId) {
                document.getElementById('preview-asset-name').textContent = 'กรุณาเลือกอุปกรณ์ในฟอร์ม';
                document.getElementById('preview-warranty-badge').textContent = '-';
                document.getElementById('preview-warranty-badge').className = 'inline-block text-xs px-2.5 py-0.5 rounded-full bg-slate-700 text-slate-300';
                document.getElementById('preview-cost-display').textContent = '0.00 บาท';
                document.getElementById('preview-time-display').textContent = '24 ชั่วโมง (Standard Repair)';
                return;
            }

            try {
                const res = await fetch(`api.php?action=preview_cost&asset_id=${assetId}&severity=${severity}&priority=${priority}`);
                const result = await res.json();
                if (result.success) {
                    const data = result.data;
                    document.getElementById('preview-asset-name').textContent = `${data.asset.asset_id} - ${data.asset.name}`;
                    document.getElementById('preview-device-type').textContent = `ประเภท: ${data.asset.device_type}`;
                    
                    const badge = document.getElementById('preview-warranty-badge');
                    if (data.asset.is_warranty) {
                        badge.textContent = 'ในประกัน (0 บาท)';
                        badge.className = 'inline-block text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
                    } else {
                        badge.textContent = 'หมดประกัน (คิดตามจริง)';
                        badge.className = 'inline-block text-xs px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40';
                    }

                    document.getElementById('preview-cost-display').textContent = data.formatted_cost;
                    document.getElementById('preview-time-display').textContent = data.estimated_completion_time;
                }
            } catch (err) {
                console.error(err);
            }
        }

        // Handle Create Ticket Submit (No Technician Choice)
        async function handleCreateTicket(e) {
            e.preventDefault();
            const deptVal = document.getElementById('department').value;
            const posVal = document.getElementById('position').value;
            const fullDept = posVal ? `${deptVal} (${posVal})` : deptVal;

            const formData = new FormData();
            formData.append('user_name', document.getElementById('user_name').value);
            formData.append('department', fullDept);
            formData.append('phone', document.getElementById('phone').value);
            formData.append('user_email', document.getElementById('user_email') ? document.getElementById('user_email').value : '');
            formData.append('asset_id', document.getElementById('asset_id').value);
            formData.append('issue_description', document.getElementById('issue_description').value);
            formData.append('severity', document.getElementById('severity').value);
            formData.append('priority', document.getElementById('priority').value);

            const fileInput = document.getElementById('issue_image_file');
            if (fileInput && fileInput.files[0]) {
                formData.append('issue_image', fileInput.files[0]);
            }

            try {
                const res = await fetch('api.php?action=create_ticket', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    const ticket = result.data;
                    document.getElementById('modal-ticket-id').textContent = ticket.ticket_id;
                    document.getElementById('modal-user-name').textContent = ticket.user_name;
                    document.getElementById('modal-asset-name').textContent = `${ticket.asset_id} (${ticket.device_type})`;
                    document.getElementById('modal-cost').textContent = ticket.formatted_cost;
                    document.getElementById('modal-time').textContent = ticket.estimated_completion_time;

                    document.getElementById('ticket-modal').classList.remove('hidden');
                    document.getElementById('create-ticket-form').reset();
                    updatePreviewCost();
                    loadAssets();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการส่งข้อมูล: ' + err.message, 'error');
            }
        }

        async function handleSearchStatus(e) {
            e.preventDefault();
            const query = document.getElementById('search-query').value.trim();
            if (!query) return;

            const container = document.getElementById('search-results-container');
            container.innerHTML = '<div class="text-center py-8 text-slate-400"><i class="fa-solid fa-spinner fa-spin text-2xl"></i> กำลังค้นหา...</div>';

            try {
                const res = await fetch(`api.php?action=check_status&query=${encodeURIComponent(query)}`);
                const result = await res.json();

                if (result.success && result.data.length > 0) {
                    renderTicketsList(result.data, container);
                } else {
                    container.innerHTML = `
                        <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500">
                            <i class="fa-solid fa-circle-exclamation text-4xl text-amber-500 mb-2"></i>
                            <h3 class="text-lg font-bold">ไม่พบใบแจ้งซ่อม</h3>
                            <p class="text-sm">ไม่พบข้อมูลใบแจ้งซ่อมตรงกับ "${query}" กรุณาตรวจสอบอีกครั้ง</p>
                        </div>
                    `;
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาด: ' + err.message, 'error');
            }
        }

        // Render Ticket Search Results
        function renderTicketsList(tickets, container) {
            container.innerHTML = '';
            tickets.forEach(ticket => {
                const statusSteps = {
                    'Pending': { step: 1, text: 'รอดำเนินการ', color: 'bg-amber-500' },
                    'In Progress': { step: 2, text: 'กำลังดำเนินการซ่อม', color: 'bg-blue-600' },
                    'Completed': { step: 3, text: 'ซ่อมเสร็จเรียบร้อยแล้ว', color: 'bg-emerald-600' }
                };

                const currentStep = statusSteps[ticket.status] || statusSteps['Pending'];

                let issueImageHtml = '';
                if (ticket.issue_image) {
                    issueImageHtml = `
                        <div class="mt-3">
                            <span class="text-xs text-slate-400 block mb-1 font-bold"><i class="fa-solid fa-camera text-indigo-500"></i> รูปถ่ายอาการเสีย (ก่อนซ่อม):</span>
                            <a href="${ticket.issue_image}" target="_blank">
                                <img src="${ticket.issue_image}" class="w-32 h-32 object-cover rounded-xl border border-slate-200 shadow-sm hover:opacity-90 transition">
                            </a>
                        </div>
                    `;
                }

                let completionImageHtml = '';
                if (ticket.completion_image) {
                    completionImageHtml = `
                        <div class="mt-3">
                            <span class="text-xs text-emerald-600 block mb-1 font-bold"><i class="fa-solid fa-circle-check text-emerald-600"></i> รูปถ่ายรายงานการซ่อมเสร็จ (หลังซ่อม):</span>
                            <a href="${ticket.completion_image}" target="_blank">
                                <img src="${ticket.completion_image}" class="w-32 h-32 object-cover rounded-xl border-2 border-emerald-400 shadow-sm hover:opacity-90 transition">
                            </a>
                        </div>
                    `;
                }

                const card = document.createElement('div');
                card.className = 'bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6';
                card.innerHTML = `
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pb-4 border-b border-slate-100">
                        <div>
                            <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-md">Ticket ID</span>
                            <h3 class="text-2xl font-extrabold text-slate-800 tracking-wide inline-block ml-2">${ticket.ticket_id}</h3>
                        </div>
                        <span class="px-3.5 py-1.5 rounded-full text-xs font-bold text-white ${currentStep.color}">
                            ${currentStep.text}
                        </span>
                    </div>

                    <!-- Visual Progress Stepper -->
                    <div class="py-2">
                        <div class="flex items-center justify-between text-xs font-semibold mb-2 text-slate-600">
                            <span class="${currentStep.step >= 1 ? 'text-indigo-600' : 'text-slate-400'}">1. รอดำเนินการ (Pending)</span>
                            <span class="${currentStep.step >= 2 ? 'text-indigo-600' : 'text-slate-400'}">2. กำลังซ่อม (In Progress)</span>
                            <span class="${currentStep.step >= 3 ? 'text-emerald-600' : 'text-slate-400'}">3. ซ่อมเสร็จแล้ว (Completed)</span>
                        </div>
                        <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden flex">
                            <div class="bg-indigo-600 h-full transition-all duration-500" style="width: ${currentStep.step === 1 ? '33%' : currentStep.step === 2 ? '66%' : '100%'}"></div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm bg-slate-50 p-4 rounded-xl">
                        <div><span class="text-slate-400">ผู้แจ้งซ่อม:</span> <strong class="text-slate-700">${ticket.user_name} (${ticket.department})</strong></div>
                        <div><span class="text-slate-400">เบอร์โทรศัพท์:</span> <strong class="text-slate-700">${ticket.phone}</strong></div>
                        <div><span class="text-slate-400">รหัสอุปกรณ์:</span> <strong class="text-slate-700">${ticket.asset_id} (${ticket.device_type})</strong></div>
                        <div><span class="text-slate-400">ราคาประเมิน:</span> <strong class="text-emerald-600 font-bold">${ticket.formatted_cost}</strong></div>
                        <div class="md:col-span-2">
                            <span class="text-slate-400">รายละเอียดอาการเสีย:</span> 
                            <p class="text-slate-700 font-medium mt-0.5">${ticket.issue_description}</p>
                            ${issueImageHtml}
                        </div>
                        <div class="md:col-span-2">
                            ${completionImageHtml}
                        </div>
                        <div><span class="text-slate-400">ประเมินเวลาเสร็จ:</span> <strong class="text-indigo-600">${ticket.estimated_completion_time}</strong></div>
                        <div><span class="text-slate-400">ช่างผู้รับผิดชอบ:</span> <strong class="text-indigo-700">${ticket.assigned_technician ? ticket.assigned_technician : 'ยังไม่มีช่างรับงาน (รอช่างกดรับงาน)'}</strong></div>
                        <div><span class="text-slate-400">วันที่แจ้งซ่อม:</span> <span class="text-slate-600">${ticket.created_at}</span></div>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        // Load Technician Dashboard Tickets
        async function loadTechnicianTickets() {
            const tbody = document.getElementById('tech-tickets-table-body');
            if (!tbody) return;

            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-slate-400"><i class="fa-solid fa-spinner fa-spin"></i> กำลังโหลด...</td></tr>';

            try {
                const res = await fetch('api.php?action=get_all_tickets');
                const result = await res.json();

                if (result.success) {
                    allTechTicketsList = result.data || [];
                    currentDutyStatusesList = result.duty_statuses || [];
                    currentUserData = result.current_user || null;

                    // Update Duty Toggle Buttons
                    if (result.my_username && currentDutyStatusesList.length > 0) {
                        const myDuty = currentDutyStatusesList.find(u => u.username === result.my_username);
                        if (myDuty) {
                            updateDutyButtonsUI(myDuty.duty_status || 'Available');
                        }
                    }

                    // Render Monthly Stats Banner
                    const myBadgeEl = document.getElementById('my-tech-job-badge');
                    if (myBadgeEl) {
                        const myName = result.my_tech_name || 'ผู้ใช้ระบบ';
                        myBadgeEl.innerHTML = `ผู้ใช้งานปัจจุบัน: <strong class="text-indigo-700">${myName}</strong> (รับงานไปแล้ว <strong class="text-amber-600 text-sm font-extrabold">${result.my_job_count || 0} งาน</strong> ในเดือนนี้)`;
                    }

                    const allStatsContainer = document.getElementById('all-tech-stats-badges');
                    if (allStatsContainer && result.stats) {
                        if (result.stats.length === 0) {
                            allStatsContainer.innerHTML = '<span class="text-slate-400 italic">ยังไม่มีช่างรับงานในเดือนนี้</span>';
                        } else {
                            allStatsContainer.innerHTML = result.stats.map(s => `
                                <span class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-sm font-semibold text-slate-700 flex items-center gap-1">
                                    <i class="fa-solid fa-user-gear text-indigo-500"></i> ${s.assigned_technician}: <strong class="text-amber-600 font-bold ml-1">${s.job_count} งาน</strong>
                                </span>
                            `).join('');
                        }
                    }

                    // Render Workload Overview Cards
                    renderWorkloadCards(allTechTicketsList, currentDutyStatusesList);

                    // Filter Table according to state
                    const filterVal = (isAdmin && document.getElementById('tech-filter-select')) ? document.getElementById('tech-filter-select').value : 'SUBTAB';
                    filterTicketsByTech(filterVal);
                }
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-red-500">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
            }
        }

        // Sub-Tab Switcher for Technicians
        function switchTechSubTab(subTab) {
            currentTechSubTab = subTab;

            const tabs = ['pool', 'my-active', 'my-completed', 'all'];
            tabs.forEach(t => {
                const btn = document.getElementById(`tech-subtab-${t}`);
                if (btn) {
                    if (t === subTab) {
                        btn.className = "px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 bg-indigo-600 text-white shadow";
                    } else {
                        btn.className = "px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 bg-slate-100 text-slate-600 hover:bg-slate-200";
                    }
                }
            });

            filterTicketsByTech('SUBTAB');
        }

        // Filter tickets table by technician name or subtab
        function filterTicketsByTech(selectedTech) {
            const selectEl = document.getElementById('tech-filter-select');
            if (selectEl && selectedTech !== 'SUBTAB' && selectEl.value !== selectedTech) {
                selectEl.value = selectedTech;
            }

            const noteEl = document.getElementById('tech-filter-status-note');
            let filtered = allTechTicketsList;
            const myName = currentUserData ? currentUserData.full_name : '';

            // Update Technician Sub-Tab Badges & Filter
            if (isTech) {
                const poolTickets = allTechTicketsList.filter(t => t.status === 'Pending' && (!t.assigned_technician || t.assigned_technician === ''));
                const myActiveTickets = allTechTicketsList.filter(t => t.assigned_technician === myName && t.status === 'In Progress');
                const myCompletedTickets = allTechTicketsList.filter(t => t.assigned_technician === myName && t.status === 'Completed');

                const poolBadge = document.getElementById('subtab-pool-count');
                const activeBadge = document.getElementById('subtab-my-active-count');
                const completedBadge = document.getElementById('subtab-my-completed-count');
                const myClaimedBadge = document.getElementById('my-claimed-count-badge');

                if (poolBadge) poolBadge.textContent = poolTickets.length;
                if (activeBadge) activeBadge.textContent = myActiveTickets.length;
                if (completedBadge) completedBadge.textContent = myCompletedTickets.length;
                if (myClaimedBadge) myClaimedBadge.textContent = (myActiveTickets.length + myCompletedTickets.length);

                if (selectedTech === 'SUBTAB') {
                    if (currentTechSubTab === 'pool') {
                        filtered = poolTickets;
                    } else if (currentTechSubTab === 'my-active') {
                        filtered = myActiveTickets;
                    } else if (currentTechSubTab === 'my-completed') {
                        filtered = myCompletedTickets;
                    } else {
                        filtered = allTechTicketsList;
                    }
                }
            }

            if (isAdmin && selectedTech !== 'SUBTAB') {
                if (selectedTech === 'UNASSIGNED') {
                    filtered = allTechTicketsList.filter(t => !t.assigned_technician);
                    if (noteEl) noteEl.textContent = `กำลังแสดง: งานที่ยังไม่มีช่างรับงาน (${filtered.length} รายการ)`;
                } else if (selectedTech !== 'ALL' && selectedTech) {
                    filtered = allTechTicketsList.filter(t => t.assigned_technician === selectedTech);
                    const pendingCnt = filtered.filter(t => t.status !== 'Completed').length;
                    const completedCnt = filtered.filter(t => t.status === 'Completed').length;
                    if (noteEl) noteEl.textContent = `กำลังแสดง: ${selectedTech} (งานค้าง: ${pendingCnt} | เสร็จแล้ว: ${completedCnt})`;
                } else {
                    if (noteEl) noteEl.textContent = `แสดงงานทั้งหมด (${filtered.length} รายการ)`;
                }
            }

            renderTechTableBody(filtered);
        }

        // Render Technician Table Body
        function renderTechTableBody(tickets) {
            const tbody = document.getElementById('tech-tickets-table-body');
            if (!tbody) return;

            if (tickets.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-slate-400">ไม่พบรายการแจ้งซ่อมตามเงื่อนไขที่เลือก</td></tr>';
                return;
            }

            tbody.innerHTML = '';
            const myFullName = currentUserData ? currentUserData.full_name : '';

            tickets.forEach(t => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50 transition border-b border-slate-100';
                
                let badgeColor = 'bg-amber-100 text-amber-800';
                if (t.status === 'In Progress') badgeColor = 'bg-blue-100 text-blue-800';
                if (t.status === 'Completed') badgeColor = 'bg-emerald-100 text-emerald-800';

                let photosHtml = '<span class="text-xs text-slate-400">-</span>';
                if (t.issue_image || t.completion_image) {
                    photosHtml = '<div class="flex gap-1.5 items-center">';
                    if (t.issue_image) photosHtml += `<a href="${t.issue_image}" target="_blank" title="ดูรูปอาการเสียก่อนซ่อม"><img src="${t.issue_image}" class="w-8 h-8 rounded object-cover border border-slate-300 hover:scale-110 transition"></a>`;
                    if (t.completion_image) photosHtml += `<a href="${t.completion_image}" target="_blank" title="ดูรูปรายงานซ่อมเสร็จ"><img src="${t.completion_image}" class="w-8 h-8 rounded object-cover border-2 border-emerald-500 hover:scale-110 transition"></a>`;
                    photosHtml += '</div>';
                }

                // Column: ช่างผู้รับงาน
                let techDisplayHtml = '';
                if (t.assigned_technician) {
                    techDisplayHtml = `<span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 font-bold rounded-lg border border-indigo-100 text-xs inline-flex items-center gap-1"><i class="fa-solid fa-user-gear"></i> ${t.assigned_technician}</span>`;
                } else {
                    techDisplayHtml = `<span class="px-2.5 py-1 bg-amber-50 text-amber-700 font-bold rounded-lg border border-amber-200 text-xs inline-flex items-center gap-1"><i class="fa-solid fa-clock text-amber-600"></i> ยังไม่มีช่างรับงาน</span>`;
                }

                // Column: อัปเดตสถานะงาน / การกระทำ
                let actionHtml = '';

                // Case 1: Job is Pending / Unassigned (งานรอช่างรับงาน)
                if (t.status === 'Pending' || !t.assigned_technician) {
                    if (isTech) {
                        actionHtml = `
                            <button onclick="claimJob('${t.ticket_id}')" title="กดรับงานซ่อมนี้มาทำ" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                                <i class="fa-solid fa-hand"></i> ✋ กดรับงานเอง
                            </button>
                        `;
                    } else if (isAdmin) {
                        actionHtml = `
                            <button onclick="openAssignTechModal('${t.ticket_id}', '')" title="เลือกช่างและส่งงานให้ช่าง" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                                <i class="fa-solid fa-user-plus"></i> ส่งงานให้ช่าง
                            </button>
                        `;
                    } else {
                        actionHtml = `<span class="text-xs text-amber-600 font-medium italic">รอช่างเข้ามากดรับงาน</span>`;
                    }
                }
                // Case 2: Job is In Progress (กำลังซ่อม)
                else if (t.status === 'In Progress') {
                    const isMyJob = myFullName && (myFullName === t.assigned_technician);

                    if (isMyJob) {
                        actionHtml = `
                            <button onclick="handleTechnicianStatusSelect('${t.ticket_id}', 'Completed')" title="บันทึกว่าซ่อมเสร็จเรียบร้อยแล้ว" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                                <i class="fa-solid fa-camera"></i> บันทึกซ่อมเสร็จ
                            </button>
                        `;
                    } else if (isAdmin) {
                        actionHtml = `
                            <button onclick="openAssignTechModal('${t.ticket_id}', '${t.assigned_technician}')" title="เปลี่ยนช่างผู้รับงาน" class="px-2.5 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold text-xs rounded-lg border border-indigo-200 transition flex items-center gap-1">
                                <i class="fa-solid fa-user-gear"></i> เปลี่ยนช่าง
                            </button>
                        `;
                    } else {
                        actionHtml = `
                            <span class="text-xs text-slate-500 font-medium italic border border-slate-200 px-2.5 py-1 rounded-lg bg-slate-50 flex items-center gap-1">
                                <i class="fa-solid fa-user-gear text-indigo-500"></i> ช่าง ${t.assigned_technician}
                            </span>
                        `;
                    }
                }
                // Case 3: Job is Completed
                else if (t.status === 'Completed') {
                    actionHtml = `
                        <span class="text-xs text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 flex items-center gap-1">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i> ซ่อมเสร็จเรียบร้อย
                        </span>
                    `;
                }

                // Extra Admin action: Reassign (เฉพาะ Admin เท่านั้น)
                let assignBtnHtml = '';
                if (isAdmin && t.status !== 'Completed') {
                    assignBtnHtml = `
                        <button onclick="openAssignTechModal('${t.ticket_id}', '${t.assigned_technician || ''}')" title="เลือก/เปลี่ยนช่างผู้รับงาน (แอดมิน)" class="p-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold border border-indigo-200 transition ml-1 cursor-pointer">
                            <i class="fa-solid fa-user-gear"></i>
                        </button>
                    `;
                }

                // Delete button for Admin only
                let deleteBtnHtml = '';
                if (isAdmin) {
                    deleteBtnHtml = `
                        <button onclick="handleDeleteTicket('${t.ticket_id}')" title="ลบงานซ่อมนี้ (แอดมิน)" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-semibold border border-rose-200 transition ml-1 cursor-pointer">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    `;
                }

                tr.innerHTML = `
                    <td class="p-3.5 font-bold text-indigo-700 font-mono">${t.ticket_id}</td>
                    <td class="p-3.5 font-medium">${t.user_name}<br><span class="text-xs text-slate-400">${t.department} • ${t.phone}</span></td>
                    <td class="p-3.5"><span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded">${t.asset_id}</span></td>
                    <td class="p-3.5 max-w-xs truncate" title="${t.issue_description}">${t.issue_description}</td>
                    <td class="p-3.5">${techDisplayHtml}</td>
                    <td class="p-3.5">${photosHtml}</td>
                    <td class="p-3.5"><span class="px-2.5 py-1 rounded-full text-xs font-semibold ${badgeColor}">${t.status}</span></td>
                    <td class="p-3.5 text-center flex items-center justify-center gap-1">
                        ${actionHtml}
                        ${assignBtnHtml}
                        ${deleteBtnHtml}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Claim Job Handler
        async function claimJob(ticketId) {
            try {
                const res = await fetch('api.php?action=claim_job', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `ticket_id=${encodeURIComponent(ticketId)}`
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    loadTechnicianTickets();
                    loadAssets();
                } else {
                    showToast(result.message || 'ไม่สามารถกดรับงานได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการกดรับงาน', 'error');
            }
        }

        // Technician Status Change Handler
        function handleTechnicianStatusSelect(ticketId, newStatus) {
            if (newStatus === 'Completed') {
                document.getElementById('modal-completion-ticket-id').value = ticketId;
                document.getElementById('completion-modal').classList.remove('hidden');
            } else {
                updateTicketStatus(ticketId, newStatus);
            }
        }

        function closeCompletionModal() {
            document.getElementById('completion-modal').classList.add('hidden');
        }

        async function submitCompletionWithImage(e) {
            e.preventDefault();
            const ticketId = document.getElementById('modal-completion-ticket-id').value;
            const fileInput = document.getElementById('modal-completion-file');

            const formData = new FormData();
            formData.append('ticket_id', ticketId);
            formData.append('new_status', 'Completed');

            if (fileInput && fileInput.files[0]) {
                formData.append('completion_image', fileInput.files[0]);
            }

            try {
                const res = await fetch('api.php?action=update_status', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    closeCompletionModal();
                    loadTechnicianTickets();
                    loadAssets();
                } else {
                    showToast(result.message || 'ไม่สามารถอัปเดตสถานะได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' + err.message, 'error');
            }
        }

        async function updateTicketStatus(ticketId, newStatus) {
            const formData = new FormData();
            formData.append('ticket_id', ticketId);
            formData.append('new_status', newStatus);

            try {
                const res = await fetch('api.php?action=update_status', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    loadTechnicianTickets();
                    loadAssets();
                } else {
                    showToast(result.message || 'ไม่สามารถอัปเดตสถานะได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาด: ' + err.message, 'error');
            }
        }

        async function handleDeleteTicket(ticketId) {
            if (!confirm(`คุณต้องการลบใบแจ้งซ่อมรหัส ${ticketId} ใช่หรือไม่?`)) return;

            try {
                const res = await fetch(`api.php?action=delete_ticket&ticket_id=${encodeURIComponent(ticketId)}`);
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    loadTechnicianTickets();
                    loadAssets();
                } else {
                    showToast(result.message || 'ไม่สามารถลบใบแจ้งซ่อมได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการลบข้อมูล: ' + err.message, 'error');
            }
        }

        function openAssignTechModal(ticketId, currentTech) {
            document.getElementById('modal-assign-ticket-id').value = ticketId;
            document.getElementById('modal-assign-ticket-id-display').textContent = ticketId;
            const select = document.getElementById('modal-assign-tech-select');
            if (select) {
                if (currentTech) {
                    select.value = currentTech;
                } else if (select.options.length > 1) {
                    select.selectedIndex = 1;
                }
            }
            document.getElementById('assign-tech-modal').classList.remove('hidden');
        }

        function closeAssignTechModal() {
            document.getElementById('assign-tech-modal').classList.add('hidden');
        }

        async function submitAssignTechModal(e) {
            e.preventDefault();
            const ticketId = document.getElementById('modal-assign-ticket-id').value;
            const techName = document.getElementById('modal-assign-tech-select').value;
            const submitBtn = document.getElementById('index-assign-confirm-btn');

            if (!techName) {
                alert('กรุณาเลือกช่างผู้รับงาน');
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>กำลังแจกงานให้ช่าง...</span>';
            }

            try {
                const res = await fetch('api.php?action=assign_technician', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        ticket_id: ticketId, 
                        assigned_technician: techName,
                        admin_password: '1234',
                        admin_username: 'Admin'
                    })
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    closeAssignTechModal();
                    loadTechnicianTickets();
                } else {
                    showToast(result.message || 'ไม่สามารถมอบหมายงานได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการเลือกช่าง: ' + err.message, 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>กดยืนยันการแจกงานให้ช่าง</span>';
                }
            }
        }

        // Set Technician Duty Status
        async function setMyDutyStatus(newStatus) {
            try {
                const res = await fetch('api.php?action=toggle_duty_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: newStatus })
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    updateDutyButtonsUI(newStatus);
                    loadTechnicianTickets();
                } else {
                    showToast(result.message || 'ไม่สามารถเปลี่ยนสถานะได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ: ' + err.message, 'error');
            }
        }

        function updateDutyButtonsUI(status) {
            const btnAvailable = document.getElementById('duty-btn-available');
            const btnResting = document.getElementById('duty-btn-resting');
            if (!btnAvailable || !btnResting) return;

            if (status === 'Available') {
                btnAvailable.className = 'px-2.5 py-1 rounded-lg font-bold transition bg-emerald-600 text-white shadow-sm';
                btnResting.className = 'px-2.5 py-1 rounded-lg font-bold transition bg-slate-200 text-slate-600 hover:bg-amber-100 hover:text-amber-800';
            } else {
                btnAvailable.className = 'px-2.5 py-1 rounded-lg font-bold transition bg-slate-200 text-slate-600 hover:bg-emerald-100 hover:text-emerald-800';
                btnResting.className = 'px-2.5 py-1 rounded-lg font-bold transition bg-amber-600 text-white shadow-sm';
            }
        }

        // Render Workload Summary Cards for each Technician
        function renderWorkloadCards(tickets, dutyStatuses = []) {
            const container = document.getElementById('tech-workload-cards-container');
            if (!container) return;

            const techDefs = [
                { name: 'วิชัย ช่างคอมพิวเตอร์', role: 'ซ่อมบำรุงคอมพิวเตอร์', icon: 'fa-laptop-code', color: 'blue' },
                { name: 'กิตติ ช่างเน็ตเวิร์ก', role: 'โครงข่ายและเครือข่าย', icon: 'fa-network-wired', color: 'indigo' },
                { name: 'ธีระ ช่างอุปกรณ์ทั่วไป', role: 'ฮาร์ดแวร์ & ปริ้นเตอร์', icon: 'fa-print', color: 'purple' },
                { name: 'สมศักดิ์ หัวหน้าช่าง IT (Admin)', role: 'หัวหน้าแอดมิน IT', icon: 'fa-user-shield', color: 'amber' }
            ];

            container.innerHTML = techDefs.map(t => {
                const techTickets = tickets.filter(tk => tk.assigned_technician === t.name);
                const total = techTickets.length;
                const pending = techTickets.filter(tk => tk.status === 'Pending' || tk.status === 'In Progress').length;
                const completed = techTickets.filter(tk => tk.status === 'Completed').length;

                const techUser = dutyStatuses.find(u => u.full_name === t.name);
                const isResting = techUser && techUser.duty_status === 'Resting';
                const statusBadge = isResting
                    ? `<span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-bold">☕ พักงาน</span>`
                    : `<span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold">🟢 พร้อมรับงาน</span>`;

                return `
                    <div onclick="filterTicketsByTech('${t.name}')" class="p-4 rounded-2xl bg-white border border-slate-200 hover:border-indigo-400 hover:shadow-md cursor-pointer transition flex flex-col justify-between group">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-xl bg-${t.color}-100 text-${t.color}-700 flex items-center justify-center text-xs font-bold shadow-sm group-hover:scale-105 transition">
                                        <i class="fa-solid ${t.icon}"></i>
                                    </span>
                                    <div>
                                        <h5 class="font-bold text-slate-800 text-xs truncate max-w-[120px] group-hover:text-indigo-600 transition">${t.name}</h5>
                                        <span class="text-[10px] text-slate-400 block">${t.role}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    ${statusBadge}
                                    <span class="text-[10px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full font-extrabold">รวม ${total} งาน</span>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-2.5 border-t border-slate-100 text-[11px] mt-2">
                            <div class="bg-amber-50/80 border border-amber-200/60 text-amber-800 p-1.5 rounded-xl text-center font-semibold">
                                <span class="text-[10px] text-amber-600 block">งานค้าง</span>
                                <strong class="text-amber-800 text-sm font-black">${pending} งาน</strong>
                            </div>
                            <div class="bg-emerald-50/80 border border-emerald-200/60 text-emerald-800 p-1.5 rounded-xl text-center font-semibold">
                                <span class="text-[10px] text-emerald-600 block">ทำเสร็จแล้ว</span>
                                <strong class="text-emerald-800 text-sm font-black">${completed} งาน</strong>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderAssetManagerGrid() {
            const container = document.getElementById('assets-grid-container');
            if (!container) return;

            if (!allAssets || allAssets.length === 0) {
                container.innerHTML = '<div class="col-span-full text-center py-8 text-slate-400">ไม่พบรายการอุปกรณ์</div>';
                return;
            }

            container.innerHTML = '';
            allAssets.forEach(asset => {
                let statusBadge = 'bg-emerald-100 text-emerald-800';
                if (asset.status === 'Repairing') statusBadge = 'bg-amber-100 text-amber-800';
                if (asset.status === 'Damaged') statusBadge = 'bg-rose-100 text-rose-800';

                const card = document.createElement('div');
                card.className = 'p-5 rounded-2xl border border-slate-200 bg-white hover:shadow-md transition space-y-3';
                card.innerHTML = `
                    <div class="flex justify-between items-start">
                        <span class="text-xs font-mono font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded">${asset.asset_id}</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold ${statusBadge}">${asset.status}</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-base line-clamp-1">${asset.name}</h4>
                        <p class="text-xs text-slate-500">ประเภท: ${asset.device_type}</p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 text-xs flex justify-between items-center">
                        <span class="text-slate-400">สถานะประกัน:</span>
                        <span class="${asset.is_warranty ? 'text-emerald-600 font-bold' : 'text-slate-500'}">
                            ${asset.is_warranty ? '<i class="fa-solid fa-shield-halved mr-1"></i>ในประกัน' : 'หมดประกัน'}
                        </span>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function showToast(msg, type = 'success') {
            const toast = document.getElementById('toast-banner');
            const icon = document.getElementById('toast-icon');
            const text = document.getElementById('toast-message');

            text.textContent = msg;
            toast.className = `mb-6 p-4 rounded-xl shadow-md flex items-center justify-between border ${
                type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'
            }`;
            icon.className = type === 'success' ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-xmark text-rose-600';

            toast.classList.remove('hidden');
            setTimeout(hideToast, 5000);
        }

        function hideToast() {
            document.getElementById('toast-banner').classList.add('hidden');
        }

        function closeModal() {
            document.getElementById('ticket-modal').classList.add('hidden');
        }

        async function openMailConfigModal() {
            try {
                const res = await fetch('api.php?action=get_mail_config');
                const result = await res.json();

                if (result.success && result.data) {
                    const cfg = result.data;
                    document.getElementById('cfg_driver').value = cfg.driver || 'brevo_api';
                    document.getElementById('cfg_smtp_active').checked = !!cfg.smtp_active;
                    document.getElementById('cfg_brevo_api_key').value = cfg.brevo_api_key_masked || cfg.brevo_api_key || '';
                    document.getElementById('cfg_from_email').value = cfg.from_email || '';
                    document.getElementById('cfg_from_name').value = cfg.from_name || '';
                    document.getElementById('cfg_host').value = cfg.host || 'smtp-relay.brevo.com';
                    document.getElementById('cfg_port').value = cfg.port || 587;
                    document.getElementById('cfg_username').value = cfg.username || '';
                    document.getElementById('cfg_password').value = cfg.password_masked || cfg.password || '';

                    toggleMailDriverFields();
                    document.getElementById('mail-config-modal').classList.remove('hidden');
                }
            } catch (err) {
                showToast('ไม่สามารถดึงข้อมูลการตั้งค่าอีเมลได้: ' + err.message, 'error');
            }
        }

        function closeMailConfigModal() {
            document.getElementById('mail-config-modal').classList.add('hidden');
        }

        function toggleMailDriverFields() {
            const driver = document.getElementById('cfg_driver').value;
            const apiKeyField = document.getElementById('field-brevo-api-key');
            const smtpField = document.getElementById('field-smtp-details');

            if (driver === 'brevo_api') {
                apiKeyField.classList.remove('hidden');
                smtpField.classList.add('hidden');
            } else if (driver === 'smtp') {
                apiKeyField.classList.add('hidden');
                smtpField.classList.remove('hidden');
            } else {
                apiKeyField.classList.add('hidden');
                smtpField.classList.add('hidden');
            }
        }

        async function handleSaveMailConfig(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('driver', document.getElementById('cfg_driver').value);
            formData.append('smtp_active', document.getElementById('cfg_smtp_active').checked ? 'true' : 'false');
            formData.append('brevo_api_key', document.getElementById('cfg_brevo_api_key').value);
            formData.append('from_email', document.getElementById('cfg_from_email').value);
            formData.append('from_name', document.getElementById('cfg_from_name').value);
            formData.append('host', document.getElementById('cfg_host').value);
            formData.append('port', document.getElementById('cfg_port').value);
            formData.append('username', document.getElementById('cfg_username').value);
            formData.append('password', document.getElementById('cfg_password').value);

            try {
                const res = await fetch('api.php?action=save_mail_config', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    closeMailConfigModal();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการบันทึก: ' + err.message, 'error');
            }
        }

        async function handleTestSendEmail() {
            const targetEmail = prompt('กรุณากรอกอีเมลปลายทางที่ต้องการทดสอบส่ง:');
            if (!targetEmail || !targetEmail.trim()) return;

            showToast('กำลังส่งอีเมลทดสอบไปยัง ' + targetEmail + '...', 'success');

            const formData = new FormData();
            formData.append('test_email', targetEmail.trim());

            try {
                const res = await fetch('api.php?action=test_send_email', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    alert('🎉 ' + result.message);
                    showToast(result.message, 'success');
                } else {
                    alert('❌ ' + result.message);
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการทดสอบ: ' + err.message, 'error');
            }
        }
    </script>

    <!-- ================= MODAL: BREVO / MAIL CONFIGURATION ================= -->
    <div id="mail-config-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-xl w-full p-6 md:p-8 shadow-2xl border border-slate-100 my-8">
            <div class="flex justify-between items-start mb-5 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-paper-plane"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">ตั้งค่าระบบแจ้งเตือนอีเมล (Brevo Email)</h3>
                        <p class="text-xs text-slate-500">ตั้งค่า Brevo API Key หรือ SMTP เพื่อส่งอีเมลซ่อมเสร็จไปยังผู้แจ้ง</p>
                    </div>
                </div>
                <button onclick="closeMailConfigModal()" class="text-slate-400 hover:text-slate-600 text-xl p-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="mail-config-form" onsubmit="handleSaveMailConfig(event)" class="space-y-4">
                <!-- Status active toggle -->
                <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="font-bold text-slate-800 text-sm block">เปิดใช้งานระบบส่งอีเมลจริง</span>
                        <span class="text-xs text-slate-500">หากปิดไว้ ระบบจะบันทึก Log การส่งลงในไฟล์ storage/email_logs.txt</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="cfg_smtp_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <!-- Driver selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ช่องทางการส่งอีเมล (Email Driver)</label>
                    <select id="cfg_driver" onchange="toggleMailDriverFields()" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white font-medium">
                        <option value="brevo_api">🚀 Brevo REST API v3 (แนะนำสำหรับ Localhost/XAMPP)</option>
                        <option value="smtp">📧 Brevo Transactional SMTP / Custom SMTP</option>
                        <option value="local">💻 Local Simulation (Log file เท่านั้น)</option>
                    </select>
                </div>

                <!-- Brevo API Key field -->
                <div id="field-brevo-api-key" class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100 space-y-2">
                    <label class="block text-xs font-bold text-indigo-900">
                        <i class="fa-solid fa-key mr-1"></i> Brevo API Key (xkeysib-...)
                    </label>
                    <input type="password" id="cfg_brevo_api_key" placeholder="วาง Brevo API Key ที่นี่ (เช่น xkeysib-...)" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white font-mono">
                    <p class="text-[11px] text-indigo-600">รับ API Key ได้ที่ <a href="https://app.brevo.com/settings/keys/api" target="_blank" class="underline font-bold">Brevo Dashboard -&gt; Transactional -&gt; SMTP &amp; API</a></p>
                </div>

                <!-- Sender info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">อีเมลผู้ส่ง (Sender Email)</label>
                        <input type="email" id="cfg_from_email" placeholder="เช่น sender@company.com" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">ชื่อผู้ส่ง (Sender Name)</label>
                        <input type="text" id="cfg_from_name" placeholder="เช่น ระบบแจ้งซ่อมอุปกรณ์ IT" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <!-- SMTP Fields -->
                <div id="field-smtp-details" class="hidden space-y-3 pt-2 border-t border-slate-100">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">SMTP Host</label>
                            <input type="text" id="cfg_host" placeholder="smtp-relay.brevo.com" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Port</label>
                            <input type="number" id="cfg_port" placeholder="587" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">SMTP Username</label>
                            <input type="text" id="cfg_username" placeholder="Brevo Login Email" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">SMTP Key / Password</label>
                            <input type="password" id="cfg_password" placeholder="Brevo SMTP Key" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300">
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" onclick="handleTestSendEmail()" class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-paper-plane text-indigo-600"></i> ทดสอบส่งอีเมล (Test Email)
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="closeMailConfigModal()" class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                            ยกเลิก
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition shadow flex items-center gap-1.5">
                            <i class="fa-solid fa-floppy-disk"></i> บันทึกการตั้งค่า
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
