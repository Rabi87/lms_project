<?php
session_start();
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// معالجة طلبات AJAX
if (isset($_GET['ajax'])) {
    $search = $_GET['search'] ?? '';
    $category_filter = $_GET['category'] ?? 'all';

    $base_query = "
        SELECT 
            books.*, 
            categories.category_name,
            books.price,
            books.description,
            books.cover_image 
        FROM books
        INNER JOIN categories 
            ON books.category_id = categories.category_id
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    if (!empty($search)) {
        $base_query .= " AND (books.title LIKE ? OR books.author LIKE ?)";
        $search_term = "%$search%";
        array_push($params, $search_term, $search_term);
        $types .= 'ss';
    }

    if ($category_filter !== 'all') {
        $base_query .= " AND categories.category_id = ?";
        array_push($params, $category_filter);
        $types .= 'i';
    }

    $stmt = $conn->prepare($base_query);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $all_books = $result->fetch_all(MYSQLI_ASSOC);
    $recommended = [];
    $rated = [];

    // فلترة الكتب المقترحة والأعلى تقييمًا
    foreach ($all_books as $book) {
        if ($book['evaluation'] > 2) $rated[] = $book;
    }

    // جلب الكتب المقترحة إذا كان المستخدم مسجل الدخول
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $query = "
            SELECT b.*, categories.category_name 
            FROM books b
            JOIN user_categories uc ON b.category_id = uc.category_id
            INNER JOIN categories ON b.category_id = categories.category_id
            WHERE uc.user_id = ?
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $recommended = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'all' => $all_books,
        'rated' => $rated,
        'recommended' => $recommended
    ]);
    exit;
}

// جلب البيانات الأولية
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? 'all';

$base_query = "
    SELECT 
        books.*, 
        categories.category_name,
        books.price,
        books.description,
        books.cover_image 
    FROM books
    INNER JOIN categories 
        ON books.category_id = categories.category_id
    WHERE 1=1
";

// ... (بقية شروط البحث كما هي)
// إضافة شروط البحث
if (!empty($search)) {
    $base_query .= " AND (books.title LIKE ? OR books.author LIKE ?)";
    $search_term = "%$search%";
    array_push($params, $search_term, $search_term);
    $types .= 'ss';
}

// إضافة فلتر التصنيف
if ($category_filter !== 'all') {
    $base_query .= " AND categories.category_id = ?";
    array_push($params, $category_filter);
    $types .= 'i';
}

$stmt = $conn->prepare($base_query);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$all_books = $result->fetch_all(MYSQLI_ASSOC);
$rated_books = array_filter($all_books, function($book) {
    return $book['evaluation'] > 2;
});


// جلب الكتب المقترحة
$recommended_books = [];
if (isset($_SESSION['user_id'])) {
    $query = "
    SELECT 
        b.*, 
        categories.category_name 
    FROM books b
    JOIN user_categories uc 
        ON b.category_id = uc.category_id
    INNER JOIN categories 
        ON b.category_id = categories.category_id
    WHERE uc.user_id = ?
";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $recommended_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// جلب الكتب المقترحة
$rated_books = [];

$query = "
SELECT 
    b.*, 
    categories.category_name 
FROM books b
INNER JOIN categories 
    ON b.category_id = categories.category_id
WHERE b.evaluation > 2
";
$stmt = $conn->prepare($query);

$stmt->execute();
$rated_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


?>


<style>
.flip-card {
    perspective: 1000px;
    min-height: 200px;
    margin-bottom: 1.5rem;
}

.flip-inner {
    position: relative;
    width: 60%;
    height: 100%;
    transition: transform 0.6s;
    transform-style: preserve-3d;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.flip-card:hover .flip-inner {
    transform: rotateY(180deg);
}

.flip-front,
.flip-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    border-radius: 1px;
    overflow: hidden;
}

.flip-back {
    background: #000;
    color: #fff;
    padding: 15px;
    transform: rotateY(180deg);
    display: flex;
    flex-direction: column;
}

.card-actions {
    margin-top: auto;
    display: flex;
    gap: 10px;
    justify-content: center;
}

/* تنسيقات النافذة المنبثقة */
#bookDetailsModal .modal-content {
    background: #1a1a1a;
    color: #fff;
}

#bookDetailsModal img {
    max-height: 300px;
    object-fit: cover;
}
</style>


<div>
   <!-- شريط البحث -->
<div class="home-search mb-4 text-center">
    <form id="searchForm" onsubmit="return false;">
        <input type="text" id="searchInput" class="form-control rounded-pill w-100 mx-auto" 
            placeholder="ابحث عن كتاب..." autocomplete="off">
    </form>
</div>

