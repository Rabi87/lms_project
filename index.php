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
    top: 30px;
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
    
    overflow: hidden;
    
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

/* التجاوبية */
@media (max-width: 992px) {
    .cat-wrapper {
        grid-template-columns: repeat(2, 1fr);
    }
}


.evel{
    color:red;
    text-align:center;
}
.stats-section {
    
    
    padding: 30px;
    margin: 30px 0;
   
}




.stat-value {
    color:rgb(153, 156, 162);
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.stat-label {
    color: #555;
    font-size: 1.2rem;
    margin-bottom: 0;
}
/* تنسيقات الـ cat-card الموجودة أصلاً - سأعدل بعضها */
.cat-card {
    height: 100px; /* زيادة الارتفاع لاستيعاب التصميم الجديد */
     border: 1px solid #303a4b;
    border-radius: 15px;
    padding: 15px;
    background:rgb(56, 66, 84);
    color:rgb(205, 207, 212);
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.cat-card:hover {
   
    background:rgb(56, 66, 84);
    box-shadow: 0 10px 25px rgba(83, 85, 87, 0.4);
}

.static-card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    width: 100%;
}
.cat-card .card-content {
    display: flex;
    align-items: center;
    gap: 20px;
    width: 70%;
    height: 100%;
}

.cat-card h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
    transition: all 0.3s ease;
}

