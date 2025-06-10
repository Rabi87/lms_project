<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';


// معالجة حذف العناصر
if (isset($_GET['remove'])) {
    $book_id = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$book_id])) {
        unset($_SESSION['cart'][$book_id]);
    }
}

// احتساب المجموع الكلي مع مراعاة التخفيضات
$total = 0;
$discounted_total = 0;
$discount_savings = 0;
$cart_items = [];

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $book_id => $item) {
        // جلب تفاصيل التخفيض من قاعدة البيانات
        $book_query = "SELECT has_discount, discount_percentage FROM books WHERE id = ?";
        $stmt = $conn->prepare($book_query);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $book_result = $stmt->get_result();
        
        if ($book_result->num_rows > 0) {
            $book_data = $book_result->fetch_assoc();
            $has_discount = $book_data['has_discount'];
            $discount_percentage = $book_data['discount_percentage'];
            
            // حساب السعر المخفض
            $original_price = $item['price'];
            $discounted_price = $has_discount ? 
                $original_price - ($original_price * ($discount_percentage / 100)) : 
                $original_price;
            
            // تخزين البيانات المعدلة
            $cart_items[$book_id] = array_merge($item, [
                'has_discount' => $has_discount,
                'discount_percentage' => $discount_percentage,
                'discounted_price' => $discounted_price,
                'original_price' => $original_price
            ]);
            
            $total += $original_price;
            $discounted_total += $discounted_price;
            $discount_savings += ($original_price - $discounted_price);
        }
    }
}
?>

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

<div class="container mt-5">
    <h2 class="text-center mb-4">سلة المشتريات</h2>
    
    <div class="row">
        <!-- قائمة المنتجات -->
        <div class="col-md-8">
            <?php if (!empty($cart_items)): ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($cart_items as $book_id => $item): ?>
                        <div class="col">
                            <div class="card h-100 shadow position-relative">
                                <?php if ($item['has_discount']): ?>
                                <div class="discount-ribbon">
                                    خصم <?= $item['discount_percentage'] ?>%
                                </div>
                                <?php endif; ?>
                                
                                <div class="row g-0 h-100">
                                    <div class="col-md-5">
                                        <img src="<?= BASE_URL . $item['cover_image'] ?>" 
                                             class="img-fluid rounded-start h-100 w-100" 
                                             alt="غلاف الكتاب"
                                             style="object-fit: cover;">
                                    </div>
                                    <div class="col-md-7">
                                        <div class="card-body d-flex flex-column h-100">
                                            <h5 class="card-title"><?= $item['title'] ?></h5>
                                            
                                            <div class="mt-2">
                                                <?php if ($item['has_discount']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="text-danger fs-5 fw-bold">
                                                            <?= number_format($item['discounted_price']) ?> ل.س
                                                        </span>
                                                        <span class="text-decoration-line-through text-muted ms-2">
                                                            <?= number_format($item['original_price']) ?> ل.س
                                                        </span>
                                                    </div>
                                                    <small class="text-success">
                                                        وفرت: <?= number_format($item['original_price'] - $item['discounted_price']) ?> ل.س
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-success fs-5 fw-bold">
                                                        <?= number_format($item['original_price']) ?> ل.س
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mt-auto">
                                                <div class="d-flex justify-content-between mt-3">
                                                    <a href="details.php?id=<?= $book_id ?>" 
                                                       class="btn btn-info btn-sm">
                                                        <i class="fas fa-info-circle"></i> التفاصيل
                                                    </a>
                                                    <a href="cart.php?remove=<?= $book_id ?>" 
                                                       class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i> حذف
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                    <h3>سلة المشتريات فارغة</h3>
                </div>
            <?php endif; ?>
        </div>

        <!-- ملخص الفاتورة -->
        <div class="col-md-4">
            <div class="card shadow sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 class="card-title">ملخص الطلبية</h5>
                    
                    <div class="d-flex justify-content-between">
                        <span>عدد العناصر:</span>
                        <span><?= count($cart_items) ?></span>
                    </div>
                    
                    <?php if ($discount_savings > 0): ?>
                        <div class="d-flex justify-content-between text-success">
                            <span>إجمالي الخصومات:</span>
                            <span><?= number_format($discount_savings) ?> ل.س</span>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <?php if (!empty($cart_items)): ?>
                        <div class="d-flex justify-content-between">
                            <span>المجموع الفرعي:</span>
                            <span><?= number_format($total) ?> ل.س</span>
                        </div>
                        
                        <?php if ($discount_savings > 0): ?>
                            <div class="d-flex justify-content-between text-danger">
                                <span>الخصم:</span>
                                <span>- <?= number_format($discount_savings) ?> ل.س</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between fw-bold mt-2 fs-5">
                            <span>المجموع الكلي:</span>
                            <span><?= number_format($discounted_total) ?> ل.س</span>
                        </div>
                    <?php endif; ?>
                    
                    <form action="process.php" method="POST" class="mt-3">
                        <input type="hidden" name="actionii" value="checkout">
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-check"></i> استكمال الشراء
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="index.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-arrow-left"></i> مواصلة التسوق
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

.text-decoration-line-through {
    text-decoration: line-through;
}

.card {
    transition: transform 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>