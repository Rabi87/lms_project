<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/config.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المكتبة الذكية</title>
    <link href="<?= BASE_URL ?>assets/bootstrap/css/bootstrap.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="d-flex flex-column min-vh-100">
    <header>
        <nav class="navbar navbar-expand-lg navbar-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?= BASE_URL ?>index.php">
                    <img src="<?= BASE_URL ?>assets/images/logo3.png" class="logo-hover" alt="شعار المكتبة">
                </a>
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="user-dropdown position-relative d-none d-lg-block">
                        <!-- يظهر فقط على الشاشات الكبيرة -->
                        <div class="d-flex align-items-center gap-2 cursor-pointer" onclick="toggleDropdown()">
                            <i class="fas fa-user-circle fa-2x"></i>
                            <div class="d-flex flex-column">
                                <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                                <span><i class="fas fa-shield-alt"></i>
                                    <?= $_SESSION['user_type'] == 'admin' ? 'مدير النظام' : 'مستخدم' ?></span>
                            </div>
                        </div>
                        <div id="logoutDropdown" class="dropdown-menu-custom1">
                            <a href="<?= BASE_URL ?>logout.php" class="text-light text-decoration-none">
                                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                            </a>
                        </div>
                    </div>

                    <!-- نسخة محسنة للهواتف -->
                    <div class="d-lg-none">
                        <a href="<?= BASE_URL ?>login.php" class="btn btn-success-custom w-100">تسجيل الدخول</a>
                    </div>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>login.php" class="btn btn-success-custom">تسجيل الدخول</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <nav class="navbar navbar-expand-lg navbar-light main-navbar sticky-top">
            <div class="container-fluid">
                <!-- زر التبديل للقائمة (يظهر على الشاشات الصغيرة) -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- العناصر المطوية -->
                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php">المكتبة</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>complaint.php">شكاوي</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item d-lg-none">
                            <!-- يظهر فقط على الشاشات الصغيرة -->
                            <a class="nav-link" href="<?= BASE_URL ?>Forum/manage_groups.php">المنتدى</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>admin/dashboard.php">لوحة التحكم</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="flex-grow-1 container my-4">