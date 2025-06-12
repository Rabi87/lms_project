<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تسجيل البيانات الواردة

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// توليد CSRF token إذا لم موجود
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// جلب المفضلة
$favorites = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_query = "SELECT book_id FROM favorite_books WHERE user_id = $user_id";
    $fav_result = $conn->query($fav_query);
    while ($row = $fav_result->fetch_assoc()) {
        $favorites[] = $row['book_id'];
    }
}

// جلب البيانات الأساسية
$categories = $conn->query("SELECT * FROM categories")->fetch_all(MYSQLI_ASSOC);
$authors = $conn->query("SELECT DISTINCT author FROM books")->fetch_all(MYSQLI_ASSOC);
$materialTypes = $conn->query("SELECT DISTINCT material_type FROM books WHERE material_type IS NOT NULL AND material_type != ''")->fetch_all(MYSQLI_ASSOC);

// معالجة معاملات البحث والتصفية
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$filterCategory = isset($_GET['category']) ? intval($_GET['category']) : 0;
$filterAuthor = isset($_GET['author']) ? htmlspecialchars($_GET['author']) : '';
$filterRating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$searchTerm = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$filterMaterial = isset($_GET['material']) ? htmlspecialchars($_GET['material']) : '';
$section = isset($_GET['section']) ? $_GET['section'] : '';

// استعلام الكتب الأكثر مبيعًا مع حساب عدد المرات
// استعلام الكتب الأكثر مبيعًا
$baseQuery = "SELECT 
    b.*,
    IF(b.has_discount = 1, 
        (b.price - (b.price * (b.discount_percentage / 100))), 
        NULL
    ) AS discounted_price,
    COUNT(br.id) AS sales_count
FROM books b
LEFT JOIN borrow_requests br 
    ON b.id = br.book_id 
    AND br.status = 'approved' 
WHERE 1=1";

// تحديد نوع القائمة
switch ($type) {
    case 'bestsellers':
        $baseQuery .= " GROUP BY b.id ORDER BY sales_count DESC";
        $title = "الأكثر مبيعًا";
        break;
    case 'discounted':
        $baseQuery .= " AND b.has_discount = 1 GROUP BY b.id ORDER BY b.discount_percentage DESC";
        $title = "العروض الخاصة";
        break;
    case 'new':
        $baseQuery .= " GROUP BY b.id ORDER BY b.created_at DESC";
        $title = "أحدث الإضافات";
        break;
    default:
        $baseQuery .= " GROUP BY b.id ORDER BY b.created_at DESC";
        $title = "جميع الكتب";
}



// إضافة شروط التصفية
$conditions = [];
$params = [];
$types = '';

if ($filterCategory > 0) {
    $conditions[] = " b.category_id = ? ";
    $params[] = $filterCategory;
    $types .= 'i';
}

if (!empty($filterAuthor)) {
    $conditions[] = " b.author LIKE ? ";
    $params[] = '%' . $filterAuthor . '%';
    $types .= 's';
}

if ($filterRating > 0) {
    $conditions[] = " b.evaluation >= ? ";
    $params[] = $filterRating;
    $types .= 'i';
}

if (!empty($searchTerm)) {
    $conditions[] = "(
        b.title LIKE ? OR 
        b.author LIKE ? OR 
        b.isbn LIKE ? OR 
        b.category_id IN (SELECT category_id FROM categories WHERE category_name LIKE ?)
    )";
    $params = array_merge($params, array_fill(0, 4, '%' . $searchTerm . '%'));
    $types .= 'ssss';
}

if (!empty($filterMaterial)) {
    $conditions[] = " b.material_type = ? ";
    $params[] = $filterMaterial;
    $types .= 's';
}

// دمج الشروط مع الاستعلام
if (!empty($conditions)) {
    $whereClause = " AND " . implode(" AND ", $conditions);
    $baseQuery = str_replace("WHERE 1=1", "WHERE 1=1" . $whereClause, $baseQuery);
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
    .badge.bg-danger {
    display: block !important;
    position: static !important;
    opacity: 1 !important;
}

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
        color:white;
    }

    .reset-btn:hover {
        background-color: #5c636a;
        color:white;
    }
    /* تنسيقات الكتب المخفضة */
.discount-ribbon {
    position: absolute;
    top: 10px;
    left: -15px;
    background:rgb(173, 4, 4);
    color:rgb(240, 255, 31);
;
    padding: 10px 40px;
    font-size: 0.9rem;
    z-index: 2;
    box-shadow: 2px 2px 5px rgba(1, 1, 1, 0.8);
    clip-path: polygon(0 0, 100% 0, 90% 50%, 100% 100%, 0 100%, 10% 50%);
    /*clip-path: polygon(20% 0, 50% 0, 0 63%, 0 33%);*/




}

.text-decoration-line-through {
    text-decoration: line-through;
}
</style>

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

