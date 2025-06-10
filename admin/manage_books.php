<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require __DIR__ . '/../includes/config.php';
// التحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}
// معالجة طلب التصدير
if (isset($_GET['export'])) {
    require __DIR__ . '/export_books_report.php';
    exit(); 

}
// معالجة حذف الكتاب
if (isset($_GET['delete'])) {
    try {
        $book_id = (int)$_GET['delete'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM borrow_requests WHERE book_id = ? AND status IN ('approved', 'pending')");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $active_requests = $stmt->get_result()->fetch_row()[0];
        
        if ($active_requests > 0) {
            throw new Exception("لا يمكن حذف الكتاب بسبب وجود طلبات استعارة فعالة");
        }

        $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "تم حذف الكتاب بنجاح";
           echo '<script>window.location.href = "dashboard.php?section=books";</script>';
           exit();
        } else {
            throw new Exception("فشل في الحذف: " . $conn->error);
        }
        
    } 
    catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        echo '<script>window.location.href = "dashboard.php?section=books";</script>';
        exit();
    }
   
}

// جلب بيانات الكتب
$records_per_page = 6;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $records_per_page;

$total_books = $conn->query("SELECT COUNT(*) AS total FROM books")->fetch_assoc()['total'];
$total_pages = ceil($total_books / $records_per_page);

$books = $conn->query("SELECT * FROM books LIMIT $records_per_page OFFSET $offset");
$categories = $conn->query("SELECT * FROM categories");
// جلب احصائيات الكتب
$stats_sql = "
    SELECT 
        SUM(CASE WHEN material_type = 'كتاب' THEN 1 ELSE 0 END) AS total_books,
        SUM(CASE WHEN material_type = 'مجلة' THEN 1 ELSE 0 END) AS total_magazines,
        SUM(CASE WHEN material_type = 'جريدة' THEN 1 ELSE 0 END) AS total_newspapers
    FROM books
";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>
<?php if (isset($_SESSION['success'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'نجاح!',
    text: '<?= $_SESSION['success'] ?>'
});
</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'خطأ!',
    text: '<?= $_SESSION['error'] ?>'
});
</script>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>
<style>
.card {
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17ead9 0%, #6078ea 100%);
}

.table-responsive {
    max-height: 500px;
    scrollbar-width: thin;
    scrollbar-color: #667eea #f8f9fa;
}

.table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background-color: #667eea;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-track {
    background-color: #f8f9fa;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.modal-header {
    header
}

.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.dropdown-item {
    z-index: 10000;
}

/* إخفاء الأعمدة المخفية بسلاسة */
.table th,
.table td {
    transition: opacity 0.3s ease;
}

.table th[style*="none"],
.table td[style*="none"] {
    opacity: 0.3;
    /* تلميح مرئي للعمود المخفي */
}

#columnToggleMenu {
    z-index: 99999;
    /* تأكد من أنها أعلى من أي عنصر آخر */
    max-height: 400px;
    /* ارتفاع مناسب */
    overflow-y: auto;
    /* إض
    افة scroll إذا لزم الأمر */
}

#reportform {
    z-index: 99999;
    /* تأكد من أنها أعلى من أي عنصر آخر */
    max-height: 400px;
    /* ارتفاع مناسب */
    overflow-y: auto;
    max-width: 50px;
    text-align: right;
    /* إض
    افة scroll إذا لزم الأمر */
}

