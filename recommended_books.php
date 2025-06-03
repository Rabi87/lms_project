
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// جلب الكتب المفضلة للمستخدم
$favorites = [];
$fav_query = "SELECT book_id FROM favorite_books WHERE user_id = $user_id";
$fav_result = $conn->query($fav_query);
while ($row = $fav_result->fetch_assoc()) {
    $favorites[] = $row['book_id'];
}

// جلب التصنيفات المفضلة للمستخدم
$stmt = $conn->prepare("SELECT category_id FROM user_categories WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_categories = [];
while ($row = $result->fetch_assoc()) {
    $user_categories[] = $row['category_id'];
}

// إذا لم يكن هناك تصنيفات، نعرض رسالة
if (empty($user_categories)) {
    echo '<div class="container mt-5">
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle fa-2x mb-3"></i>
            <h4>لم تقم باختيار أي تصنيفات مفضلة بعد</h4>
            <p class="mt-3">
                يمكنك تحديث تصنيفاتك المفضلة من 
                <a href="profile.php" class="text-primary">صفحة الملف الشخصي</a>
            </p>
        </div>
    </div>';
    include __DIR__ . '/includes/footer.php';
    exit();
}

// تحويل المصفوفة إلى سلسلة للاستعلام مع التأكد من أنها أعداد صحيحة
$categories_str = implode(',', array_map('intval', $user_categories));

// استعلام محسن لجلب الكتب المنتمية للتصنيفات المفضلة
$books_query = "
    SELECT 
        b.*,
        CASE 
            WHEN b.has_discount = 1 
            THEN ROUND(b.price * (1 - (b.discount_percentage / 100)), 2)
            ELSE NULL 
        END AS discounted_price
    FROM books b
    INNER JOIN book_categories bc ON b.id = bc.book_id
    WHERE bc.category_id IN ($categories_str)
    GROUP BY b.id
    ORDER BY b.created_at DESC
";

$books_result = $conn->query($books_query);

// التحقق من وجود أخطاء في الاستعلام
if (!$books_result) {
    die("خطأ في الاستعلام: " . $conn->error);
}

// جلب عدد الكتب
$total_books = $books_result->num_rows;
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold" style="color: #4a5568;">
            <i class="fas fa-star me-2 text-warning"></i> الكتب الموصى بها لك
        </h2>
        <span class="badge bg-info p-2">
            <i class="fas fa-book me-1"></i>
            <?= $total_books ?> كتاب
        </span>
    </div>

    <?php if ($total_books > 0): ?>
        <div class="row g-4">
            <?php while($book = $books_result->fetch_assoc()): 
                $is_discounted = ($book['has_discount'] == 1);
                $is_favorite = in_array($book['id'], $favorites);
            ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card h-100 shadow position-relative">
                        <?php if($is_discounted): ?>
                            <div class="discount-ribbon">
                                خصم <?= $book['discount_percentage'] ?>%
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($book['cover_image'])): ?>
                            <img src="<?= BASE_URL . $book['cover_image'] ?>" class="card-img-top" alt="غلاف الكتاب" style="height: 300px; object-fit: cover;">
                        <?php else: ?>
                            <div class="text-center py-5 bg-light">
                                <i class="fas fa-book fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary"><?= $book['material_type'] ?></span>
                                
                                <?php if($is_discounted): ?>
                                    <div>
                                        <span class="text-danger fs-5 fw-bold">
                                            <?= number_format($book['discounted_price']) ?>
                                        </span>
                                        <span class="text-decoration-line-through text-muted ms-2">
                                            <?= number_format($book['price']) ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-success fs-5 fw-bold"><?= number_format($book['price']) ?> ل.س</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="details.php?id=<?= $book['id'] ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-info"></i> التفاصيل
                                </a>
                                
                                <button class="btn btn-sm <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite" 
                                    data-book-id="<?= $book['id'] ?>">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center py-4">
            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
            <h4>لا توجد كتب متاحة في التصنيفات المفضلة لديك حالياً</h4>
            <p class="mt-3">يمكنك استكشاف الكتب من <a href="all_books.php" class="text-primary">المكتبة الكاملة</a></p>
            
            <!-- قسم تصحيح الأخطاء -->
            <div class="mt-4 text-start bg-light p-3 rounded">
                <h5>معلومات التصحيح:</h5>
                <p>التصنيفات المختارة: <?= implode(', ', $user_categories) ?></p>
                <p>عدد التصنيفات: <?= count($user_categories) ?></p>
                <p>الاستعلام المستخدم: <?= htmlspecialchars($books_query) ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
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

.card {
    transition: transform 0.3s, box-shadow 0.3s;
    border-radius: 10px;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
</style>

<script>
// إضافة/إزالة من المفضلة
$(document).on('click', '.toggle-favorite', function() {
    const button = $(this);
    const bookId = button.data('book-id');

    $.ajax({
        url: 'toggle_favorite.php',
        method: 'POST',
        data: {
            book_id: bookId,
            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
        },
        success: function(response) {
            if (response.is_favorite) {
                button.removeClass('btn-outline-danger').addClass('btn-danger');
            } else {
                button.removeClass('btn-danger').addClass('btn-outline-danger');
            }
        }
    });
});
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>
