<?php
header('Content-Type: application/json');
include __DIR__ . '/../includes/config.php';

$bookId = (int)$_GET['book_id'] ?? 0;

try {
    $stmt = $conn->prepare("
        SELECT 
            u.name AS user_name,
            DATE_FORMAT(p.payment_date, '%Y-%m-%d %H:%i') AS payment_date,
            p.amount,
            'مكتمل' AS status 
        FROM payments p
        JOIN borrow_requests br ON p.request_id = br.id
        JOIN users u ON br.user_id = u.id
        WHERE br.book_id = ?
          AND br.type = 'purchase'
          AND p.status = 'completed'
        ORDER BY p.payment_date DESC
    ");
    
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}