#columnToggleMenu {
    text-align: right;
    /* محاذاة النص لليمين */
}
</style>
<!-- بطاقات الإحصائيات -->
<div class="row mb-4">
    <!-- بطاقة الكتب -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-gradient-primary text-white py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-book me-2"></i> الكتب
                </h5>
            </div>
            <div class="card-body text-center py-4">
                <h2 class="display-5 mb-0 fw-bold text-primary">
                    <?= $stats['total_books'] ?>
                </h2>
            </div>
        </div>
    </div>

    <!-- بطاقة المجلات -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-gradient-primary text-white py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-newspaper me-2"></i> المجلات
                </h5>
            </div>
            <div class="card-body text-center py-4">
                <h2 class="display-5 mb-0 fw-bold text-warning">
                    <?= $stats['total_magazines'] ?>
                </h2>
            </div>
        </div>
    </div>

    <!-- بطاقة الصحف -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-gradient-primary text-white py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-file-alt me-2"></i> الصحف
                </h5>
            </div>
            <div class="card-body text-center py-4">
                <h2 class="display-5 mb-0 fw-bold text-danger">
                    <?= $stats['total_newspapers'] ?>
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm row mb-4">
   
        <div class="card-header bg-gradient-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-book me-2"></i> إدارة الكتب
                </h5>
                <!-- شريط البحث -->
            <div class="input-group input-group-sm" style="width: 200px;">
                <input type="text" id="searchall" class="form-control rounded-pill"
                    placeholder="ابحث">
                <span class="input-group-text bg-transparent border-0"><i class="fas fa-search"></i></span>
            </div>
                <div class="d-flex">
            
                    <!-- زر إضافة كتاب -->               
                    <a href="add_book.php" class="btn btn-light btn-sm rounded-pill">
                        <i class="fas fa-plus me-1"></i> إضافة كتاب
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success m-3">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th class="column-0">العنوان</th>
                            <th class="column-1">المؤلف</th>
                            <th class="column-2">النوع</th>
                            <th class="column-3">الكمية</th>
                            <th class="column-4">السعر(ل.س)</th>
                            <th class="column-5">الصفحات</th>
                            <th class="column-6"> النشر</th>
                            <th class="column-7">ISBN</th>
                            <th class="column-8">الشهري</th>
                            <th class="text-end column-8">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($book = $books->fetch_assoc()): ?>
                        <tr>
                            <td class="column-0"><?= htmlspecialchars($book['title']) ?></td>
                            <td class="column-1"><?= htmlspecialchars($book['author']) ?></td>
                            <td class="column-2">
                                <span class="badge 
                                    <?= $book['material_type'] == 'كتاب' ? 'bg-primary' : 
                                        ($book['material_type'] == 'مجلة' ? 'bg-warning' : 'bg-danger') ?>">
                                    <?= htmlspecialchars($book['material_type']) ?>
                                </span>
                            </td>
                            <td class="column-3">
                                <span class="badge <?= $book['quantity'] > 0 ? 'bg-primary' : 'bg-danger' ?>">
                                    <?= $book['quantity'] ?>
                                </span>
                            </td>
                            <td class="column-4"><?= $book['price'] ?></td>
                            <td class="column-5"><?= $book['page_count'] ?? '--' ?></td>
                            <td class="column-6"><?= $book['publication_date'] ?? '--' ?></td>
                            <td class="column-7"><?= $book['isbn'] ?? '--' ?></td>
                            <td class="column-8">
                                <div class="form-check form-switch">
                                    <input 
                                        type="checkbox" 
                                        class="form-check-input book-of-month-toggle" 
                                        data-book-id="<?= $book['id'] ?>" 
                                        <?= $book['book_of_the_month'] ? 'checked' : '' ?>
                                    >
                                </div>
                            </td>
                            <td class="text-end column-8">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit_book.php?id=<?= $book['id'] ?>" class="btn btn-outline-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?= $book['id'] ?>" class="btn btn-outline-danger"
                                        onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- الترقيم -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-light">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>&section=books"
                                aria-label="السابق">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&section=books"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>&section=books"
                                aria-label="التالي">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>



<script>
    
// تأكيد الحذف
function confirmDelete(e) {
    if (!confirm('هل أنت متأكد من حذف هذا الكتاب؟')) {
        e.preventDefault();
    }
}
// تحديث حالة كتاب الشهر عبر AJAX
document.querySelectorAll('.book-of-month-toggle').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const bookId = this.getAttribute('data-book-id');
        const isChecked = this.checked ? 1 : 0;

        fetch('../update_book_of_month.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                book_id: bookId,
                status: isChecked
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('حدث خطأ!');
                this.checked = !isChecked;
            }
        });
    });
});
// البحث الفوري  
document.getElementById('searchall').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        // جمع محتوى جميع الخلايا (td) في الصف
        const cells = row.querySelectorAll('td');
        let found = false;
        
        // البحث في كل خلية من خلايا الصف
        cells.forEach(cell => {
            const cellText = cell.textContent.toLowerCase();
            if (cellText.includes(searchTerm)) {
                found = true;
            }
        });

        // إظهار/إخفاء الصف بناءً على نتيجة البحث
        row.style.display = found ? '' : 'none';
    });

    // إخفاء الترقيم عند البحث
    const pagination = document.querySelector('.pagination');
    if (pagination) {
        pagination.style.display = searchTerm ? 'none' : '';
    }
});


</script>