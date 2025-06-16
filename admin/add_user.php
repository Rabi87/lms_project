<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/header.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}
if ($_SESSION['user_type'] != 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// توليد CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// تهيئة المتغيرات
$error = '';
$success = '';
$name = '';
$email = '';

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'طلب غير صالح';
    } else {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $conn->real_escape_string($_POST['password']);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // التحقق من عدم وجود البريد مسبقاً
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows > 0) {
            $error = "البريد الإلكتروني مسجل مسبقاً";
        } else {
            // إضافة المستخدم
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, status) 
                                    VALUES (?, ?, ?, 'user', 1)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                
                // إرسال رسالة ترحيبية داخلية
                $subject = "مرحباً بك في نظامنا!";
                $message = "عزيزي <br><br>تم إنشاء حسابك بنجاح.<br>كلمة المرور الخاصة بك هي: <strong>$password</strong><br><br>ننصحك بتغيير كلمة المرور بعد تسجيل الدخول.<br><br>شكراً لانضمامك.";
                
                // إدراج الرسالة في جدول الرسائل
                $insert_message = $conn->prepare("INSERT INTO messages (user_id, subject, message) VALUES (?, ?, ?)");
                $insert_message->bind_param("iss", $new_user_id, $subject, $message);
                
                if ($insert_message->execute()) {
                    $success = "تم إضافة العضو وإرسال بياناته عبر الرسائل الداخلية";
                } else {
                    $success = "تم إضافة العضو ولكن فشل إرسال الرسالة الداخلية";
                }
            } else {
                $error = "خطأ في إضافة العضو: " . $conn->error;
            }
        }
    }
}
?>


    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-plus me-2"></i>إضافة عضو جديد
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">الاسم الكامل</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور</label>
                            <div class="input-group">
                                <input type="text" class="form-control"  name="password" required>
                               
                            </div>
                            <small class="form-text text-muted">سيتم إرسال كلمة المرور عبر الرسائل الداخلية للمستخدم</small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> حفظ وإرسال
                            </button>
                            <a href="dashboard.php?section=users" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>





<?php require __DIR__ . '/../includes/footer.php'; ?>