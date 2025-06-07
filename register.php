<?php
if (session_status() === PHP_SESSION_NONE) 
{
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// جلب التصنيفات
$stmt = $conn->prepare("SELECT category_id, category_name FROM categories");
$stmt->execute();
$categories = $stmt->get_result();

// جلب تصنيفات المستخدم المختارة (إذا كان مسجلاً)
$user_categories = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT category_id FROM user_categories WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_categories[] = $row['category_id'];
    }
}
?>
<!-- عرض الأخطاء باستخدام SweetAlert -->
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
                <div class="card-header bg-gradient-info text-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-user-plus me-2"></i> إنشاء حساب جديد</h4>
                </div>
                <div class="card-body p-4">
                    <form action="process.php" method="POST" id="regForm">
                        <!-- الخطوة 1 -->
                        <div id="step1">
                            <!-- حقل الاسم -->
                            <div class="mb-4 input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-id-card text-info"></i></span>
                                <input type="text" class="form-control" name="name" placeholder="الاسم الكامل" required>
                            </div>
                            <!-- حقل البريد -->
                            <div class="mb-4 input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-envelope text-info"></i></span>
                                <input type="email" class="form-control" name="email" placeholder="البريد الإلكتروني" required>
                            </div>

                            <!-- حقل كلمة المرور -->
                            <div class="mb-4 input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-info"></i></span>
                                <input type="password" class="form-control" name="password" id="password" placeholder="كلمة المرور" required>
                            </div>

                            <!-- تأكيد كلمة المرور -->
                            <div class="mb-4 input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-check-circle text-info"></i></span>
                                <input type="password" class="form-control" id="confirm_password" placeholder="تأكيد كلمة المرور" required>
                            </div>

                            <button type="button" class="btn btn-primary w-100 py-2 fw-bold" onclick="validateStep1()">
                                التالي <i class="fas fa-arrow-left ms-2"></i>
                            </button>
                        </div>

                        <!-- الخطوة 2 -->
                        <div id="step2" style="display:none;">
                            <h5 class="mb-4 fw-bold text-info"><i class="fas fa-tags me-2"></i> اختر التصنيفات المفضلة</h5>
                            <div class="row g-3">
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                <div class="col-md-4">
                                    <div class="category-card">
                                        <input type="checkbox" 
                                            name="categories[]" 
                                            value="<?= $cat['category_id'] ?>" 

                                            id="cat-<?= $cat['category_id'] ?>" 

                                            class="form-check-input visually-hidden">

                                          
                                        <label for="cat-<?= $cat['category_id'] ?>" 
                                            class="d-block p-3 rounded-3 border bg-light">
                                            <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($cat['category_name']) ?></h6>
                                        </label>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary px-4" onclick="prevStep()">
                                    <i class="fas fa-arrow-right me-2"></i> السابق
                                </button>
                                <button type="submit" class="btn btn-success px-4" name="register">
                                    <i class="fas fa-check-circle me-2"></i> تسجيل
                                </button>
                            </div>
                        </div>       
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- الأنماط المخصصة -->
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

.category-card label {
    transition: all 0.3s ease;
    cursor: pointer;
}

.category-card input:checked + label {
    background: #e3f2fd !important;
    border: 2px solid #17ead9 !important;
    transform: translateY(-3px);
}

.category-card label:hover {
    background: #f8f9fa !important;
}
</style>

<!-- التحقق من الصحة -->
<script>
function validateStep1() {
    document.querySelectorAll('.error-msg').forEach(e => e.remove());

    const name = document.querySelector('[name="name"]').value.trim();
    const email = document.querySelector('[name="email"]').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    let isValid = true;

    if (name === '') {
        showError('[name="name"]', 'الاسم الكامل مطلوب');
        isValid = false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showError('[name="email"]', 'البريد الإلكتروني غير صالح');
        isValid = false;
    }

    if (password.length < 6) {
        showError('#password', 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
        isValid = false;
    }

    if (password !== confirmPassword) {
        showError('#confirm_password', 'كلمة المرور غير متطابقة');
        isValid = false;
    }

    if (isValid) {
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
    }
}

function showError(selector, message) {
    const input = document.querySelector(selector);
    const errorElem = document.createElement('div');
    errorElem.className = 'error-msg mt-2';
    errorElem.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${message}`;
    input.closest('.input-group').appendChild(errorElem);
}

document.getElementById('regForm').addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('input[name="categories[]"]:checked');
    if (checkboxes.length === 0) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'خطأ!', text: 'يجب اختيار تصنيف واحد على الأقل' });
    }
});

function prevStep() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
}
</script>

<?php include 'includes/footer.php'; ?>