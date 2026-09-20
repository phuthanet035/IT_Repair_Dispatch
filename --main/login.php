<?php
/**
 * Login Page (login.php)
 * หน้าเข้าสู่ระบบ แยกสิทธิ์ 3 บทบาทชัดเจน:
 * 1. ผู้ใช้งานทั่วไป (User): เพิ่มงาน และ ติดตามงานได้เท่านั้น
 * 2. ช่างเทคนิค (Technician): กดรับงานเอง หรือ รับงานจากแอดมินที่ส่งมาให้ได้แค่นั้น
 * 3. ผู้ดูแลระบบ (Admin): ส่งงานไปให้ช่าง, รับงานจากผู้ใช้งาน และ ติดตามงานได้เท่านั้น
 */

require_once __DIR__ . '/classes/UserManager.php';

$userManager = new UserManager();
$currentUser = $userManager->getCurrentUser();

// หากล็อกอินอยู่แล้ว ให้ส่งไปยังหน้าเฉพาะตามสิทธิ์ของบทบาทนั้น
if ($currentUser) {
    if ($currentUser->isAdmin()) {
        header('Location: dashboard.php');
    } elseif ($currentUser->isTechnician()) {
        header('Location: index.php?tab=tech-dashboard');
    } else {
        header('Location: index.php?tab=create-ticket');
    }
    exit;
}

$error = '';
$deniedMsg = '';
if (isset($_GET['denied']) && $_GET['denied'] === 'admin_only') {
    $deniedMsg = '🔒 หน้านี้สงวนสิทธิ์เฉพาะผู้ดูแลระบบ (Admin) เท่านั้น กรุณาเข้าสู่ระบบด้วยบัญชีแอดมิน';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $user = $userManager->login($username, $password);
        if ($user) {
            // แยก Redirect ตามบทบาทที่ผู้ใช้สั่ง
            if ($user->isAdmin()) {
                header('Location: dashboard.php');
            } elseif ($user->isTechnician()) {
                header('Location: index.php?tab=tech-dashboard');
            } else {
                header('Location: index.php?tab=create-ticket');
            }
            exit;
        } else {
            $error = 'ชื่อผู้ใช้ หรือ รหัสผ่านไม่ถูกต้อง!';
        }
    } else {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน!';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - IT Repair Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Kanit', sans-serif; } </style>
