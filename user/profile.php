<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/config.php';

// التحقق من تسجيل الدخول والصالحيات
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header("Location:" . BASE_URL ."login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = $success = '';
$step = 1; // الخطوة الافتراضية

// تحديث البيانات
if (isset($_POST['update_profile']))  {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // التحقق من البريد الإلكتروني
    $check_sql = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $error = "البريد الإلكتروني مسجل مسبقاً";
    } else {
        // تحديث كلمة المرور إذا تم إدخالها
        $password_update = '';
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $password_update = ", password = '$hashed_password'";
        }
        
        $sql = "UPDATE users 
                SET name = '$name', email = '$email' $password_update 
                WHERE id = $user_id";
        
        if ($conn->query($sql) === TRUE) {
            $success = "تم تحديث البيانات بنجاح";
            $_SESSION['user_name'] = $name; // تحديث الجلسة
        } else {
            $error = "خطأ في التحديث: " . $conn->error;
        }
    }
}

// Handle account deletion
if (isset($_POST['delete_account'])) {
    // Verify password for security
    $password = $_POST['confirm_password'];
    
    if (empty($password)) {
        $error = 'يرجى إدخال كلمة المرور لتأكيد الحذف';
        $step = 2; // البقاء في خطوة التأكيد عند وجود خطأ
    } else {
        // Verify password
        $check_sql = "SELECT password FROM users WHERE id = $user_id";
        $check_result = $conn->query($check_sql);
        $user_data = $check_result->fetch_assoc();
        
        if (password_verify($password, $user_data['password'])) {
            // Delete user account
            $update_sql = "UPDATE users SET status = 0 WHERE id = $user_id";
            
            if ($conn->query($update_sql)) {
                // Destroy session and redirect
                session_destroy();
                echo '<script>window.location.href = "../login.php";</script>';
                exit();
            } else {
                $error = 'حدث خطأ أثناء محاولة حذف الحساب';
                $step = 2; // البقاء في خطوة التأكيد عند وجود خطأ
            }
        } else {
            $error = 'كلمة المرور غير صحيحة';
            $step = 2; // البقاء في خطوة التأكيد عند وجود خطأ
        }
    }
}

// جلب البيانات الحالية
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>

<!-- الخطوة 1: تعديل الملف الشخصي (تظهر افتراضيًا) -->
<div class="container mt-4" id="step1" style="<?php echo ($step == 2) ? 'display: none;' : ''; ?>">
    <div class="card border-0 shadow-sm">
        <!-- هيدر البطاقة مع التدرج اللوني -->
        <div class="card-header bg-gradient-info text-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-user-edit me-2"></i> الملف الشخصي
            </h5>
        </div>
        
        <div class="card-body p-4">
            <!-- رسائل التنبيه المنسقة -->
            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">خطأ!</h5>
                    <p class="mb-0"><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="fas fa-check-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">تم التحديث!</h5>
                    <p class="mb-0"><?php echo $success; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- نموذج التعديل المحسّن -->
            <form method="POST" action="">
                <!-- حقل الاسم -->
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">الاسم الكامل</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-user-tag text-info"></i>
                        </span>
                        <input 
                            type="text" 
                            name="name" 
                            class="form-control border-start-0 ps-3" 
                            value="<?php echo htmlspecialchars($user['name']); ?>" 
                            required
                        >
                    </div>
                </div>
                
                <!-- حقل البريد الإلكتروني -->
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">البريد الإلكتروني</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-at text-info"></i>
                        </span>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control border-start-0 ps-3" 
                            value="<?php echo htmlspecialchars($user['email']); ?>" 
                            required
                        >
                    </div>
                </div>
                
                <!-- حقل كلمة المرور مع خاصية الإظافة -->
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">كلمة المرور الجديدة</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-lock text-info"></i>
                        </span>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-control border-start-0 ps-3" 
                            placeholder="اتركه فارغاً للحفاظ على كلمة المرور الحالية"
                            pattern="\d{6}
                        >
                     

                        <button type="button" class="btn btn-link text-muted" 
                                onclick="togglePasswordVisibility()">
                                <i class="fas fa-eye"></i>
                            </button>
                    </div>
                    <small class="text-muted">6 أحرف على الأقل مع رموز وأرقام</small>
                </div>
                
                <!-- زر الحفظ مع الأيقونة -->
                <div class="d-grid gap-2 mt-4">
                    <button 
                        type="submit" 
                        name="update_profile" 
                        class="btn btn-info btn-lg rounded-pill text-white"
                    >
                        <i class="fas fa-save me-2"></i> حفظ التعديلات
                    </button>
                </div>
            </form>
            
            <!-- زر الانتقال إلى خطوة حذف الحساب -->
            <div class="mt-5 pt-4 border-top">
                <h5 class="text-danger mb-3 fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i> منطقة الخطر
                </h5>
                
                <div class="d-grid gap-2">
                    <button 
                        type="button" 
                        class="btn btn-danger btn-lg rounded-pill"
                        onclick="showDeleteConfirmation()"
                    >
                        <i class="fas fa-trash-alt me-2"></i> حذف الحساب
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- الخطوة 2: تأكيد حذف الحساب (مخفي افتراضيًا) -->
<div class="container mt-4" id="step2" style="<?php echo ($step == 2) ? '' : 'display: none;'; ?>">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-gradient-danger text-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-user-slash me-2"></i> تأكيد حذف الحساب
            </h5>
        </div>
        
        <div class="card-body p-4">
            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">خطأ!</h5>
                    <p class="mb-0"><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="alert alert-danger">
                <h6 class="alert-heading fw-bold">تحذير!</h6>
                <p class="mb-2">حذف حسابك هو إجراء دائم. سيتم:</p>
                <ul class="mb-0">
                    <li>إزالة جميع بياناتك الشخصية</li>
                    <li>حذف جميع المحتويات المرتبطة بحسابك</li>
                    <li>فقدان الوصول إلى الخدمات</li>
                </ul>
            </div>
            
            <form method="POST" action="" id="deleteAccountForm">
                <input type="hidden" name="delete_account" value="1">
                
                <div class="mb-4">
                    <label class="form-label text-danger fw-bold mb-2">تأكيد كلمة المرور</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-lock text-danger"></i>
                        </span>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            class="form-control border-start-0 ps-3" 
                            placeholder="أدخل كلمة المرور للتأكيد"
                            required
                        >
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button 
                        type="button" 
                        class="btn btn-secondary rounded-pill px-4"
                        onclick="showProfileForm()"
                    >
                        <i class="fas fa-arrow-left me-2"></i> رجوع
                    </button>
                    <button 
                        type="submit" 
                        class="btn btn-danger rounded-pill px-4"
                    >
                        <i class="fas fa-trash-alt me-2"></i> تأكيد الحذف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- سكربتات JavaScript -->
<script>
// تبديل إظافة/إخفاء كلمة المرور
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}

// الانتقال إلى شاشة حذف الحساب
function showDeleteConfirmation() {
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
}

// العودة إلى شاشة الملف الشخصي
function showProfileForm() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
}
</script>