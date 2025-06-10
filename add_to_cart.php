<?php
session_start();
require __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$book_id = (int)$_POST['id'];
$_SESSION['cart'][$book_id] = [
    'title' => $_POST['title'],
    'price' => (float)$_POST['price'],
    'cover_image' => $_POST['cover_image']
];

echo json_encode([
    'success' => true,
    'cart_count' => count($_SESSION['cart'])
]);
// عند إضافة عنصر للسلة
setcookie('cart', json_encode($_SESSION['cart']), time() + (86400 * 30), "/");