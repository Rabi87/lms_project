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

.cat-wrapper {// إعادة تعيين مؤشرات النتائج
$new_books_result->data_seek(0);
$discounted_books_result->data_seek(0);
$bestsellers_result->data_seek(0);
$books_result->data_seek(0);
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


/* الحاوية الرئيسية */
.main-container {
    max-width: 1200px;
    max-height: 400px;
    margin: 0 auto 30px;
    padding: 0 15px;
    display: flex;
    gap: 20px;
    
}
/* قسم السلايدر */
.slideshow-container {
    position: relative;
    flex: 0 0 100%;
    height: 430px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.mySlides {
    display: none;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 1s ease-in-out;
}

.mySlides.active {
    display: block;
    opacity: 1;
}

.mySlides img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    
}
/* أزرار التحكم */
.prev, .next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    padding: 16px;
    color: white;
    font-weight: bold;
    font-size: 20px;
    cursor: pointer;
    background: rgba(0,0,0,0.3);
    z-index: 100;
}

.next { right: 0; }
.prev { left: 0; }

/* المؤشرات */
/* المؤشرات */
.indicators {
    position: absolute;
    bottom: 15px;
    width: 100%;
    text-align: center;
    z-index: 100;
}

.indicator {
    display: inline-block; /* تغيير من none إلى inline-block */
    width: 12px;
    height: 12px;
    margin: 0 5px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
    transition: background 0.3s ease;
}

.indicator.active {
    background: white;
}

/* التصميم المعدل للبطاقات */
.cat-wrapper {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    position: relative;
    margin-bottom: 30px;
    padding: 0 15px;
}

.cat-card {
    height: 100px;
    background: #fff;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #000;
    font-style: bold;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.cat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    
    opacity: 0;
    z-index: -1;
    transition: opacity 0.3s ease;
}

.cat-card:hover {
    transform: translateY(-10px);
    color: #A12;
    shadow: 10px 10px 25px rgba(0,0,0,0.2);
}

.cat-card:hover::before {
    opacity: 1;
}

.cat-card h1 {
    margin: 0;
    font-size: 3rem;
    transition: all 0.3s ease;
}

.cat-card:hover h1 {
    transform: scale(1.1);
}