<!-- تصفية التصنيفات -->
<div class="filter-bar d-flex justify-content-center gap-2 mb-4 flex-wrap" id="categoryFilter">
    <button class="filter-btn btn btn-outline-primary rounded-pill active" 
            data-category="all">الكل</button>
    <?php
    $categories = $conn->query("SELECT * FROM categories");
    while($cat = $categories->fetch_assoc()):
    ?>
    <button class="filter-btn btn btn-outline-primary rounded-pill" 
            data-category="<?= $cat['category_id'] ?>"><?= $cat['category_name'] ?></button>
    <?php endwhile; ?>
</div>


<div>
    <!-- شريط البحث والتصفية كما هو -->

    <div class="accordion">
        <?php if (!empty($rated_books)): ?>
        <button class="accordion-header"> الأعلى تقييماً</button>
        <div class="accordion-content">
            <div class="row g-4" id="ratedBooksContainer">
                <?php foreach ($rated_books as $book): ?>
                <!-- بطاقة الكتاب -->
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="accordion">
        <?php if (!empty($recommended_books)): ?>
        <button class="accordion-header"> المفضلة</button>
        <div class="accordion-content">
            <div class="row g-4" id="recommendedBooksContainer">
                <?php foreach ($recommended_books as $book): ?>
                <!-- بطاقة الكتاب -->
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="accordion">
        <button class="accordion-header"> المكتبة الشاملة</button>
        <div class="accordion-content">
            <div class="row g-4" id="booksContainer">
                <?php foreach ($all_books as $book): ?>
                <!-- بطاقة الكتاب -->
                <?php endforeach; ?>
            </div>
        </div>
    </div>

     <!-- نافذة التفاصيل -->
     <div class="modal fade" id="bookDetails">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل الكتاب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <img id="modalCover" src="" class="img-fluid">
                        </div>
                        <div class="col-md-8">
                            <h4 id="modalTitle"></h4>
                            <p><strong>المؤلف:</strong> <span id="modalAuthor"></span></p>
                            <p><strong>التصنيف:</strong> <span id="modalCategory"></span></p>
                            <p><strong>التقييم:</strong> <span id="modalRating"></span></p>
                            <p><strong>السعر:</strong> <span id="modalPrice"></span> ل.س</p>
                            <p id="modalDesc"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال تسجيل الدخول -->
    <div class="modal fade" id="loginModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تسجيل الدخول مطلوب</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>يجب تسجيل الدخول لإكمال هذه العملية</p>
                        <a href="login.php" class="btn btn-primary">تسجيل الدخول</a>
                        <a href="register.php" class="btn btn-secondary">إنشاء حساب</a>
                    </div>
                </div>
            </div>
        </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const loadingIndicator = document.querySelector('.loading-indicator');

    // أحداث البحث
    searchInput.addEventListener('input', debounce(handleSearch, 500));
    
    // أحداث التصفية
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            handleSearch();
        });
    });

    async function handleSearch() {
        loadingIndicator.style.display = 'block';
        
        try {
            const search = searchInput.value;
            const category = document.querySelector('#categoryFilter .active').dataset.category;
            
            const response = await fetch(`home.php?ajax=1&search=${encodeURIComponent(search)}&category=${category}`);
            const data = await response.json();
            
            updateAllSections(data);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            loadingIndicator.style.display = 'none';
        }
    }

    function updateAllSections(data) {
        updateSection('booksContainer', data.all); // المكتبة الشاملة
        updateSection('ratedBooksContainer', data.rated); // الأعلى تقييمًا
        updateSection('recommendedBooksContainer', data.recommended); // المفضلة
    }

    function updateSection(containerId, books) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = books.map(book => `
            <div class="col-6 col-md-4 col-lg-2">
                <div class="flip-card h-100">
                    <div class="flip-inner">
                        <div class="flip-front">
                            <img src="<?= BASE_URL ?>${book.cover_image}" alt="غلاف الكتاب">
                        </div>
                        <div class="flip-back">
                            <h6 class="fw-bold">${escapeHtml(book.title)}</h6>
                            <p class="small">${escapeHtml(book.author)}</p>
                            <div class="rating-stars mb-3">
                                ${'★'.repeat(book.evaluation) + '☆'.repeat(5 - book.evaluation)}
                            </div>
                            <div class="card-actions">
                                ${generateActions(book)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

   // دالة مساعدة لإنشاء بطاقات الكتب
function generateBookCard(book) {
    return `
    <div class="flip-card h-100">
        <div class="flip-inner">
            <div class="flip-front">
                <img src="<?= BASE_URL ?>${book.cover_image}" alt="غلاف الكتاب">
            </div>
            <div class="flip-back">
                <!-- باقي محتوى البطاقة -->
            </div>
        </div>
    </div>`;
}
</script>


<?php require __DIR__ . '/includes/footer.php'; ?>