<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// جلب التصنيفات من قاعدة البيانات
$categories = $conn->query("SELECT * FROM categories")->fetch_all(MYSQLI_ASSOC);

// جلب المؤلفين من قاعدة البيانات
$authors = $conn->query("SELECT DISTINCT author FROM books")->fetch_all(MYSQLI_ASSOC);

// تحديد نوع القائمة من الرابط
$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';

// استلام معايير التصفية من الرابط
$filterCategory = isset($_GET['category']) ? intval($_GET['category']) : 0;
$filterAuthor = isset($_GET['author']) ? htmlspecialchars($_GET['author']) : '';
$filterRating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// بناء الاستعلام الأساسي
$baseQuery = "SELECT *, 
    IF(has_discount = 1, 
        (price - (price * (discount_percentage / 100))), 
        NULL
    ) AS discounted_price 
FROM books 
WHERE 1=1";

// إضافة شروط التصفية حسب النوع
switch ($type) {
    case 'bestsellers':
        $baseQuery .= " ORDER BY created_at DESC LIMIT 10";
        $title = "الأكثر مبيعًا";
        break;
    case 'discounted':
        $baseQuery .= " AND has_discount = 1 ORDER BY discount_percentage DESC";
        $title = "العروض الخاصة";
        break;
    case 'new':
        $baseQuery .= " ORDER BY created_at DESC";
        $title = "أحدث الإضافات";
        break;
    default:
        $title = "جميع الكتب";
}

// بناء شروط التصفية
$conditions = [];
$params = [];
$types = '';

// إضافة شرط التصنيف
if ($filterCategory > 0) {
    $conditions[] = " category_id = ? ";
    $params[] = $filterCategory;
    $types .= 'i';
}

// إضافة شرط المؤلف
if (!empty($filterAuthor)) {
    $conditions[] = " author LIKE ? ";
    $params[] = '%' . $filterAuthor . '%';
    $types .= 's';
}

// إضافة شرط التقييم
if ($filterRating > 0) {
    $conditions[] = " evaluation >= ? ";
    $params[] = $filterRating;
    $types .= 'i';
}

// دمج الشروط مع الاستعلام الأساسي
if (!empty($conditions)) {
    $whereClause = " AND " . implode(" AND ", $conditions);

    // إذا كان هناك نوع محدد، نزيل ORDER BY/LIMIT ونضيفه لاحقاً
    if ($type !== '') {
        $baseQuery = preg_replace('/ORDER BY.*$/', '', $baseQuery);
        $baseQuery = preg_replace('/LIMIT.*$/', '', $baseQuery);
    }

    $baseQuery = str_replace("WHERE 1=1", "WHERE 1=1" . $whereClause, $baseQuery);

    // إعادة إضافة ORDER BY/LIMIT إذا كان هناك نوع محدد
    if ($type === 'bestsellers') {
        $baseQuery .= " ORDER BY created_at DESC LIMIT 10";
    } elseif ($type === 'discounted') {
        $baseQuery .= " ORDER BY discount_percentage DESC";
    } elseif ($type === 'new') {
        $baseQuery .= " ORDER BY created_at DESC";
    }
}

