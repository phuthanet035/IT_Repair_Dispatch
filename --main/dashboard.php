<?php
/**
 * IT Service Desk & Dispatch Management (dashboard.php)
 * หน้าแดชบอร์ดภาพรวมงานซ่อม และระบบแอดมินสำหรับแจกจ่ายงาน/มอบหมายงานให้ช่าง
 */

require_once __DIR__ . '/classes/UserManager.php';
require_once __DIR__ . '/classes/RepairTicketController.php';

$userManager = new UserManager();
$currentUser = $userManager->getCurrentUser();

// สิทธิ์เข้าใช้งานแดชบอร์ดแจกงาน: เฉพาะแอดมิน (Admin) เท่านั้น
if (!$currentUser || !$currentUser->isAdmin()) {
    if ($currentUser && $currentUser->isTechnician()) {
        header('Location: index.php?tab=tech-dashboard&denied=admin_only');
    } elseif ($currentUser && $currentUser->isUser()) {
        header('Location: index.php?tab=create-ticket&denied=admin_only');
    } else {
        header('Location: login.php?denied=admin_only');
    }
    exit;
}

$isUser = $currentUser->isUser();
$isTech = $currentUser->isTechnician();
$isAdmin = $currentUser->isAdmin();
$isTechOrAdmin = true;

