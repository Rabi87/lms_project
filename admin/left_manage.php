<?php
// بدء الجلسة مرة واحدة فقط
session_start();
// ملف admin/dashboard.php
require __DIR__ . '/../includes/config.php';


// التحقق من وجود الجلسة و نوع المستخدم
if (!isset($_SESSION['user_id']) ){
    header("Location: " . BASE_URL . "login.php");
    exit();
}

if ($_SESSION['user_type'] != 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}


?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <title>لوحة التحكم - المدير</title>
    <style>
    .sidebar {
        background: #f8f9fa;
        min-height: 100vh;
    }

    .sidebar .btn {
        text-align: right;
        width: 100%;
        margin: 5px 0;
    }

    .content-section {
        display: none;
    }

    .content-section.active {
        display: block;
    }

    .overdue {
        background-color: #ffe6e6;
    }

    .due-soon {
        background-color: #fff3cd;
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- الشريط الجانبي -->
            <div class="col-md-3 sidebar p-4">
                <div class="d-grid gap-2">
                    <button onclick="showSection('profile')" class="btn btn-outline-primary active">
                        <i class="fas fa-user control-link"></i> 
                        <a href="profile.php" class="control-link fas fa-user">الملف الشخصي</a>
                    </button>

                    <button onclick="showSection('borrowed')" class="btn btn-outline-success">
                        <i class="fas fa-book control-link"></i> إدارة المكتبة
                    </button>

                    <button onclick="showSection('pending')" class="btn btn-outline-warning">
                        <i class="fas fa-clock control-link"></i> الطلبات المعلقة
                    </button>

                    <button onclick="showSection('pending')" class="btn btn-outline-warning">
                        <i class="fas fa-clock control-link"></i> إدارة المستخدمين
                    </button>

                    <button onclick="showSection('pending')" class="btn btn-outline-warning">
                        <i class="fas fa-clock control-link"></i> إدارة الموقع
                    </button>

                </div>
            </div>

            <main class="content-panel" id="contentPanel">
                <div class="welcome-message">
                    <h2>مرحبًا، <?php echo $_SESSION['user_name']; ?></h2>
                    <p>اختر أحد الخيارات من لوحة التحكم لبدء الإدارة</p>
                </div>
            </main>
</div>
</div>



<script>
        function showSection(sectionId) {
            // إزالة النشاط من جميع الأزرار
            document.querySelectorAll('.sidebar .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // إخفاء جميع الأقسام
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // إظهار القسم المحدد وإضافة النشاط للزر
            document.getElementById(sectionId).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
        

            <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>
