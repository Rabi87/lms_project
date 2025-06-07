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
    } else {
        // Verify password
        $check_sql = "SELECT password FROM users WHERE id = $user_id";
        $check_result = $conn->query($check_sql);
        $user_data = $check_result->fetch_assoc();
        
        if (password_verify($password, $user_data['password'])) {
            // Delete user account
            $delete_sql = "DELETE FROM users WHERE id = $user_id";
            
            if ($conn->query($delete_sql)) {
                // Destroy session and redirect
                session_destroy();
                header("Location:". BASE_URL ."login.php");
                exit();
            } else {
                $error = 'حدث خطأ أثناء محاولة حذف الحساب';
            }
        } else {
            $error = 'كلمة المرور غير صحيحة';
        }
    }
}
// جلب البيانات الحالية
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>

<div class="container mt-4">
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
                        >
                        <button 
                            type="button" 
                            class="btn btn-outline-secondary" 
                            onclick="togglePasswordVisibility()"
                        >
                            <i class="fas fa-eye" id="eyeIcon"></i>
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
            
            <!-- إضافة قسم حذف الحساب (منفصل عن نموذج التحديث) -->
            <div class="mt-5 pt-4 border-top">
                <h5 class="text-danger mb-3 fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i> منطقة الخطر
                </h5>
                
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
                    <!-- حقل مخفي لتحديد إجراء الحذف -->
                    <input type="hidden" name="delete_account" value="1">
                    
                    <div class="mb-3">
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
                    
                    <div class="d-grid gap-2">
                        <button 
                            type="button" 
                            class="btn btn-danger btn-lg rounded-pill"
                            data-bs-toggle="modal" 
                            data-bs-target="#confirmDeleteModal"
                        >
                            <i class="fas fa-trash-alt me-2"></i> حذف الحساب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal تأكيد الحذف -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 bg-danger text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exclamation-circle me-2"></i> تأكيد الحذف
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h4 class="fw-bold text-danger">هل أنت متأكد من رغبتك في حذف حسابك؟</h4>
                </div>
                <p class="text-muted text-center">
                    هذا الإجراء لا يمكن التراجع عنه. سيتم حذف جميع بياناتك بشكل دائم من النظام.
                </p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">إلغاء</button>
                <button 
                    type="button" 
                    class="btn btn-danger rounded-pill"
                    onclick="document.getElementById('deleteAccountForm').submit();"
                >
                    <i class="fas fa-trash-alt me-2"></i> نعم، احذف الحساب
                </button>
            </div>
        </div>
    </div>
</div>

<!-- الأنماط المخصصة -->
<style>
.card {
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease;
    margin-bottom: 2rem;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17ead9 0%, #6078ea 100%);
}

.input-group-text {
    border-right: none;
    background-color: #f8f9fa !important;
}

.form-control {
    border-left: none;
    padding-left: 0.75rem;
}

.form-control:focus {
    box-shadow: none;
    border-color: #ced4da;
}

.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
    transition: all 0.3s ease;
}

.btn-info:hover {
    background-color: #138496;
    border-color: #117a8b;
    transform: translateY(-2px);
}

.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

/* إضافة أنماط جديدة */
.border-top {
    border-top: 1px solid #eee !important;
    padding-top: 1.5rem;
    margin-top: 1.5rem;
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    transition: all 0.3s ease;
}

.btn-danger:hover {
    background-color: #bb2d3b;
    border-color: #b02a37;
    transform: translateY(-2px);
}

.text-danger {
    color: #dc3545 !important;
}

.modal-content {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

.danger-zone {
    background-color: #fff8f8;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #ffe6e6;
}
</style>

<!-- سكربت إظافة/إخفاء كلمة المرور -->
<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// إضافة تأثيرات للتحذير
document.addEventListener('DOMContentLoaded', function() {
    const dangerZone = document.querySelector('.border-top');
    if (dangerZone) {
        dangerZone.classList.add('danger-zone');
        
        // إضافة تأثير تحذيري
        setInterval(() => {
            dangerZone.style.borderColor = dangerZone.style.borderColor === 'rgb(255, 230, 230)' ? '#ffcccc' : '#ffe6e6';
        }, 1000);
    }
});
</script>
