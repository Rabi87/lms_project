<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// جلب المفضلات للمستخدم
$favorites = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_query = "SELECT book_id FROM favorite_books WHERE user_id = $user_id";
    $fav_result = $conn->query($fav_query);
    while ($row = $fav_result->fetch_assoc()) {
        $favorites[] = $row['book_id'];
    }
}

// استعلام لاسترجاع تصنيفات المستخدم المفضلة
$stmt = $conn->prepare("
    SELECT c.category_id, c.category_name 
    FROM user_categories uc
    JOIN categories c ON uc.category_id = c.category_id
    WHERE uc.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$userCategories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// إذا لم يختر المستخدم أي تصنيفات
if (empty($userCategories)) {
    echo '<div class="alert alert-info mt-4 text-center">';
    echo 'لم تختر أي تصنيفات مفضلة بعد. <a href="user/dashboard.php?section=favorit">اختر تصنيفاتك المفضلة</a>';
    echo '</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// استخراج معرفات التصنيفات فقط
$categoryIds = array_column($userCategories, 'category_id');

// استعلام لاسترجاع الكتب المقترحة
$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
$types = str_repeat('i', count($categoryIds));

$query = "
    SELECT 
        b.id, 
        b.title, 
        b.author, 
        b.cover_image, 
        b.description, 
        b.evaluation,
        b.has_discount,
        b.discount_percentage,
        b.price,
        b.material_type,
        b.created_at,
        (b.price - (b.price * (b.discount_percentage / 100))) AS discounted_price
    FROM books b
    WHERE b.category_id IN ($placeholders)
    ORDER BY b.evaluation DESC
    LIMIT 12
";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$categoryIds);
$stmt->execute();
$recommendedBooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الكتب المقترحة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* تنسيقات مشابهة لصفحة index.php */
        body {
            background-color: #f5f7fa;
            font-family: 'Tahoma', 'Arial', sans-serif;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h2 {
            color: #0d6efd;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0d6efd;
            position: relative;
        }
        
        .category-badges {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .category-badges h5 {
            color: #303a4b;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .category-badge {
            display: inline-block;
            padding: 8px 15px;
            background-color: #0d6efd;
            color: white;
            border-radius: 50px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .category-badge:hover, .category-badge.active {
            background-color: #303a4b;
            transform: none;
        }
        
        /* تصميم البطاقات - مشابه لصفحة index.php */
        .card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }
        
        .card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }
        
        .card-img-top {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .card-body {
            padding: 15px;
        }
        
        .card-title {
            font-weight: bold;
            font-size: 1rem;
            color: #303a4b;
            margin-bottom: 10px;
            height: 45px;
            overflow: hidden;
        }
        
        .card-text {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .discount-ribbon {
            position: absolute;
            top: 10px;
            left: -10px;
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            font-size: 0.8rem;
            z-index: 2;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.3);
            clip-path: polygon(0 0, 100% 0, 90% 50%, 100% 100%, 0 100%, 10% 50%);
            font-weight: bold;
        }
        
        .price-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
        }
        
        .material-badge {
            background: #303a4b;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        
        .price {
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .discounted-price {
            color: #dc3545;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        
        .rating {
            color: #ffc107;
            font-size: 0.9rem;
            margin: 5px 0;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .btn {
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: none;
        }
        
        .btn i {
            margin-left: 3px;
        }
        
        .btn-info {
            background: #0d6efd;
            border: none;
            color: white;
        }
        
        .btn-outline-danger {
            color: #dc3545;
            border: 1px solid #dc3545;
            background: white;
        }
        
        .btn-danger {
            background: #dc3545;
            border: none;
            color: white;
        }
        
        .btn-primary {
            background: #303a4b;
            border: none;
            color: white;
        }
        
        .btn-success {
            background: #198754;
            border: none;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            color: white;
        }
        
        .no-books {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .no-books i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0,0,0,0.1);
            border-radius: 50%;
            border-top-color: #0d6efd;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .card-img-top {
                height: 180px;
            }
            
            .btn {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
            
            .btn i {
                margin-left: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h2>الكتب المقترحة لك</h2>
        
        <!-- عرض التصنيفات المختارة -->
        <div class="category-badges">
            <h5>تصنيفاتك المفضلة:</h5>
            <div>
                <?php foreach ($userCategories as $category): ?>
                    <span class="category-badge" 
                          data-category-id="<?= $category['category_id'] ?>"
                          style="cursor: pointer;">
                        <?= htmlspecialchars($category['category_name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- منطقة عرض الكتب -->
        <div id="books-container">
            <?php if (!empty($recommendedBooks)): ?>
                <div class="row">
                    <?php foreach ($recommendedBooks as $book): 
                        $is_discounted = ($book['has_discount'] == 1);
                        $book_id = $book['id'];
                        $is_favorite = in_array($book_id, $favorites);
                    ?>
                        <div class="col-md-3 mb-4">
                            <div class="card h-100">
                                <?php if($is_discounted): ?>
                                    <div class="discount-ribbon">
                                        خصم <?= $book['discount_percentage'] ?>%
                                    </div>
                                <?php endif; ?>
                                
                                <img src="<?= htmlspecialchars($book['cover_image']) ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($book['title']) ?>">
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                                    <p class="card-text text-muted"><?= htmlspecialchars($book['author']) ?></p>
                                    
                                    <!-- عرض الأسعار ونوع المادة -->
                                    <div class="price-container">
                                        <span class="material-badge"><?= $book['material_type'] ?></span>
                                        <div>
                                            <?php if($is_discounted): ?>
                                                <span class="discounted-price">
                                                    <?= number_format($book['discounted_price']) ?> ل.س
                                                </span>
                                                <span class="original-price">
                                                    <?= number_format($book['price']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="price text-success">
                                                    <?= number_format($book['price']) ?> ل.س
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- التقييم -->
                                    <div class="rating">
                                        <?php
                                        $rating = $book['evaluation'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                    
                                    <!-- الأيقونات - تصميم مشابه لصفحة index.php -->
                                    <div class="action-buttons">
                                        <!-- التفاصيل -->
                                        <button class="btn btn-info"
                                            onclick="window.location.href='details.php?id=<?= $book['id'] ?>'">
                                            <i class="fas fa-info"></i>
                                        </button>
                                        
                                        <!-- المفضلة -->
                                        <button class="btn <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite"
                                            data-book-id="<?= $book['id'] ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                        
                                        <?php if(isset($_SESSION['user_id'])): ?>
                                            <!-- الاستعارة -->
                                            <form method="POST" action="process.php" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                                <input type="hidden" name="action" value="borrow">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-hand-holding"></i>
                                                </button>
                                            </form>
                                            
                                            <!-- الشراء -->
                                            <button class="btn btn-success add-to-cart" 
                                                data-book-id="<?= $book['id'] ?>"
                                                data-book-title="<?= htmlspecialchars($book['title']) ?>"
                                                data-book-price="<?= $book['price'] ?>"
                                                data-book-image="<?= $book['cover_image'] ?>">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-secondary">
                                                <i class="fas fa-sign-in-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-books">
                    <i class="fas fa-book-open"></i>
                    <h4>لا توجد كتب متاحة في التصنيفات المفضلة لديك حالياً</h4>
                    <p class="text-muted">يمكنك استكشاف المزيد من الكتب في أقسام المكتبة المختلفة</p>
                    <a href="all_books.php" class="btn btn-primary mt-3">تصفح المكتبة</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- إضافة كود JavaScript للتفاعلية -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoryBadges = document.querySelectorAll('.category-badge');
        const booksContainer = document.getElementById('books-container');
        
        // إضافة حدث النقر لكل تصنيف
        categoryBadges.forEach(badge => {
            badge.addEventListener('click', function() {
                // إزالة النشط من جميع التصنيفات
                categoryBadges.forEach(b => b.classList.remove('active'));
                // إضافة النشط للتصنيف المحدد
                this.classList.add('active');
                
                const categoryId = this.getAttribute('data-category-id');
                
                // إظهار مؤشر تحميل
                booksContainer.innerHTML = `
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                    </div>
                `;
                
                // جلب الكتب الخاصة بهذا التصنيف
                fetchBooksByCategory(categoryId);
            });
        });
        
        // دالة لجلب الكتب عبر AJAX
        function fetchBooksByCategory(categoryId) {
            const formData = new FormData();
            formData.append('category_id', categoryId);
            
            fetch('get_books_by_category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    booksContainer.innerHTML = `
                        <div class="no-books">
                            <i class="fas fa-book-open"></i>
                            <h4>لا توجد كتب متاحة في هذا التصنيف</h4>
                            <p class="text-muted">يمكنك استكشاف تصنيفات أخرى</p>
                        </div>
                    `;
                    return;
                }
                
                // بناء واجهة الكتب الجديدة
                let booksHTML = '<div class="row">';
                data.forEach(book => {
                    // تحضير التقييم
                    let ratingHTML = '';
                    for (let i = 1; i <= 5; i++) {
                        ratingHTML += i <= book.evaluation ? '★' : '☆';
                    }
                    
                    // تحضير السعر
                    let priceHTML = '';
                    if (book.has_discount == 1) {
                        priceHTML = `
                            <span class="discounted-price">
                                ${book.discounted_price.toLocaleString()} ل.س
                            </span>
                            <span class="original-price">
                                ${book.price.toLocaleString()}
                            </span>
                        `;
                    } else {
                        priceHTML = `
                            <span class="price text-success">
                                ${book.price.toLocaleString()} ل.س
                            </span>
                        `;
                    }
                    
                    booksHTML += `
                        <div class="col-md-3 mb-4">
                            <div class="card h-100">
                                ${book.has_discount == 1 ? 
                                    `<div class="discount-ribbon">
                                        خصم ${book.discount_percentage}%
                                    </div>` : ''}
                                
                                <img src="${book.cover_image}" class="card-img-top"
                                    alt="${book.title}">
                                
                                <div class="card-body">
                                    <h5 class="card-title">${book.title}</h5>
                                    <p class="card-text text-muted">${book.author}</p>
                                    
                                    <div class="price-container">
                                        <span class="material-badge">${book.material_type}</span>
                                        <div>${priceHTML}</div>
                                    </div>
                                    
                                    <div class="rating">${ratingHTML}</div>
                                    
                                    <div class="action-buttons">
                                        <button class="btn btn-info"
                                            onclick="window.location.href='details.php?id=${book.id}'">
                                            <i class="fas fa-info"></i>
                                        </button>
                                        
                                        <button class="btn ${book.is_favorite ? 'btn-danger' : 'btn-outline-danger'} toggle-favorite"
                                            data-book-id="${book.id}">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                        
                                        <?php if(isset($_SESSION['user_id'])): ?>
                                            <form method="POST" action="process.php" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="book_id" value="${book.id}">
                                                <input type="hidden" name="action" value="borrow">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-hand-holding"></i>
                                                </button>
                                            </form>
                                            
                                            <button class="btn btn-success add-to-cart" 
                                                data-book-id="${book.id}"
                                                data-book-title="${book.title}"
                                                data-book-price="${book.price}"
                                                data-book-image="${book.cover_image}">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-secondary">
                                                <i class="fas fa-sign-in-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                booksHTML += '</div>';
                
                booksContainer.innerHTML = booksHTML;
                
                // إعادة إضافة مستمعي الأحداث للعناصر الجديدة
                addEventListeners();
            })
            .catch(error => {
                console.error('Error:', error);
                booksContainer.innerHTML = `
                    <div class="alert alert-danger text-center">
                        حدث خطأ أثناء جلب البيانات. يرجى المحاولة مرة أخرى.
                    </div>
                `;
            });
        }
        
        // إضافة مستمعي الأحداث للعناصر الديناميكية
        function addEventListeners() {
            // إضافة/إزالة من المفضلة
            $('.toggle-favorite').on('click', function() {
                <?php if(!isset($_SESSION['user_id'])): ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'تنبيه!',
                    text: 'يجب تسجيل الدخول أولاً'
                });
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
                                Swal.fire({
                                    icon: 'success',
                                    title: 'تم!',
                                    text: 'أضيف إلى المفضلة'
                                });
                            } else {
                                button.removeClass('btn-danger').addClass('btn-outline-danger');
                                Swal.fire({
                                    icon: 'info',
                                    title: 'تم!',
                                    text: 'حُذف من المفضلة'
                                });
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ!',
                                text: response.message || 'فشلت العملية'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error, xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ!',
                            text: 'حدث خطأ في الاتصال بالخادم'
                        });
                    }
                });
            });

            // إضافة إلى السلة
            $('.add-to-cart').on('click', function() {
                <?php if(!isset($_SESSION['user_id'])): ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'تنبيه!',
                    text: 'يجب تسجيل الدخول أولاً'
                });
                return;
                <?php endif; ?>

                const button = $(this);
                const bookId = button.data('book-id');
                const bookTitle = button.data('book-title');
                
                $.ajax({
                    url: 'add_to_cart.php',
                    method: 'POST',
                    data: {
                        book_id: bookId,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                    },
                    success: function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم الإضافة',
                            text: 'تمت إضافة ' + bookTitle + ' إلى سلة الشراء'
                        });
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ!',
                            text: 'حدث خطأ أثناء الإضافة إلى السلة'
                        });
                    }
                });
            });
        }
        
        // استدعاء أولي لإضافة المستمعين
        addEventListeners();
    });
    </script>

    <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>