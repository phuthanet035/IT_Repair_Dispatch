<?php
/**
 * Logout Page (logout.php)
 * ออกจากระบบ และส่งกลับไปหน้า login.php
 */

require_once __DIR__ . '/classes/UserManager.php';

$userManager = new UserManager();
$userManager->logout();

header('Location: login.php');
exit;
