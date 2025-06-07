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
    <div class="row">
        <!-- قائمة المنتجات -->
        <div class="col-md-8">
            <h2>سلة المشتريات</h2>
            <?php if (!empty($_SESSION['cart'])): ?>
                <?php foreach ($_SESSION['cart'] as $book_id => $item): ?>
                    <div class="card mb-3">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <img src="<?= BASE_URL . $item['cover_image'] ?>" class="img-fluid rounded-start"
                                    alt="غلاف الكتاب">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $item['title'] ?></h5>
                                    <p class="card-text">السعر: <?= number_format($item['price']) ?> ل.س</p>
                                    <a href="cart.php?remove=<?= $book_id ?>" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> إزالة
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">السلة فارغة</div>
            <?php endif; ?>
        </div>

        <!-- ملخص الفاتورة -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title">ملخص الطلبية</h5>
                    <div class="d-flex justify-content-between">
                        <span>عدد العناصر:</span>
                        <span><?= count($_SESSION['cart'] ?? []) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>المجموع الكلي:</span>
                        <span>
                            <?= number_format(array_sum(array_column($_SESSION['cart'] ?? [], 'price'))) ?> ل.س
                        </span>
                    </div>
                    <form action="process.php" method="POST" class="mt-3">
                        <input type="hidden" name="actionii" value="checkout">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check"></i> استكمال الشراء
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>