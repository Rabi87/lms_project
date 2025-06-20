<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/config.php';

// تحديث آخر نشاط للمستخدم (بدون مسافات قبل <?php)
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>المكتبة الذكية</title>
    <!-- 1. الخطوط -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- 2. مكتبة Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- 3. Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- 4. SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- 5. الملفات المحلية (CSS) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">

    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
</head>
<style>
.hover-effect {
    transition: all 0.3s ease;
    padding: 8px;
    border-radius: 50%;
}

.hover-effect:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: scale(1.1);
}

.hover-effect:hover i {
    color: #e2e8f0 !important;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
}

.loading-spinner {
    text-align: center;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>
<?php
// عند بداية الجلسة
if (isset($_COOKIE['cart'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['cart'], true);
}
?>
<body class="d-flex flex-column min-vh-100">
    <header>
        <div class="container">
            <!-- الجزء العلوي -->
            <nav class="navbar navbar-expand-lg py-3" style="background: transparent !important;">
                <div class="row g-3 w-100 mx-0 align-items-stretch">
                    <!-- البطاقة 1: الشعار -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100"
                            style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                            <div class="card-body d-flex align-items-center justify-content-center py-3 px-4">
                                <a href="<?= BASE_URL ?>index.php">
                                    <img src="<?= BASE_URL ?>assets/images/aa.png" class="img-fluid"
                                        style="height: 80px; filter: brightness(0) invert(1);" alt="شعار المكتبة">
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- البطاقة 2: فارغة -->
                    <div class="col-md-4">
                        <div>
                            <!-- محتوى فارغ مع ارتفاع ثابت -->
                        </div>
                    </div>

                    <!-- البطاقة 3: معلومات المستخدم -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100"
                            style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                            <?php if(isset($_SESSION['user_id'])): ?>
                            <div class="card-body py-3 px-4 h-100">
                                <div class="d-flex align-items-center justify-content-between h-100">
                                    <!-- محتوى المستخدم -->
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fas fa-user-shield text-white fs-2"></i>
                                        <div class="d-flex flex-column">
                                            <h4 class="text-white mb-0 fw-semibold">
                                                <?= htmlspecialchars($_SESSION['user_name']) ?>
                                            </h4>
                                            <span class="badge bg-light text-dark mt-1" style="font-size: 0.75rem;">
                                                <?= ($_SESSION['user_type'] == 'admin') ? 'مدير النظام' : 'مستخدم' ?>
                                            </span>
                                        </div> 
                                        <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type']==='user'): ?>  
                                        <div class="vertical-divider"
                                            style="border-right: 2px solid rgba(255,255,255,0.3); height: 40px;">
                                        </div>                                                        
                                        <a class="nav-link text-white " href="<?= BASE_URL ?>favorites.php">
                                            <i class="fas fa-heart"></i>
                                        </a>                                       
                                        <a class="nav-link position-relative text-white " href="<?= BASE_URL ?>cart.php">
                                            <i class="fas fa-shopping-cart"></i>
                                            <span class="badge bg-white text-black cart-counter">
                                                <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                                            </span>
                                        </a> 
                                         <?php endif; ?>                                            
                                    </div>

                                    <!-- زر الخروج -->
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="vertical-divider"
                                            style="border-right: 2px solid rgba(255,255,255,0.3); height: 40px;"></div>
                                        <a href="<?= BASE_URL ?>logout.php" class="text-decoration-none">
                                            <i class="fas fa-sign-out-alt text-white fs-5"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="card-body py-3 px-4 h-100">
                                <div class="d-flex align-items-center justify-content-center h-100">
                                    <a href="<?= BASE_URL ?>login.php"
                                        class="text-white text-decoration-none d-flex align-items-center gap-2">
                                        <i class="fas fa-sign-in-alt fs-5"></i>
                                        <span>تسجيل الدخول</span>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Loading Overlay -->
            <div id="loadingOverlay" class="loading-overlay">
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-3">جاري التحميل...</p>
                </div>
            </div>

            <!-- القائمة الرئيسية  -->
            <nav class="navbar navbar-expand-lg navbar-dark sticky-top"
                style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%); color: white;">
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                    data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav">
                        <li class="nav-item"> <a class="nav-link" href="<?= BASE_URL ?>index.php">المكتبة</a> </li>
                        <li class="nav-item"> <a class="nav-link" href="<?= BASE_URL ?>book_of_the_month.php">كتاب الشهر</a> </li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type']==='user'): ?>  
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>recommended_books.php">
                                </i>تفضيلاتك                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item"> <a class="nav-link"
                                href="<?= BASE_URL ?>Forum/manage_groups.php">المنتدى</a></li>
                        <li class="nav-item"> <a class="nav-link"
                                href="<?= BASE_URL . ($_SESSION['user_type'] == 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php') ?>">
                                لوحة التحكم
                            </a>
                        
                        <?php endif; ?>
                        </li>
                    </ul>
                
                <!--<div class="lang-switcher" style="position: absolute; top: 10px; right: 10px;">
                    <?php if($current_lang == 'ar'): ?>
                        <a href="?lang=en" class="btn btn-sm btn-light">English</a>
                    <?php else: ?>
                        <a href="?lang=ar" class="btn btn-sm btn-light">العربية</a>
                    <?php endif; ?>
                </div> -->
                </div>
            </nav>
        </div>
    </header>



    <script>
    function toggleDropdown() {
        const dropdown = document.getElementById('logoutDropdown');
        dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
    }
    window.onclick = function(event) {
        if (!event.target.closest('.user-dropdown')) {
            document.getElementById('logoutDropdown').style.display = 'none';
        }
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: '<?= $_SESSION['error'] ?>'
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])) : ?>
                Swal.fire({
                    icon: 'success',
                    title: 'نجاح',
                    text: '<?= $_SESSION['success'] ?>'
                });
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['info'])) : ?>
                Swal.fire({
                    icon: 'info',
                    title: 'تنبيه',
                    text: '<?= $_SESSION['info'] ?>'
                });
                <?php unset($_SESSION['info']); ?>
            <?php endif; ?>
        });
    </script>

    <main class="flex-grow-1 container my-2">