<?php if (isset($_SESSION['success'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'شكرا لك.. !',
    text: '<?= $_SESSION['success'] ?>'
});

</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="container my-5">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="index.php" class="btn btn-secondary btn-sm">العودة</a>
    </div>
   

    <!-- قسم الفلترة -->
    <div class="filter-section">
        <div class="filter-header d-flex justify-content-between align-items-center">
            <h4>تصفية النتائج</h4>
            <a href="?type=<?= $type ?>" class="btn btn-sm reset-btn color-white">إعادة تعيين</a>
        </div>

        <form method="get" class="row">
            <!-- إخفاء نوع القائمة -->
            <input type="hidden" name="type" value="<?= $type ?>">

             <!-- شريط البحث الجديد -->
        <div class="col-12 mb-4">
            <div class="input-group shadow-sm rounded-pill">
                <input type="text" name="search" class="form-control border-0 py-3" 
                       placeholder="ابحث عن كتاب، مؤلف، ISBN أو تصنيف..."
                       value="<?= $searchTerm ?>"
                       style="border-top-right-radius: 50px; border-bottom-right-radius: 50px;">
                <button class="btn btn-primary border-0 px-4" type="submit"
                        style="border-top-left-radius: 50px; border-bottom-left-radius: 50px;">
                    <i class="fas fa-search me-2"></i> بحث
                </button>
            </div>
        </div>

            <!-- فلتر التصنيفات -->
            <div class="col-md-3 filter-group">
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
            <div class="col-md-3 filter-group">
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
            <div class="col-md-3 filter-group">
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

            <!-- فلتر نوع المادة (إضافة جديدة) -->
            <div class="col-md-3 filter-group">
                <label class="form-label fw-bold">نوع المادة</label>
                <select name="material" class="form-select">
                    <option value="">جميع الأنواع</option>
                    <?php foreach ($materialTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type['material_type']) ?>"
                            <?= $filterMaterial == $type['material_type'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type['material_type']) ?>
                        </option>
                    <?php endforeach; ?>
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

    <<!-- عرض الكتب -->
    <div class="row g-4">
        
        <?php if ($result->num_rows > 0): ?>
            <?php while ($book = $result->fetch_assoc()): 
                $is_discounted = ($book['has_discount'] == 1);
                 $book_id = $book['id'];
            $is_favorite = in_array($book_id, $favorites);
            ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 shadow">
                        <?php if ($is_discounted): ?>
                            <div class="discount-ribbon">
                                خصم <?= $book['discount_percentage'] ?>%
                            </div>
                        <?php endif; ?>
                        

                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?= BASE_URL . $book['cover_image'] ?>" class="card-img-top" alt="غلاف الكتاب" style="height: 300px; object-fit: cover;">
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>
                        
                            <!-- عرض عدد المبيعات للأكثر مبيعًا -->
                           <?php if ($section === 'bestsellers'): ?>
    <div class="d-flex align-items-center mb-2">
        <span class="badge bg-danger me-2">
            <i class="fas fa-fire"></i>
            <?= (int)$book['sales_count'] ?> مبيعًا
        </span>
    </div>
<?php endif; ?>   <!-- التقييم -->
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
                                <?php if (isset($_SESSION['user_id'])): ?>
                                <script>
                                console.log("User ID: <?= $_SESSION['user_id'] ?>");
                                console.log("Favorites: <?= implode(',', $favorites) ?>");
                                console.log("CSRF Token: <?= $_SESSION['csrf_token'] ?>");
                                </script>
                                <?php endif; ?>

                                <!-- المفضلة -->
                                <button class="btn btn-sm <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite"
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
                                     <a href="login.php" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-sign-in-alt"></i>
                                     </a>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script>
$(document).on('click', '.toggle-favorite', function() {
    const button = $(this);
    const bookId = button.data('book-id');
    
    <?php if(!isset($_SESSION['user_id'])): ?>
        Swal.fire({
            title: 'تنبيه!',
            text: 'يجب تسجيل الدخول أولاً',
            icon: 'warning',
            confirmButtonText: 'تسجيل الدخول'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'login.php';
            }
        });
        return;
    <?php endif; ?>

    console.log("Toggle favorite for book ID:", bookId);
    console.log("CSRF Token: <?= $_SESSION['csrf_token'] ?>");
    
    $.ajax({
        url: 'toggle_favorite.php',
        method: 'POST',
        data: {
            book_id: bookId,
            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
        },
        dataType: 'json',
        success: function(response) {
            console.log("Response:", response);
            if (response.success) {
                if (response.is_favorite) {
                    button.removeClass('btn-outline-danger').addClass('btn-danger');
                    Swal.fire('تم!', 'أضيف إلى المفضلة', 'success');
                } else {
                    button.removeClass('btn-danger').addClass('btn-outline-danger');
                    Swal.fire('تم!', 'حُذف من المفضلة', 'info');
                }
            } else {
                Swal.fire('خطأ!', response.message || 'فشلت العملية', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error, xhr.responseText);
            Swal.fire('خطأ!', 'حدث خطأ في الاتصال بالخادم: ' + xhr.responseText, 'error');
        }
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>