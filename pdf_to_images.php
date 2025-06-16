
<?php
require __DIR__ . '/includes/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

// التحقق من الصلاحيات
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

$request_id = (int)$_GET['request_id'];
$user_id = $_SESSION['user_id'];

// التحقق من صلاحية الطلب
$stmt = $conn->prepare("
    SELECT b.file_path, br.due_date 
    FROM borrow_requests br
    JOIN books b ON br.book_id = b.id
    WHERE br.id = ? AND br.user_id = ?
");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

$pdf_path = $result['file_path'];
$due_date = $result['due_date'];

// التحقق من وجود مكتبة ImageMagick
if (!extension_loaded('imagick')) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Imagick extension not installed");
}

try {
    $imagick = new Imagick();
    $imagick->setResolution(150, 150);
    $imagick->readImage($pdf_path);
    $imagick->setImageFormat('jpg');

    // إعداد المخرجات
    header('Content-Type: application/json');
    $pages = [];

    foreach ($imagick as $page) {
        // إضافة علامة مائية
        $draw = new ImagickDraw();
        $draw->setFillColor('rgba(200, 200, 200, 0.4)');
        $draw->setFontSize(40);
        
        $draw->setGravity(Imagick::GRAVITY_CENTER);
        
        // نص العلامة المائية
        $watermark = "{$_SESSION['username']} | {$due_date} | " . BASE_URL;
        
        $page->annotateImage($draw, 0, 0, 45, $watermark);
        
        // حفظ الصفحة في ذاكرة مؤقتة
        ob_start();
        $page->writeImage('jpg:-');
        $image_data = ob_get_clean();
        
        $pages[] = 'data:image/jpeg;base64,' . base64_encode($image_data);
    }

    echo json_encode([
        'pages' => $pages,
        'title' => basename($pdf_path)
    ]);

} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    exit($e->getMessage());
}
