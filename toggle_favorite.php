<?php
session_start();
require __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('طلب غير مصرح به');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('يجب تسجيل الدخول');
    }

    $user_id = $_SESSION['user_id'];
    $book_id = (int)$_POST['book_id'];

    // حذف من المفضلة فقط
    $stmt = $conn->prepare("DELETE FROM favorite_books WHERE user_id = ? AND book_id = ?");
    $stmt->bind_param("ii", $user_id, $book_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('فشلت العملية');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}