<?php
/**
 * Auto Import Database Schema into MySQL
 * เรียกใช้งานผ่าน เบราว์เซอร์: http://localhost/Itแจ้งว่อม/schema_import.php
 * หรือผ่าน CLI: C:\xampp\php\php.exe schema_import.php
 */

header('Content-Type: text/html; charset=utf-8');

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'it_repair_db');
define('DB_USER', 'root');
define('DB_PASS', '');

echo "<div style='font-family: sans-serif; padding: 20px; max-width: 600px; margin: 40px auto; border: 1px solid #ccc; rounded: 10px;'>";
echo "<h2>🗄️ MySQL Database Setup & Import Tool</h2>";

try {
    // 1. Connect to MySQL Server
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color: green;'>✅ 1. เชื่อมต่อ MySQL Server สำเร็จ (127.0.0.1:3306)</p>";

    // 2. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `" . DB_NAME . "`;");
    echo "<p style='color: green;'>✅ 2. สร้าง/เลือกฐานข้อมูล `it_repair_db` สำเร็จ</p>";

    // 3. Read and execute schema.sql
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("ไม่พบไฟล์ schema.sql");
    }

    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);

    echo "<p style='color: green;'>✅ 3. นำเข้าตาราง `assets`, `repair_tickets` และข้อมูลตัวอย่างเรียบร้อยแล้ว!</p>";
    echo "<hr>";
    echo "<p style='font-weight: bold;'>🎉 ติดตั้งฐานข้อมูล MySQL พร้อมใช้งานสำเร็จ!</p>";
    echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #4F46E5; color: white; border-radius: 5px; text-decoration: none;'>เปิดหน้าระบบแจ้งซ่อม (index.php)</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาดใน MySQL: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>ข้อแนะนำ:</strong> กรุณาเปิดโปรแกรม XAMPP Control Panel แล้วกด <strong>Start</strong> ที่ <strong>MySQL</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";
