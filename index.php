<?php
// بدء الجلسة إذا لم تكن بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/lang.php';
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
// إعدادات عرض الأخطاء (للتطوير فقط)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// استعلام الصور المنزلقة (Slider)
$slider_query = "SELECT * FROM slider_images WHERE is_active = 1 ORDER BY created_at DESC";
$slider_result = $conn->query($slider_query);
$slides = [];
if ($slider_result && $slider_result->num_rows > 0) {
    while ($row = $slider_result->fetch_assoc()) {
        $slides[] = $row;
    }
}

$favorites = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_query = "SELECT book_id FROM favorite_books WHERE user_id = $user_id";
    $fav_result = $conn->query($fav_query);
    while ($row = $fav_result->fetch_assoc()) {
        $favorites[] = $row['book_id'];
    }
}


// استعلام الكتب المخفضة
$discounted_books_query = "
    SELECT *,
        (price - (price * (discount_percentage / 100))) AS discounted_price
    FROM books
    WHERE has_discount = 1
    ORDER BY discount_percentage DESC
    LIMIT 10
";
$discounted_books_result = $conn->query($discounted_books_query);

// استعلام أحدث الكتب المضافة (معدّل)
$new_books_query = "
    SELECT *,
        IF(has_discount = 1, 
            (price - (price * (discount_percentage / 100))), 
            NULL
        ) AS discounted_price
    FROM books 
    ORDER BY created_at DESC 
    LIMIT 10
";
$new_books_result = $conn->query($new_books_query);

// استبدال الاستعلام العام بجلب البيانات مع التحقق من الأخطاء
// استعلام قسم المكتبة (معدّل)
$books_query = "
    SELECT *,
        IF(has_discount = 1, 
            (price - (price * (discount_percentage / 100))), 
            NULL
        ) AS discounted_price
    FROM books
";

$books_result = $conn->query($books_query);

// استعلام الكتب الأكثر مبيعًا (معدّل)
$bestsellers_query = "
    SELECT 
        b.*, 
        COUNT(br.id) AS sales_count,
        IF(b.has_discount = 1, 
            (b.price - (b.price * (b.discount_percentage / 100))), 
            NULL
        ) AS discounted_price
    FROM books b
    LEFT JOIN borrow_requests br 
        ON b.id = br.book_id 
        AND br.status = 'approved'
    GROUP BY b.id
    ORDER BY sales_count DESC
    LIMIT 10
";
$bestsellers_result = $conn->query($bestsellers_query);

// التحقق من وجود أخطاء في الاستعلام
if (!$books_result) {
    die("خطأ في الاستعلام: " . $conn->error);
}


?>

<style>
.owl-carousel {
    overflow: hidden !important;
    /* إصلاح مشكلة التمرير الزائد */
}

/* إجبار الكاروسيل على عدم التكرار عندما تكون العناصر قليلة */
.owl-stage {
    display: flex !important;
    justify-content: flex-start !important;
}

.owl-carousel .item {
    padding: 10px;
}

.owl-carousel .card {
    border-radius: 15px;
    transition: transform 0.3s;
}

.owl-carousel .card:hover {
    transform: translateY(-10px);
}

.owl-nav {
    position: absolute;
    top: 50%;
    width: 100%;
    display: flex;
    justify-content: space-between;
    transform: translateY(-50%);
}

.owl-prev,
.owl-next {
    width: 40px;
    height: 40px;
    border-radius: 50% !important;
    background: rgba(0, 0, 0, 0.1) !important;
}

/* تنسيقات الكتب المخفضة */
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
.divider {
  position: relative;
  margin: 30px 0;
  text-align: center;
}

.divider::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 0;
  width: 100%;
  height: 2px;
  background-color: #303a4b; /* لون الخط */
  transform: translateY(-50%);
  z-index: 1;
}

