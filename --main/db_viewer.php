<?php
/**
 * Simple Database Viewer (db_viewer.php)
 * เปิดดูข้อมูลในฐานข้อมูล (assets & repair_tickets) ได้อย่างง่ายดายผ่านเว็บเบราว์เซอร์
 * URL: http://localhost/Itแจ้งว่อม/db_viewer.php
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();
    $driver = Database::getDriver();

    // Fetch assets
    $assets = $pdo->query("SELECT * FROM assets ORDER BY id ASC")->fetchAll();

    // Fetch tickets
    $tickets = $pdo->query("SELECT * FROM repair_tickets ORDER BY id DESC")->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Viewer - IT Repair System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Kanit', sans-serif; } </style>
</head>
<body class="bg-slate-100 text-slate-800 p-6">

    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Header -->
        <div class="bg-slate-900 text-white p-6 rounded-2xl shadow-lg flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fa-solid fa-database text-amber-400"></i> Database Viewer (เครื่องมือดูข้อมูลในฐานข้อมูล)
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Database Driver ปัจจุบัน: <span class="bg-indigo-600 px-2 py-0.5 rounded text-white font-mono"><?= strtoupper($driver) ?></span>
                    • ฐานข้อมูล: <code class="text-amber-300">it_repair_db</code>
                </p>
            </div>
            <a href="index.php" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                <i class="fa-solid fa-arrow-left"></i> กลับหน้าระบบแจ้งซ่อม
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-100 text-rose-800 p-4 rounded-xl border border-rose-300">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>

            <!-- Table 1: Repair Tickets -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-ticket text-indigo-600"></i> ตารางใบแจ้งซ่อม (`repair_tickets`)
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2.5 py-0.5 rounded-full font-normal"><?= count($tickets) ?> รายการ</span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                <th class="p-3">ID</th>
                                <th class="p-3">Ticket ID</th>
                                <th class="p-3">ผู้แจ้งซ่อม</th>
                                <th class="p-3">แผนก / ตำแหน่ง</th>
                                <th class="p-3">เบอร์โทร</th>
                                <th class="p-3">รหัสอุปกรณ์</th>
                                <th class="p-3">อาการเสีย</th>
                                <th class="p-3">ราคาประเมิน</th>
                                <th class="p-3">ประเมินเวลา</th>
                                <th class="p-3">สถานะ</th>
                                <th class="p-3">วันที่แจ้ง</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($tickets)): ?>
                                <tr><td colspan="11" class="text-center py-6 text-slate-400">ยังไม่มีข้อมูลในตาราง `repair_tickets`</td></tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $t): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-3 text-slate-400"><?= $t['id'] ?></td>
                                        <td class="p-3 font-bold text-indigo-600 font-mono"><?= htmlspecialchars($t['ticket_id']) ?></td>
                                        <td class="p-3 font-semibold text-slate-800"><?= htmlspecialchars($t['user_name']) ?></td>
                                        <td class="p-3 text-slate-600"><?= htmlspecialchars($t['department']) ?></td>
                                        <td class="p-3 text-slate-600 font-mono"><?= htmlspecialchars($t['phone']) ?></td>
                                        <td class="p-3 font-mono"><?= htmlspecialchars($t['asset_id']) ?></td>
                                        <td class="p-3 max-w-xs truncate" title="<?= htmlspecialchars($t['issue_description']) ?>"><?= htmlspecialchars($t['issue_description']) ?></td>
                                        <td class="p-3 font-bold text-emerald-600"><?= number_format($t['estimated_cost'], 2) ?> ฿</td>
                                        <td class="p-3 text-slate-600"><?= htmlspecialchars($t['estimated_completion_time']) ?></td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $t['status'] === 'Completed' ? 'bg-emerald-100 text-emerald-800' : ($t['status'] === 'In Progress' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') ?>">
                                                <?= htmlspecialchars($t['status']) ?>
                                            </span>
                                        </td>
                                        <td class="p-3 text-slate-400"><?= htmlspecialchars($t['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table 2: IT Assets -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-desktop text-sky-600"></i> ตารางอุปกรณ์ IT (`assets`)
                        <span class="text-xs bg-sky-100 text-sky-700 px-2.5 py-0.5 rounded-full font-normal"><?= count($assets) ?> รายการ</span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                <th class="p-3">ID</th>
                                <th class="p-3">รหัสอุปกรณ์ (Asset ID)</th>
                                <th class="p-3">ชื่ออุปกรณ์</th>
                                <th class="p-3">ประเภทอุปกรณ์</th>
                                <th class="p-3">สิทธิ์ประกัน</th>
                                <th class="p-3">สถานะอุปกรณ์</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($assets as $a): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-3 text-slate-400"><?= $a['id'] ?></td>
                                    <td class="p-3 font-mono font-bold text-slate-800"><?= htmlspecialchars($a['asset_id']) ?></td>
                                    <td class="p-3 font-medium text-slate-700"><?= htmlspecialchars($a['name']) ?></td>
                                    <td class="p-3 text-slate-600"><?= htmlspecialchars($a['device_type']) ?></td>
                                    <td class="p-3">
                                        <?php if ($a['is_warranty']): ?>
                                            <span class="text-emerald-600 font-bold"><i class="fa-solid fa-shield-halved"></i> ในประกัน</span>
                                        <?php else: ?>
                                            <span class="text-slate-400">หมดประกัน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $a['status'] === 'Normal' ? 'bg-emerald-100 text-emerald-800' : ($a['status'] === 'Repairing' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') ?>">
                                            <?= htmlspecialchars($a['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>
    </div>

</body>
</html>
