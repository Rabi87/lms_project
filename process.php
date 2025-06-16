<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); // سجل الأخطاء في ملف

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db_logger.php';
//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
   //    die("Invalid CSRF Token");
// }
//}
// إيقاف عرض الأخطاء للمستخدمين

// التحقق من صلاحيات الأدمن
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// ======== معالجة تسجيل المستخدم ========
if (isset($_POST['register'])) {
  
    try {
        // التحقق من البيانات الأساسية
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // ----------- التحقق من البريد الإلكتروني واسم المستخدم -----------
        $check_stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {

            $row = $check_result->fetch_assoc();
            
            if ($row['email'] === $email) {
                throw new Exception("البريد الإلكتروني مسجل مسبقًا");
            } 
        }
        $check_stmt->close();

        // ----------- إضافة المستخدم -----------
        $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        
        $insert_stmt->bind_param("sss", $name, $email, $password);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("فشل في تسجيل المستخدم");
        }
        $user_id = $insert_stmt->insert_id;
        $insert_stmt->close();

        // ----------- إضافة التصنيفات المفضلة -----------
        if (isset($_POST['categories'])) {
            $category_stmt = $conn->prepare("INSERT INTO user_categories (user_id, category_id) VALUES (?, ?)");
            
            foreach ($_POST['categories'] as $category_id) {
                $category_id = (int)$category_id;
                $category_stmt->bind_param("ii", $user_id, $category_id);
                
                if (!$category_stmt->execute()) {
                    throw new Exception("فشل في إضافة التصنيفات");
                }
            }
            $category_stmt->close();
        }

        $_SESSION['success'] = "تم التسجيل بنجاح!";
        header("Location: login.php");
        exit();
    } catch (Exception $e) {
     
        
        
        $_SESSION['error'] = $e->getMessage();
        header("Location: register.php");
        exit();
    }
}


// ======== معالجة المفضلة ========
if (isset($_POST['action']) && $_POST['action'] === 'toggle_favorite') {
    try {
        // التحقق من CSRF Token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('طلب غير مصرح به');
        }

        if (!isset($_SESSION['user_id'])) {
            throw new Exception('يجب تسجيل الدخول أولاً');
        }

        $book_id = (int)$_POST['book_id'];
        $user_id = $_SESSION['user_id'];

        // التحقق من وجود السجل
        $check_stmt = $conn->prepare("SELECT * FROM favorite_books WHERE user_id = ? AND book_id = ?");
        $check_stmt->bind_param("ii", $user_id, $book_id);
        $check_stmt->execute();
        $exists = $check_stmt->get_result()->num_rows > 0;

        if ($exists) {
            // الحذف
            $stmt = $conn->prepare("DELETE FROM favorite_books WHERE user_id = ? AND book_id = ?");
        } else {
            // الإضافة
            $stmt = $conn->prepare("INSERT INTO favorite_books (user_id, book_id) VALUES (?, ?)");
        }
        
        $stmt->bind_param("ii", $user_id, $book_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('فشل في العملية');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ======== معالجة تسجيل الدخول ========
if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            
            throw new Exception("المستخدم غير موجود");
        }
                
        $user = $result->fetch_assoc();
        if ($user['status'] === 0) {
           
            throw new Exception("المستخدم غير مفعل");
        }
        
        if (!password_verify($password, $user['password'])) {
            
            throw new Exception("كلمة المرور خاطئة");
        }
        
        // تعيين بيانات الجلسة
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['user_email'] = $user['email'];


       // ━━━━━━━━━━ تفعيل تذكرني ━━━━━━━━━━
        if (isset($_POST['remember_me'])) {
            // توليد token فريد
            $token = bin2hex(random_bytes(64));
            $expiry = time() + 30 * 24 * 3600; // 30 يومًا

            // تخزين الtoken في قاعدة البيانات
            $update_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $update_stmt->bind_param("si", $token, $user['id']);
            $update_stmt->execute();
            $update_stmt->close();

            // تعيين الكوكي (Secure و HttpOnly)
            setcookie(
                'remember_me',
                $token,
                $expiry,
                '/',
                '',
                true, // Secure (يجب أن يكون HTTPS مفعل)
                true  // HttpOnly
            );
        }
        
        DatabaseLogger::log(
            'login_success',
            $user['name'],
            'تم تسجيل الدخول بنجاح'
        );
        
        header("Location: " . BASE_URL . ($user['user_type'] == 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
        
    } catch (Exception $e) {
        DatabaseLogger::log(
            'login_failed',
            $name,
            $e->getMessage()
        );
        $_SESSION['error'] =  $e->getMessage();
        
        header("Location:" . BASE_URL . "login.php");
        exit();
    }
   
}

// ======== معالجة نسيان كلمة المرور ========
if (isset($_POST['forget_password'])) {
    $email = $_POST['email'];

    try {
        // التحقق من وجود البريد الإلكتروني
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("البريد الإلكتروني غير مسجل");
        }

        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(50)); // توليد رمز فريد
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // صلاحية ساعة

        // حفظ الرمز في قاعدة البيانات
        $conn->query("
            UPDATE users 
            SET 
                password_reset_token = '$token',
                password_reset_expires = '$expires' 
            WHERE id = {$user['id']}
        ");
       

        // إرسال البريد الإلكتروني (يجب استبدال هذا الجزء بآلية إرسال حقيقية)
        $reset_link = BASE_URL . "reset_password.php?token=$token";
        $_SESSION['reset_link'] = $reset_link; // حفظ الرابط في الجلسة
       

        // mail($email, "استعادة كلمة المرور", "الرجاء الضغط على الرابط: $reset_link");

        $_SESSION['success'] = "تم إرسال رابط الاستعادة إلى بريدك الإلكتروني";
        header("Location: " . BASE_URL . "forget_password_confirmation.php");
        

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: forget_password.php");
    }
    exit();
}