.cat-card:hover h1 {
    transform: scale(1.1);
    color: #fff;
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


    <!-- قسم السلايدر -->   
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
        <a class="prev text-decoration-none" onclick="changeSlide(-1)">❮❮</a>
            <a class="next text-decoration-none" onclick="changeSlide(1)">❯❯</a>
        <!-- المؤشرات -->
        <div class="indicators">
            <?php for ($i = 0; $i < $slides->num_rows; $i++): ?>
                <span class="indicator <?= $i === 0 ? 'active' : '' ?>" onclick="showSlide(<?= $i ?>)"></span>
            <?php endfor; ?>
        </div>


        
    </div>  
    <!-- قسم الإحصائيات -->  
    <div class="row stats-section">
        <!-- مؤلفين -->
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card text-center p-4 rounded">
                <img src="assets/images/mobile-app.svg" class="card-img-top" alt="غلاف الكتاب"
                    style="height: 100px;width:100px; object-fit: cover;">
                <h3 class="stat-value"><?php 
                    $author_query = "SELECT COUNT(DISTINCT author) AS author_count FROM books";
                    $author_result = $conn->query($author_query);
                    $author_count = $author_result ? $author_result->fetch_assoc()['author_count'] : 0;
                    echo $author_count;
                ?></h3>
                <p class="stat-label">مؤلف</p>
                <span style="font-weight: lighter; font-size: 15px;color:rgb(151, 140, 140)">تهدف مكتبة LMS إلى إنشاء أكبر قاعدة بيانات لمؤلفين الكتب العربية عبر التاريخ</span>
            </div>
        </div>
        
        <!-- كتب -->
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card text-center p-4 rounded">
                <img src="assets/images/digital-library.svg" class="card-img-top" alt="غلاف الكتاب" style="height: 100px;width:100px; object-fit: cover;">
                <h3 class="stat-value"><?php 
                    $book_query = "SELECT COUNT(*) AS book_count FROM books";
                    $book_result = $conn->query($book_query);
                    $book_count = $book_result ? $book_result->fetch_assoc()['book_count'] : 0;
                    echo $book_count;
                ?></h3>
                <p class="stat-label">كتاب</p>
                <span style="font-weight: lighter; font-size: 15px;color:rgb(151, 140, 140)">آلاف الكتب المنشورة على مكتبة LMS منها ما نشره المؤلف بنفسه أو فريق المكتبة</span>
            </div>
        </div>
        
        <!-- عمليات بحث يومية -->
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card text-center p-4 rounded">
                <img src="assets/images/home_search.svg" class="card-img-top" alt="غلاف الكتاب"style="height: 100px;width:100px; object-fit: cover;">
                <h3 class="stat-value">1000</h3>
                <p class="stat-label">عملية بحث يومية</p>
                <span style="font-weight: lighter; font-size: 15px;color:rgb(151, 140, 140)">أكثر من 1000  عملية بحث عن كتاب عربي و أجنبي تحدث يومياً على مكتبة LMS</span>
            </div>
        </div>
        
        <!-- زوار شهرياً -->
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card text-center p-4 rounded">
                <img src="assets/images/online-education.svg" class="card-img-top" alt="غلاف الكتاب"style="height: 100px;width:100px; object-fit: cover;">
                <h3 class="stat-value">8,000</h3>
                <p class="stat-label">زائر شهرياً</p>
                <span style="font-weight: lighter; font-size: 15px;color:rgb(151, 140, 140)">يزور موقع مكتبة LMS اكثر من 8 آلاف زائر مهتم بالكتب العربية شهرياً حول العالم</span>
            </div>
        </div>
        
    </div>
    <!--  التصنيفات -->
    <div class="cat-wrapper">
        <!-- كتب -->
        <div class="cat-card">  
            <div class="card-content">
            <!-- <img src="assets/images/books.png" class="card-img" alt="كتب"> -->
                <a href="all_books.php?type=&search=&category=0&author=&rating=0&material=كتاب" class="btn static-card-link btn-outline">
                    <h1>كتب</h1>
                </a>
            </div>
        </div>
        
        <!-- مجلات -->
        <div class="cat-card">  
            <div class="card-content">
            <!-- <img src="assets/images/magazine.png" class="card-img" alt="مجلات"> -->
                <a href="all_books.php?type=&search=&category=0&author=&rating=0&material=مجلة" class="static-card-link">
                    <h1>مجلات</h1>
                </a>
            </div>
        </div>
        
        <!-- صحف -->
        <div class="cat-card">  
            <div class="card-content">
            <!--  <img src="assets/images/news.png" class="card-img" alt="صحف">-->
                <a href="all_books.php?type=&search=&category=0&author=&rating=0&material=جريدة" class="static-card-link">
                    <h1>صحف</h1>
                </a>
            </div>
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
                    <div class="evel">
                    <?php
                        $rating = $book['evaluation'];
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $rating ? '★' : '☆';
                        }
                        ?>
                    </div>

                    <?php if(!empty($book['cover_image'])): ?>
                    <img src="<?= $book['cover_image'] ?>" class="card-img-top" alt="غلاف الكتاب"
                        style="height: 300px; object-fit: cover;">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>
                        
                        <!-- عرض الأسعار -->
                    
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-info">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('Y-m-d', strtotime($book['created_at'])) ?>
                            </span>
                            <!-- التقييم -->
                                
                                
                            
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
            <a href="all_books.php?type=new" class="btn btn-outline-primary load-more">
                عرض المزيد </i>
            </a>
        </div>
        <?php else: ?>
        <div class="alert alert-info">لا توجد كتب جديدة</div>
        <?php endif; ?>
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
                    <div class="evel">
                    <?php
                        $rating = $book['evaluation'];
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $rating ? '★' : '☆';
                        }
                        ?>
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
                    <div class="evel">
                    <?php
                        $rating = $book['evaluation'];
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $rating ? '★' : '☆';
                        }
                        ?>
                    </div>
                    
                    <?php if(!empty($book['cover_image'])): ?>
                    <img src="<?= BASE_URL . $book['cover_image'] ?>" class="card-img-top" alt="غلاف الكتاب"
                        style="height: 300px; object-fit: cover;">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-danger"> <?= $book['sales_count'] ?></span>
                        
                            
                            
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
                    <div class="evel">
                    <?php
                        $rating = $book['evaluation'];
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $rating ? '★' : '☆';
                        }
                        ?>
                    </div>
                    <?php if(!empty($book['cover_image'])): ?>
                    <img src="<?= BASE_URL . $book['cover_image'] ?>" class="card-img-top" alt="غلاف الكتاب"
                        style="height: 300px; object-fit: cover;">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>
                        
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-primary"><?= $book['material_type'] ?></span>
        
                        
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


<?php require __DIR__ . '/includes/footer.php';?>