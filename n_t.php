<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// جلب الكتب المقترحة
$recommended_books = [];
if (isset($_SESSION['user_id'])) {
    $query = "
        SELECT b.* 
        FROM books b
        JOIN user_categories uc ON b.category_id = uc.category_id
        WHERE uc.user_id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $recommended_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
// جلب جميع الكتب
$all_books = $conn->query("SELECT * FROM books");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- إضافة Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- تضمين Swiper.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
</head>

<body class="bg-light">
    <div class="container">
        <!-- شريط الأخبار (تم تعديله باستخدام Bootstrap) -->
        <div class="news-ticker bg-primary text-white p-2 overflow-hidden mb-3">
            <div class="ticker-content d-flex">
                <span class="mx-4">مرحبًا بك في موقعنا! 📚 تمتع بأفضل الكتب والمقالات.</span>
                <span class="mx-4">لا تنسَ زيارة قسم العروض الخاصة للحصول على خصومات كبيرة!</span>
                <span class="mx-4">تابعونا لمعرفة المزيد عن الكتب الجديدة والتحديثات القادمة.</span>
            </div>
        </div>

        <!-- قسم الرعاة (معدل باستخدام Bootstrap) -->
        <div class="sponsors-bar bg-light py-4 mb-4 border-bottom">
            <div class="sponsors d-flex justify-content-center gap-4 flex-wrap">
                <a href="#" class="sponsor-link"><img src="assets/images/sham.jpeg" alt="راعي 3"
                        class="img-fluid h-50px"></a>
                <a href="#" class="sponsor-link"><img src="assets/images/Syriatel.png" alt="راعي 1"
                        class="img-fluid h-50px"></a>
                <a href="#" class="sponsor-link"><img src="assets/images/mtn.jpeg" alt="راعي 2"
                        class="img-fluid h-50px"></a>
            </div>
        </div>
        <!-- شريط البحث (معدل باستخدام Bootstrap) -->
        <div class="search-box mb-4 text-center">
            <input type="text" id="searchInput" class="form-control rounded-pill w-100 mx-auto"
                style="max-width: 400px;" placeholder="ابحث عن كتاب...">
        </div>
        <!-- شريط التصنيفات (معدل باستخدام Bootstrap) -->
        <div class="filter-bar d-flex justify-content-center gap-2 mb-4 flex-wrap">
            <button class="filter-btn btn btn-outline-primary rounded-pill active" data-category="all">الكل</button>
            <button class="filter-btn btn btn-outline-primary rounded-pill" data-category="علم النفس">علم النفس</button>
            <button class="filter-btn btn btn-outline-primary rounded-pill" data-category="تقنية">تقنية</button>
            <button class="filter-btn btn btn-outline-primary rounded-pill" data-category="تنمية ذاتية">تنمية
                ذاتية</button>
            <button class="filter-btn btn btn-outline-primary rounded-pill" data-category="تاريخ">تاريخ</button>
        </div>

        <!-- Accordion 1 -->
        <div class="accordion">
            <?php if (!empty($recommended_books)): ?>
            <button class="accordion-header">المجموعة الأولى</button>
            <div class="accordion-content">
                <div class="card-grid">
                    <?php foreach ($recommended_books as $book): ?>

                    <div class="card" data-title="العقل الباطن" data-category="علم النفس">
                        <div class="card-front">
                            <img src="<?= BASE_URL ?>assets/images/books/<?= $book['cover_image'] ?>" alt="غلاف الكتاب">
                        </div>
                        <div class="card-back">
                            <h5><?= htmlspecialchars($book['title']) ?></h5>
                            <p><?= htmlspecialchars($book['author']) ?></p>
                            <p><?= htmlspecialchars($book['category_id']) ?></p>
                            
                            <div class="card-actions">
                                <button class="btn-icon"><i class="fa-solid fa-book"></i></button>
                                <button class="btn-icon"><i class="fa-solid fa-cart-shopping"></i></button>
                                <button class="btn-details" onclick="openModal('modal1')"><i
                                        class="fa-solid fa-info"></i></button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Accordion 2 -->
        <div class="accordion">
            <button class="accordion-header">المجموعة الثانية</button>
            <div class="accordion-content">
                <?php while ($book = $all_books->fetch_assoc()): ?>
                <div class="card-grid">
                    <div class="card" data-title="فن إدارة الوقت" data-category="تنمية ذاتية">
                        <div class="card-front">
                            <img src="<?= BASE_URL ?>assets/images/books/<?= $book['cover_image'] ?>"
                                alt="غلاف الكتاب">
                        </div>
                        <div class="card-back">
                            <h5><?= htmlspecialchars($book['title']) ?></h5>
                            <p><?= htmlspecialchars($book['author']) ?></p>
                            <p><?= htmlspecialchars($book['category_id']) ?></p>
                            <p class="status available">متوفر</p>
                            <div class="card-actions">
                                <button class="btn-icon"><i class="fa-solid fa-book"></i></button>
                                <button class="btn-icon"><i class="fa-solid fa-cart-shopping"></i></button>
                                <button class="btn-details" onclick="openModal('modal3')"><i
                                        class="fa-solid fa-info"></i></button>
                            </div>

                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- النوافذ المنبثقة -->
        <div id="modal1" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('modal1')">&times;</span>
                <h2><?= htmlspecialchars($book['title']) ?></h2>
                <p><strong>ملخص:</strong> <?= htmlspecialchars($book['description']) ?></p>
                <p><strong>التقييم:</strong> ⭐⭐⭐⭐ (4.5/5)</p>
                <p><strong>السعر:</strong> <?= htmlspecialchars($book['price']) ?></p>
            </div>
        </div>

    </div>
</body>
<script src="script.js"></script>
</body>

</html>
<!--
<div class="card">
    <div class="card-front">
        <img src="images/book1.jpeg"صورة 4">
    </div>
    <div class="card-back">
        <h3>العنوان الرابع</h3>
        <p>وصف مختصر عن الصورة.</p>
    </div>
</div>
-->