// ======== معالجة تعيين كلمة المرور ========
if (isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        if ($new_password !== $confirm_password) {
            throw new Exception("كلمتا المرور غير متطابقتين");
        }

        // التحقق من الرمز ومدى صلاحيته
        $stmt = $conn->prepare("
            SELECT id 
            FROM users 
            WHERE 
                password_reset_token = ? 
                AND password_reset_expires > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("الرابط غير صالح أو منتهي الصلاحية");
        }

        $user = $result->fetch_assoc();
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // تحديث كلمة المرور وإزالة الرمز
        $conn->query("
            UPDATE users 
            SET 
                password = '$hashed_password',
                password_reset_token = NULL,
                password_reset_expires = NULL 
            WHERE id = {$user['id']}
        ");

        $_SESSION['success'] = "تم تعيين كلمة المرور بنجاح!";
        header("Location: login.php");

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: reset_password.php?token=$token");
    }
    exit();
}

// ======== إضافة كتب (للمسؤولين فقط) ========
if (isset($_POST['add_book']) && isAdmin()) {
    try {
        // التحقق من البيانات
       
        $title = htmlspecialchars($_POST['title']);
        $author = htmlspecialchars($_POST['author']);
        $type = in_array($_POST['type'], ['physical', 'e-book']) ? $_POST['type'] : 'physical';
        $quantity = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];
        $category_id = (int)$_POST['category_id'];
        $evaluation = (float)$_POST['evaluation'];
        $description = htmlspecialchars($_POST['description']);
        $material_type = htmlspecialchars($_POST['material_type']);
        $page_count = isset($_POST['page_count']) ? (int)$_POST['page_count'] : null;
        $publication_date = isset($_POST['publication_date']) ? $_POST['publication_date'] : null;
        $isbn = isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : null;
        // معالجة البيانات
            
        $has_discount = isset($_POST['has_discount']) ? 1 : 0; // تحويل إلى 1 أو 0
        $discount_percentage = $has_discount ? (int)$_POST['discount_percentage'] : 0; // تحويل إلى عدد صحيح
        
        // معالجة تحميل الصورة
        if (!isset($_FILES['cover_image']['error']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('يجب اختيار صورة غلاف');
        }
        
        $upload_dir = 'assets/images/books/';
        $extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . date('YmdHis') . '.' . $extension;
        $target_path = $upload_dir . $new_filename;
        
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
            throw new Exception('فشل في حفظ الصورة');
        }

        // ━━━━━━━━━━ معالجة تحميل الملف (file_path) ━━━━━━━━━━
        if (!isset($_FILES['file_path']['error']) || $_FILES['file_path']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('يجب رفع ملف الكتاب');
        }
        
        $file_upload_dir = BASE_PATH . 'assets/files/'; // مجلد تخزين الملفات
        $file_extension = pathinfo($_FILES['file_path']['name'], PATHINFO_EXTENSION);
        $file_new_name = uniqid() . '_' . date('YmdHis') . '.' . $file_extension;
        $file_target_path = $file_upload_dir . $file_new_name;
        
        if (!is_dir($file_upload_dir)) mkdir($file_upload_dir, 0755, true);
        if (!move_uploaded_file($_FILES['file_path']['tmp_name'], $file_target_path)) {
            throw new Exception('فشل في حفظ الملف');
        }
        
        // إدخال البيانات
                $stmt = $conn->prepare("
            INSERT INTO books 
            (title, author, material_type, page_count, publication_date, isbn, quantity, price, cover_image, category_id, file_path, evaluation, description, has_discount, discount_percentage) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)
        ");
                
        if (!$stmt) {
            throw new Exception("خطأ في إعداد الاستعلام: " . $conn->error);
        }
        
        $stmt->bind_param(
            "sssisssissssdii", // 15 حرفًا (أنواع البيانات)
            $title, 
            $author, 
            $material_type,
            $page_count,
            $publication_date,
            $isbn,
            $quantity,
            $price,
            $target_path,
            $category_id,
            $file_target_path,
            $evaluation,
            $description, // يجب أن تكون قبل الحقلين الرقميين
            $has_discount,
            $discount_percentage
        );
        if ($stmt->execute()) {
            $_SESSION['success'] = "تمت إضافة الكتاب بنجاح!";
             DatabaseLogger::log(
            'book_added_success',
            'Admin',
            'تمت إضافة كتاب بنجاح'
        );
            header("Location: " . BASE_URL . "admin/dashboard.php?section=books");
        } else {
            
            throw new Exception("فشل في إضافة الكتاب: " . $stmt->error); 
        }    
    } catch (Exception $e) {
        DatabaseLogger::log(
            'login_failed',
            'Admin',
            $e->getMessage()
        );
        $_SESSION['error'] = $e->getMessage();
        
        header("Location:" .BASE_URL."admin/dashboard.php?section=books");
         exit();
    }
   
}

