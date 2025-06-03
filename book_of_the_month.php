<?php
// تفعيل عرض الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// بدء الجلسة إذا لم تكن بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تضمين ملفات الإعدادات والرأس
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// جلب كتاب الشهر الحالي
$book_of_month_query = $conn->query("
    SELECT 
        b.id,
        b.title,
        b.author,
        b.description,
        b.price,
        b.isbn,
        b.page_count,
        b.cover_image,
        b.evaluation,
        c.category_name,
        b.has_discount,
        b.discount_percentage,
        IF(b.has_discount = 1, 
            (b.price - (b.price * (b.discount_percentage / 100))), 
            NULL
        ) AS discounted_price,
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(r.id) AS total_reviews
    FROM books b
    LEFT JOIN categories c 
        ON b.category_id = c.category_id
    LEFT JOIN book_ratings r 
        ON b.id = r.book_id
    WHERE b.book_of_the_month = 1
    GROUP BY b.id
    LIMIT 1
");

// التحقق من وجود كتاب الشهر
if (!$book_of_month_query || $book_of_month_query->num_rows === 0) {
    echo '<div class="container mt-5">
        <div class="alert alert-warning">لا يوجد كتاب محدد ككتاب الشهر حالياً.</div>
    </div>';
    require __DIR__ . '/includes/footer.php';
    exit();
}

$book = $book_of_month_query->fetch_assoc();
$book_id = $book['id'];

// جلب بيانات المفضلة
$favorites = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_result = $conn->query("SELECT book_id FROM favorite_books WHERE user_id = $user_id");
    while ($row = $fav_result->fetch_assoc()) {
        $favorites[] = $row['book_id'];
    }
}

