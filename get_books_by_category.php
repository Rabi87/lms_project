<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/config.php';


// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

// التحقق من وجود معرف التصنيف
if (!isset($_POST['category_id'])) {
    echo json_encode([]);
    exit;
}

$categoryId = (int)$_POST['category_id'];

// استعلام لاسترجاع كتب التصنيف المحدد
$stmt = $conn->prepare("
    SELECT 
        id, 
        title, 
        author, 
        cover_image, 
        description, 
        evaluation,
        has_discount,
        discount_percentage,
        price,
        material_type,
        (price - (price * (discount_percentage / 100))) AS discounted_price
    FROM books
    WHERE category_id = ?
    ORDER BY evaluation DESC
    LIMIT 12
");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// إرجاع النتائج كـ JSON
header('Content-Type: application/json');
echo json_encode($books);