// تنفيذ الاستعلام
$stmt = $conn->prepare($baseQuery);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<style>
    /* الأنماط السابقة... */

    /* أنماط الفلتر الجديدة */
    .filter-section {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .filter-header {
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .filter-group {
        margin-bottom: 15px;
    }

    .filter-btn {
        background-color: #0d6efd;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        transition: all 0.3s;
    }

    .filter-btn:hover {
        background-color: #0b5ed7;
        transform: translateY(-2px);
    }

    .reset-btn {
        background-color: #6c757d;
    }

    .reset-btn:hover {
        background-color: #5c636a;
    }
</style>

<div class="container my-5">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="index.php" class="btn btn-secondary btn-sm">العودة</a>
    </div>
    <h1 class="text-center mb-3"><?= $title ?></h1>

    <!-- قسم الفلترة -->
    <div class="filter-section">
        <div class="filter-header d-flex justify-content-between align-items-center">
            <h4>تصفية النتائج</h4>
            <a href="?type=<?= $type ?>" class="btn btn-sm reset-btn">إعادة تعيين</a>
        </div>

        <form method="get" class="row">
            <!-- إخفاء نوع القائمة -->
            <input type="hidden" name="type" value="<?= $type ?>">

            <!-- فلتر التصنيفات -->
            <div class="col-md-4 filter-group">
                <label class="form-label fw-bold">التصنيف</label>
                <select name="category" class="form-select">
                    <option value="0">جميع التصنيفات</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['category_id'] ?>"
                            <?= $filterCategory == $category['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- فلتر المؤلفين -->
            <div class="col-md-4 filter-group">
                <label class="form-label fw-bold">المؤلف</label>
                <select name="author" class="form-select">
                    <option value="">جميع المؤلفين</option>
                    <?php foreach ($authors as $author): ?>
                        <option value="<?= htmlspecialchars($author['author']) ?>"
                            <?= $filterAuthor == $author['author'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($author['author']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- فلتر التقييم -->
            <div class="col-md-4 filter-group">
                <label class="form-label fw-bold">التقييم</label>
                <select name="rating" class="form-select">
                    <option value="0">جميع التقييمات</option>
                    <option value="5" <?= $filterRating == 5 ? 'selected' : '' ?>>5 نجوم</option>
                    <option value="4" <?= $filterRating == 4 ? 'selected' : '' ?>>4 نجوم فأكثر</option>
                    <option value="3" <?= $filterRating == 3 ? 'selected' : '' ?>>3 نجوم فأكثر</option>
                    <option value="2" <?= $filterRating == 2 ? 'selected' : '' ?>>2 نجوم فأكثر</option>
                    <option value="1" <?= $filterRating == 1 ? 'selected' : '' ?>>1 نجمة فأكثر</option>
                </select>
            </div>

            <!-- زر التطبيق -->
            <div class="col-12 text-center mt-3">
                <button type="submit" class="btn filter-btn">
                    <i class="fas fa-filter me-2"></i> تطبيق الفلتر
                </button>
            </div>
        </form>
    </div>

    <!-- عرض الكتب -->
    <div class="row g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($book = $result->fetch_assoc()):
                $is_discounted = ($book['has_discount'] == 1);
            ?>
                <!-- بطاقة الكتاب (نفس الكود السابق) -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 shadow">
                        <!-- شريط الخصم -->
                        <?php if ($is_discounted): ?>
                            <div class="discount-ribbon">
                                خصم <?= $book['discount_percentage'] ?>%
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?= BASE_URL . $book['cover_image'] ?>" class="card-img-top" alt="غلاف الكتاب"
                                style="height: 300px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>

                            <!-- عرض السعر -->
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($is_discounted): ?>
                                    <div>
                                        <span class="discounted-price">
                                            <?= number_format($book['discounted_price']) ?> ل.س
                                        </span>
                                        <span class="text-decoration-line-through text-muted ms-2">
                                            <?= number_format($book['price']) ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-success"><?= number_format($book['price']) ?> ل.س</span>
                                <?php endif; ?>

                                <?php if ($type === 'bestsellers'): ?>
                                    <span class="badge bg-danger">🔥 <?= $book['sales_count'] ?> مبيعًا</span>
                                <?php endif; ?>
                            </div>

                            <!-- التقييم -->
                            <div class="mt-2">
                                <?php
                                $rating = $book['evaluation'];
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                                <span class="ms-2">(<?= $rating ?>)</span>
                            </div>

                            <!-- الأزرار والوظائف -->
                            <div class="d-flex justify-content-between mt-3">
                                <!-- أيقونة التفاصيل -->
                                <button class="btn btn-info btn-sm"
                                    onclick="window.location.href='details.php?id=<?= $book['id'] ?>'">
                                    <i class="fas fa-info"></i>
                                </button>

                                <!-- المفضلة -->
                                <button
                                    class="btn btn-sm <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite"
                                    data-book-id="<?= $book['id'] ?>">
                                    <i class="fas fa-heart"></i>
                                </button>

                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <!-- استعارة الكتاب -->
                                    <form method="POST" action="process.php" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                        <input type="hidden" name="action" value="borrow">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-hand-holding"></i>
                                        </button>
                                    </form>

                                    <!-- شراء الكتاب -->
                                    <button class="btn btn-success btn-sm add-to-cart" data-book-id="<?= $book['id'] ?>"
                                        data-book-title="<?= htmlspecialchars($book['title']) ?>"
                                        data-book-price="<?= $is_discounted ? $book['discounted_price'] : $book['price'] ?>"
                                        data-book-image="<?= $book['cover_image'] ?>">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#loginModal">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center py-4">
                    <i class="fas fa-book-open fa-2x mb-3"></i>
                    <h4>لا توجد كتب تطابق معايير البحث</h4>
                    <p class="mb-0">حاول تغيير معايير الفلتر أو <a href="?type=<?= $type ?>">إعادة تعيين الفلتر</a></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>