.divider-text {
  position: relative;
  display: inline-block;
  padding: 10px 20px;
 background-color:#E7EDF0;
  color: #303a4b;
  font-size: 25px;
  z-index: 2;
 border: 1px solid #303a4b;
  border-radius: 5px;
}

 .test{
     text-align: center;
  margin-bottom: 20px;
  font-size: 24px;
  color:#0d6efd;
  font-weight: bold;
  display: inline-block;
  padding: 10px 20px;
  
  border: 1px solid #0d6efd;
  border-radius: 5px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.slider-wrapper {
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    margin-bottom: 30px;
}

.static-card {
    flex: 0 0 200px;
    height: 350px;
    background:rgb(255, 255, 255);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #000;
    text-align: center;
}

.cat-wrapper {
    display: flex;0d6efd
    align-items: center;
    gap: 20px;
    position: relative;
    margin-bottom: 30px;
    
}

.cat-card {
    flex: 0 0 420px;
    height: 100px;
    background:rgb(255, 255, 255);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #000;
    text-align: center;
    transition: transform 0.3s;
    
}
.cat-card:hover{
    transform: translateY(-10px);
    background:#303a4b;
    color: #fff;
}

.slider-container {
    flex: 1;
    overflow: hidden;
    position: relative;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    height: 400px; /* تحديد ارتفاع ثابت */
}

.slider {
    display: flex;
    transition: transform 0.5s ease-in-out;
    width: 100%;
    height: 100%; /* استخدام الارتفاع الكامل */
}

.slide {
    min-width: 100%;
    flex-shrink: 0;
    position: relative;
    height: 100%; /* استخدام الارتفاع الكامل */
}

.slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 15px;
}

.slider-controls {
    position: absolute;
    top: 50%;
    width: 100%;
    display: flex;
    justify-content: space-between;
    transform: translateY(-50%);
    padding: 0 20px;
    z-index: 10;
}

.slider-controls button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.7) !important;
    border: none;
    color: #333;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.slider-controls button:hover {
    background: rgba(0, 0, 0, 0.7) !important;
    color: white;
    transform: scale(1.1);
}

.slider-indicators {
    position: absolute;
    bottom: 20px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: 10px;
    z-index: 10;
}

.slider-indicators span {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

.slider-indicators span.active {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.2);
}

.slide-caption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 15px 20px;
    border-bottom-left-radius: 15px;
    border-bottom-right-radius: 15px;
    z-index: 5;
}

.slide-caption h3 {
    margin-bottom: 5px;
    font-size: 1.2rem;
}

@media (max-width: 992px) {
    .slider-wrapper {
        flex-direction: column;
    }
    
    .static-card {
        width: 100%;
        height: auto;
        margin-bottom: 20px;
        flex: none;
    }
    
    .slider-container {
        height: 350px;
    }
}

