<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
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
            $stmt->bind_param("sss", $name, $email, $password);
            
            if ($stmt->execute()) {
                // إرسال البريد الإلكتروني
                $to = $email;
                $subject = "حسابك في نظام إدارة المكتبة";
                
                // استخدام القيم مباشرة من $_POST
                $message = "مرحباً " . $_POST['name'] . "،<br><br>";
                $message .= "تم إنشاء حساب لك في نظام إدارة المكتبة.<br>";
                $message .= "<strong>بريدك الإلكتروني:</strong> " . $_POST['email'] . "<br>";
                $message .= "<strong>كلمة المرور:</strong> " . $_POST['password'] . "<br><br>";
                $message .= "يمكنك تسجيل الدخول من خلال الرابط: <a href='" . BASE_URL . "login.php'>" . BASE_URL . "login.php</a>";
                
                $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                
                if (mail($to, $subject, $message, $headers)) {
                    $success = "تم إضافة العضو وإرسال بياناته إلى بريده الإلكتروني";
                } else {
                    $success = "تم إضافة العضو ولكن فشل إرسال البريد الإلكتروني";
                }
            } else {
                $error = "خطأ في إضافة العضو: " . $conn->error;
            }
        }
    }
}
?>

   

 
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-lg">
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
                                    <input type="text" class="form-control" id="password" name="password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="generatePassword">
                                        توليد
                                    </button>
                                </div>
                                <small class="form-text text-muted">سيتم إرسال كلمة المرور إلى البريد الإلكتروني للمستخدم</small>
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
    </div>

    <script>
        // توليد كلمة مرور عشوائية
        document.getElementById('generatePassword').addEventListener('click', function() {
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
            let password = "";
            for (let i = 0; i < 10; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            document.getElementById('password').value = password;
        });
    </script>
    
    <script src="<?= BASE_URL ?>js/bootstrap.bundle.min.js"></script>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
