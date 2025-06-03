<?php
header('Content-Type: application/json');
include __DIR__ . '/../includes/config.php';

$bookId = filter_input(INPUT_GET, 'book_id', FILTER_VALIDATE_INT);

if (!$bookId) {
    http_response_code(400);
    echo json_encode(['error' => 'معرف كتاب غير صالح']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            u.name AS user_name,
            DATE_FORMAT(br.request_date, '%Y-%m-%d %H:%i') AS request_date,
            CASE br.status
                WHEN 'pending' THEN 'قيد المراجعة'
                WHEN 'approved' THEN 'مقبول'
                ELSE 'مرفوض'
            END AS status,
            CASE br.type
                WHEN 'borrow' THEN 'استعارة'
                WHEN 'purchase' THEN 'شراء'
                ELSE 'تجديد'
            END AS type
        FROM borrow_requests br
        JOIN users u ON br.user_id = u.id
        WHERE br.book_id = ?
        ORDER BY br.request_date DESC
    ");
    
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'خطأ في الخادم: ' . $e->getMessage()]);
}