<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
require __DIR__ . '/includes/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ━━━━━━━━━━ معالجة إرسال التقييم ━━━━━━━━━━
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    try {
        // طباعة القيم المرسلة للتحقق
        //echo "<pre>";
       // print_r($_POST);
      //  echo "</pre>";
        
        $request_id = (int)$_POST['request_id'];
        $rating = (int)$_POST['rating'];
        $comment = htmlspecialchars($_POST['comment']);

        // جلب book_id من جدول الاستعارات
        $stmt = $conn->prepare("SELECT book_id FROM borrow_requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $book_id = $result['book_id'];

        // ━━━━━━━━━━ التحقق من التقييم المكرر ━━━━━━━━━━
        // 1. عبر الجلسة (Session)
        if (isset($_SESSION['rated_books']) && in_array($book_id, $_SESSION['rated_books'])) {
            $_SESSION['error'] = "لقد قمت بتقييم هذا الكتاب مسبقاً في هذه الجلسة!";
            header("Location: read_book.php?request_id=" . $request_id);
            exit();
        }

        // 2. عبر قاعدة البيانات (اختياري)
        $stmt_check = $conn->prepare("SELECT id FROM book_ratings WHERE user_id = ? AND book_id = ?");
        $stmt_check->bind_param("ii", $_SESSION['user_id'], $book_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $_SESSION['error'] = "لقد قمت بتقييم هذا الكتاب مسبقاً!";
            header("Location: read_book.php?request_id=" . $request_id);
            exit();
        }

        // ━━━━━━━━━━ إدخال التقييم الجديد ━━━━━━━━━━
        $stmt_insert = $conn->prepare("
            INSERT INTO book_ratings 
            (user_id, book_id, rating, comment, request_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt_insert->bind_param("iiisi", 
            $_SESSION['user_id'], 
            $book_id, 
            $rating, 
            $comment, 
            $request_id
        );
        $stmt_insert->execute();

        // ━━━━━━━━━━ تحديث متوسط التقييم في جدول الكتب ━━━━━━━━━━
        $stmt_avg = $conn->prepare("
            UPDATE books 
            SET evaluation = (
                SELECT ROUND(AVG(rating), 2) 
                FROM book_ratings 
                WHERE book_id = ?
            ) 
            WHERE id = ?
        ");
        $stmt_avg->bind_param("ii", $book_id, $book_id);
        $stmt_avg->execute();

        // إضافة book_id إلى الجلسة لمنع التقييم المكرر
        $_SESSION['rated_books'][] = $book_id;
        $_SESSION['success'] = "تم إرسال التقييم بنجاح!";

        header("Location: read_book.php?request_id=" . $request_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "حدث خطأ: " . $e->getMessage();
        header("Location: read_book.php?request_id=" . $request_id);
        exit();
    }
}

// ━━━━━━━━━━ معالجة إتمام القراءة ━━━━━━━━━━
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_reading'])) {
    try {
        $request_id = (int)$_POST['request_id'];
        $user_id = $_SESSION['user_id'];

        // التحقق من ملكية الطلب
        $stmt_check = $conn->prepare("
            UPDATE borrow_requests 
            SET 
                reading_completed = 1,
                due_date = NOW()
            WHERE 
                id = ? 
                AND user_id = ?
                AND reading_completed = 0 
        ");
        $stmt_check->bind_param("ii", $request_id, $user_id);
        $stmt_check->execute();

        if ($stmt_check->affected_rows > 0) {
            // تحديث الإشعار المرتبط بالطلب
            $stmt_notification = $conn->prepare("
                UPDATE notifications 
                SET link_read = 1 
                WHERE 
                    user_id = ? 
                    AND link LIKE ? 
                    AND link_read = 0
            ");
            $link_pattern = "%request_id=$request_id%";
            $stmt_notification->bind_param("is", $user_id, $link_pattern);
            $stmt_notification->execute();
            $_SESSION['success'] = "تم تسجيل إتمام القراءة بنجاح!";
        } else {
            $_SESSION['error'] = "لم يتم العثور على الطلب أو تم تسجيله مسبقاً!";
        }

        header("Location:user/dashboard.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "حدث خطأ: " . $e->getMessage();
        header("Location: read_book.php?request_id=" . $request_id);
        exit();
    }
}

// ━━━━━━━━━━ جلب بيانات الكتاب وعرضه ━━━━━━━━━━
$request_id = (int)$_GET['request_id'];
$user_id = $_SESSION['user_id'];

try {
    // جلب بيانات الكتاب
    $stmt_book = $conn->prepare("
        SELECT b.*, br.status 
        FROM borrow_requests br
        JOIN books b ON br.book_id = b.id
        WHERE br.id = ? AND br.user_id = ?
    ");
    $stmt_book->bind_param("ii", $request_id, $user_id);
    $stmt_book->execute();
    $book = $stmt_book->get_result()->fetch_assoc();

    if (!$book) {
        die("الطلب غير موجود أو ليس لديك صلاحية الوصول!");
    }

    // التحقق من وجود الملف
    $file_path = $book['file_path'];
    if (!file_exists($file_path)) {
        die("الملف غير موجود على الخادم!");
    }

} catch (Exception $e) {
    die("حدث خطأ: " . $e->getMessage());
}
// جلب بيانات الكتاب و due_date و renewed
$stmt_book = $conn->prepare("
    SELECT b.*, br.status, br.due_date, br.renewed 
    FROM borrow_requests br
    JOIN books b ON br.book_id = b.id
    WHERE br.id = ? AND br.user_id = ?
");
$stmt_book->bind_param("ii", $request_id, $user_id);
$stmt_book->execute();
$book = $stmt_book->get_result()->fetch_assoc();

// حساب الأيام المتبقية
$due_date = new DateTime($book['due_date']);
$today = new DateTime();
$interval = $today->diff($due_date);
$days_left = $interval->days;
$is_renewable = $days_left <= 2;
$is_passed = $due_date < $today;

// ━━━━━━━━━━ عرض الصفحة ━━━━━━━━━━
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($book['title']) ?></title>
    <link href="<?= BASE_URL ?>assets/bootstrap/css/bootstrap.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/viewerjs/dist/viewer.min.css">
    <style>
    .book-viewer {
        position: relative;
        height: 80vh;
        overflow: hidden;
        background-color: #f5f5f5;
        border: 1px solid #ddd;
        margin-bottom: 20px;
    }
    
    .page-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px 0;
    }
    
    .page-image {
        max-width: 100%;
        max-height: 70vh;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        user-select: none;
        pointer-events: none;
    }
    
    .page-controls {
        display: flex;
        justify-content: center;
        margin: 20px 0;
        gap: 15px;
    }
    
    .page-info {
        text-align: center;
        font-size: 1.2rem;
        margin: 10px 0;
    }
    
    .loading-indicator {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 2rem;
        color: #666;
    }
    
    .watermarked {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" opacity="0.05"><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="30" fill="gray"><?= BASE_URL ?></text></svg>');
        background-repeat: repeat;
    }
    </style>
</head>

<body>
    <div class="container book-container">
        <!-- التعديل على الهيكل -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <!-- زر العودة على اليسار -->

            <form method="POST" style="display: inline;">
                <input type="hidden" name="request_id" value="<?= $request_id ?>">
                <button type="submit" name="complete_reading" class="btn btn-primary btn-sm">تمت القراءة</button>
            </form>
            <!-- زر تجديد الاستعارة -->
            <?php if ($is_renewable || $is_passed): ?>
            <form method="POST" action="process.php">
                <input type="hidden" name="request_id" value="<?= $request_id ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" name="actions" value="renew" class="btn btn-warning">
                    تجديد الاستعارة
                </button>
            </form>
            <?php endif; ?>
            <!-- العناصر على اليمين في مجموعة واحدة -->
            <div class="d-flex align-items-center gap-2">
                <!-- زر تمت القراءة -->
                <a href="user/dashboard.php" class="btn btn-danger btn-sm">العودة للوحة التحكم</a>
            </div>
        </div>

        <!-- الرسائل التحذيرية تبقى خارج التنسيق -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <!-- عرض الملف -->
        <!-- عرض الملف كصور -->
        <div class="card">
            <div class="card-body">
                <h3 class="card-title text-center"><?= htmlspecialchars($book['title']) ?></h3>
                
                <div class="book-viewer">
                    <div class="loading-indicator">
                        <i class="fas fa-spinner fa-spin"></i> جاري تحميل الكتاب...
                    </div>
                    
                    <div class="page-container" id="pageContainer" style="display:none;">
                        <img id="currentPage" class="page-image" src="" alt="صفحة الكتاب">
                        <div class="page-info">
                            الصفحة <span id="currentPageNum">1</span> من <span id="totalPages">0</span>
                        </div>
                    </div>
                </div>
                
                <div class="page-controls">
                    <button id="prevPage" class="btn btn-primary" disabled>
                        <i class="fas fa-arrow-right"></i> السابقة
                    </button>
                    <button id="nextPage" class="btn btn-primary" disabled>
                        التالية <i class="fas fa-arrow-left"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- نموذج التقييم مع التعديلات -->
        <div class="rating-form">

            <form method="POST" class="d-flex align-items-center gap-2">
                <input type="hidden" name="request_id" value="<?= $request_id ?>">
                <textarea type="text" name="comment" class="form-control" rows="3" placeholder="أكتب مراجعاتك للكتاب .."
                    style="width:900px;"></textarea>

                <!-- النجوم -->
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1"><i class="fas fa-star"></i></label>
                </div>

                <!-- زر الصوت -->
                <button type="submit" name="submit_rating" class="btn btn-info btn-sm">صوُت</button>
            </form>
        </div>
    </div>


    <!-- إضافة JavaScript لتحديث التقييم -->
    <script>
    document.addEventListener('contextmenu', (e) => e.preventDefault());
    const ratingInputs = document.querySelectorAll('input[name="rating"]');
    const selectedRatingDisplay = document.getElementById('selected-rating');

    ratingInputs.forEach(input => {
        input.addEventListener('change', () => {
            if (input.checked) {
                selectedRatingDisplay.textContent = input.value + ' نجوم';
            }
        });
    });
    </script>
<!-- مكتبات JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/viewerjs/dist/viewer.min.js"></script>
    
    <script>
    $(document).ready(function() {
        const requestId = <?= $request_id ?>;
        let pages = [];
        let currentPage = 1;
        
        // جلب صفحات الكتاب
        function loadBookPages() {
            $.ajax({
                url: 'pdf_to_images.php?request_id=' + requestId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    pages = response.pages;
                    if (pages.length > 0) {
                        $('#totalPages').text(pages.length);
                        showPage(1);
                        $('.loading-indicator').hide();
                        $('#pageContainer').show();
                        updateControls();
                    } else {
                        $('.loading-indicator').html('<div class="alert alert-danger">فشل في تحميل الكتاب</div>');
                    }
                },
                error: function() {
                    $('.loading-indicator').html('<div class="alert alert-danger">حدث خطأ أثناء تحميل الكتاب</div>');
                }
            });
        }
        
        // عرض صفحة محددة
        function showPage(pageNum) {
            currentPage = pageNum;
            $('#currentPage').attr('src', pages[pageNum - 1]);
            $('#currentPageNum').text(pageNum);
            updateControls();
        }
        
        // تحديث حالة أزرار التحكم
        function updateControls() {
            $('#prevPage').prop('disabled', currentPage <= 1);
            $('#nextPage').prop('disabled', currentPage >= pages.length);
        }
        
        // أحداث الأزرار
        $('#prevPage').click(function() {
            if (currentPage > 1) {
                showPage(currentPage - 1);
            }
        });
        
        $('#nextPage').click(function() {
            if (currentPage < pages.length) {
                showPage(currentPage + 1);
            }
        });
        
        // بدء تحميل الكتاب
        loadBookPages();
        
        // حماية المحتوى
        // 1. منع النقر الأيمن
        $(document).on('contextmenu', function(e) {
            e.preventDefault();
            alert('غير مسموح بالنقر الأيمن');
        });
        
        // 2. منع اختصارات لوحة المفاتيح
        $(document).on('keydown', function(e) {
            // منع Ctrl+S, Ctrl+C, Ctrl+P
            if (e.ctrlKey && (e.key === 's' || e.key === 'c' || e.key === 'p')) {
                e.preventDefault();
                alert('غير مسموح بهذه العملية');
            }
            
            // مفاتيح الأسهم للتنقل بين الصفحات
            if (e.key === 'ArrowRight') {
                $('#prevPage').click();
            } else if (e.key === 'ArrowLeft') {
                $('#nextPage').click();
            }
        });
        
        // 3. منع سحب الصور
        $('.page-image').on('dragstart', function(e) {
            e.preventDefault();
        });
    });
    </script>
</body>
</html>