/* التجاوبية */
@media (max-width: 992px) {
    .cat-wrapper {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .cat-wrapper {
        grid-template-columns: 1fr;
    }
    
    .cat-card {
        height: 90px;
        font-size: 1.1rem;
    }
    
    .cat-card h1 {
        font-size: 1.3rem;
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
<div class="container my-5">

<div class="main-container">
    <div class="slideshow-container">
        <?php
        $slides = $conn->query("SELECT * FROM slider_images WHERE is_active = 1");
        $first = true;
        while ($slide = $slides->fetch_assoc()):
        ?>
        <div class="mySlides <?= $first ? 'active' : '' ?>">
            <img src="<?= BASE_URL . $slide['image_path'] ?>" alt="Slider Image">
        </div>       
        <?php $first = false; endwhile; ?>
        <a class="prev" onclick="changeSlide(-1)">❮❮</a>
            <a class="next" onclick="changeSlide(1)">❯❯</a>
        <!-- المؤشرات -->
        <div class="indicators">
            <?php for ($i = 0; $i < $slides->num_rows; $i++): ?>
                <span class="indicator <?= $i === 0 ? 'active' : '' ?>" onclick="showSlide(<?= $i ?>)"></span>
            <?php endfor; ?>
        </div>
    
     <!-- أزرار التحكم -->
        
    </div>  
</div>  
</div>
<!--  التصنيفات -->
<div class="cat-wrapper">
    <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
        <a href="all_books.php?type=&search=&category=0&author=&rating=0&material=كتاب" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>كتب</h1>
    </a>
    </div>
     <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
        <a href="all_books.php?type=&search=&category=0&author=&rating=0&material=مجلة" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>مجلات</h1>
    </a>
    </div>
     <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
       <a href="all_books.php?type=&search=&category=0&author=&rating=0&material=جريدة" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>صحف</h1>
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
            $book_id = $book['id'];
            $is_favorite = in_array($book_id, $favorites);
        ?>
        <div class="item">
            <div class="card h-100 shadow">
                <?php if($is_discounted): ?>
                <div class="discount-ribbon">
                    خصم <?= $book['discount_percentage'] ?>%
                </div>
                <?php endif; ?>

                <?php if(!empty($book['cover_image'])): ?>
                <img src="<?= $book['cover_image'] ?>" class="card-img-top" alt="غلاف الكتاب"
                    style="height: 300px; object-fit: cover;">
                <?php endif; ?>
                
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>
                    
                    <!-- عرض الأسعار -->
                    <?php if($is_discounted): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-info">
                            <i class="fas fa-calendar-alt"></i>
                            <?= date('Y-m-d', strtotime($book['created_at'])) ?>
                        </span>
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
                        <a href="login.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-sign-in-alt"></i>
    </a>
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
   <div class="cat-card" style="background-image: url('assets/1.jpeg');">       
        <a href="all_books.php?type=&search=&category=2&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>علمية</h1>
        </a>
    </div>
    
     <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
        <a href="all_books.php?type=&search=&category=3&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>تاريخية</h1>
        </a>
    </div>
     <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
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
        <?php while($book = $discounted_books_result->fetch_assoc()):
             $book_id = $book['id'];
            $is_favorite = in_array($book_id, $favorites);
            ?>
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
                          <a href="login.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-sign-in-alt"></i>
    </a>
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
    <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
        <a href="all_books.php?type=&search=&category=8&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>أطفال</h1>
    </a>
    </div>
     <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
        <a href="all_books.php?type=&search=&category=4&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>برمجة</h1>
    </a>
    </div>
     <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
       <a href="all_books.php?type=&search=&category=5&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
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
             $book_id = $book['id'];
            $is_favorite = in_array($book_id, $favorites);
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
                          <a href="login.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-sign-in-alt"></i>
    </a>
                        <?php endif; ?>

                    </div>


                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <!-- زر المزيد -->
    <div class="text-center mt-3">
        <a href="all_books.php?type=bestsellers&section=bestsellers" class="btn btn-outline-primary load-more">
            عرض المزيد </i>
        </a>
    </div>



    <?php else: ?>
    <div class="alert alert-info">لا توجد بيانات عن الكتب الأكثر مبيعًا</div>
    <?php endif; ?>
</div>

      <!--  التصنيفات -->
<div class="cat-wrapper">
    <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
        <a href="all_books.php?type=&search=&category=13&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>سياسة</h1>
    </a>
    </div>
     <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
        <a href="all_books.php?type=&search=&category=10&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
        <h1>أقتصاد</h1>
    </a>
    </div>
     <div class="cat-card" style="background-image: url('assets/1.jpeg');">  
       <a href="all_books.php?type=&search=&category=12&author=&rating=0" class="static-card-link" style="text-decoration: none; color: inherit;">
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
             $book_id = $book['id'];
            $is_favorite = in_array($book_id, $favorites);
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
                          <a href="login.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-sign-in-alt"></i>
    </a>
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
 
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.mySlides');
    if (slides.length === 0) return;

    let currentIndex = 0;
    let slideInterval;

    function showSlide(index) {
    // إخفاء جميع الشرائح
    slides.forEach(slide => {
        slide.classList.remove('active');
    });
    
    // إظهار الشريحة المطلوبة
    slides[index].classList.add('active');
    currentIndex = index;
    
    // تحديث المؤشرات
    const indicators = document.querySelectorAll('.indicator');
    indicators.forEach((indicator, i) => {
        if (i === index) {
            indicator.classList.add('active');
        } else {
            indicator.classList.remove('active');
        }
    });
}
    function nextSlide() {
        let nextIndex = (currentIndex + 1) % slides.length;
        showSlide(nextIndex);
    }

    function startAutoSlide() {
        if (slides.length > 1) {
            slideInterval = setInterval(nextSlide, 7000);
        }
    }

    function stopAutoSlide() {
        clearInterval(slideInterval);
    }

    // بدء التمرير التلقائي
    startAutoSlide();
    
    // إيقاف التمرير عند تحويم الماوس
    const sliderContainer = document.querySelector('.slideshow-container');
    if (sliderContainer) {
        sliderContainer.addEventListener('mouseenter', stopAutoSlide);
        sliderContainer.addEventListener('mouseleave', startAutoSlide);
    }
    window.changeSlide = function(n) {
    stopAutoSlide();
    let newIndex = currentIndex + n;
    
    if (newIndex < 0) newIndex = slides.length - 1;
    else if (newIndex >= slides.length) newIndex = 0;
    
    showSlide(newIndex);
    startAutoSlide();
}

window.showSlide = function(index) {
    stopAutoSlide();
    showSlide(index);
    startAutoSlide();
}
});
// الكاروسيل
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
        dataType: 'json',
        success: function(response) {
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

<?php

require __DIR__ . '/includes/footer.php';?>