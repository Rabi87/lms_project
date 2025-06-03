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

// رفع صورة
if (isset($_POST['upload_image'])) {
    $target_dir = BASE_PATH . "assets/slides/";
    
    // إنشاء المجلد إذا لم يكن موجودًا
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) { // 0777 لأقصى صلاحيات (اضبطها حسب حاجة الخادم)
            $_SESSION['error'] = "فشل في إنشاء المجلد: " . $target_dir;
            header("Location: dashboard.php?section=slider");
            exit();
        }
    }
    
    // معالجة اسم الملف
    $original_name = basename($_FILES["slider_image"]["name"]);
    $sanitized_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $original_name);
    $filename = uniqid() . '_' . $sanitized_name;
    $target_file = $target_dir . $filename;
    
    // التحقق من نوع الملف
    $allowed_types = ['jpg', 'jpeg', 'png'];
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    if (!in_array($imageFileType, $allowed_types)) {
        $_SESSION['error'] = "نوع الملف غير مدعوم (JPG/JPEG/PNG فقط)";
        header("Location: dashboard.php?section=slider");
        exit();
    }
    
    // نقل الملف
    if (move_uploaded_file($_FILES["slider_image"]["tmp_name"], $target_file)) {
        $image_path = "assets/slides/" . $filename;
        $caption = $_POST['caption'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO slider_images (image_path, caption, is_active) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $image_path, $caption);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "تم رفع الصورة بنجاح!";
        } else {
            $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "خطأ في رفع الملف. تأكد من صلاحيات المجلد.";
    }
}

// حذف صورة
if (isset($_POST['delete_image'])) {
    $image_id = (int)$_POST['image_id'];
    
    // استرجاع مسار الصورة
    $stmt = $conn->prepare("SELECT image_path FROM slider_images WHERE id = ?");
    $stmt->bind_param("i", $image_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $image = $result->fetch_assoc();
    
    if ($image) {
        $file_path = BASE_PATH . $image['image_path'];
        
        // حذف الملف الفعلي
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // حذف من قاعدة البيانات
        $stmt = $conn->prepare("DELETE FROM slider_images WHERE id = ?");
        $stmt->bind_param("i", $image_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "تم حذف الصورة";
        } else {
            $_SESSION['error'] = "خطأ في الحذف: " . $conn->error;
        }
    }
}

header("Location: dashboard.php?section=slider");
exit();