<?php
/**
 * Registration Page (register.php)
 * หน้าสมัครสมาชิกใหม่ สำหรับผู้ใช้งานทั่วไป และ เจ้าหน้าที่ IT
 */

require_once __DIR__ . '/classes/UserManager.php';

$userManager = new UserManager();
$currentUser = $userManager->getCurrentUser();

if ($currentUser) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'User';

    $fullDept = $position ? "$department ($position)" : $department;
    $hasAdminInName = (stripos($username, 'Admin') !== false);

    if (empty($username) || empty($password) || empty($fullName) || empty($phone)) {
        $error = 'กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบถ้วน';
    } elseif ($password !== $confirmPassword) {
        $error = 'รหัสผ่าน และ ยืนยันรหัสผ่าน ไม่ตรงกัน';
    } elseif ($role === 'Admin' && !$hasAdminInName) {
        $error = "การสมัครบัญชีแอดมิน (Admin) ชื่อผู้ใช้งาน (Username) ต้องประกอบด้วยคำว่า 'Admin' (เช่น Admin, Admin1, ITAdmin)";
    } elseif (($role === 'Admin' || $hasAdminInName) && $password !== '1234') {
        $error = "รหัสผ่านสำหรับบัญชีแอดมิน (Admin) ต้องเป็น '1234' เท่านั้นตามเงื่อนไขของระบบ";
    } elseif (strlen($password) < 4) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร';
    } elseif ($userManager->usernameExists($username)) {
        $error = "ชื่อผู้ใช้ '$username' มีผู้ใช้งานในระบบแล้ว กรุณาเลือกชื่ออื่น";
    } else {
        // หากชื่อมีคำว่า Admin และรหัสผ่านคือ 1234 จะได้รับสิทธิ์เป็น Admin ทันที
        if ($hasAdminInName && $password === '1234') {
            $role = 'Admin';
        }

        $created = $userManager->registerUser($username, $password, $fullName, $fullDept, $phone, $email, $role);
        if ($created) {
            // Auto login after registration
            $userManager->login($username, $password);
            if ($role === 'Admin') {
                header('Location: dashboard.php?registered=admin_success');
            } else {
                header('Location: index.php?registered=success');
            }
            exit;
        } else {
            $error = 'เกิดข้อผิดพลาดในการลงทะเบียน กรุณาลองใหม่อีกครั้ง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - IT Repair Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Kanit', sans-serif; } </style>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-slate-900 to-slate-950 min-h-screen flex items-center justify-center p-4 py-8">

    <div class="max-w-lg w-full bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-100">
        <!-- Header Banner -->
        <div class="bg-indigo-600 p-6 text-white text-center relative overflow-hidden">
            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-2 backdrop-blur-sm">
                <i class="fa-solid fa-user-plus text-2xl text-amber-300"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-wide">สมัครสมาชิกใหม่ (Register)</h1>
            <p class="text-xs text-indigo-200 mt-0.5">สร้างบัญชีเพื่อใช้งานระบบแจ้งซ่อมและติดตามงาน</p>
        </div>

        <!-- Form Content -->
        <div class="p-6 md:p-8">
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Admin registration guideline tip box -->
            <div class="mb-5 p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-2.5">
                <i class="fa-solid fa-crown text-amber-600 text-base mt-0.5 shrink-0"></i>
                <div class="space-y-0.5">
                    <strong class="text-amber-950 font-bold block">💡 เงื่อนไขสำหรับการสมัครเป็นแอดมิน (Admin):</strong>
                    <p class="text-amber-800">
                        เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถใช้งาน <strong>ระบบแจกงานช่าง</strong> ได้ โดยชื่อผู้ใช้งาน (Username) <u>ต้องประกอบด้วยคำว่า <strong>Admin</strong></u> (เช่น Admin, AdminIT, Admin01) และรหัสผ่าน <u>ต้องเป็น <strong>1234</strong></u>
                    </p>
                </div>
            </div>

            <form method="POST" action="register.php" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">ชื่อผู้ใช้งาน (Username) <span class="text-red-500">*</span></label>
                        <input type="text" name="username" id="reg-username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="เช่น Admin, user123..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">ประเภทบัญชี (Account Role)</label>
                        <select name="role" id="reg-role" onchange="handleRoleChange()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm bg-white font-medium">
                            <option value="User" selected>👤 ผู้ใช้งานทั่วไป (User) - เพิ่มงานและติดตามงานได้เท่านั้น</option>
                            <option value="Technician">👨‍🔧 ช่างซ่อม (Technician) - กดรับงานเอง หรือรับงานจากแอดมินได้แค่นั้น</option>
                            <option value="Admin">👑 แอดมินแจกงาน (Admin) - รับงานจากผู้ใช้, ส่งงาน/เลือกช่าง และติดตามงานได้เท่านั้น</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">รหัสผ่าน (Password) <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="reg-password" required placeholder="อย่างน้อย 4 ตัวอักษร (Admin ต้องเป็น 1234)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">ยืนยันรหัสผ่าน <span class="text-red-500">*</span></label>
                        <input type="password" name="confirm_password" id="reg-confirm-password" required placeholder="ป้อนรหัสผ่านอีกครั้ง" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">ชื่อ-นามสกุล ผู้แจ้งซ่อม <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="เช่น นายสมชาย ใจดี" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">ตำแหน่ง (Position)</label>
                        <select name="position" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm bg-white">
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
                        <label class="block text-xs font-medium text-slate-700 mb-1">แผนก / ฝ่าย <span class="text-red-500">*</span></label>
                        <select name="department" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm bg-white">
                            <option value="">-- เลือกแผนก --</option>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="เช่น 0812345678" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">อีเมล (Email)</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="เช่น user@company.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                    </div>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-lg shadow-indigo-200 transition flex items-center justify-center gap-2 text-sm mt-4">
                    <i class="fa-solid fa-user-plus"></i> ยืนยันสมัครสมาชิก
                </button>
            </form>

            <div class="mt-6 text-center text-xs text-slate-500">
                มีบัญชีสมาชิกอยู่แล้ว? <a href="login.php" class="text-indigo-600 font-bold hover:underline">เข้าสู่ระบบที่นี่</a>
            </div>
        </div>
    </div>

    <script>
        function handleRoleChange() {
            const role = document.getElementById('reg-role').value;
            const usernameInput = document.getElementById('reg-username');
            const passInput = document.getElementById('reg-password');
            const confirmPassInput = document.getElementById('reg-confirm-password');

            if (role === 'Admin') {
                if (!usernameInput.value || !usernameInput.value.toLowerCase().includes('admin')) {
                    usernameInput.value = usernameInput.value ? 'Admin_' + usernameInput.value : 'Admin';
                }
                if (!passInput.value) {
                    passInput.value = '1234';
                    confirmPassInput.value = '1234';
                }
            }
        }
    </script>
</body>
</html>
