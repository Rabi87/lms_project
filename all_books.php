<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// تحديد نوع القائمة من الرابط
$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';

// استعلام بناءً على النوع
switch ($type) {
    case 'bestsellers':
        // استعلام أكثر الكتب مبيعاً مع دعم الخصومات
        $query = "
            SELECT *,
                IF(has_discount = 1, 
                    (price - (price * (discount_percentage / 100))), 
                    NULL
                ) AS discounted_price
            FROM books 
            ORDER BY created_at DESC 
            LIMIT 10
        ";
        $title = "الأكثر مبيعًا";
        break;
    case 'discounted':
        // استعلام الكتب المخفضة
        $query = "
            SELECT *,
                (price - (price * (discount_percentage / 100))) AS discounted_price
            FROM books
            WHERE has_discount = 1
            ORDER BY discount_percentage DESC
        ";
        $title = "العروض الخاصة";
        break;
    case 'new':
        // استعلام أحدث الكتب مع دعم الخصومات
        $query = "
            SELECT *,
                IF(has_discount = 1, 
                    (price - (price * (discount_percentage / 100))), 
                    NULL
                ) AS discounted_price
            FROM books 
            ORDER BY created_at DESC
        ";
        $title = "أحدث الإضافات";
        break;
    default:
        // استعلام جميع الكتب مع دعم الخصومات
        $query = "
            SELECT *,
                IF(has_discount = 1, 
                    (price - (price * (discount_percentage / 100))), 
                    NULL
                ) AS discounted_price
            FROM books
        ";
        $title = "جميع الكتب";
}

// تنفيذ الاستعلام
$result = $conn->query($query);
?>
<style>
/* داخل قسم الـ style أو ملف منفصل */
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
    transition: transform 0.3s;
    position: relative; /* مهم لعرض شريط الخصم */
}

.card:hover {
    transform: translateY(-5px);
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.text-decoration-line-through {
    text-decoration: line-through;
    color: #6c757d;
}

.discounted-price {
    color: #dc3545;
    font-weight: bold;
}
</style>

<div class="container my-5">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="index.php" class="btn btn-secondary btn-sm">العودة</a>
    </div>
    <h1 class="text-center mb-5"><?= $title ?></h1>
    <div class="row g-4">
        <?php while ($book = $result->fetch_assoc()): 
            $is_favorite = in_array($book['id'], $favorites);
            $is_discounted = ($book['has_discount'] == 1);
            ?>
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 shadow">
                    <!-- شريط الخصم -->
                    <?php if ($is_discounted): ?>
                    <div class="discount-ribbon">
                        خصم <?= $book['discount_percentage'] ?>%
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($book['cover_image'])): ?>
                        <img src="<?= BASE_URL . $book['cover_image'] ?>" 
                             class="card-img-top" 
                             alt="غلاف الكتاب"
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

                        <!-- الأزرار والوظائف -->
                        <div class="d-flex justify-content-between mt-3">
                            <!-- أيقونة التفاصيل -->
                            <button class="btn btn-info btn-sm"
                                onclick="window.location.href='details.php?id=<?= $book['id'] ?>'">
                                <i class="fas fa-info"></i>
                            </button>

                            <!-- المفضلة -->
                            <button class="btn btn-sm <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite"
                                data-book-id="<?= $book['id'] ?>">
                                <i class="fas fa-heart"></i>
                            </button>

                            <?php if(isset($_SESSION['user_id'])): ?>
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
                                <button class="btn btn-success btn-sm add-to-cart" 
                                    data-book-id="<?= $book['id'] ?>"
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
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>