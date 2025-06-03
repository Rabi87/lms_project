<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// بدء الجلسة (إذا لم تكن بدأت)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location:". BASE_URL."login.php?error= ". urlencode("يجب تسجيل الدخول أولاً"));
    exit();
}

// التحقق من صلاحية المدير
if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php?error=ليس لديك صلاحية الوصول");
    exit();
}

// (اختياري) تحديث وقت الجلسة لمنع التهريب
$_SESSION['last_activity'] = time();
?>