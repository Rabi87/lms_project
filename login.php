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


<div class="container mt-51">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h4 class="mb-0 fw-bold">
                        <i class="fas fa-sign-in-alt me-2"></i> تسجيل الدخول
                    </h4>
                </div>
                <div class="card-body p-4">
                    <form action="<?= BASE_URL ?>process.php" method="POST" id="regForm">
                        <!-- حقل اسم المستخدم مع أيقونة -->
                        <div class="mb-4 input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-info"></i></span>
                            <input type="email" class="form-control" name="email" placeholder="البريد الإلكتروني" required>
                        </div>
                        <!-- حقل كلمة المرور مع أيقونة -->                       
                        <div class="mb-4 input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-info"></i></span>
                                <input type="password" class="form-control" name="password" id="password" placeholder="كلمة المرور" required>
                            
                            <!-- زر إظهار/إخفاء -->
                            <button type="button" 
                                    class="btn btn-link text-muted" 
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
                            class="btn btn-primary w-100 py-2 fw-bold" onclick="validateStep1()">
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
    .error-msg {
    color: #dc3545;
    font-size: 0.85rem;
    animation: slideDown 0.3s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

</style>
<script>
function validateStep1() {
    document.querySelectorAll('.error-msg').forEach(e => e.remove());

    
    const email = document.querySelector('[name="email"]').value.trim();
    const password = document.getElementById('password').value;
    let isValid = true;  
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showError('[name="email"]', 'البريد الإلكتروني غير صالح');
        isValid = false;
    }

    if (password.length < 6) {
        showError('#password', 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
        isValid = false;
    }

   
}

function showError(selector, message) {
    const input = document.querySelector(selector);
    const errorElem = document.createElement('div');
    errorElem.className = 'error-msg mt-2';
    errorElem.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${message}`;
    input.closest('.input-group').appendChild(errorElem);
}
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