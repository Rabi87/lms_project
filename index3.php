<?php
// ملف index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
// معالجة معاملات البحث والتصفية
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? 'all';
// بناء الاستعلام الأساسي مع شروط التصفية
$base_query = "
    SELECT 
        books.*, 
        categories.category_name 
    FROM books
    INNER JOIN categories 
        ON books.category_id = categories.category_id
    WHERE 1=1
    AND (books.type = 'physical' AND books.quantity > 0 OR books.type = 'e-book')
";
// إضافة شروط البحث
$params = [];
$types = '';
if (!empty($search)) {
    $base_query .= " AND (books.title LIKE ? OR books.author LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}
// إضافة فلتر التصنيف
if ($category_filter !== 'all') {
    $base_query .= " AND categories.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}
// تقسيم الاستعلام حسب النوع
$physical_query = $base_query . " AND books.type = 'physical'";
$e_book_query = $base_query . " AND books.type = 'e-book'";

// تنفيذ الاستعلامات
$stmt_physical = $conn->prepare($physical_query);
if ($types !== '') $stmt_physical->bind_param($types, ...$params);
$stmt_physical->execute();
$physical_books = $stmt_physical->get_result();

$stmt_e = $conn->prepare($e_book_query);
if ($types !== '') $stmt_e->bind_param($types, ...$params);
$stmt_e->execute();
$e_books = $stmt_e->get_result();
?>
<!-- قسم الكتب الفيزيائية -->
<div class="container py-5">
    <!-- العنوان الرئيسي -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold border-bottom pb-3">الكتب الفيزيائية</h2>
            <div class="d-flex gap-2">
                <!-- شريط البحث -->
                <form class="w-100" method="GET" action="">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control"
                            placeholder="ابحث عن كتاب، مؤلف، أو تصنيف..." value="<?= htmlspecialchars($search) ?>">

                    </div>


                    <!-- تصنيفات الكتب -->
                    <div class="col-md-3 mb-3">
                        <select name="category" class="form-select">
                            <option value="all">جميع التصنيفات</option>
                            <?php
                            $categories = $conn->query("SELECT * FROM categories");
                            while ($cat = $categories->fetch_assoc()):
                            ?>
                            <option value="<?= $cat['category_id'] ?>"
                                <?= ($category_filter == $cat['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php if($physical_books->num_rows > 0): ?>
    <div class="row g-4">
        <!-- إضافة صف واحد يحتوي جميع البطاقات -->
        <?php while($book = $physical_books->fetch_assoc()): ?>
        <!-- البطاقة -->
        <div class="col-6 col-md-4 col-lg-2">
            <!-- تعديل الأعمدة لعرض 6 بطاقات في الصف -->
            <div class="card h-100 shadow-sm">
                <img src="<?= BASE_URL ?>assets/images/books/<?= $book['cover_image'] ?>" class="card-img-top"
                    alt="غلاف الكتاب" style="height: 200px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title fs-6"><?= htmlspecialchars($book['title']) ?></h5>
                    
                    <p class="card-text text-muted small">
                        <?= htmlspecialchars($book['category_name']) ?><br>
                        <?= htmlspecialchars($book['author']) ?> 
                        <!-- <?= $book['quantity'] ?><i class="fas fa-box"></i>-->
                        <!-- عرض التقييم كنجوم -->
                    <div class="rating-stars" style="color: #ffd700;">
                        <?php
            $rating = (float)$book['evaluation'];
            $full_stars = floor($rating);
            $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
            $empty_stars = 5 - $full_stars - $half_star;
            
            // نجوم مملوءة
            echo str_repeat('★', $full_stars);
            
            // نصف نجمة (إذا وجد)
            echo $half_star ? '½' : '';
            
            // نجوم فارغة
            echo str_repeat('☆', $empty_stars);
            ?>
                    </div>

                    </p>
                </div>
                <?php if(isset($_SESSION['user_id'])): ?>
                <form method="POST" action="process.php" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" name="action" class="btn btn-primary w-100 btn-sm" value="borrow"
                        onclick="return confirm('هل تريد استعارة هذا الكتاب؟')">
                        استعارة الكتاب
                    </button>
                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                </form>
                <form method="POST" action="process.php" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" name="action" class="btn btn-primary w-100 btn-sm" value="purchase"
                        onclick="return confirm('هل تريد شراء هذا الكتاب؟')">
                        شراء الكتاب
                    </button>
                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                </form>
                <?php else: ?>
                <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#loginModal">
                    سجل دخول للاستعارة
                </button>
                <?php endif; ?>
            </div>

        </div>
        <?php endwhile; ?>
    </div> <!-- إغلاق صف البطاقات هنا -->
    <?php else: ?>
    <div class="col-12">
        <div class="alert alert-warning">لا توجد كتب فيزيائية متاحة حاليًا</div>
    </div>
    <?php endif; ?>
</div>



<?php

require __DIR__ . '/includes/footer.php'; ?>