// جلب كتب أخرى لنفس الكاتب
$author = $book['author'];
$author_books_query = $conn->prepare("
    SELECT b.id, b.title, b.author, b.cover_image, b.price 
    FROM books b
    WHERE b.author = ? 
    AND b.id != ?
    AND b.book_of_the_month = 0
    ORDER BY b.evaluation DESC
    LIMIT 10
");

// التحقق من صحة الاستعلام
if (!$author_books_query) {
    die("خطأ في إعداد استعلام كتب المؤلف: " . $conn->error);
}

$author_books_query->bind_param("si", $author, $book_id);

// التحقق من تنفيذ الاستعلام
if (!$author_books_query->execute()) {
    die("خطأ في تنفيذ استعلام كتب المؤلف: " . $author_books_query->error);
}

$author_books_result = $author_books_query->get_result();

// جلب المراجعات
$reviews = $conn->prepare("
    SELECT 
        r.rating,
        r.comment,
        r.created_at,
        u.name
    FROM book_ratings r
    LEFT JOIN users u
        ON r.user_id = u.id
    WHERE r.book_id = ?
");
$reviews->bind_param("i", $book_id);
$reviews->execute();
$reviews_result = $reviews->get_result();
?>
<style>
/* تنسيقات مشابهة لصفحة التفاصيل */
.book-of-month-badge {
    font-size: 1.2rem;
    vertical-align: middle;
    background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
    border-radius: 20px;
    padding: 5px 15px;
    margin-right: 10px;
}

.review-card {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}
.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}
.owl-carousel .item {
    padding: 10px;
}
.rating-stars {
    color: #f39c12;
    font-size: 1.5rem;
    margin-top: 5px;
}
.meta-item {
    display: flex;
    margin-bottom: 12px;
    align-items: center;
}
.meta-label {
    flex: 0 0 150px;
    font-weight: bold;
    color: #34495e;
}
.meta-value {
    flex: 1;
    color: #2c3e50;
}
.line{
    border-bottom: 2px solid rgb(174, 172, 172);
    margin: 15px 0;
}
.book-cover-frame {
    width: 315px;
    height: 475px;
    overflow: hidden;
    border-radius: 10px;
    position: relative;
}
.book-cover-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.discount-ribbon {
    position: absolute;
    top: 10px;
    left: -10px;
    background: #dc3545;
    color: white;
    padding: 5px 15px;
    font-size: 0.9rem;
    z-index: 2;
    box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.8);
    clip-path: polygon(0 0, 100% 0, 90% 50%, 100% 100%, 0 100%, 10% 50%);
}
.text-decoration-line-through {
    text-decoration: line-through;
    color: #6c757d;
}
.discounted-price {
    color: #dc3545;
    font-weight: bold;
    font-size: 1.2rem;
}
.special-offer {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
</style>

<div class="container mt-5">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="home.php" class="btn btn-secondary btn-sm">العودة</a>
    </div>
    
    <div class="book-details-container">
        <!-- قسم التفاصيل -->
        <div class="row">
            <div class="col-md-4">
                <div class="book-cover-frame">
                    <!-- شريط الخصم -->
                    <?php if($book['has_discount'] == 1): ?>
                    <div class="discount-ribbon">
                        خصم <?= $book['discount_percentage'] ?>%
                    </div>
                    <?php endif; ?>
                    
                    <img src="<?= BASE_URL . htmlspecialchars($book['cover_image']) ?>" 
                         class="book-cover-img"
                         alt="غلاف الكتاب">
                </div>
            </div>
            <div class="col-md-8">
                <h1 class="mb-3">
                    <?= htmlspecialchars($book['title']) ?>
                    <span class="book-of-month-badge text-white">كتاب الشهر ★</span>
                </h1>
                
                <p class="rating-stars">
                    <?= str_repeat('★', round($book['evaluation'])) . str_repeat('☆', 5 - round($book['evaluation'])) ?>
                   
                </p>
                <p class="badge bg-danger"><?= htmlspecialchars($book['category_name']) ?></p>
                <p class="line"></p>

                <!-- معلومات التخفيض -->
                <?php if($book['has_discount'] == 1): ?>
                <div class="special-offer">
                    <h5><i class="fas fa-tag"></i> عرض خاص</h5>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="discounted-price"><?= number_format($book['discounted_price'], 2) ?> ل.س</span>
                            <span class="text-decoration-line-through ms-2"><?= number_format($book['price'], 2) ?> ل.س</span>
                        </div>
                        <span class="badge bg-danger">
                            توفير <?= number_format($book['price'] - $book['discounted_price'], 2) ?> ل.س
                        </span>
                    </div>
                    <p class="mt-2 mb-0">ينتهي العرض قريباً</p>
                </div>
                <?php endif; ?>

                <div class="meta-item">
                    <div class="meta-label">المؤلف</div>
                    <div class="meta-value"><?= htmlspecialchars($book['author']) ?></div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">السعر</div>
                    <div class="meta-value">
                        <?php if($book['has_discount'] == 1): ?>
                            <span class="discounted-price"><?= number_format($book['discounted_price'], 2) ?> ل.س</span>
                            <span class="text-decoration-line-through text-muted ms-2"><?= number_format($book['price'], 2) ?> ل.س</span>
                        <?php else: ?>
                            <span><?= number_format($book['price'], 2) ?> ل.س</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">عدد الصفحات</div>
                    <div class="meta-value"><?= $book['page_count'] ?></div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">ISBN</div>
                    <div class="meta-value"><?= $book['isbn'] ?></div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">نبذة عن الكتاب</div>
                    <div class="meta-value"><?= nl2br(htmlspecialchars($book['description'])) ?></div>
                </div>

                <!-- أزرار الإجراءات -->
                <div class="mt-4 d-flex gap-2">
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="process.php" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <button type="submit" name="action" value="borrow" class="btn btn-primary">
                            <i class="fas fa-book"></i> استعارة
                        </button>
                    </form>
                    
                    <button class="btn btn-success add-to-cart" 
                        data-book-id="<?= $book['id'] ?>"
                        data-book-title="<?= htmlspecialchars($book['title']) ?>"
                        data-book-price="<?= $book['has_discount'] ? $book['discounted_price'] : $book['price'] ?>"
                        data-book-image="<?= $book['cover_image'] ?>">
                        <i class="fas fa-cart-plus"></i> شراء
                    </button>
                    
                    <?php if($book['has_discount'] == 1): ?>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#discountModal">
                        <i class="fas fa-percentage"></i> تفاصيل الخصم
                    </button>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> سجل الدخول
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- قسم المراجعات -->
        <div class="mt-5">
            <h3>مراجعات القراء (<?= $reviews_result->num_rows ?>)</h3>
            <?php if($reviews_result->num_rows > 0): ?>
                <?php while($review = $reviews_result->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div>
                            <strong><?= htmlspecialchars($review['name'] ?? 'مستخدم مجهول') ?></strong>
                            <span class="text-warning">
                                <?= str_repeat('★', $review['rating']) ?>
                            </span>
                        </div>
                        <small class="text-muted">
                            <?= date('Y-m-d', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($review['comment'] ?? 'بدون تعليق')) ?></p>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">لا توجد مراجعات حتى الآن</div>
            <?php endif; ?>
        </div>

        <!-- كتب أخرى لنفس الكاتب -->
        <?php if ($author_books_result->num_rows > 0): ?>
            <div class="mt-5">
                <h3>كتب أخرى لـ <?= htmlspecialchars($author) ?></h3>
                <div class="owl-carousel owl-theme">
                    <?php while($author_book = $author_books_result->fetch_assoc()): 
                        $is_favorite = in_array($author_book['id'], $favorites);
                    ?>
                    <div class="item">
                        <div class="card h-100 shadow">
                            <?php if(!empty($author_book['cover_image'])): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($author_book['cover_image']) ?>" 
                                 class="card-img-top" 
                                 alt="غلاف الكتاب"
                                 style="height: 250px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($author_book['title']) ?></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-success"><?= number_format($author_book['price'], 2) ?> ل.س</span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <button class="btn btn-info btn-sm"
                                            onclick="window.location.href='details.php?id=<?= $author_book['id'] ?>'">
                                        <i class="fas fa-info"></i>
                                    </button>
                                    <button class="btn btn-sm <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite"
                                            data-book-id="<?= $author_book['id'] ?>">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-5">لا توجد كتب أخرى لنفس المؤلف</div>
        <?php endif; ?>
    </div>
</div>

<!-- نافذة تفاصيل الخصم -->
<?php if($book['has_discount'] == 1): ?>
<div class="modal fade" id="discountModal" tabindex="-1" aria-labelledby="discountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="discountModalLabel">تفاصيل التخفيض</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <h6>السعر الأصلي</h6>
                        <p><?= number_format($book['price'], 2) ?> ل.س</p>
                    </div>
                    <div>
                        <h6>نسبة الخصم</h6>
                        <p class="text-danger fw-bold"><?= $book['discount_percentage'] ?>%</p>
                    </div>
                    <div>
                        <h6>السعر بعد الخصم</h6>
                        <p class="text-success fw-bold"><?= number_format($book['discounted_price'], 2) ?> ل.س</p>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    هذا العرض ساري حتى نهاية الشهر الحالي أو حتى نفاذ الكمية
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- مكتبات JavaScript المطلوبة -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" />

<script>
$(document).ready(function(){
    // تهيئة Owl Carousel
    $('.owl-carousel').owlCarousel({
        rtl: true,
        loop: false,
        margin: 15,
        nav: true,
        responsive: {
            0: { items: 1 },
            600: { items: 2 },
            1000: { items: 3 }
        },
        navText: [
            '<i class="fas fa-chevron-right"></i>',
            '<i class="fas fa-chevron-left"></i>'
        ]
    });

    // معالجة إضافة إلى المفضلة
    $('.toggle-favorite').click(function(){
        var bookId = $(this).data('book-id');
        var button = $(this);
        
        $.ajax({
            url: 'process.php',
            method: 'POST',
            data: {
                action: 'toggle_favorite',
                book_id: bookId,
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
            },
            success: function(response) {
                var data = JSON.parse(response);
                if(data.success) {
                    button.toggleClass('btn-outline-danger btn-danger');
                }
            }
        });
    });

    // معالجة إضافة إلى السلة
    $('.add-to-cart').click(function(){
        var bookId = $(this).data('book-id');
        var bookTitle = $(this).data('book-title');
        var bookPrice = $(this).data('book-price');
        var bookImage = $(this).data('book-image');
        
        // يمكنك هنا إضافة كود إدارة السلة
        alert('تمت إضافة الكتاب "' + bookTitle + '" إلى سلة التسوق');
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>