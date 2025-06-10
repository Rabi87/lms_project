<?php
// تمكين عرض الأخطاء للتصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تسجيل البيانات الواردة للتصحيح
error_log('Received POST data: ' . print_r($_POST, true));

require __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['csrf_token'])) {
        throw new Exception('CSRF token missing');
    }

    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('طلب غير مصرح به');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('يجب تسجيل الدخول');
    }

    $user_id = $_SESSION['user_id'];
    $book_id = (int)$_POST['book_id'];

    // التحقق من وجود الكتاب في المفضلة
    $checkStmt = $conn->prepare("SELECT book_id FROM favorite_books WHERE user_id = ? AND book_id = ?");
    if (!$checkStmt) {
        throw new Exception('تحضير الاستعلام فشل: ' . $conn->error);
    }
    
    $checkStmt->bind_param("ii", $user_id, $book_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // حذف من المفضلة
        $stmt = $conn->prepare("DELETE FROM favorite_books WHERE user_id = ? AND book_id = ?");
        $action = 'removed';
    } else {
        // إضافة إلى المفضلة
        $stmt = $conn->prepare("INSERT INTO favorite_books (user_id, book_id) VALUES (?, ?)");
        $action = 'added';
    }

    if (!$stmt) {
        throw new Exception('تحضير الاستعلام فشل: ' . $conn->error);
    }

    if ($action === 'added') {
        $stmt->bind_param("ii", $user_id, $book_id);
    } else {
        $stmt->bind_param("ii", $user_id, $book_id);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'is_favorite' => ($action === 'added')
        ]);
    } else {
        throw new Exception('فشلت العملية: ' . $stmt->error);
    }

} catch (Exception $e) {
    error_log('Error in toggle_favorite: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}