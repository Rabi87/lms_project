<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__ . '/../includes/config.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("الوصول مرفوض!");
}

// إنشاء مجلد السلايدر إذا لم يكن موجوداً
$slides_dir = __DIR__ . '/../assets/slides/';
if (!is_dir($slides_dir)) {
    if (!mkdir($slides_dir, 0755, true)) {
        $_SESSION['error'] = "فشل في إنشاء مجلد السلايدر. يرجى إنشائه يدوياً: " . $slides_dir;
    } else {
        $_SESSION['success'] = "تم إنشاء مجلد السلايدر بنجاح.";
    }
}

// معالجة رفع الصورة
if (isset($_POST['upload_image'])) {
    // معالجة اسم الملف
    $original_name = basename($_FILES["slider_image"]["name"]);
    $sanitized_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $original_name);
    $filename = uniqid() . '_' . $sanitized_name;
    $target_file = $slides_dir . $filename;
    
    // التحقق من نوع الملف
    $allowed_types = ['jpg', 'jpeg', 'png'];
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    if (!in_array($imageFileType, $allowed_types)) {
        $_SESSION['error'] = "نوع الملف غير مدعوم (JPG/JPEG/PNG فقط)";
    } else {
        // نقل الملف
        if (move_uploaded_file($_FILES["slider_image"]["tmp_name"], $target_file)) {
            $image_path = "assets/slides/" . $filename;
            $caption = $_POST['caption'] ?? '';
            $is_active = 1 ;
            
            // إدراج الصورة في قاعدة البيانات
            $stmt = $conn->prepare("INSERT INTO slider_images (image_path, caption, is_active) VALUES (?, ?, ?)");
            
            if (!$stmt) {
                $_SESSION['error'] = "خطأ في إعداد الاستعلام: " . $conn->error;
            } else {
                $stmt->bind_param("ssi", $image_path, $caption, $is_active);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "تم رفع الصورة وإضافتها إلى السلايدر بنجاح!";
                    echo '<script>window.location.href = "dashboard.php?section=slider";</script>';
                    exit();
                } else {
                    $_SESSION['error'] = "خطأ في تنفيذ الاستعلام: " . $stmt->error;
                    // حذف الملف إذا فشلت عملية الإدراج
                    unlink($target_file);
                }
                $stmt->close();
            }
        } else {
            $uploadError = $_FILES["slider_image"]['error'];
            $errorMessages = [
                1 => 'حجم الملف أكبر من المسموح به',
                2 => 'حجم الملف أكبر من المسموح به في النموذج',
                3 => 'تم رفع جزء من الملف فقط',
                4 => 'لم يتم رفع أي ملف',
                6 => 'مجلد مؤقت مفقود',
                7 => 'فشل الكتابة على القرص',
                8 => 'تم إيقاف الرفع بواسطة إضافة PHP'
            ];
            
            $_SESSION['error'] = "خطأ في رفع الملف: " . 
                                ($errorMessages[$uploadError] ?? "خطأ غير معروف (كود: $uploadError)");
        }
    }
}

// معالجة حذف الصورة
if (isset($_POST['delete_image'])) {
    $image_id = (int)$_POST['image_id'];
    
    // استرجاع مسار الصورة
    $stmt = $conn->prepare("SELECT image_path FROM slider_images WHERE id = ?");
    
    if (!$stmt) {
        $_SESSION['error'] = "خطأ في إعداد استعلام الحذف: " . $conn->error;
    } else {
        $stmt->bind_param("i", $image_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $image = $result->fetch_assoc();
        $stmt->close();
        
        if ($image) {
            $file_path = __DIR__ . '/../' . $image['image_path'];
            
            // حذف الملف الفعلي
            if (file_exists($file_path)) {
                if (!unlink($file_path)) {
                    $_SESSION['error'] = "فشل في حذف الملف: " . $file_path;
                }
            }
            
            // حذف من قاعدة البيانات
            $stmt = $conn->prepare("DELETE FROM slider_images WHERE id = ?");
            
            if (!$stmt) {
                $_SESSION['error'] = "خطأ في إعداد استعلام الحذف: " . $conn->error;
            } else {
                $stmt->bind_param("i", $image_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "تم حذف الصورة بنجاح";
                    echo '<script>window.location.href = "dashboard.php?section=slider";</script>';
                    exit();
                } else {
                    $_SESSION['error'] = "خطأ في حذف الصورة من قاعدة البيانات: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = "لم يتم العثور على الصورة المطلوبة";
        }
    }
}

// جلب الصور من قاعدة البيانات
$slider_images = [];
$result = $conn->query("SELECT * FROM slider_images ORDER BY created_at DESC");
if ($result) {
    $slider_images = $result->fetch_all(MYSQLI_ASSOC);
}
?>