@media (max-width: 576px) {
    .slider-container {
        height: 250px;
    }
    
    .slider-controls button {
        width: 30px;
        height: 30px;
        font-size: 1rem;
    }
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

<div class="slider-wrapper">
    <div class="static-card">
        <i class="fas fa-tag fa-3x mb-3 text-primary"></i>
        <h5>عروض خاصة</h5>
        <p class="mt-2">خصومات تصل إلى 50% على مجموعة مختارة من الكتب</p>
    </div>

    <div class="slider-container">
        <div class="slider" id="main-slider">
            <?php foreach ($slides as $index => $slide): ?>
            <div class="slide">
                <img src="<?= BASE_URL . $slide['image_path'] ?>" alt="صورة <?= $index + 1 ?>">
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="slider-controls">
            <button id="prev-slide"><i class="fas fa-chevron-right"></i></button>
            <button id="next-slide"><i class="fas fa-chevron-left"></i></button>
        </div>
        
        <?php if (count($slides) > 0): ?>
        <div class="slider-indicators" id="slider-indicators">
            <?php for ($i = 0; $i < count($slides); $i++): ?>
            <span <?= $i === 0 ? 'class="active"' : '' ?> data-index="<?= $i ?>"></span>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>


    <div class="dot-container">
        <?php for ($i = 0; $i < count($slides); $i++): ?>
            <span class="dot" onclick="currentSlide(<?php echo $i + 1; ?>)"></span>
        <?php endfor; ?>
    </div>

    
    <div class="static-card">
        <i class="fas fa-star fa-3x mb-3 text-warning"></i>
        <a href="category_books.php" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h5>التصنيفات العامة</h5>
        <p class="mt-2">اكتشف الكتب حسب التصنيفات المعتمدة في المنصة</p>
        </a>
    </div>

</div>

<!-- أحدث الكتب المضافة -->
<div class="container my-5">
    <div class="divider">
    <?php if ($new_books_result->num_rows > 0): ?>
    <span class="divider-text"><?= __('latest_additions') ?></span>
    </div>
    <div class="owl-carousel owl-theme">
        <?php while($book = $new_books_result->fetch_assoc()): 
            $is_discounted = ($book['has_discount'] == 1);
        ?>
        <div class="item">
            <div class="card h-100 shadow">
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
                    
                    <!-- عرض الأسعار -->
                    <?php if($is_discounted): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-danger fs-5 fw-bold">
                                <?= number_format($book['discounted_price']) ?>
                            </span>
                            <span class="text-decoration-line-through text-muted ms-2">
                                <?= number_format($book['price']) ?>
                            </span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-info">
                            <i class="fas fa-calendar-alt"></i>
                            <?= date('Y-m-d', strtotime($book['created_at'])) ?>
                        </span>
                        <span class="text-success"><?= number_format($book['price']) ?> ل.س</span>
                    </div>
                    <?php endif; ?>
                    <!-- الأيقونات -->
                    <div class="d-flex justify-content-between mt-3">

                        <!-- أيقونة التفاصيل -->
                        <button class="btn btn-info btn-sm"
                            onclick="window.location.href='details.php?id=<?= $book['id'] ?>'">
                            <i class="fas fa-info"></i>
                        </button>
                        <!-- داخل كل بطاقة كتاب -->
                        <button
                            class="btn btn-sm <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite"
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
    <!-- زر المزيد -->
    <div class="text-center mt-3">
        <a href="all_books.php?type=new" class="btn btn-outline-primary load-more">
            عرض المزيد </i>
        </a>
    </div>
    <?php else: ?>
    <div class="alert alert-info">لا توجد كتب جديدة</div>
    <?php endif; ?>
</div>

    <!--  التصنيفات -->
<div class="cat-wrapper">
    <div class="cat-card">
        <a href="all_books.php?type=&search=&category=2&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>العلمية</h1>
        </a>
    </div>
     <div class="cat-card">
        <a href="all_books.php?type=&search=&category=3&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>تاريخية</h1>
        </a>
    </div>
     <div class="cat-card">
       <a href="all_books.php?type=&search=&category=1&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>أدبية</h1>
        </a>
    </div>
  
</div>

<!-- الكتب المخفضة -->
<div class="container my-5">
    <div class="divider">
    <?php if ($discounted_books_result->num_rows > 0): ?>
    <span class="divider-text"><?= __('discounts')?></span>
    </div>
    <div class="owl-carousel owl-theme">
        <?php while($book = $discounted_books_result->fetch_assoc()): ?>
        <div class="item">
            <div class="card h-100 shadow position-relative">
                <!-- شريط الخصم -->
                <div class="discount-ribbon">
                    خصم <?= $book['discount_percentage'] ?>%
                </div>

                <?php if(!empty($book['cover_image'])): ?>
                <img src="<?= BASE_URL . $book['cover_image'] ?>" class="card-img-top" alt="غلاف الكتاب"
                    style="height: 300px; object-fit: cover;">
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-danger fs-5 fw-bold">
                                <?= number_format($book['discounted_price']) ?>
                            </span>
                            <span class="text-decoration-line-through text-muted ms-2">
                                <?= number_format($book['price']) ?>
                            </span>
                        </div>
                    </div>
                    <!-- الأيقونات -->
                    <div class="d-flex justify-content-between mt-3">

                        <!-- أيقونة التفاصيل -->
                        <button class="btn btn-info btn-sm"
                            onclick="window.location.href='details.php?id=<?= $book['id'] ?>'">
                            <i class="fas fa-info"></i>
                        </button>
                        <!-- داخل كل بطاقة كتاب -->
                        <button
                            class="btn btn-sm <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite"
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
    <!-- زر المزيد -->
    <div class="text-center mt-3">
        <a href="all_books.php?type=discounted" class="btn btn-outline-primary load-more">
            عرض المزيد </i>
        </a>
    </div>

    <?php else: ?>
    <div class="alert alert-info text-center">لا توجد عروض خاصة حالياً</div>
    <?php endif; ?>
</div>

      <!--  التصنيفات -->
<div class="cat-wrapper">
    <div class="cat-card">
        <a href="category_books.php" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>أطفال</h1>
        </a>
    </div>
     <div class="cat-card">
        <a href="category_books.php" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>برمجة</h1>
        </a>
    </div>
     <div class="cat-card">
       <a href="category_books.php" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>ذكاء</h1>
        </a>
    </div>
  
</div>

 
<!-- قسم الكتب الأكثر مبيعًا -->
<div class="container my-5">
    <div class="divider">
    <?php if ($bestsellers_result->num_rows > 0): ?>
    <span class="divider-text"><?=  __('bestsellers') ?></span>
    </div>
    <div class="owl-carousel owl-theme bestsellers-carousel">
        <?php while($book = $bestsellers_result->fetch_assoc()): 
            $is_discounted = ($book['has_discount'] == 1);
        ?>
        <div class="item">
            <div class="card h-100 shadow">
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
                        <span class="badge bg-danger"> <?= $book['sales_count'] ?></span>
                        
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
                        <!-- داخل كل بطاقة كتاب -->
                        <button
                            class="btn btn-sm <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite"
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
    <!-- زر المزيد -->
    <div class="text-center mt-3">
        <a href="all_books.php?type=bestsellers" class="btn btn-outline-primary load-more">
            عرض المزيد </i>
        </a>
    </div>
    <?php else: ?>
    <div class="alert alert-info">لا توجد بيانات عن الكتب الأكثر مبيعًا</div>
    <?php endif; ?>
</div>

      <!--  التصنيفات -->
<div class="cat-wrapper">
    <div class="cat-card">
        <a href="category_books.php" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>سياسة</h1>
        </a>
    </div>
     <div class="cat-card">
        <a href="category_books.php" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>أقتصاد</h1>
        </a>
    </div>
     <div class="cat-card">
       <a href="category_books.php" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>فنون</h1>
        </a>
    </div>
  
</div>

 
<!-- قسم كل محتويات المكتبة -->
<div class="container my-5">
     <div class="divider">
    <?php if ($books_result->num_rows > 0): ?>
    <span class="divider-text"><?=  __('library')  ?></span>
    </div>
  
    <div class="owl-carousel owl-theme">
        <?php while($book = $books_result->fetch_assoc()): 
            $is_discounted = ($book['has_discount'] == 1);
        ?>
        <div class="item">
            <div class="card h-100 shadow">
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
                    
                    <div class="d-flex justify-content-between">
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
                        <!-- داخل كل بطاقة كتاب -->
                        <button
                            class="btn btn-sm <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite"
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
    <!-- زر المزيد -->
    <div class="text-center mt-3">
        <a href="all_books.php" class="btn btn-outline-primary load-more">
            عرض المزيد </i>
        </a>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">لا توجد كتب متاحة حالياً</div>
    <?php endif; ?>
</div>

<!-- قبل إغلاق body -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>


<script>
// إضافة إلى المفضلة
$(document).on('click', '.toggle-favorite', function() {
    <?php if(!isset($_SESSION['user_id'])): ?>
    Swal.fire('تنبيه!', 'يجب تسجيل الدخول أولاً', 'warning');
    return;
    <?php endif; ?>

    const bookId = $(this).data('book-id');
    $.post("<?= BASE_URL ?>toggle_favorite.php", {
        book_id: bookId
    }, function(response) {
        if (response.is_favorite) {
            Swal.fire('تم!', 'أضيف إلى المفضلة', 'success');
        } else {
            Swal.fire('تم!', 'حُذف من المفضلة', 'info');
        }
    }).fail(() => Swal.fire('خطأ!', 'حدث خطأ غير متوقع', 'error'));
    });
$(document).ready(function() {
    $('.owl-carousel').each(function() {
        var $carousel = $(this);
        var itemCount = $carousel.find('.item').length;

        // تحديد الإعدادات الديناميكية
        var options = {
            rtl: true,
            margin: 20,
            nav: true,
            dots: false,
            responsive: {
                0: {
                    items: 1
                },
                600: {
                    items: 3
                },
                1000: {
                    items: 5
                }
            },
            navText: [
                '<i class="fas fa-chevron-right"></i>',
                '<i class="fas fa-chevron-left"></i>'
            ]
        };

        // تعطيل التكرار إذا كانت العناصر أقل من الحد الأقصى
        if (itemCount <= options.responsive[1000].items) {
            options.loop = false;
            options.nav = (itemCount > options.responsive[0]
                .items); // تعطيل الأسهم إذا كانت العناصر أقل من عرض الشاشة
        } else {
            options.loop = true;
        }

        $carousel.owlCarousel(options);
    });
});
// إضافة/إزالة من المفضلة
$(document).on('click', '.toggle-favorite', function() {
    <?php if(!isset($_SESSION['user_id'])): ?>
    Swal.fire('تنبيه!', 'يجب تسجيل الدخول أولاً', 'warning');
    return;
    <?php endif; ?>

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
                Swal.fire('تم!', 'أضيف إلى المفضلة', 'success');
            } else {
                button.removeClass('btn-danger').addClass('btn-outline-danger');
                Swal.fire('تم!', 'حُذف من المفضلة', 'info');
            }
        },
        error: function() {
            Swal.fire('خطأ!', 'فشلت العملية', 'error');
        }
    });
});
$(document).ready(function() {
    // سكريبت السلايدر الجديد
    const slider = $('#main-slider');
    const slides = slider.find('.slide');
    const slideCount = slides.length;
    
    if (slideCount > 0) {
        const indicators = $('#slider-indicators span');
        let currentIndex = 0;
        let autoSlideInterval;

        // تحديد عرض الحاوية
        const container = $('.slider-container');
        const containerWidth = container.width();
        
        // تعيين عرض كل شريحة وعرض السلايدر الكلي
        slides.css('width', containerWidth + 'px');
        slider.css('width', (slideCount * containerWidth) + 'px');
        
        // تحديث العرض عند تغيير حجم النافذة
        $(window).resize(function() {
            const newContainerWidth = container.width();
            slides.css('width', newContainerWidth + 'px');
            slider.css('width', (slideCount * newContainerWidth) + 'px');
            goToSlide(currentIndex);
        });

        function goToSlide(index) {
            if (index < 0) {
                index = slideCount - 1;
            } else if (index >= slideCount) {
                index = 0;
            }
            
            const slideWidth = slides.eq(0).width();
            slider.css('transform', 'translateX(-' + (index * slideWidth) + 'px)');
            
            if (indicators.length > 0) {
                indicators.removeClass('active');
                indicators.eq(index).addClass('active');
            }
            currentIndex = index;
        }

        function nextSlide() {
            goToSlide(currentIndex + 1);
        }

        function prevSlide() {
            goToSlide(currentIndex - 1);
        }

        function startAutoSlide() {
            if (slideCount > 1) {
                autoSlideInterval = setInterval(nextSlide, 5000);
            }
        }

        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
        }

        // أحداث الأزرار
        $('#next-slide').click(function() {
            stopAutoSlide();
            nextSlide();
            startAutoSlide();
        });

        $('#prev-slide').click(function() {
            stopAutoSlide();
            prevSlide();
            startAutoSlide();
        });

        // أحداث المؤشرات
        if (indicators.length > 0) {
            indicators.click(function() {
                stopAutoSlide();
                goToSlide(parseInt($(this).data('index')));
                startAutoSlide();
            });
        }

        // بدء التمرير التلقائي
        startAutoSlide();
        
        // إيقاف التمرير التلقائي عند تحويم الماوس
        container.hover(stopAutoSlide, startAutoSlide);
    }
});
    </script>

<?php

require __DIR__ . '/includes/footer.php';?>