$ticketController = new RepairTicketController();
$serverAnalytics = $ticketController->getDashboardAnalytics();
$kpi = $serverAnalytics['kpi'];
$allTicketsObj = $ticketController->getAllTickets();
$allTicketsData = array_map(fn($t) => $t->toArray(), $allTicketsObj);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด & ระบบแจกงานช่าง IT (IT Dispatch & Analytics Dashboard)</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Kanit -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { font-family: 'Kanit', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
        }
        .pulse-urgent {
            animation: pulse-glow 2s infinite;
        }
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-100/80 text-slate-800 min-h-screen flex flex-col antialiased">

    <!-- Top Navigation Bar -->
    <header class="bg-slate-900 text-white shadow-xl sticky top-0 z-50 border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 py-3.5 flex flex-col md:flex-row justify-between items-center gap-4">
            
            <!-- Branding -->
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-amber-400 p-0.5 shadow-lg flex items-center justify-center">
                    <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center">
                        <i class="fa-solid fa-chart-pie text-xl text-amber-400"></i>
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold tracking-tight text-white">ศูนย์แดชบอร์ด & ระบบแจกงานช่าง</h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                            Dispatch Center
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">IT Repair Dispatch & Workload Management System</p>
                </div>
            </div>

            <!-- Quick Navigation & Current Profile -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="index.php" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition flex items-center gap-1.5 border border-slate-700 shadow-sm">
                    <i class="fa-solid fa-house text-slate-400"></i> หน้าแรก (Home)
                </a>
                <a href="index.php#tab-create-ticket" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition flex items-center gap-1.5 shadow-md shadow-indigo-600/20">
                    <i class="fa-solid fa-plus-circle"></i> แจ้งซ่อมใหม่
                </a>
                <button onclick="refreshDashboardData()" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition flex items-center gap-1.5 border border-slate-700" title="รีเฟรชข้อมูลแดชบอร์ด">
                    <i id="refresh-icon" class="fa-solid fa-rotate"></i> รีเฟรช
                </button>

                <!-- Quick Admin Login Button if not admin -->
                <?php if (!$isAdmin): ?>
                    <button onclick="quickAdminLogin()" class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs transition shadow-md flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-crown"></i> เข้าสู่ระบบแอดมิน (Admin/1234)
                    </button>
                <?php endif; ?>

                <!-- Profile Badge -->
                <?php if ($currentUser): ?>
                    <div class="flex items-center gap-2.5 bg-slate-800/90 pl-3 pr-2 py-1.5 rounded-xl border border-slate-700 text-xs">
                        <div class="text-right">
                            <span class="font-bold text-white block truncate max-w-[140px]"><?= htmlspecialchars($currentUser->getFullName()) ?></span>
                            <span class="text-[11px] <?= $isAdmin ? 'text-amber-400 font-bold' : ($isTech ? 'text-blue-400 font-bold' : 'text-emerald-400') ?>">
                                <?= htmlspecialchars($currentUser->getRoleLabel()) ?>
                            </span>
                        </div>
                        <a href="logout.php" title="ออกจากระบบ" class="w-8 h-8 rounded-lg bg-rose-500/20 hover:bg-rose-600 text-rose-300 hover:text-white flex items-center justify-center transition">
                            <i class="fa-solid fa-right-from-bracket text-xs"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition border border-slate-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-right-to-bracket"></i> หน้า Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 py-6 flex-grow w-full space-y-6">

        <!-- ALERT TOAST BANNER -->
        <div id="toast-banner" class="hidden p-4 rounded-2xl shadow-lg flex items-center justify-between border transition-all duration-300">
            <div class="flex items-center gap-3">
                <div id="toast-icon-wrap" class="w-10 h-10 rounded-xl flex items-center justify-center text-lg">
                    <i id="toast-icon"></i>
                </div>
                <div>
                    <h5 id="toast-title" class="text-xs font-black uppercase tracking-wider"></h5>
                    <p id="toast-message" class="text-sm font-medium"></p>
                </div>
            </div>
            <button onclick="hideToast()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <!-- ================= 1. KPI TOP METRICS OVERVIEW ================= -->
        <section>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-lg font-black text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-gauge-high text-indigo-600"></i> ตัวชี้วัดและสถิติภาพรวมงานซ่อม (Key Metrics)
                    </h2>
                    <p class="text-xs text-slate-500">สรุปข้อมูลสถานะงานซ่อม การปฏิบัติงานของช่าง และประมาณการค่าใช้จ่าย</p>
                </div>
                <div class="text-xs text-slate-500 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white rounded-lg border border-slate-200 text-slate-600 font-semibold shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span> Live Update
                    </span>
                    <span id="last-updated-text" class="text-[11px] font-medium text-slate-400">อัปเดตล่าสุด: กำลังโหลด...</span>
                </div>
            </div>

            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3.5">
                
                <!-- Card 1: Total Tickets -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-500">งานทั้งหมด</span>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-sm group-hover:scale-110 transition">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span id="kpi-total-tickets" class="text-2xl font-black text-slate-900"><?= $kpi['total_tickets'] ?></span>
                        <span class="text-xs text-slate-400">งาน</span>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1 block">ตั้งแต่เปิดระบบ</span>
                </div>

                <!-- Card 2: Unassigned / Pool (Highlight) -->
                <div class="bg-white rounded-2xl p-4 border border-amber-200 shadow-sm hover:shadow-md transition group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-amber-500/10 rounded-bl-full pointer-events-none"></div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-amber-800 flex items-center gap-1">
                            <span id="kpi-unassigned-dot" class="<?= $kpi['unassigned_count'] > 0 ? 'w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping inline-block' : 'w-2 h-2 rounded-full bg-slate-300 inline-block' ?>"></span> กองกลางรอแจก
                        </span>
                        <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm group-hover:scale-110 transition">
                            <i class="fa-solid fa-inbox"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span id="kpi-unassigned-count" class="text-2xl font-black text-amber-600"><?= $kpi['unassigned_count'] ?></span>
                        <span class="text-xs text-amber-600/70 font-semibold">งาน</span>
                    </div>
                    <span class="text-[11px] text-amber-700/80 font-medium mt-1 block">รอมอบหมายช่าง</span>
                </div>

                <!-- Card 3: In Progress -->
                <div class="bg-white rounded-2xl p-4 border border-blue-200 shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-blue-700">กำลังซ่อม</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm group-hover:scale-110 transition">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span id="kpi-in-progress-count" class="text-2xl font-black text-blue-600"><?= $kpi['in_progress_count'] ?></span>
                        <span class="text-xs text-blue-500">งาน</span>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1 block">ช่างกำลังดูแล</span>
                </div>

                <!-- Card 4: Completed -->
                <div class="bg-white rounded-2xl p-4 border border-emerald-200 shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-emerald-700">ซ่อมเสร็จสิ้น</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm group-hover:scale-110 transition">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span id="kpi-completed-count" class="text-2xl font-black text-emerald-600"><?= $kpi['completed_count'] ?></span>
                        <span class="text-xs text-emerald-500">งาน</span>
                    </div>
                    <span id="kpi-completion-rate" class="text-[11px] font-bold text-emerald-700 mt-1 block">สำเร็จ: <?= $kpi['completion_rate'] ?>%</span>
                </div>

                <!-- Card 5: Urgent / Critical -->
                <div class="bg-white rounded-2xl p-4 border border-rose-200 shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-rose-700">ด่วน/วิกฤติ</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm group-hover:scale-110 transition">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span id="kpi-urgent-count" class="text-2xl font-black text-rose-600"><?= $kpi['urgent_critical_count'] ?></span>
                        <span class="text-xs text-rose-500">งาน</span>
                    </div>
                    <span class="text-[11px] text-rose-500 font-medium mt-1 block">ต้องเร่งด่วน</span>
                </div>

                <!-- Card 6: Total Estimated Cost -->
                <div class="bg-white rounded-2xl p-4 border border-indigo-200 shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-indigo-700">รวมงบซ่อม</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm group-hover:scale-110 transition">
                            <i class="fa-solid fa-baht-sign"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span id="kpi-total-cost" class="text-lg font-black text-indigo-600"><?= $kpi['formatted_cost'] ?></span>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1 block">ประเมินค่าอะไหล่</span>
                </div>

                <!-- Card 7: Technicians On Duty -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-700">ช่างพร้อมรับงาน</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm group-hover:scale-110 transition">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span id="kpi-available-techs" class="text-2xl font-black text-slate-900"><?= $kpi['available_technicians'] ?></span>
                        <span id="kpi-total-techs" class="text-xs text-slate-500 font-semibold">/ <?= $kpi['total_technicians'] ?> ช่าง</span>
                    </div>
                    <span class="text-[11px] text-emerald-600 font-bold mt-1 block">🟢 On-Duty</span>
                </div>
                </div>

            </div>
        </section>

        <!-- ================= 2. INTERACTIVE CHARTS & ANALYTICS ================= -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Chart 1: Status Breakdown -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-chart-pie text-indigo-500"></i> สัดส่วนสถานะงานซ่อม
                    </h3>
                </div>
                <div class="relative flex items-center justify-center py-2" style="height: 190px;">
                    <canvas id="chart-status"></canvas>
                </div>
                <div class="text-center text-[11px] text-slate-400 mt-2">
                    แสดงสถานะงานปัจจุบันทั้งหมด
                </div>
            </div>

            <!-- Chart 2: Technician Workload Comparison -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between lg:col-span-2">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-chart-column text-amber-500"></i> ภาระงานช่างแต่ละคน (กำลังทำ vs ซ่อมเสร็จ)
                    </h3>
                    <span class="text-[11px] text-slate-400">เปรียบเทียบ Workload</span>
                </div>
                <div class="relative w-full" style="height: 190px;">
                    <canvas id="chart-tech-workload"></canvas>
                </div>
                <div class="text-center text-[11px] text-slate-400 mt-2">
                    ใช้เพื่อช่วยแอดมินเฉลี่ยงานไม่ให้ช่างคนใดคนหนึ่งโหลดเกินไป
                </div>
            </div>

            <!-- Chart 3: Device Type Breakdown -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-desktop text-blue-500"></i> ประเภทอุปกรณ์ที่แจ้งซ่อม
                    </h3>
                </div>
                <div class="relative flex items-center justify-center py-2" style="height: 190px;">
                    <canvas id="chart-device-type"></canvas>
                </div>
                <div class="text-center text-[11px] text-slate-400 mt-2">
                    สถิติความเสียหายตามชนิดอุปกรณ์
                </div>
            </div>

        </section>

        <!-- ================= 3. ADMIN DISPATCH & WORKLOAD MANAGEMENT CENTER ================= -->
        <section class="space-y-6">

            <!-- Dispatch Center Title Header -->
            <div class="p-5 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white shadow-lg border border-slate-800 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600/50 border border-indigo-400/30 flex items-center justify-center text-2xl text-amber-300 shadow-inner">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-black tracking-wide text-white">ศูนย์แอดมินแจกงาน & บริหารจัดการทีมช่าง</h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-400 text-slate-950">
                                Dispatch Console
                            </span>
                        </div>
                        <p class="text-xs text-indigo-200 mt-0.5">
                            มอบหมายงานใหม่ให้ช่าง, สลับเปลี่ยนตัวช่างผู้รับผิดชอบ, และติดตามสถานะความพร้อมในการปฏิบัติงาน
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="scrollToSection('unassigned-pool-section')" class="px-3 py-2 rounded-xl bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 text-xs font-bold transition border border-amber-500/30 flex items-center gap-1.5">
                        <i class="fa-solid fa-inbox"></i> งานกองกลางรอแจก (<span id="dispatch-unassigned-badge">0</span>)
                    </button>
                    <button onclick="scrollToSection('tech-roster-section')" class="px-3 py-2 rounded-xl bg-indigo-500/20 hover:bg-indigo-500 text-indigo-200 hover:text-white text-xs font-bold transition border border-indigo-500/30 flex items-center gap-1.5">
                        <i class="fa-solid fa-users"></i> แผงช่างซ่อม
                    </button>
                    <button onclick="scrollToSection('master-table-section')" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition border border-slate-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-list-check"></i> รายการงานทั้งหมด
                    </button>
                </div>
            </div>

            <!-- ADMIN AUTHORIZATION STATUS BANNER -->
            <?php if ($isAdmin): ?>
                <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-900/90 via-slate-900 to-emerald-950 text-white border border-emerald-500/40 shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-400/40 text-emerald-400 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-extrabold text-sm text-emerald-300">เข้าสู่ระบบในฐานะแอดมิน: <?= htmlspecialchars($currentUser->getFullName()) ?> (<?= htmlspecialchars($currentUser->getUsername()) ?>)</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-400 text-slate-950">
                                    Admin Authorized
                                </span>
                            </div>
                            <p class="text-xs text-slate-300 mt-0.5">
                                คุณมีสิทธิ์เต็มในการแจกงานอัจฉริยะ มอบหมายงานให้ช่าง โอนย้ายงาน และบริหารจัดการสถานะทีมช่าง
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <span class="text-xs text-emerald-300 bg-emerald-950/80 px-3 py-1.5 rounded-xl border border-emerald-800 flex items-center gap-1.5 font-bold">
                            <i class="fa-solid fa-circle-check text-emerald-400"></i> สิทธิ์แจกงานพร้อมใช้งาน
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-950/60 via-amber-900/40 to-slate-900 border-2 border-amber-400/60 shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-400/40 text-amber-400 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-black text-sm text-amber-300">🔒 ระบบแจกงานและมอบหมายช่างสงวนสิทธิ์เฉพาะ "แอดมิน (Admin)" เท่านั้น</h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400 text-slate-950">
                                    Admin Login Required
                                </span>
                            </div>
                            <p class="text-xs text-slate-300 mt-1">
                                ต้องเข้าสู่ระบบด้วยบัญชีแอดมินที่ <strong>ชื่อผู้ใช้ประกอบด้วยคำว่า 'Admin'</strong> (เช่น Admin, AdminIT) และ <strong>รหัสผ่าน '1234'</strong> จึงจะสามารถสั่งการแจกงานได้
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0 self-end sm:self-auto">
                        <button onclick="quickAdminLogin()" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs shadow-md transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-crown"></i> ⚡ เข้าสู่ระบบแอดมินทันที (Admin / 1234)
                        </button>
                        <a href="login.php" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-right-to-bracket"></i> หน้า Login
                        </a>
                        <a href="register.php" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs border border-slate-700 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-user-plus"></i> สมัครสมาชิกแอดมิน
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SUB-SECTION 3.1: UNASSIGNED POOL (งานกองกลางรอแอดมินแจก) -->
            <div id="unassigned-pool-section" class="bg-white rounded-2xl p-6 border border-amber-200/90 shadow-sm relative overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <h3 class="text-base font-black text-slate-800">
                                📥 กองกลางงานแจ้งซ่อมที่รอช่างรับงาน (Unassigned Job Pool)
                            </h3>
                            <span id="pool-count-pill" class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-extrabold">0 งาน</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            รายการงานแจ้งซ่อมที่เข้ามาใหม่แต่ยังไม่มีช่างดูแล แอดมินสามารถเลือกช่างเพื่อแจกงาน หรือกด <strong>"แจกงานอัจฉริยะ"</strong> ได้ทันที
                        </p>
                    </div>
                </div>

                <!-- Unassigned List Container -->
                <div id="unassigned-pool-container" class="space-y-3">
                    <div class="py-8 text-center text-slate-400 text-sm">กำลังโหลดงานกองกลาง...</div>
                </div>
            </div>

            <!-- SUB-SECTION 3.2: TECHNICIAN WORKLOAD ROSTER (กระดานภาระงานช่าง) -->
            <div id="tech-roster-section" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-users-gear text-indigo-600"></i> กระดานช่างซ่อม & งานที่กำลังรับผิดชอบ (Technician Active Workload)
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            แสดงสถานะการปฏิบัติงาน (🟢 พร้อมรับงาน / ☕ พักงาน) และงานซ่อมทั้งหมดที่ช่างแต่ละคนกำลังดูแล สามารถโอนย้ายงานหรือเปลี่ยนช่างได้ที่นี่
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                            🟢 พร้อมรับงาน (Available)
                        </span>
                        <span class="inline-flex items-center gap-1 text-slate-600 font-semibold bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                            ☕ พักงาน (Resting)
                        </span>
                    </div>
                </div>

                <!-- Technicians Cards Grid -->
                <div id="technicians-roster-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="col-span-full py-8 text-center text-slate-400 text-sm">กำลังโหลดข้อมูลช่าง...</div>
                </div>
            </div>

            <!-- SUB-SECTION 3.3: MASTER DISPATCH & TRACKING TABLE (ตารางควบคุมงานซ่อมทั้งหมด) -->
            <div id="master-table-section" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                
                <!-- Table Header with Live Search & Filters -->
                <div class="flex flex-col gap-4 mb-5 pb-4 border-b border-slate-100">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-table-list text-indigo-600"></i> ทะเบียนงานซ่อมและติดตามสถานะงานทั้งหมด (Master Job Dispatch Table)
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">ค้นหา กรอง และจัดการมอบหมาย/เปลี่ยนช่าง หรืออัปเดตงานซ่อมเสร็จ</p>
                        </div>
                        <span id="table-total-count" class="px-3 py-1 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs self-start sm:self-auto">
                            ทั้งหมด - รายการ
                        </span>
                    </div>

                    <!-- Search & Filter Controls Bar -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                        
                        <!-- Search Box -->
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" id="table-search-input" oninput="applyTableFilters()" placeholder="ค้นหา Ticket ID, ผู้แจ้ง, อุปกรณ์, อาการ..." class="w-full pl-9 pr-3.5 py-2 rounded-lg border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        </div>

                        <!-- Filter by Technician -->
                        <div>
                            <select id="filter-technician" onchange="applyTableFilters()" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 outline-none bg-white focus:ring-2 focus:ring-indigo-500">
                                <option value="ALL">👨‍🔧 ช่างทุกคน (ทั้งหมด)</option>
                                <option value="UNASSIGNED">⚠️ กองกลาง (ยังไม่มีช่างรับงาน)</option>
                                <!-- Dynamically populated with technicians -->
                            </select>
                        </div>

                        <!-- Filter by Status -->
                        <div>
                            <select id="filter-status" onchange="applyTableFilters()" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 outline-none bg-white focus:ring-2 focus:ring-indigo-500">
                                <option value="ALL">🚦 ทุกสถานะงาน</option>
                                <option value="Pending">⏳ รอดำเนินการ (Pending)</option>
                                <option value="In Progress">🛠️ กำลังซ่อม (In Progress)</option>
                                <option value="Completed">✅ ซ่อมเสร็จแล้ว (Completed)</option>
                            </select>
                        </div>

                        <!-- Filter by Priority -->
                        <div>
                            <select id="filter-priority" onchange="applyTableFilters()" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 outline-none bg-white focus:ring-2 focus:ring-indigo-500">
                                <option value="ALL">⚡ ทุกระดับความเร่งด่วน</option>
                                <option value="Urgent">🚨 ด่วนที่สุด (Urgent)</option>
                                <option value="High">🔥 ด่วนมาก (High)</option>
                                <option value="Normal">⚡ ปกติ (Normal)</option>
                                <option value="Low">🌱 ไม่ด่วน (Low)</option>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-bold tracking-wider">
                                <th class="p-3.5">Ticket ID</th>
                                <th class="p-3.5">ผู้แจ้งซ่อม / แผนก / ติดต่อ</th>
                                <th class="p-3.5">อุปกรณ์ IT</th>
                                <th class="p-3.5">อาการเสีย</th>
                                <th class="p-3.5">ความเร่งด่วน</th>
                                <th class="p-3.5">ช่างผู้รับผิดชอบงาน</th>
                                <th class="p-3.5">รูปก่อน/หลัง</th>
                                <th class="p-3.5">สถานะ</th>
                                <th class="p-3.5 text-center">จัดการงาน</th>
                            </tr>
                        </thead>
                        <tbody id="master-tickets-tbody" class="divide-y divide-slate-100 text-xs">
                            <tr>
                                <td colspan="9" class="text-center py-10 text-slate-400">
                                    <i class="fa-solid fa-spinner fa-spin text-lg mb-2 block"></i> กำลังโหลดตารางงานซ่อม...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-5 mt-10">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-500">
            ระบบบริหารจัดการแจ้งซ่อมและแจกงานช่าง IT (IT Service Desk & Dispatch Management) • สถาปัตยกรรม OOP/OOAD
        </div>
    </footer>

    <!-- ================= MODALS ================= -->

    <!-- 1. ASSIGN / REASSIGN TECHNICIAN MODAL -->
    <div id="assign-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 transform transition-all animate-in fade-in zoom-in duration-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white flex items-center justify-center text-xl font-bold shadow-md shadow-indigo-600/30">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-base leading-tight">มอบหมาย / แจกงานให้ช่าง</h4>
                        <p class="text-xs text-slate-500 mt-0.5">ใบแจ้งซ่อมรหัส: <strong id="modal-assign-ticket-id-display" class="font-mono text-indigo-700 font-extrabold">-</strong></p>
                    </div>
                </div>
                <button type="button" onclick="closeAssignModal()" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-xl flex items-center justify-center hover:bg-slate-100 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form id="assign-tech-form" onsubmit="submitReassignModal(event)">
                <input type="hidden" id="modal-assign-ticket-id" value="">

                <!-- Ticket Quick Summary Preview -->
                <div id="modal-assign-ticket-preview" class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl mb-4 text-xs text-slate-700 hidden">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-slate-900" id="modal-assign-preview-user">-</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800" id="modal-assign-preview-priority">-</span>
                    </div>
                    <div class="text-slate-500 truncate" id="modal-assign-preview-desc">-</div>
                </div>

                <!-- Select Technician Dropdown -->
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-800 mb-2 flex items-center justify-between">
                        <span>👨‍🔧 เลือกช่างผู้รับผิดชอบงานนี้: <span class="text-rose-500">*</span></span>
                        <span class="text-[11px] font-normal text-indigo-600">ช่างพร้อมรับงานจะแสดงลำดับแรก</span>
                    </label>
                    <select id="modal-assign-tech-select" class="w-full px-4 py-3 rounded-xl border border-slate-300 font-semibold text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white shadow-sm transition" required>
                        <!-- Dynamic populated -->
                    </select>
                    <p class="text-[11px] text-slate-500 mt-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-indigo-500"></i>
                        ระบบจะเปลี่ยนสถานะเป็น <strong class="text-blue-600 font-bold">กำลังซ่อม (In Progress)</strong> และแจ้งเตือนช่างทันที
                    </p>
                </div>

                <!-- Admin Authority & Verification Badge -->
                <div class="p-3.5 rounded-2xl bg-gradient-to-r from-amber-50 to-indigo-50 border border-amber-200/90 mb-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-black text-amber-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved text-amber-600"></i> สิทธิ์ผู้ดูแลระบบ (Admin Authority)
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-slate-950">
                            <?= $isAdmin ? '✅ ล็อกอินเป็นแอดมินแล้ว' : '⚡ พร้อมยืนยันสิทธิ์' ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <div class="text-slate-600">
                            ชื่อแอดมิน: <strong class="text-slate-900"><?= htmlspecialchars($currentFullName ?: 'ผู้ดูแลระบบ (Admin)') ?></strong>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <label for="modal-assign-admin-pass" class="text-[11px] text-slate-500 whitespace-nowrap">รหัสแอดมิน:</label>
                            <input type="password" id="modal-assign-admin-pass" value="1234" class="px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-mono font-bold w-20 text-center bg-white focus:ring-2 focus:ring-indigo-500 outline-none shadow-inner" placeholder="1234" title="รหัสผ่าน Admin เริ่มต้นคือ 1234">
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-2.5 pt-1">
                    <button type="button" onclick="closeAssignModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-xs hover:bg-slate-50 transition cursor-pointer">
                        ยกเลิก
                    </button>
                    <button type="submit" id="modal-assign-submit-btn" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-extrabold text-xs shadow-lg shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-circle-check text-sm"></i>
                        <span>กดยืนยันการแจกงานให้ช่าง</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. PHOTO PREVIEW LIGHTBOX MODAL -->
    <div id="photo-modal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 hidden" onclick="closePhotoModal()">
        <div class="max-w-2xl w-full bg-white rounded-3xl overflow-hidden shadow-2xl" onclick="event.stopPropagation()">
            <div class="p-4 bg-slate-900 text-white flex justify-between items-center">
                <span id="photo-modal-title" class="font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-image text-amber-400"></i> รูปถ่ายงานซ่อม
                </span>
                <button onclick="closePhotoModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-4 flex items-center justify-center bg-slate-100 max-h-[70vh] overflow-auto">
                <img id="photo-modal-img" src="" alt="รูปภาพ" class="max-h-[65vh] w-auto object-contain rounded-xl shadow border border-slate-200">
            </div>
            <div class="p-3 bg-white text-right border-t border-slate-100">
                <button onclick="closePhotoModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 rounded-xl text-xs font-bold text-slate-700">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>

    <!-- 3. COMPLETION CONFIRMATION & IMAGE MODAL -->
    <div id="completion-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-base">บันทึกซ่อมเสร็จสิ้น</h4>
                        <p class="text-xs text-slate-500">Ticket: <strong id="modal-completion-ticket-id-display" class="font-mono text-emerald-600">-</strong></p>
                    </div>
                </div>
                <button onclick="closeCompletionModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form id="completion-form" onsubmit="submitCompletionForm(event)">
                <input type="hidden" id="modal-completion-ticket-id" value="">
                
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">แนบรูปถ่ายรายงานผลการซ่อมเสร็จ (ถ้ามี):</label>
                    <input type="file" id="modal-completion-file" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-xl p-2 bg-slate-50">
                    <p class="text-[11px] text-slate-400 mt-1">รองรับ JPG, PNG, WEBP</p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeCompletionModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-xs hover:bg-slate-50 transition">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition flex items-center gap-1.5">
                        <i class="fa-solid fa-check"></i> ยืนยันซ่อมเสร็จสมบูรณ์
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. ADMIN PRIVILEGE REQUIRED MODAL -->
    <div id="admin-lock-modal" class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-amber-200 transform transition-all text-center">
            <div class="w-16 h-16 rounded-3xl bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner border border-amber-200">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h4 class="font-black text-slate-800 text-lg">เฉพาะแอดมิน (Admin) เท่านั้น</h4>
            <p id="admin-lock-action-desc" class="text-xs text-slate-500 mt-1 mb-4">
                ฟังก์ชันระบบแจกงานช่างสงวนสิทธิ์เฉพาะบัญชีผู้ดูแลระบบ (Admin) เท่านั้น
            </p>

            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-left mb-5 space-y-2">
                <div class="flex items-start gap-2 text-amber-900">
                    <i class="fa-solid fa-key text-amber-600 mt-0.5"></i>
                    <div>
                        <strong>เงื่อนไขการเข้าใช้งาน:</strong>
                        <p class="text-amber-800 mt-0.5">
                            • ชื่อผู้ใช้งานต้องประกอบด้วยคำว่า <strong>Admin</strong> (เช่น Admin, ITAdmin, Admin01)<br>
                            • รหัสผ่านต้องเป็น <strong>1234</strong>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <a href="login.php" class="py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs shadow transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-right-to-bracket"></i> เข้าสู่ระบบแอดมิน
                </a>
                <a href="register.php" class="py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-user-plus"></i> สมัครสมาชิกแอดมิน
                </a>
            </div>
            <button onclick="closeAdminLockModal()" class="w-full mt-3 py-2 rounded-xl text-slate-400 hover:text-slate-600 font-semibold text-xs transition">
                ปิดหน้าต่าง
            </button>
        </div>
    </div>

    <!-- ================= JAVASCRIPT LOGIC ================= -->
    <script>
        // Pre-rendered Data from Server (Instant Zero-Delay Paint)
        const initialAnalytics = <?= json_encode($serverAnalytics, JSON_UNESCAPED_UNICODE) ?>;
        const initialTickets = <?= json_encode($allTicketsData, JSON_UNESCAPED_UNICODE) ?>;

        // Global State
        let dashboardData = initialAnalytics || null;
        let allTicketsMasterList = initialTickets || [];
        let techniciansList = (dashboardData && dashboardData.technicians) ? dashboardData.technicians : [];
        let statusChartInstance = null;
        let techWorkloadChartInstance = null;
        let deviceTypeChartInstance = null;

        // Current User Info (Session)
        let currentRole = <?= json_encode($currentUser ? $currentUser->getRole() : '') ?>;
        let currentFullName = <?= json_encode($currentUser ? $currentUser->getFullName() : '') ?>;
        let currentUsername = <?= json_encode($currentUser ? $currentUser->getUsername() : '') ?>;
        let isAdmin = <?= json_encode($isAdmin) ?>;
        let isTech = <?= json_encode($isTech) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            // Check for registration toast
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('registered') === 'admin_success') {
                showToast('🎉 ยินดีต้อนรับแอดมินใหม่! คุณได้รับสิทธิ์เต็มในการแจกงานและบริหารจัดการช่างแล้ว', 'success');
            }

            // Instant render with preloaded server data
            if (dashboardData) {
                renderKPICards(dashboardData.kpi);
                renderCharts(dashboardData);
                renderUnassignedPool(dashboardData.unassigned_tickets || []);
                renderTechnicianRoster(dashboardData.technicians || []);
                populateTechnicianDropdowns();
                applyTableFilters();
            }
            // Background sync
            initDashboard();
        });

        // Initialize and Load Data in Parallel
        async function initDashboard() {
            await Promise.all([
                fetchDashboardAnalytics(),
                fetchAllTicketsMaster()
            ]);
        }

        // 1. Fetch Analytics Data
        async function fetchDashboardAnalytics() {
            try {
                const res = await fetch('api.php?action=get_dashboard_analytics');
                const result = await res.json();
                if (result.success && result.data) {
                    dashboardData = result.data;
                    techniciansList = dashboardData.technicians || [];
                    renderKPICards(dashboardData.kpi);
                    renderCharts(dashboardData);
                    renderUnassignedPool(dashboardData.unassigned_tickets || []);
                    renderTechnicianRoster(dashboardData.technicians || []);
                    populateTechnicianDropdowns();
                    
                    const now = new Date();
                    document.getElementById('last-updated-text').textContent = 'อัปเดตล่าสุด: ' + now.toLocaleTimeString('th-TH');
                }
            } catch (err) {
                console.error('Failed to load analytics:', err);
                showToast('เกิดข้อผิดพลาดในการโหลดข้อมูลแดชบอร์ด: ' + err.message, 'error');
            }
        }

        // 2. Fetch All Tickets for Master Table
        async function fetchAllTicketsMaster() {
            try {
                const res = await fetch('api.php?action=get_all_tickets');
                const result = await res.json();
                if (result.success) {
                    allTicketsMasterList = result.data || [];
                    applyTableFilters();
                }
            } catch (err) {
                console.error('Failed to fetch tickets:', err);
            }
        }

        // Refresh Data
        async function refreshDashboardData() {
            const icon = document.getElementById('refresh-icon');
            if (icon) icon.classList.add('fa-spin');
            await initDashboard();
            setTimeout(() => {
                if (icon) icon.classList.remove('fa-spin');
                showToast('รีเฟรชข้อมูลแดชบอร์ดเรียบร้อยแล้ว', 'success');
            }, 500);
        }

        // Render KPI Top Cards
        function renderKPICards(kpi) {
            if (!kpi) return;
            document.getElementById('kpi-total-tickets').textContent = kpi.total_tickets || 0;
            document.getElementById('kpi-unassigned-count').textContent = kpi.unassigned_count || 0;
            document.getElementById('kpi-in-progress-count').textContent = kpi.in_progress_count || 0;
            document.getElementById('kpi-completed-count').textContent = kpi.completed_count || 0;
            document.getElementById('kpi-urgent-count').textContent = kpi.urgent_critical_count || 0;
            document.getElementById('kpi-total-cost').textContent = kpi.formatted_cost || '0.00 ฿';
            document.getElementById('kpi-available-techs').textContent = kpi.available_technicians || 0;
            document.getElementById('kpi-total-techs').textContent = `/ ${kpi.total_technicians || 0} ช่าง`;
            document.getElementById('kpi-completion-rate').textContent = `สำเร็จ: ${kpi.completion_rate || 0}%`;

            // Badge count in dispatch bar
            const dispatchBadge = document.getElementById('dispatch-unassigned-badge');
            if (dispatchBadge) dispatchBadge.textContent = kpi.unassigned_count || 0;

            const poolCountPill = document.getElementById('pool-count-pill');
            if (poolCountPill) poolCountPill.textContent = `${kpi.unassigned_count || 0} งาน`;

            // Pulsing dot if unassigned tickets exist
            const dot = document.getElementById('kpi-unassigned-dot');
            if (dot) {
                if (kpi.unassigned_count > 0) {
                    dot.className = "w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping inline-block";
                } else {
                    dot.className = "w-2 h-2 rounded-full bg-slate-300 inline-block";
                }
            }
        }

        // Render Chart.js Visualizations
        function renderCharts(data) {
            // Chart 1: Status Donut
            const statusCtx = document.getElementById('chart-status');
            if (statusCtx) {
                if (statusChartInstance) statusChartInstance.destroy();
                const sData = data.status_breakdown || { Pending: 0, 'In Progress': 0, Completed: 0 };
                statusChartInstance = new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['รอดำเนินการ', 'กำลังซ่อม', 'เสร็จสิ้น'],
                        datasets: [{
                            data: [sData['Pending'] || 0, sData['In Progress'] || 0, sData['Completed'] || 0],
                            backgroundColor: ['#F59E0B', '#3B82F6', '#10B981'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, font: { family: 'Kanit', size: 11 } } }
                        },
                        cutout: '70%'
                    }
                });
            }

            // Chart 2: Technician Workload Comparison Bar Chart
            const techCtx = document.getElementById('chart-tech-workload');
            if (techCtx && data.technicians) {
                if (techWorkloadChartInstance) techWorkloadChartInstance.destroy();
                const techLabels = data.technicians.map(t => t.full_name.split(' ')[0]);
                const activeJobs = data.technicians.map(t => t.active_jobs);
                const completedJobs = data.technicians.map(t => t.completed_jobs);

                techWorkloadChartInstance = new Chart(techCtx, {
                    type: 'bar',
                    data: {
                        labels: techLabels,
                        datasets: [
                            {
                                label: 'กำลังซ่อม (Active)',
                                data: activeJobs,
                                backgroundColor: '#6366F1',
                                borderRadius: 6
                            },
                            {
                                label: 'ซ่อมเสร็จแล้ว (Completed)',
                                data: completedJobs,
                                backgroundColor: '#10B981',
                                borderRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 10, font: { family: 'Kanit', size: 11 } } }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0, font: { family: 'Kanit', size: 10 } } },
                            x: { ticks: { font: { family: 'Kanit', size: 11 } } }
                        }
                    }
                });
            }

            // Chart 3: Device Type Breakdown
            const deviceCtx = document.getElementById('chart-device-type');
            if (deviceCtx && data.device_breakdown) {
                if (deviceTypeChartInstance) deviceTypeChartInstance.destroy();
                const dLabels = data.device_breakdown.map(d => d.device_type);
                const dCounts = data.device_breakdown.map(d => d.count);

                deviceTypeChartInstance = new Chart(deviceCtx, {
                    type: 'bar',
                    data: {
                        labels: dLabels,
                        datasets: [{
                            label: 'จำนวนที่แจ้งซ่อม',
                            data: dCounts,
                            backgroundColor: ['#3B82F6', '#EC4899', '#8B5CF6', '#10B981', '#F59E0B'],
                            borderRadius: 6
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0, font: { family: 'Kanit', size: 10 } } },
                            y: { ticks: { font: { family: 'Kanit', size: 11 } } }
                        }
                    }
                });
            }
        }

        // Render Unassigned Job Pool
        function renderUnassignedPool(tickets) {
            const container = document.getElementById('unassigned-pool-container');
            if (!container) return;

            if (tickets.length === 0) {
                container.innerHTML = `
                    <div class="py-10 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl mx-auto mb-2">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <h4 class="font-bold text-slate-700 text-sm">ไม่มีงานค้างในกองกลาง!</h4>
                        <p class="text-xs text-slate-400">งานแจ้งซ่อมทั้งหมดได้รับการมอบหมายให้ช่างเรียบร้อยแล้ว</p>
                    </div>
                `;
                return;
            }

            let html = '';
            tickets.forEach(t => {
                let pBadge = 'bg-slate-100 text-slate-700';
                if (t.priority === 'Urgent') pBadge = 'bg-rose-100 text-rose-700 border-rose-200 font-bold';
                else if (t.priority === 'High') pBadge = 'bg-amber-100 text-amber-800 font-bold';

                html += `
                    <div class="p-4 rounded-2xl border border-slate-200 bg-white hover:border-amber-300 hover:shadow-md transition flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-base font-bold shrink-0">
                                <i class="fa-solid fa-inbox"></i>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono font-black text-indigo-700 text-sm">${t.ticket_id}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${pBadge} border">
                                        ⚡ ความเร่งด่วน: ${t.priority}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                        ${t.device_type} (${t.asset_id})
                                    </span>
                                </div>
                                <p class="text-sm font-bold text-slate-800 mt-1">${t.issue_description}</p>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 mt-1">
                                    <span><i class="fa-solid fa-user text-slate-300"></i> ${t.user_name} (${t.department})</span>
                                    <span><i class="fa-solid fa-phone text-slate-300"></i> ${t.phone}</span>
                                    <span><i class="fa-solid fa-clock text-slate-300"></i> ${t.created_at}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Dispatch Actions -->
                        <div class="flex flex-wrap items-center gap-2 shrink-0 self-end md:self-center w-full md:w-auto justify-end">
                            <button onclick="autoDispatch('${t.ticket_id}')" title="ให้ระบบสุ่มเลือกช่างที่ว่างที่สุดให้อัตโนมัติ" class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-extrabold text-xs shadow transition flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-bolt"></i> แจกงานอัจฉริยะ (Auto)
                            </button>
                            <button onclick="openAssignModal('${t.ticket_id}')" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-user-plus"></i> เลือกช่าง & แจกงาน (Assign)
                            </button>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Render Technician Roster & Active Assignments Cards
        function renderTechnicianRoster(technicians) {
            const container = document.getElementById('technicians-roster-grid');
            if (!container) return;

            if (technicians.length === 0) {
                container.innerHTML = '<div class="col-span-full py-8 text-center text-slate-400">ไม่พบรายชื่อช่างในระบบ</div>';
                return;
            }

            let html = '';
            technicians.forEach(t => {
                const isAvailable = t.duty_status === 'Available';
                const statusBadge = isAvailable 
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">🟢 พร้อมรับงาน</span>'
                    : '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">☕ พักงาน</span>';

                // Workload Meter
                let loadLevel = 'สบาย (งานน้อย)';
                let loadColor = 'bg-emerald-500';
                if (t.active_jobs >= 3) {
                    loadLevel = 'งานแน่น (ภาระสูง)';
                    loadColor = 'bg-rose-500';
                } else if (t.active_jobs >= 1) {
                    loadLevel = 'ปานกลาง';
                    loadColor = 'bg-amber-500';
                }

                // Active assigned tickets list chips
                let activeChipsHtml = '';
                if (t.active_tickets && t.active_tickets.length > 0) {
                    activeChipsHtml = t.active_tickets.map(job => `
                        <div class="p-2 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between gap-2 hover:bg-indigo-50/50 transition">
                            <div class="truncate">
                                <span class="font-mono font-bold text-indigo-700">${job.ticket_id}</span>
                                <span class="text-slate-600 truncate block text-[11px]">${job.issue_description}</span>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button onclick="openAssignModal('${job.ticket_id}', '${t.full_name}')" title="โอนย้ายงานนี้ให้ช่างคนอื่น" class="p-1 rounded bg-white hover:bg-indigo-100 text-indigo-600 border border-slate-200 text-[10px] cursor-pointer">
                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                </button>
                                <button onclick="unassignJob('${job.ticket_id}')" title="ดึงงานนี้คืนเข้ากองกลาง" class="p-1 rounded bg-white hover:bg-rose-100 text-rose-600 border border-slate-200 text-[10px] cursor-pointer">
                                    <i class="fa-solid fa-arrow-rotate-left"></i>
                                </button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    activeChipsHtml = '<div class="py-3 text-center text-xs text-slate-400 italic">ไม่มีงานค้างอยู่ในมือ</div>';
                }

                // Duty Status Toggle Button (for admin or if technician matches)
                let dutyToggleBtn = '';
                if (isAdmin || (currentUsername && currentUsername === t.username)) {
                    const nextStatus = isAvailable ? 'Resting' : 'Available';
                    const nextText = isAvailable ? 'ปรับเป็น: พักงาน' : 'ปรับเป็น: พร้อมรับงาน';
                    dutyToggleBtn = `
                        <button onclick="toggleTechDuty('${t.username}', '${nextStatus}')" class="text-[10px] text-slate-500 hover:text-indigo-600 underline font-semibold mt-1">
                            ${nextText}
                        </button>
                    `;
                }

                html += `
                    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <!-- Header -->
                            <div class="flex items-start justify-between gap-2 mb-2 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow">
                                        <i class="fa-solid fa-user-gear"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm leading-tight">${t.full_name}</h4>
                                        <span class="text-[11px] text-slate-400 block">${t.department}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    ${statusBadge}
                                    ${dutyToggleBtn}
                                </div>
                            </div>

                            <!-- Workload Progress Bar -->
                            <div class="mb-3">
                                <div class="flex justify-between text-[11px] mb-1 font-semibold">
                                    <span class="text-slate-500">ภาระงานในมือ: <strong class="text-indigo-700">${t.active_jobs} งาน</strong></span>
                                    <span class="text-slate-400">${loadLevel}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="${loadColor} h-1.5 rounded-full" style="width: ${Math.min(100, t.active_jobs * 33.3)}%"></div>
                                </div>
                            </div>

                            <!-- Active Assigned Jobs List -->
                            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">งานที่รับผิดชอบอยู่:</span>
                                ${activeChipsHtml}
                            </div>
                        </div>

                        <!-- Footer stats -->
                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                            <span>ซ่อมเสร็จแล้ว: <strong class="text-emerald-600 font-bold">${t.completed_jobs} งาน</strong></span>
                            <span class="text-slate-300">•</span>
                            <span>รวมทั้งหมด: <strong class="text-slate-700 font-bold">${t.total_jobs} งาน</strong></span>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Populate Technician Dropdown in Filter & Modal
        function populateTechnicianDropdowns() {
            const filterSelect = document.getElementById('filter-technician');
            const modalSelect = document.getElementById('modal-assign-tech-select');

            if (!techniciansList || techniciansList.length === 0) return;

            // Update Filter Select
            if (filterSelect) {
                const currentVal = filterSelect.value;
                let opts = `
                    <option value="ALL">👨‍🔧 ช่างทุกคน (ทั้งหมด)</option>
                    <option value="UNASSIGNED">⚠️ กองกลาง (ยังไม่มีช่างรับงาน)</option>
                `;
                techniciansList.forEach(t => {
                    opts += `<option value="${t.full_name}">👨‍🔧 ${t.full_name} (${t.active_jobs} งานค้าง)</option>`;
                });
                filterSelect.innerHTML = opts;
                if (currentVal) filterSelect.value = currentVal;
            }

            // Update Modal Select
            if (modalSelect) {
                let modalOpts = '<option value="">-- กรุณาเลือกช่างผู้รับงาน --</option>';
                techniciansList.forEach(t => {
                    const dutyIcon = t.duty_status === 'Available' ? '🟢 พร้อมรับงาน' : '☕ พักงาน';
                    modalOpts += `<option value="${t.full_name}">${t.full_name} [${dutyIcon} | งานในมือ: ${t.active_jobs}]</option>`;
                });
                modalSelect.innerHTML = modalOpts;
            }
        }

        // Apply Table Filters and Render Master Table
        function applyTableFilters() {
            const tbody = document.getElementById('master-tickets-tbody');
            if (!tbody) return;

            const searchInput = (document.getElementById('table-search-input')?.value || '').toLowerCase().trim();
            const techFilter = document.getElementById('filter-technician')?.value || 'ALL';
            const statusFilter = document.getElementById('filter-status')?.value || 'ALL';
            const priorityFilter = document.getElementById('filter-priority')?.value || 'ALL';

            let filtered = allTicketsMasterList.filter(t => {
                // Search match
                if (searchInput) {
                    const matchId = (t.ticket_id || '').toLowerCase().includes(searchInput);
                    const matchUser = (t.user_name || '').toLowerCase().includes(searchInput);
                    const matchDept = (t.department || '').toLowerCase().includes(searchInput);
                    const matchAsset = (t.asset_id || '').toLowerCase().includes(searchInput);
                    const matchDesc = (t.issue_description || '').toLowerCase().includes(searchInput);
                    const matchTech = (t.assigned_technician || '').toLowerCase().includes(searchInput);
                    if (!matchId && !matchUser && !matchDept && !matchAsset && !matchDesc && !matchTech) {
                        return false;
                    }
                }

                // Technician filter
                if (techFilter === 'UNASSIGNED') {
                    if (t.assigned_technician && t.assigned_technician !== '') return false;
                } else if (techFilter !== 'ALL') {
                    if (t.assigned_technician !== techFilter) return false;
                }

                // Status filter
                if (statusFilter !== 'ALL' && t.status !== statusFilter) {
                    return false;
                }

                // Priority filter
                if (priorityFilter !== 'ALL' && t.priority !== priorityFilter) {
                    return false;
                }

                return true;
            });

            const totalCountEl = document.getElementById('table-total-count');
            if (totalCountEl) totalCountEl.textContent = `แสดง ${filtered.length} จาก ${allTicketsMasterList.length} รายการ`;

            renderMasterTableBody(filtered);
        }

        // Render Master Table Rows
        function renderMasterTableBody(tickets) {
            const tbody = document.getElementById('master-tickets-tbody');
            if (!tbody) return;

            if (tickets.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-12 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-2xl mb-2 text-slate-300 block"></i>
                            ไม่พบรายการงานซ่อมตามเงื่อนไขการค้นหา
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            tickets.forEach(t => {
                // Priority Badge
                let pBadge = 'bg-slate-100 text-slate-700';
                if (t.priority === 'Urgent') pBadge = 'bg-rose-100 text-rose-700 font-bold border border-rose-200';
                else if (t.priority === 'High') pBadge = 'bg-amber-100 text-amber-800 font-bold border border-amber-200';
                else if (t.priority === 'Normal') pBadge = 'bg-blue-50 text-blue-700 border border-blue-200';

                // Status Badge
                let sBadge = 'bg-amber-100 text-amber-800';
                if (t.status === 'In Progress') sBadge = 'bg-blue-100 text-blue-800 font-bold';
                else if (t.status === 'Completed') sBadge = 'bg-emerald-100 text-emerald-800 font-bold';

                // Assigned Tech Display
                let techHtml = '';
                if (t.assigned_technician) {
                    techHtml = `
                        <div class="flex items-center gap-1.5">
                            <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-bold">
                                <i class="fa-solid fa-user-gear"></i>
                            </div>
                            <span class="font-bold text-slate-800">${t.assigned_technician}</span>
                        </div>
                    `;
                } else {
                    techHtml = `
                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 font-bold rounded-lg border border-amber-200 text-[11px] inline-flex items-center gap-1">
                            <i class="fa-solid fa-clock text-amber-600"></i> กองกลาง (รอช่าง)
                        </span>
                    `;
                }

                // Photos Before / After
                let photosHtml = '<span class="text-slate-400">-</span>';
                if (t.issue_image || t.completion_image) {
                    photosHtml = '<div class="flex items-center gap-1.5">';
                    if (t.issue_image) {
                        photosHtml += `
                            <button onclick="openPhotoModal('${t.issue_image}', 'รูปอาการเสียก่อนซ่อม (${t.ticket_id})')" title="ดูรูปก่อนซ่อม" class="w-7 h-7 rounded-lg overflow-hidden border border-slate-300 hover:scale-110 transition">
                                <img src="${t.issue_image}" class="w-full h-full object-cover">
                            </button>
                        `;
                    }
                    if (t.completion_image) {
                        photosHtml += `
                            <button onclick="openPhotoModal('${t.completion_image}', 'รูปรายงานผลซ่อมเสร็จ (${t.ticket_id})')" title="ดูรูปรายงานซ่อมเสร็จ" class="w-7 h-7 rounded-lg overflow-hidden border-2 border-emerald-500 hover:scale-110 transition">
                                <img src="${t.completion_image}" class="w-full h-full object-cover">
                            </button>
                        `;
                    }
                    photosHtml += '</div>';
                }

                // Actions buttons
                let actionsHtml = '<div class="flex items-center justify-center gap-1">';
                
                // Assign / Reassign Button
                if (t.status !== 'Completed') {
                    actionsHtml += `
                        <button onclick="openAssignModal('${t.ticket_id}', '${t.assigned_technician || ''}')" title="มอบหมาย / แจกงานให้ช่าง" class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold text-xs transition cursor-pointer">
                            <i class="fa-solid fa-user-gear"></i>
                        </button>
                    `;
                }

                // Unassign Button (if assigned and not completed)
                if (t.assigned_technician && t.status !== 'Completed') {
                    actionsHtml += `
                        <button onclick="unassignJob('${t.ticket_id}')" title="ดึงงานกลับเข้ากองกลาง" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs transition cursor-pointer">
                            <i class="fa-solid fa-arrow-rotate-left"></i>
                        </button>
                    `;
                }

                // Complete Button
                if (t.status === 'In Progress') {
                    actionsHtml += `
                        <button onclick="openCompletionModal('${t.ticket_id}')" title="บันทึกซ่อมเสร็จ" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs transition">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    `;
                }

                // Delete Button
                if (isAdmin || isTech) {
                    actionsHtml += `
                        <button onclick="deleteTicket('${t.ticket_id}')" title="ลบงานนี้" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 text-xs transition">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    `;
                }

                actionsHtml += '</div>';

                html += `
                    <tr class="hover:bg-slate-50 transition border-b border-slate-100">
                        <td class="p-3.5 font-mono font-bold text-indigo-700 whitespace-nowrap">
                            ${t.ticket_id}<br>
                            <span class="text-[10px] text-slate-400 font-sans">${t.created_at || ''}</span>
                        </td>
                        <td class="p-3.5">
                            <strong class="text-slate-800">${t.user_name}</strong><br>
                            <span class="text-slate-400 text-[11px]">${t.department} • ${t.phone}</span>
                        </td>
                        <td class="p-3.5 whitespace-nowrap">
                            <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-[11px]">${t.asset_id}</span><br>
                            <span class="text-slate-500 text-[11px]">${t.device_type}</span>
                        </td>
                        <td class="p-3.5 max-w-xs truncate" title="${t.issue_description}">
                            ${t.issue_description}
                        </td>
                        <td class="p-3.5 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded-full text-[11px] ${pBadge}">
                                ${t.priority}
                            </span>
                        </td>
                        <td class="p-3.5 whitespace-nowrap">
                            ${techHtml}
                        </td>
                        <td class="p-3.5">
                            ${photosHtml}
                        </td>
                        <td class="p-3.5 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[11px] ${sBadge}">
                                ${t.status}
                            </span>
                        </td>
                        <td class="p-3.5 text-center whitespace-nowrap">
                            ${actionsHtml}
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // Helper: Prompt Admin Required Modal
        function promptAdminRequired(actionName = 'ใช้งานระบบแจกงานช่าง') {
            const desc = document.getElementById('admin-lock-action-desc');
            if (desc) {
                desc.innerHTML = `ฟังก์ชัน <strong>"${actionName}"</strong> สงวนสิทธิ์เฉพาะผู้ดูแลระบบ (Admin) เท่านั้น`;
            }
            const modal = document.getElementById('admin-lock-modal');
            if (modal) modal.classList.remove('hidden');
            showToast(`🔒 เฉพาะแอดมิน (Admin) เท่านั้นที่สามารถ${actionName}ได้`, 'error');
        }

        function closeAdminLockModal() {
            const modal = document.getElementById('admin-lock-modal');
            if (modal) modal.classList.add('hidden');
        }

        // Quick 1-Click Admin Login (Admin / 1234)
        async function quickAdminLogin() {
            try {
                const res = await fetch('api.php?action=quick_admin_login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: 'Admin', password: '1234' })
                });
                const result = await res.json();
                if (result.success) {
                    showToast('🎉 เข้าสู่ระบบเป็นผู้ดูแลระบบ (Admin) สำเร็จเรียบร้อยแล้ว!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    showToast(result.message || 'ไม่สามารถเข้าสู่ระบบแอดมินได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการเข้าสู่ระบบ', 'error');
            }
        }

        // Auto-Dispatch action
        async function autoDispatch(ticketId) {
            if (!confirm(`ต้องการให้ระบบช่วยแจกงาน ${ticketId} ให้ช่างที่ว่างที่สุดโดยอัตโนมัติใช่หรือไม่?`)) return;

            try {
                const res = await fetch('api.php?action=auto_dispatch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        ticket_id: ticketId,
                        admin_password: '1234',
                        admin_username: 'Admin'
                    })
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    isAdmin = true;
                    initDashboard();
                } else {
                    showToast(result.message || 'ไม่สามารถแจกงานได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        }

        // Unassign action
        async function unassignJob(ticketId) {
            if (!confirm(`คุณต้องการดึงงาน ${ticketId} กลับเข้ากองกลาง (ปลดช่างออก) ใช่หรือไม่?`)) return;

            try {
                const res = await fetch('api.php?action=unassign_technician', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        ticket_id: ticketId,
                        admin_password: '1234',
                        admin_username: 'Admin'
                    })
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    isAdmin = true;
                    initDashboard();
                } else {
                    showToast(result.message || 'ไม่สามารถดึงงานกลับเข้ากองกลางได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        }

        // Delete ticket action
        async function deleteTicket(ticketId) {
            if (!confirm(`คุณต้องการลบใบแจ้งซ่อมรหัส ${ticketId} ใช่หรือไม่?`)) return;

            try {
                const res = await fetch(`api.php?action=delete_ticket&ticket_id=${encodeURIComponent(ticketId)}`);
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    initDashboard();
                } else {
                    showToast(result.message || 'ไม่สามารถลบใบแจ้งซ่อมได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
            }
        }

        // Toggle Technician Duty Status (Available / Resting)
        async function toggleTechDuty(username, newStatus) {
            try {
                const res = await fetch('api.php?action=toggle_duty_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: username, status: newStatus })
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    initDashboard();
                } else {
                    showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการปรับสถานะปฏิบัติงาน', 'error');
            }
        }

        // Assign Modal Handlers
        function openAssignModal(ticketId, currentTech = '') {
            // Ensure technician dropdown is fully populated
            populateTechnicianDropdowns();

            document.getElementById('modal-assign-ticket-id').value = ticketId;
            document.getElementById('modal-assign-ticket-id-display').textContent = ticketId;

            // Search for ticket details to show preview
            const ticket = (allTicketsMasterList || []).find(t => t.ticket_id === ticketId) ||
                           (dashboardData && dashboardData.unassigned_tickets ? dashboardData.unassigned_tickets.find(t => t.ticket_id === ticketId) : null);
            
            const previewEl = document.getElementById('modal-assign-ticket-preview');
            if (previewEl) {
                if (ticket) {
                    document.getElementById('modal-assign-preview-user').textContent = `ผู้แจ้ง: ${ticket.user_name || '-'} (${ticket.department || '-'})`;
                    document.getElementById('modal-assign-preview-priority').textContent = `⚡ ความเร่งด่วน: ${ticket.priority || 'Normal'}`;
                    document.getElementById('modal-assign-preview-desc').textContent = `อาการ: ${ticket.issue_description || '-'}`;
                    previewEl.classList.remove('hidden');
                } else {
                    previewEl.classList.add('hidden');
                }
            }

            const select = document.getElementById('modal-assign-tech-select');
            if (select) {
                if (currentTech) {
                    select.value = currentTech;
                } else {
                    // Pre-select the best available technician if none was selected
                    const availableTech = (techniciansList || []).find(t => t.duty_status === 'Available');
                    if (availableTech && availableTech.full_name) {
                        select.value = availableTech.full_name;
                    } else if (select.options.length > 1) {
                        select.selectedIndex = 1;
                    }
                }
            }

            // Ensure submit button is in ready state
            const submitBtn = document.getElementById('modal-assign-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-circle-check text-sm"></i> <span>กดยืนยันการแจกงานให้ช่าง</span>';
            }

            document.getElementById('assign-modal').classList.remove('hidden');
        }

        function closeAssignModal() {
            document.getElementById('assign-modal').classList.add('hidden');
        }

        async function submitReassignModal(e) {
            e.preventDefault();
            const ticketId = document.getElementById('modal-assign-ticket-id').value;
            const techName = document.getElementById('modal-assign-tech-select').value;
            const adminPass = document.getElementById('modal-assign-admin-pass')?.value || '1234';
            const submitBtn = document.getElementById('modal-assign-submit-btn');

            if (!techName) {
                alert('กรุณาเลือกช่างผู้รับงาน');
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-sm"></i> <span>กำลังแจกงานให้ช่าง...</span>';
            }

            try {
                const res = await fetch('api.php?action=reassign_technician', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        ticket_id: ticketId, 
                        assigned_technician: techName,
                        admin_password: adminPass,
                        admin_username: 'Admin'
                    })
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    closeAssignModal();
                    // Set admin to true in current session so all badges unlock
                    isAdmin = true;
                    initDashboard();
                } else {
                    showToast(result.message || 'ไม่สามารถมอบหมายงานได้', 'error');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-circle-check text-sm"></i> <span>กดยืนยันการแจกงานให้ช่าง</span>';
                    }
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการมอบหมายงาน: ' + err.message, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-circle-check text-sm"></i> <span>กดยืนยันการแจกงานให้ช่าง</span>';
                }
            }
        }

        // Completion Modal Handlers
        function openCompletionModal(ticketId) {
            document.getElementById('modal-completion-ticket-id').value = ticketId;
            document.getElementById('modal-completion-ticket-id-display').textContent = ticketId;
            document.getElementById('completion-modal').classList.remove('hidden');
        }

        function closeCompletionModal() {
            document.getElementById('completion-modal').classList.add('hidden');
        }

        async function submitCompletionForm(e) {
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
                    initDashboard();
                } else {
                    showToast(result.message || 'ไม่สามารถบันทึกซ่อมเสร็จได้', 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' + err.message, 'error');
            }
        }

        // Photo Preview Modal
        function openPhotoModal(imgSrc, title = 'รูปภาพ') {
            document.getElementById('photo-modal-img').src = imgSrc;
            document.getElementById('photo-modal-title').textContent = title;
            document.getElementById('photo-modal').classList.remove('hidden');
        }

        function closePhotoModal() {
            document.getElementById('photo-modal').classList.add('hidden');
        }

        // Toast Feedback System
        function showToast(message, type = 'success') {
            const banner = document.getElementById('toast-banner');
            const msgEl = document.getElementById('toast-message');
            const titleEl = document.getElementById('toast-title');
            const iconWrap = document.getElementById('toast-icon-wrap');
            const icon = document.getElementById('toast-icon');

            if (!banner) return;

            msgEl.textContent = message;

            if (type === 'success') {
                banner.className = 'p-4 rounded-2xl shadow-lg flex items-center justify-between border bg-emerald-50 border-emerald-200 text-emerald-900 transition-all duration-300';
                titleEl.textContent = 'สำเร็จ (Success)';
                titleEl.className = 'text-xs font-black uppercase tracking-wider text-emerald-800';
                iconWrap.className = 'w-10 h-10 rounded-xl flex items-center justify-center text-lg bg-emerald-500 text-white';
                icon.className = 'fa-solid fa-check';
            } else {
                banner.className = 'p-4 rounded-2xl shadow-lg flex items-center justify-between border bg-rose-50 border-rose-200 text-rose-900 transition-all duration-300';
                titleEl.textContent = 'แจ้งเตือน / ผิดพลาด (Alert)';
                titleEl.className = 'text-xs font-black uppercase tracking-wider text-rose-800';
                iconWrap.className = 'w-10 h-10 rounded-xl flex items-center justify-center text-lg bg-rose-500 text-white';
                icon.className = 'fa-solid fa-exclamation';
            }

            banner.classList.remove('hidden');
            setTimeout(() => {
                hideToast();
            }, 6000);
        }

        function hideToast() {
            const banner = document.getElementById('toast-banner');
            if (banner) banner.classList.add('hidden');
        }

        // Smooth Scroll Helper
        function scrollToSection(id) {
            const el = document.getElementById(id);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    </script>
</body>
</html>
