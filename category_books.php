<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
?>

<!-- إضافة مكتبة Owl Carousel -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">

<style>
    /* تحسينات التصميم */
    .category-card {
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        height: 100%;
    }
    
    .category-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    }
    
    .category-header {
        background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
        color: white;
        padding: 15px;
        border-radius: 12px 12px 0 0;
    }
    
    .book-card {
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
    
    .book-img {
        height: 250px;
        object-fit: cover;
        border-radius: 10px 10px 0 0;
    }
    
    .rating {
        color: #FFD700;
        font-size: 18px;
        margin-top: 8px;
    }
    
    .btn-view {
        background: linear-gradient(to right, #4a5568, #2d3748);
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-view:hover {
        background: linear-gradient(to right, #2d3748, #1a202c);
        transform: translateY(-2px);
    }
    
    .category-badge {
        background: linear-gradient(to right, #4a5568, #2d3748);
        color: white;
        border-radius: 20px;
        padding: 8px 15px;
        font-size: 14px;
        margin: 5px;
        display: inline-block;
    }
    
    .section-title {
        position: relative;
        padding-bottom: 15px;
        margin-bottom: 25px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 120px;
        height: 2px;
        background: linear-gradient(to right, #4a5568, #2d3748);
    }
    
    .category-img {
        height: 180px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .category-link {
        text-decoration: none;
        color: inherit;
        transition: all 0.3s;
    }
    
    .category-link:hover {
        text-decoration: none;
        color: #2d3748;
    }
    
    .category-name {
        font-weight: 600;
        font-size: 18px;
        margin-top: 10px;
    }
    
    .book-count {
        background-color: #e9ecef;
        color: #4a5568;
        border-radius: 15px;
        padding: 3px 10px;
        font-size: 12px;
        display: inline-block;
    }
    
    .search-box {
        background-color: white;
        border-radius: 50px;
        padding: 8px 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 25px;
        max-width: 600px;
        margin: 30px auto;
    }
    
    /* جديد: تنسيقات السلايدر */
    .owl-carousel {
        overflow: hidden !important;
    }
    
    .owl-stage {
        display: flex !important;
        justify-content: flex-start !important;
    }
    
    .owl-carousel .item {
        padding: 10px;
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
        color: #333 !important;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .owl-prev:hover,
    .owl-next:hover {
        background: rgba(0, 0, 0, 0.3) !important;
    }
    
    .owl-prev {
        left: -50px;
    }
    
    .owl-next {
        right: -50px;
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
        background-color: #303a4b;
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
</style>

<div class="container mt-5 mb-5">
    <!-- شريط البحث - تم تعديله ليكون في المنتصف تماماً -->
    <div class="search-box">
        <form action="" method="get">
            <div class="input-group rounded-pill shadow-sm">
                <input type="text" class="form-control border-0 bg-light py-3 px-4" 
                       placeholder="ابحث عن تصنيف..." 
                       name="search" 
                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                       style="border-top-right-radius: 50px; border-bottom-right-radius: 50px;">
                <button class="btn btn-primary border-0 px-4" type="submit"
                        style="border-top-left-radius: 50px; border-bottom-left-radius: 50px;">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
    
    <!-- عرض جميع التصنيفات -->
    <div class="mb-5">
        <div class="divider">
            <span class="divider-text">جميع التصنيفات</span>
        </div>
        
        <?php
        // استعلام لاسترجاع جميع التصنيفات مع عدد الكتب في كل تصنيف
        $searchTerm = isset($_GET['search']) ? "%{$_GET['search']}%" : '%';
        $stmt = $conn->prepare("
            SELECT c.category_id, c.category_name, COUNT(b.id) AS book_count
            FROM categories c
            LEFT JOIN books b ON c.category_id = b.category_id
            WHERE c.category_name LIKE ?
            GROUP BY c.category_id
            ORDER BY book_count DESC
        ");
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $allCategories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        ?>
        
        <?php if (!empty($allCategories)): ?>
            <div class="row">
                <?php foreach ($allCategories as $category): ?>
                    <div class="col-md-3 mb-4">
                        <a href="#category-<?= $category['category_id'] ?>" class="category-link">
                            <div class="category-card">
                                <div class="category-header text-center">
                                    <h5><?= htmlspecialchars($category['category_name']) ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center">
                                        <span class="book-count"><?= $category['book_count'] ?> كتاب</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                لا توجد تصنيفات تطابق بحثك.
            </div>
        <?php endif; ?>
    </div>
    
    <!-- عرض الكتب حسب التصنيفات -->
    <?php foreach ($allCategories as $category): ?>
        <div id="category-<?= $category['category_id'] ?>" class="mb-5">
            <div class="divider">
                <span class="divider-text"><?= htmlspecialchars($category['category_name']) ?></span>
            </div>
            
            <?php
            // استعلام لاسترجاع الكتب في هذا التصنيف
            $stmt = $conn->prepare("
                SELECT b.id, b.title, b.author, b.cover_image, b.description, b.evaluation
                FROM books b
                WHERE b.category_id = ?
                ORDER BY b.created_at DESC
                LIMIT 10
            ");
            $stmt->bind_param("i", $category['category_id']);
            $stmt->execute();
            $categoryBooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            ?>
            
            <?php if (!empty($categoryBooks)): ?>
                <div class="owl-carousel owl-theme">
                    <?php foreach ($categoryBooks as $book): ?>
                        <div class="item">
                            <div class="card book-card h-100">
                                <img src="<?= htmlspecialchars($book['cover_image']) ?>" class="card-img-top book-img" 
                                    alt="<?= htmlspecialchars($book['title']) ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                                    <p class="card-text text-muted"><?= htmlspecialchars($book['author']) ?></p>
                                    
                                    <div class="rating">
                                        <?php
                                        $rating = $book['evaluation'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                    
                                    <p class="card-text mt-2"><?= 
                                        mb_strlen($book['description']) > 100 
                                            ? mb_substr($book['description'], 0, 100) . '...' 
                                            : $book['description'] 
                                    ?></p>
                                </div>
                                <div class="card-footer bg-white border-0">
                                    <a href="details.php?id=<?= $book['id'] ?>" class="btn btn-view btn-block">
                                        عرض التفاصيل
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-3">
                    <a href="category_details.php?category_id=<?= $category['category_id'] ?>" class="btn btn-outline-secondary">
                        عرض المزيد في هذا التصنيف
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    لا توجد كتب متاحة في هذا التصنيف حالياً.
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- إضافة مكتبات JS للسلايدر -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script>
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
                    items: 2
                },
                1000: {
                    items: 4
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
            options.nav = (itemCount > options.responsive[0].items);
        } else {
            options.loop = true;
        }

        $carousel.owlCarousel(options);
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>