</head>
<body class="bg-gradient-to-br from-indigo-950 via-slate-900 to-slate-950 min-h-screen flex items-center justify-center p-4 py-8">

    <div class="max-w-xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200">
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 p-7 text-white text-center relative overflow-hidden">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="w-16 h-16 bg-white/15 rounded-2xl flex items-center justify-center mx-auto mb-3 backdrop-blur-md shadow-inner border border-white/20">
                <i class="fa-solid fa-user-shield text-3xl text-amber-300"></i>
            </div>
            <h1 class="text-2xl font-extrabold tracking-wide">เข้าสู่ระบบ (System Login)</h1>
            <p class="text-xs text-indigo-200 mt-1">ระบบแยก 3 สิทธิ์: ผู้ใช้งาน • ช่างเทคนิค • แอดมินแจกงาน</p>
        </div>

        <!-- Form Content -->
        <div class="p-6 sm:p-8">
            <!-- Access Denied Alert -->
            <?php if (!empty($deniedMsg)): ?>
                <div class="mb-5 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-base text-amber-600 shrink-0"></i>
                    <span><?= htmlspecialchars($deniedMsg) ?></span>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-base text-rose-600 shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="login.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">ชื่อผู้ใช้งาน (Username)</label>
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-4 top-3.5 text-slate-400 text-sm"></i>
                        <input type="text" name="username" id="login-username" required placeholder="เช่น user1, tech1, Admin..." class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-slate-800 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">รหัสผ่าน (Password)</label>
                    <div class="relative">
                        <i class="fa-solid fa-key absolute left-4 top-3.5 text-slate-400 text-sm"></i>
                        <input type="password" name="password" id="login-password" required placeholder="ป้อนรหัสผ่าน..." class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-slate-800 text-sm">
                    </div>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2 cursor-pointer mt-2">
                    <i class="fa-solid fa-right-to-bracket"></i> เข้าสู่ระบบ
                </button>
            </form>

            <!-- 3 Roles Explanation & 1-Click Fast Login -->
            <div class="mt-7 pt-6 border-t border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-slate-700 uppercase tracking-wider">
                        <i class="fa-solid fa-users-gear text-indigo-600"></i> เลือกเข้าสู่ระบบด่วนตามสิทธิ์ (3 บทบาท):
                    </span>
                    <span class="text-[10px] text-slate-400">คลิกที่การ์ดเพื่อกรอกและล็อกอิน</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <!-- Role 1: User -->
                    <button type="button" onclick="quickFill('user1', 'user123')" class="p-3 rounded-2xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-left transition shadow-sm hover:scale-[1.02] cursor-pointer">
                        <div class="flex items-center justify-between mb-1">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-600 text-white">ผู้ใช้งาน</span>
                            <i class="fa-solid fa-circle-arrow-right text-emerald-600 text-xs"></i>
                        </div>
                        <strong class="text-xs text-emerald-950 font-bold block">นายสมชาย (User)</strong>
                        <p class="text-[10px] text-emerald-800 mt-1 leading-tight">
                            ✨ สิทธิ์: <strong>เพิ่มงานแจ้งซ่อม</strong> & <strong>ติดตามงาน</strong> ได้เท่านั้น
                        </p>
                        <span class="text-[10px] font-mono text-emerald-700 mt-1.5 block font-bold">user1 / user123</span>
                    </button>

                    <!-- Role 2: Technician -->
                    <button type="button" onclick="quickFill('tech1', 'tech123')" class="p-3 rounded-2xl bg-blue-50 hover:bg-blue-100 border border-blue-200 text-left transition shadow-sm hover:scale-[1.02] cursor-pointer">
                        <div class="flex items-center justify-between mb-1">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-600 text-white">ช่างซ่อม</span>
                            <i class="fa-solid fa-circle-arrow-right text-blue-600 text-xs"></i>
                        </div>
                        <strong class="text-xs text-blue-950 font-bold block">ช่างวิชัย (Tech)</strong>
                        <p class="text-[10px] text-blue-800 mt-1 leading-tight">
                            🛠️ สิทธิ์: <strong>กดรับงานเอง</strong> หรือ <strong>รับงานจากแอดมิน</strong> & บันทึกผลเสร็จ
                        </p>
                        <span class="text-[10px] font-mono text-blue-700 mt-1.5 block font-bold">tech1 / tech123</span>
                    </button>

                    <!-- Role 3: Admin -->
                    <button type="button" onclick="quickFill('Admin', '1234')" class="p-3 rounded-2xl bg-amber-50 hover:bg-amber-100 border-2 border-amber-400 text-left transition shadow-sm hover:scale-[1.02] cursor-pointer">
                        <div class="flex items-center justify-between mb-1">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-slate-950">แอดมิน</span>
                            <i class="fa-solid fa-crown text-amber-600 text-xs"></i>
                        </div>
                        <strong class="text-xs text-amber-950 font-bold block">ผู้ดูแลระบบ (Admin)</strong>
                        <p class="text-[10px] text-amber-900 mt-1 leading-tight">
                            👑 สิทธิ์: <strong>รับงานจากผู้ใช้</strong>, <strong>ส่งงาน/เลือกช่าง</strong> & <strong>ติดตามงาน</strong>
                        </p>
                        <span class="text-[10px] font-mono text-amber-800 mt-1.5 block font-bold">Admin / 1234</span>
                    </button>
                </div>
            </div>

            <!-- Footer Link -->
            <div class="mt-6 text-center text-xs text-slate-500">
                ยังไม่มีบัญชีสมาชิก? <a href="register.php" class="text-indigo-600 font-bold hover:underline">สมัครสมาชิกใหม่ที่นี่</a>
            </div>
        </div>
    </div>

    <script>
        function quickFill(u, p) {
            document.getElementById('login-username').value = u;
            document.getElementById('login-password').value = p;
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>
