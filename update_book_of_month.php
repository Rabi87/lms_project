<?php
require __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$bookId = (int)$data['book_id'];
$status = (int)$data['status'];

try {
    // إلغاء تحديد جميع الكتب الأخرى ككتاب الشهر
    if ($status === 1) {
        $conn->query("UPDATE books SET book_of_the_month = 0");
    }

    $stmt = $conn->prepare("UPDATE books SET book_of_the_month = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $bookId);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}