<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();}
require __DIR__ . '/db_logger.php'; // تأكد من وجود هذا الملف

// ━━━━━━━━━━ التحقق من تذكرني تلقائيًا ━━━━━━━━━━
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];

    // البحث عن المستخدم باستخدام الtoken
    $stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        // تسجيل الدخول التلقائي
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_type'] = $user['user_type'];

        // توليد token جديد لتجنب الهجمات
        $newToken = bin2hex(random_bytes(64));
        $expiry = time() + 30 * 24 * 3600;

        $update_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $update_stmt->bind_param("si", $newToken, $user['id']);
        $update_stmt->execute();
        $update_stmt->close();

        setcookie('remember_me', $newToken, $expiry, '/', '', true, true);
    } else {
        // إذا كان الtoken غير صالح، احذف الكوكي
        setcookie('remember_me', '', time() - 3600, '/');
    }
}

// 1. التحقق من تسجيل الدخول
if (basename($_SERVER['PHP_SELF']) !== 'login.php' && !isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php?error=" . urlencode("يجب تسجيل الدخول أولاً"));
    exit();
}

// 2. التحقق من أن المستخدم ليس مسؤولًا
if ($_SESSION['user_type'] === 'admin') {
    header("Location: admin/dashboard.php?error=غير مصرح للمسؤولين بالوصول هنا");
    exit();
}

// (اختياري) تحديث وقت النشاط لمنع انتهاء الجلسة
$_SESSION['last_activity'] = time();
?>