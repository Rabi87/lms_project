<?php
// تمكين عرض الأخطاء للتصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';


if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_redirect'] = 'favorites.php';
    header("Location: login.php");
    exit;
}

// جلب الكتب المفضلة
$user_id = $_SESSION['user_id'];
$query = "
    SELECT b.* 
    FROM books b
    JOIN favorite_books fb ON b.id = fb.book_id
    WHERE fb.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
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
 <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
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
</style>
<div class="container my-5">
    <h1 class="text-center mb-5"><i class="fas fa-heart text-danger"></i> كتبي المفضلة</h1>
    
    <?php if($result->num_rows > 0): ?>
        <div class="owl-carousel owl-theme">
            <?php while($book = $result->fetch_assoc()): 
                $is_discounted = ($book['has_discount'] == 1);
                $discounted_price = $is_discounted ? ($book['price'] - ($book['price'] * ($book['discount_percentage'] / 100))) : 0;
                $is_favorite = true; // لأنها في صفحة المفضلة
            ?>
                <div class="item">
                    <div class="card h-100 shadow position-relative">
                        <?php if($is_discounted): ?>
                        <div class="discount-ribbon">
                            خصم <?= $book['discount_percentage'] ?>%
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($book['cover_image'])): ?>
                        <img src="<?= BASE_URL . $book['cover_image'] ?>" class="card-img-top" alt="غلاف الكتاب"
                            style="height: 300px; object-fit: cover;">
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>

                            <div class="d-flex justify-content-between align-items-center">
                                <?php if($is_discounted): ?>
                                    <div>
                                        <span class="text-danger fs-5 fw-bold">
                                            <?= number_format($discounted_price) ?>
                                        </span>
                                        <span class="text-decoration-line-through text-muted ms-2">
                                            <?= number_format($book['price']) ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-success"><?= number_format($book['price']) ?> ل.س</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- الأيقونات -->
                            <div class="d-flex justify-content-between mt-3">
                                <!-- أيقونة التفاصيل -->
                                <button class="btn btn-info btn-sm"
                                    onclick="window.location.href='details.php?id=<?= $book['id'] ?>'">
                                    <i class="fas fa-info"></i>
                                </button>
                                
                                <!-- إزالة من المفضلة -->
                                <button class="btn btn-danger btn-sm remove-favorite" 
                                    data-book-id="<?= $book['id'] ?>">
                                    <i class="fas fa-trash"></i>
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
                                    data-book-price="<?= $book['price'] ?>"
                                    data-book-image="<?= $book['cover_image'] ?>">
                                <i class="fas fa-cart-plus"></i>
                                </button>
                                <?php else: ?>
                                <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#loginModal">
                                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center py-5">
            <i class="fas fa-info-circle fa-3x mb-3"></i>
            <h3>لم تقم بإضافة أي كتب إلى المفضلة بعد</h3>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // تهيئة الكاروسيل
    $('.owl-carousel').owlCarousel({
        rtl: true,
        margin: 20,
        nav: true,
        dots: false,
        responsive: {
            0: { items: 1 },
            600: { items: 3 },
            1000: { items: 5 }
        },
        navText: [
            '<i class="fas fa-chevron-right"></i>',
            '<i class="fas fa-chevron-left"></i>'
        ]
    });

    // معالجة إزالة من المفضلة
    $(document).on('click', '.remove-favorite', function() {
        const button = $(this);
        const bookId = button.data('book-id');
        
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "هل تريد إزالة هذا الكتاب من المفضلة؟",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، إزالة',
            cancelButtonText: 'إلغاء',
            customClass: {
                confirmButton: 'btn btn-danger mx-2',
                cancelButton: 'btn btn-secondary mx-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'toggle_favorite.php',
                    method: 'POST',
                    data: {
                        book_id: bookId,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            // إزالة البطاقة من الواجهة
                            button.closest('.item').fadeOut(300, function() {
                                $(this).remove();
                                
                                // التحقق إذا لم يعد هناك كتب
                                if ($('.item').length === 0) {
                                    $('.owl-carousel').replaceWith(`
                                        <div class="alert alert-info text-center py-5">
                                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                                            <h3>لم تقم بإضافة أي كتب إلى المفضلة بعد</h3>
                                        </div>
                                    `);
                                }
                            });
                            
                            // إظهار رسالة نجاح
                            Swal.fire({
                                icon: 'success',
                                title: 'تمت الإزالة',
                                text: 'تمت إزالة الكتاب من المفضلة',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('خطأ!', response.message || 'فشلت العملية', 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('خطأ!', 'حدث خطأ في الاتصال بالخادم', 'error');
                        console.error('AJAX Error:', xhr.responseText);
                    }
                });
            }
        });
    });
});
// كشف الأخطاء العامة
window.onerror = function(msg, url, line) {
    console.error(`Error: ${msg}\nURL: ${url}\nLine: ${line}`);
    return true;
};

// كشف أخطاء jQuery
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    console.error(`AJAX Error: ${thrownError}\nURL: ${settings.url}`);
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>