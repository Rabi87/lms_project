<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();}


require __DIR__ . '/includes/config.php'; 
require __DIR__ . '/includes/header.php'; 


?>
<?php if (isset($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'مبروك.. !',
            text: '<?= $_SESSION['success'] ?>'
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<!-- فشل عملية التسجيل-->
<?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'انتبه.. !',
            text: '<?= $_SESSION['error'] ?>'
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>


<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h4 class="mb-0 fw-bold">
                        <i class="fas fa-sign-in-alt me-2"></i> تسجيل الدخول
                    </h4>
                </div>
                <div class="card-body p-4">
                    <form action="<?= BASE_URL ?>process.php" method="POST">
                        <!-- حقل اسم المستخدم مع أيقونة -->
                        <div class="mb-4 input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-user text-primary"></i>
                            </span>
                            <input type="text" class="form-control" 
                                placeholder="اسم المستخدم" 
                                name="name" 
                                style="border-left: 0;">
                        </div>

                        <!-- حقل كلمة المرور مع أيقونة -->
                        <div class="mb-4 input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-lock text-primary"></i>
                            </span>
                            <input type="password" 
                                class="form-control" 
                                id="password"
                                placeholder="كلمة المرور"
                                name="password" 
                                style="border-left: 0;">
                            <!-- زر إظهار/إخفاء -->
                            <button type="button" class="btn btn-link text-muted" 
                                onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>

                        <!-- خيار تذكرني -->
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember_me">
                            <label class="form-check-label" for="remember">تذكر الحساب</label>
                        </div>

                        <!-- زر الدخول -->
                        <button type="submit" name="login" 
                            class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="fas fa-sign-in-alt me-2"></i> دخول
                        </button>
                    </form>

                    <!-- الروابط -->
                    <div class="mt-4 text-center">
                        <a href="<?= BASE_URL ?>register.php" class="text-decoration-none">
                            <i class="fas fa-user-plus me-1"></i> إنشاء حساب
                        </a>
                        <br>
                        <a href="<?= BASE_URL ?>forget_password.php" class="text-decoration-none mt-2 d-block">
                            <i class="fas fa-key me-1"></i> نسيت كلمة المرور؟
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 15px;
    overflow: hidden;
    border: none !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.input-group-text {
    transition: all 0.3s;
}

.form-control:focus {
    box-shadow: none;
    border-color: #667eea;
}
</style>
<script>
function togglePassword() {
    const passwordField = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        passwordField.type = 'password';
        eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>