// ======== معالجة تحديث الكتب (مع دعم الملفات) ========
if (isset($_POST['update_book']) && isAdmin()) {
    try {
        // التحقق من CSRF Token
        //if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
         //   throw new Exception('طلب غير مصرح به');
       // }
       

        // جلب البيانات الأساسية
        $book_id = (int)$_POST['book_id'];
        $title = htmlspecialchars($_POST['title']);
        $author = htmlspecialchars($_POST['author']);
        $type = in_array($_POST['type'], ['physical', 'e-book']) ? $_POST['type'] : 'physical';
        $quantity = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];
        $category_id = (int)$_POST['category_id'];
        $evaluation = (float)$_POST['evaluation'];
        $description = htmlspecialchars($_POST['description']);
        $material_type = htmlspecialchars($_POST['material_type']);
        if (!in_array($material_type, ['كتاب', 'مجلة', 'جريدة'])) {
            throw new Exception("نوع المادة غير صحيح!");
        }
        $page_count = isset($_POST['page_count']) ? (int)$_POST['page_count'] : null;
        $publication_date = isset($_POST['publication_date']) ? $_POST['publication_date'] : null;
        $isbn = isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : null;
        $has_discount = isset($_POST['has_discount']) ? 1 : 0;
        $discount_percentage = $has_discount ? (int)$_POST['discount_percentage'] : 0;

        // جلب البيانات الحالية
        $stmt = $conn->prepare("SELECT cover_image, file_path FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $current_data = $stmt->get_result()->fetch_assoc();

        // ━━━━━━━ معالجة صورة الغلاف ━━━━━━━
        $cover_image = $current_data['cover_image'];
        if (!empty($_FILES['cover_image']['name'])) {
            $upload_dir = 'assets/images/books/';
            $extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '_' . date('YmdHis') . '.' . $extension;
            
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_dir . $new_filename)) {
                throw new Exception('فشل في حفظ الصورة');
            }
            $cover_image = $upload_dir . $new_filename;
        }

        // ━━━━━━━ معالجة ملف الكتاب ━━━━━━━
        $file_path = $current_data['file_path'];
        if (!empty($_FILES['file_path']['name'])) {
            $file_upload_dir = 'assets/files/';
            $file_extension = pathinfo($_FILES['file_path']['name'], PATHINFO_EXTENSION);
            $file_new_name = uniqid() . '_' . date('YmdHis') . '.' . $file_extension;
            
            if (!is_dir($file_upload_dir)) mkdir($file_upload_dir, 0755, true);
            if (!move_uploaded_file($_FILES['file_path']['tmp_name'], $file_upload_dir . $file_new_name)) {
                throw new Exception('فشل في حفظ الملف');
            }
            $file_path = $file_upload_dir . $file_new_name;
        }

        // تحديث البيانات في قاعدة البيانات
        $stmt = $conn->prepare("
            UPDATE books SET 
                title = ?, 
                author = ?, 
                material_type = ?, 
                quantity = ?, 
                price = ?, 
                category_id = ?,
                cover_image = ?,
                file_path = ?,
                description = ?,
                evaluation = ?,
                page_count = ?,
                publication_date = ?,
                isbn = ?,
                has_discount = ?,
                discount_percentage = ?
            WHERE id = ?
        ");

        if (!$stmt) {
            throw new Exception("❌ خطأ في إعداد الاستعلام: " . $conn->error);
        }

         $stmt->bind_param(
            "sssidisssssssiii", // أضف 'ii' للخصم
            $title, 
            $author, 
            $material_type,
            $quantity, 
            $price, 
            $category_id,
            $cover_image,
            $file_path,
            $description,
            $evaluation,
            $page_count,
            $publication_date,
            $isbn,
            $has_discount,
            $discount_percentage,
            $book_id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ تم تحديث الكتاب بنجاح!";
            header("Location:" .BASE_URL."admin/dashboard.php?section=books");
        } else {
            throw new Exception("❌ فشل في التحديث: " . $stmt->error);
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location:".BASE_URL."admin/edit_book.php?id=" . $book_id);
    }
    exit();
}
// ======== معالجة طلب الاستعارة/الشراء ========
if (isset($_POST['action'])) {
    try {
        // التحقق من CSRF Token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('طلب غير مصرح به');
        }
        $book_id = (int)$_POST['book_id'];

        // ━━━━━━━━━━ جلب سعر الكتاب من جدول books ━━━━━━━━━━
        $stmt_book = $conn->prepare("SELECT price FROM books WHERE id = ?");
        $stmt_book->bind_param("i", $book_id);
        $stmt_book->execute();
        $result_book = $stmt_book->get_result();
        
        if ($result_book->num_rows === 0) {
            throw new Exception("الكتاب غير موجود!");
        }
        
        $book_data = $result_book->fetch_assoc();
        $book_price = $book_data['price'];
        $stmt_book->close();

        // ━━━━━━━━━━ تحديد المبلغ المطلوب بناءً على نوع العملية ━━━━━━━━━━
     
        // استخدام سعر الكتاب للشراء
        $purchase_price = $book_price;
        
        // جلب سعر الإعارة
        $stmt_rental = $conn->prepare("SELECT value FROM settings WHERE name='rental_price'");
        $stmt_rental->execute();
        $result_rental = $stmt_rental->get_result();
        $row_rental = $result_rental->fetch_assoc();
        $rental_price = $row_rental['value'];
        $stmt_rental->close();

        // جلب غرامة التأخير
        $stmt_late = $conn->prepare("SELECT value FROM settings WHERE name='late_fee'");
        $stmt_late->execute();
        $result_late = $stmt_late->get_result();
        $row_late = $result_late->fetch_assoc();
        $late_fee = $row_late['value'];
        $stmt_late->close();
        
        // تحديد نوع العملية والمبلغ المطلوب
        $action = $_POST['action'];
        $required_amount = ($action === 'borrow') ? $rental_price : $purchase_price;
        $book_id = (int)$_POST['book_id'];

        // ━━━━━━━━━━ التحقق من عدم وجود استعارة نشطة ━━━━━━━━━━
        if($action === 'borrow'){
            $check_borrow = $conn->prepare("
                SELECT id
                FROM borrow_requests 
                WHERE 
                    user_id = ? 
                    AND book_id = ? 
                    AND type='borrow'
                    AND status IN ('pending', 'approved')
                    AND due_date > NOW() 
                    AND reading_completed = 0
                    
            ");
            $check_borrow->bind_param("ii", $_SESSION['user_id'], $book_id);
            $check_borrow->execute();
            $borrow_user=$_SESSION['user_id'];

            if ($check_borrow->get_result()->num_rows > 0) {
                $_SESSION['error'] = "لا يمكنك استعارة هذا الكتاب الآن. لديك استعارة نشطة!";
                header("Location:index.php"); 
                exit();
            }
        }
        
        // التحقق من الرصيد
        $stmt_wallet = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt_wallet->bind_param("i", $_SESSION['user_id']);
        $stmt_wallet->execute();
        $wallet = $stmt_wallet->get_result()->fetch_assoc();
        
        if ($wallet['balance'] < $required_amount) {
            $_SESSION['required_amount'] = $required_amount;
            $_SESSION['book_id'] = $book_id;
            $_SESSION['action'] = $action;
            header("Location: add_funds.php");
            exit();
        }
        
        // خصم المبلغ
        //$stmt_deduct = $conn->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
        //$stmt_deduct->bind_param("di", $required_amount, $_SESSION['user_id']);
       // $stmt_deduct->execute();
        
        // إرسال الطلب إلى المدير
        $stmt_request = $conn->prepare("INSERT INTO borrow_requests (user_id, book_id, type, amount) VALUES (?, ?, ?, ?)");
        $stmt_request->bind_param("iisd", $_SESSION['user_id'], $book_id, $action, $required_amount);
        $stmt_request->execute();
        $request_id = $stmt_request->insert_id; // الحصول على ID الطلب الجديد
        // إرسال إشعار إلى المدير
        $admin = $conn->query("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1")->fetch_assoc();
        if ($admin) {
            $message = "طلب جديد: " . ($action === 'borrow' ? "استعارة" : "شراء") . " كتاب";
            $link = BASE_URL . "/admin/dashboard.php?section=ops";
        
            $stmt_notif = $conn->prepare("
                INSERT INTO notifications 
                (user_id, message, link, request_id, expires_at) 
                VALUES (?, ?, ?, ?, NOW() + INTERVAL 24 HOUR)
            ");
            $stmt_notif->bind_param("issi", $admin['id'], $message, $link, $request_id); // إضافة request_id
            $stmt_notif->execute();
        }
        $_SESSION['success'] = "تم ارسال الطلب بنجاح , يمكنك متابعة الطلب في قسم الطلبات العالقة في لوحة التحكم الخاصة بك";
        header("Location:index.php");

        DatabaseLogger::log(
            'loan_success',
            $borrow_user,
            'تم طلب الاستعارة بنجاح'
        );
        
    } catch (Exception $e) {
        $_SESSION['error'] = "خطأ: " . $e->getMessage();
        header("Location: index.php");
    }
    exit();
}

// في قسم معالجة action=checkout
if (isset($_POST['actionii']) && $_POST['actionii'] === 'checkout') {
    try {
        // التحقق من وجود عناصر في السلة
        if (empty($_SESSION['cart'])) {
            throw new Exception('السلة فارغة');
        }

        // حساب المجموع الكلي مع مراعاة التخفيضات
        $total = 0;
        $discounted_total = 0;
        $cart_items = [];

        foreach ($_SESSION['cart'] as $book_id => $item) {
            // جلب تفاصيل التخفيض من قاعدة البيانات
            $book_query = "SELECT has_discount, discount_percentage FROM books WHERE id = ?";
            $stmt_book = $conn->prepare($book_query);
            $stmt_book->bind_param("i", $book_id);
            $stmt_book->execute();
            $book_result = $stmt_book->get_result();
            
            if ($book_result->num_rows > 0) {
                $book_data = $book_result->fetch_assoc();
                $has_discount = $book_data['has_discount'];
                $discount_percentage = $book_data['discount_percentage'];
                
                // حساب السعر المخفض
                $original_price = $item['price'];
                $discounted_price = $has_discount ? 
                    $original_price - ($original_price * ($discount_percentage / 100)) : 
                    $original_price;
                
                // تخزين البيانات المعدلة
                $cart_items[$book_id] = array_merge($item, [
                    'has_discount' => $has_discount,
                    'discount_percentage' => $discount_percentage,
                    'discounted_price' => $discounted_price,
                    'original_price' => $original_price
                ]);
                
                $total += $original_price;
                $discounted_total += $discounted_price;
            }
        }

        // إضافة الطلب إلى جدول orders
        $stmt = $conn->prepare("
            INSERT INTO orders 
            (user_id, total, discounted_total, status) 
            VALUES (?, ?, ?, 'pending')");
        if (!$stmt) {
            die("خطأ في تحضير الاستعلام: " . $conn->error);
        }
        $stmt->bind_param("idd", $_SESSION['user_id'], $total, $discounted_total);
        $stmt->execute();
        $order_id = $stmt->insert_id;

        // إضافة العناصر إلى جدول order_items
foreach ($cart_items as $book_id => $item) {
    $stmt_item = $conn->prepare("
        INSERT INTO order_items (order_id, book_id, quantity, original_price, discounted_price, price) 
        VALUES (?, ?, 1, ?, ?, ?)
    ");
    $original_price = $item['original_price'];
    $discounted_price = $item['discounted_price'];
    $stmt_item->bind_param("iiddd", $order_id, $book_id, $original_price, $discounted_price, $discounted_price);
    $stmt_item->execute();
}

        // التحقق من الرصيد (باستخدام السعر المخفض)
        $stmt_wallet = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt_wallet->bind_param("i", $_SESSION['user_id']);
        $stmt_wallet->execute();
        $wallet = $stmt_wallet->get_result()->fetch_assoc();
        
        if ($wallet['balance'] < $discounted_total) {
            $_SESSION['required_amount'] = $discounted_total;
            header("Location: add_funds.php");
            exit();
        }
      
        // إضافة كل عنصر إلى جدول borrow_requests
        foreach ($cart_items as $book_id => $item) {
            // إدخال طلب شراء لكل كتاب (باستخدام السعر المخفض)
            $stmt_request = $conn->prepare("
                INSERT INTO borrow_requests 
                (user_id, book_id, type, amount, status) 
                VALUES (?, ?, 'purchase', ?, 'pending')
            ");
            $discounted_price = $item['discounted_price'];
            $stmt_request->bind_param("iid", $_SESSION['user_id'], $book_id, $discounted_price);
            $stmt_request->execute();
        }

        // إرسال إشعار إلى المدير
        $admin = $conn->query("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1")->fetch_assoc();
        if ($admin) {
            $message = "طلب شراء جديد (" . count($cart_items) . " عناصر)";
            $link = BASE_URL . "admin/dashboard.php?section=ops";

            $stmt_notif = $conn->prepare("
                INSERT INTO notifications 
                (user_id, message, link) 
                VALUES (?, ?, ?)
            ");
            $stmt_notif->bind_param("iss", $admin['id'], $message, $link);
            $stmt_notif->execute();
        }

        // تفريغ السلة وإظهار الرسالة
        unset($_SESSION['cart']);
        setcookie('cart', json_encode($_SESSION['cart']), 0, "/");
        $_SESSION['success'] = "تم إرسال طلب الشراء بنجاح! سيتم مراجعته من قبل الإدارة.";
         DatabaseLogger::log(
            'purchased_request_success',
            $_SESSION['user_name'],
            'تم طلب شراء كتاب '
        );
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "خطأ: " . $e->getMessage();
        header("Location: cart.php");
        exit();
    }
}
// ======== معالجة تجديد الاستعارة ========
if (isset($_POST['actions']) && $_POST['actions'] === 'renew') {
    try {
         // التحقق من CSRF Token
         if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('طلب غير مصرح به');
        }
        $actions = $_POST['actions'];
        $request_id = (int)$_POST['request_id'];
        $user_id = $_SESSION['user_id'];

        // ━━━━━━━━━━ التحقق من صلاحية التجديد ━━━━━━━━━━
        $stmt_check = $conn->prepare("
            SELECT due_date, renewed 
            FROM borrow_requests 
            WHERE 
                id = ? 
                AND user_id = ? 
                AND status = 'approved'
                AND reading_completed = 0
        ");
        $stmt_check->bind_param("ii", $request_id, $user_id);
        $stmt_check->execute();
        $request_data = $stmt_check->get_result()->fetch_assoc();

        if (!$request_data) {
            throw new Exception("الطلب غير صالح للتجديد");
        }

        // التحقق من المدة المتبقية (يومين أو أقل)
        $due_date = new DateTime($request_data['due_date']);
        $today = new DateTime();
        $interval = $today->diff($due_date);
        $days_left = $interval->days;
        $is_passed = $due_date < $today;

        if ($days_left > 2 && !$is_passed) {
            throw new Exception("يمكن التجديد قبل يومين من انتهاء المدة فقط");
        }

        // التحقق من عدد التجديدات (مثال: 3 مرات كحد أقصى)
        if ($request_data['renewed'] >= 3) {
            throw new Exception("وصلت إلى الحد الأقصى للتجديد");
        }

        // ━━━━━━━━━━ التحقق من الرصيد ━━━━━━━━━━
        $renew_cost = 5000; // تكلفة التجديد
        $stmt_balance = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt_balance->bind_param("i", $user_id);
        $stmt_balance->execute();
        $balance = $stmt_balance->get_result()->fetch_assoc()['balance'];

        if ($balance < $renew_cost) {
            $_SESSION['error'] = "رصيدك غير كافي لتجديد الاستعارة!";
            header("Location: read_book.php?request_id=" . $request_id);
            exit();
        }

        // ━━━━━━━━━━ إرسال إشعار للمدير ━━━━━━━━━━
        $admin_id = 5; // أو جلب id المدير من قاعدة البيانات
        $message = "طلب تجديد استعارة (الطلب #$request_id)";
        $link = "admin/renew_requests.php?request_id=$request_id";

        $stmt_notify = $conn->prepare("
            INSERT INTO notifications 
            (user_id, message, link, request_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt_notify->bind_param("issi", $admin_id, $message, $link, $request_id);
        $stmt_notify->execute();

        // ━━━━━━━━━━ تحديث حالة التجديد ━━━━━━━━━━
        $new_renewed = $request_data['renewed'] + 1;
                $stmt_renew = $conn->prepare("
            UPDATE borrow_requests 
            SET 
                renewed = ?,
                status = 'pending',
                processed_at = NOW(),
                due_date = NOW(),
                request_date=NOW(),
                loan_duration = 0,
                type=?
            WHERE id = ?
        ");
        $stmt_renew->bind_param("isi", $new_renewed, $actions,$request_id);
        $stmt_renew->execute();

        $_SESSION['success'] = "تم إرسال طلب التجديد لإدارة المكتبة!";
        header("Location: read_book.php?request_id=" . $request_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "خطأ: " . $e->getMessage();
        header("Location: read_book.php?request_id=" . $request_id);
        exit();
    }
}

// ======== معالجة حذف الطلب ========
if (isset($_POST['delete_request']) && isset($_SESSION['user_id'])) {
    // التحقق من CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("طلب غير مصرح به");
    }

    $request_id = (int)$_POST['request_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // التحقق من ملكية الطلب
        $stmt = $conn->prepare("DELETE FROM borrow_requests WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $request_id, $user_id);
        
        if ($stmt->execute()) {
            // ━━━━━━━ حذف الإشعارات المرتبطة بالطلب ━━━━━━━
            $delete_notif = $conn->prepare("DELETE FROM notifications WHERE request_id = ?");
            $delete_notif->bind_param("i", $request_id);
            $delete_notif->execute();
            $delete_notif->close();
            $_SESSION['success'] = "تم حذف الطلب بنجاح!";
        } else {
            throw new Exception("فشل في حذف الطلب");
        }
        
        header("Location:".BASE_URL."user/dashboard.php?section=waiting");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location:".BASE_URL."user/dashboard.php?section=waiting");
        exit();
    }
    
}

// ======== معالجة إرسال الشكوى ========
if (isset($_POST['submit_complaint'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $complaint_type = $_POST['complaint_type'];
    $problem_type = $_POST['problem_type'] ?? null;
    $complaint_details = htmlspecialchars($_POST['complaint_details']);

    try {
        // التحقق من صحة البريد الإلكتروني
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("البريد الإلكتروني غير صالح");
        }

        // جمع البيانات الإضافية
        $additional_data = [];
        $excluded_fields = ['email', 'complaint_type', 'problem_type', 'complaint_details', 'submit_complaint'];
        
        foreach ($_POST as $key => $value) {
            if (!in_array($key, $excluded_fields)) {
                $additional_data[$key] = is_array($value) ? $value : htmlspecialchars($value);
            }
        }

        // تحويل البيانات الإضافية إلى JSON
        $additional_json = json_encode($additional_data, JSON_UNESCAPED_UNICODE);

        // إدخال البيانات في قاعدة البيانات
        $sql = "INSERT INTO complaints 
                (email, complaint_type, problem_type, complaint, additional_data, created_at, status)
                VALUES (?, ?, ?, ?, ?, NOW(), 'pending')";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("فشل في إعداد الاستعلام: " . $conn->error);
        }
        
        $stmt->bind_param("sssss", 
            $email,
            $complaint_type,
            $problem_type,
            $complaint_details,
            $additional_json
        );
        
        if($stmt->execute()) {
            $_SESSION['success'] = 'تم تقديم الشكوى بنجاح';
            header("Location: index.php");
            exit();
        } else {
            throw new Exception("فشل في إرسال الشكوى: " . $stmt->error);
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: complaint.php");
        exit();
    }
}

// ======== معالجة تمييز الشكوى كمحلولة ========
if (isset($_GET['resolve_complaint']) && isAdmin()) {
    $complaint_id = (int)$_GET['resolve_complaint'];
    $conn->query("UPDATE complaints SET status = 'resolved' WHERE id = $complaint_id");
    header("Location:" . BASE_URL . "admin/dashboard.php?section=complaints");
    exit();
}

// ======== معالجة حذف الشكوى ========
if (isset($_GET['delete_complaint']) && isAdmin()) {
    $complaint_id = (int)$_GET['delete_complaint'];
    $conn->query("DELETE FROM complaints WHERE id = $complaint_id");
    $_SESSION['success'] = "تم حذف الشكوى بنجاح";
    header("Location:" . BASE_URL . "admin/dashboard.php?section=complaints");
    exit();
}

// معالجة اضافة عضو من قبل المدير
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... التحقق من CSRF Token ...
    
    // إضافة مستخدم جديد
    if (isset($_POST['add_user'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // التحقق من عدم وجود البريد مسبقاً
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows > 0) {
            $_SESSION['error'] = "البريد الإلكتروني مسجل مسبقاً";
        } else {
            // إضافة المستخدم
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, status) 
                                    VALUES (?, ?, ?, 'user', 1)");
            $stmt->bind_param("sss", $name, $email, $password);
            
            if ($stmt->execute()) {
                // إرسال البريد الإلكتروني
                $to = $email;
                $subject = "حسابك في نظام إدارة المكتبة";
                $message = "مرحباً $name،\n\n";
                $message .= "تم إنشاء حساب لك في نظام إدارة المكتبة.\n";
                $message .= "بريدك الإلكتروني: $email\n";
                $message .= "كلمة المرور: " . $_POST['password'] . "\n\n";
                $message .= "يمكنك تسجيل الدخول من خلال الرابط: " . BASE_URL . "login.php";
                
                $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                
                if (mail($to, $subject, $message, $headers)) {
                    $_SESSION['success'] = "تم إضافة العضو وإرسال بياناته إلى بريده الإلكتروني";
                } else {
                    $_SESSION['warning'] = "تم إضافة العضو ولكن فشل إرسال البريد الإلكتروني";
                }
            } else {
                $_SESSION['error'] = "خطأ في إضافة العضو: " . $conn->error;
            }
        }
        
        header("Location: dashboard.php?section=users");
        exit();
    }
    
    // ... باقي الإجراءات ...
}
// إذا لم يتم التعرف على أي عملية
die("طلب غير معروف");
?>