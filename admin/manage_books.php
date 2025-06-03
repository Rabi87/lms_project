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
        } else {
            throw new Exception("فشل في الحذف: " . $conn->error);
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: dashboard.php?section=books");
    exit();
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
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-gradient-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-book me-2"></i> إدارة الكتب
                </h5>
                <div>
                    <!-- زر التحكم بالأعمدة -->
                    <button class="btn btn-light btn-sm rounded-pill me-2" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-columns me-1"></i> تخصيص الأعمدة
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" id="columnToggleMenu" dir="rtl">
                        <li>
                            <h6 class="dropdown-header">اختر الأعمدة:</h6>
                        </li>
                        <li><a class="dropdown-item" href="#" data-column="0"><input type="checkbox" checked>
                                العنوان</a></li>
                        <li><a class="dropdown-item" href="#" data-column="1"><input type="checkbox" checked> المؤلف</a>
                        </li>
                        <li><a class="dropdown-item" href="#" data-column="2"><input type="checkbox" checked> النوع</a>
                        </li>
                        <li><a class="dropdown-item" href="#" data-column="3"><input type="checkbox" checked> الكمية</a>
                        </li>
                        <li><a class="dropdown-item" href="#" data-column="4"><input type="checkbox" checked>(ل.س)
                                السعر</a>
                        </li>
                        <li><a class="dropdown-item" href="#" data-column="5"><input type="checkbox" checked>الصفحات</a>
                        </li>
                        <li><a class="dropdown-item" href="#" data-column="6"><input type="checkbox" checked> تاريخ
                                النشر</a></li>
                        <li><a class="dropdown-item" href="#" data-column="7"><input type="checkbox" checked> ISBN</a>
                        </li>
                    </ul>

                    <!-- زر تصدير التقرير -->
                    <button class="btn btn-success btn-sm rounded-pill me-2 dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">
                        <i class="fas fa-file-export me-1"></i> تصدير
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" id="reportform" dir="rtl">
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportbReport('pdf')"><i
                                    class="fas fa-file-pdf me-2"></i>PDF</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportbReport('csv')"><i
                                    class="fas fa-file-csv me-2"></i>CSV</a></li>
                    </ul>
                    <!-- زر إضافة كتاب -->
                    <button class="btn btn-light btn-sm rounded-pill">
                        <a href="add_book.php" class="btn btn-light btn-sm rounded-pill">
                            <i class="fas fa-plus me-1"></i> إضافة كتاب
                        </a>
                    </button>


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
                            <th class="column-6">تاريخ النشر</th>
                            <th class="column-7">ISBN</th>
                            <th class="column-8">كتاب الشهر</th>
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
</div>

<!-- نافذة إضافة كتاب -->
<div class="modal fade" id="addBookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-2"></i> إضافة كتاب جديد
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form action="<?= BASE_URL ?>process.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">عنوان الكتاب <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">المؤلف <span class="text-danger">*</span></label>
                            <input type="text" name="author" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>هل يوجد خصم؟</label>
                            <input type="checkbox" name="has_discount" <?= $book['has_discount'] ? 'checked' : '' ?>>
                        </div>

                        <div class="mb-3">
                            <label>نسبة الخصم (%)</label>
                            <input type="number" name="discount_percentage"
                                value="<?= $book['discount_percentage'] ?? 0 ?>" min="0" max="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">نوع المادة <span class="text-danger">*</span></label>
                            <select name="material_type" class="form-select" required>
                                <option value="كتاب">كتاب</option>
                                <option value="مجلة">مجلة</option>
                                <option value="جريدة">جريدة</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">عدد الصفحات</label>
                            <input type="number" name="page_count" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تاريخ النشر</label>
                            <input type="date" name="publication_date" class="form-control">
                        </div>


                        <div class="col-md-4">
                            <label class="form-label">رقم ISBN</label>
                            <input type="text" name="isbn" class="form-control" maxlength="13">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">التصنيف <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">اختر التصنيف</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= $cat['category_id'] ?>">
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">الكمية <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">السعر (ل.س) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>

                        <div class="col-md-4">

                            <input type="file" name="cover_image" class="form-control" required>
                        </div>



                        <div class="col-md-4">
                            <label class="form-label">ملف الكتاب (للكتب الإلكترونية)</label>
                            <input type="file" name="file_path" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">الوصف <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> إلغاء
                    </button>
                    <button type="submit" name="add_book" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-save me-2"></i> حفظ الكتاب
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة تعديل كتاب -->
<div class="modal fade" id="editBookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i> تعديل الكتاب
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form action="<?= BASE_URL ?>process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="book_id" id="editBookId">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- العنوان والمؤلف -->
                        <div class="col-md-6">
                            <label class="form-label">عنوان الكتاب <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="editTitle" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">المؤلف <span class="text-danger">*</span></label>
                            <input type="text" name="author" id="editAuthor" class="form-control" required>
                        </div>

                        <!-- نوع الكتاب ونوع المادة -->
                        <div class="col-md-4">
                            <label class="form-label">نوع الكتاب <span class="text-danger">*</span></label>
                            <select name="type" id="editType" class="form-select" required>
                                <option value="physical">كتاب فيزيائي</option>
                                <option value="e-book">كتاب إلكتروني</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">نوع المادة <span class="text-danger">*</span></label>
                            <select name="material_type" id="editMaterialType" class="form-select" required>
                                <option value="كتاب">كتاب</option>
                                <option value="مجلة">مجلة</option>
                                <option value="جريدة">جريدة</option>
                            </select>
                        </div>

                        <!-- عدد الصفحات وتاريخ النشر -->
                        <div class="col-md-4">
                            <label class="form-label">عدد الصفحات</label>
                            <input type="number" name="page_count" id="editPageCount" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تاريخ النشر</label>
                            <input type="date" name="publication_date" id="editPublicationDate" class="form-control">
                        </div>

                        <!-- ISBN والتصنيف -->
                        <div class="col-md-4">
                            <label class="form-label">رقم ISBN</label>
                            <input type="text" name="isbn" id="editIsbn" class="form-control" maxlength="13">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">التصنيف <span class="text-danger">*</span></label>
                            <select name="category_id" id="editCategory" class="form-select" required>
                                <option value="">اختر التصنيف</option>
                                <?php 
                                $categories = $conn->query("SELECT * FROM categories");
                                while ($cat = $categories->fetch_assoc()): 
                                ?>
                                <option value="<?= $cat['category_id'] ?>">
                                    <?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- الكمية والسعر -->
                        <div class="col-md-4">
                            <label class="form-label">الكمية <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="editQuantity" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">السعر (ل.س) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" id="editPrice" class="form-control" required>
                        </div>

                        <!-- الصورة والملف -->
                        <div class="col-md-4">
                            <label class="form-label">صورة الغلاف</label>
                            <input type="file" name="cover_image" class="form-control" accept="image/*">
                            <small class="text-muted">اختياري - اتركه فارغًا للحفاظ على الصورة الحالية</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ملف الكتاب</label>
                            <input type="file" name="file_path" class="form-control">
                            <small class="text-muted">اختياري - اتركه فارغًا للحفاظ على الملف الحالي</small>
                        </div>

                        <!-- الوصف والتقييم -->
                        <div class="col-12">
                            <label class="form-label">الوصف <span class="text-danger">*</span></label>
                            <textarea name="description" id="editDescription" class="form-control" rows="3"
                                required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">التقييم</label>
                            <input type="number" name="evaluation" id="editEvaluation" class="form-control" min="1"
                                max="5" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> إلغاء
                    </button>
                    <button type="submit" name="update_book" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-save me-2"></i> حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// عند فتح نافذة التعديل
document.getElementById('editBookModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget; // الزر الذي تم النقر عليه
    const row = button.closest('tr'); // الصف الذي يحتوي على بيانات الكتاب
    const cells = row.querySelectorAll('td');

    // ملء البيانات في النموذج
    document.getElementById('editBookId').value = button.getAttribute('data-id');
    document.getElementById('editTitle').value = cells[0].innerText;
    document.getElementById('editAuthor').value = cells[1].innerText;
    document.getElementById('editType').value = (cells[5].innerText.trim() === 'فيزيائي') ? 'physical' :
        'e-book';
    document.getElementById('editMaterialType').value = cells[4].innerText.trim();
    document.getElementById('editPageCount').value = parseInt(cells[6].innerText) || '';
    document.getElementById('editPublicationDate').value = cells[7].innerText;
    document.getElementById('editIsbn').value = cells[8].innerText;
    document.getElementById('editQuantity').value = parseInt(cells[3].innerText);
    document.getElementById('editPrice').value = parseFloat(cells[4].innerText.replace(' ل.س', ''));
    document.getElementById('editDescription').value = '<?= $book['description'] ?? '' ?>';
    document.getElementById('editEvaluation').value = parseFloat('<?= $book['evaluation'] ?? 3 ?>');

    // تحديد التصنيف المناسب
    const categoryName = '<?= $book['category_name'] ?? '' ?>';
    const categorySelect = document.getElementById('editCategory');
    Array.from(categorySelect.options).forEach(option => {
        if (option.text === categoryName) option.selected = true;
    });
});
</script>
<script>
// استعادة التفضيلات من localStorage
const savedColumns = JSON.parse(localStorage.getItem('visibleColumns')) || [];
if (savedColumns.length > 0) {
    savedColumns.forEach(col => {
        document.querySelectorAll(`.column-${col}`).forEach(el => {
            el.style.display = ''; // إظهار العمود
        });
    });
}

// إدارة القائمة المنسدلة
document.querySelectorAll('#columnToggleMenu .dropdown-item').forEach(item => {
    const columnIndex = item.getAttribute('data-column');
    const checkbox = item.querySelector('input[type="checkbox"]');

    // تحديث حالة الصندوق بناءً على التخزين
    checkbox.checked = savedColumns.includes(columnIndex) || savedColumns.length === 0;

    // إضافة حدث التغيير
    checkbox.addEventListener('change', function() {
        const isVisible = this.checked;

        // إخفاء/إظيار كل العناصر ذات الفئة column-X
        document.querySelectorAll(`.column-${columnIndex}`).forEach(el => {
            el.style.display = isVisible ? '' : 'none';
        });

        // تحديث localStorage
        const visibleColumns = Array.from(document.querySelectorAll(
                '#columnToggleMenu input[type="checkbox"]:checked'))
            .map(cb => cb.parentElement.parentElement.getAttribute('data-column'));
        localStorage.setItem('visibleColumns', JSON.stringify(visibleColumns));
    });
});

// إعادة تحميل الصفحة بعد الإضافة بنجاح
<?php if (isset($_SESSION['book_added'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('addBookModal'));
    if (modal) modal.hide();
    <?php unset($_SESSION['book_added']); ?>
});
<?php endif; ?>

// تأكيد الحذف
function confirmDelete(e) {
    if (!confirm('هل أنت متأكد من حذف هذا الكتاب؟')) {
        e.preventDefault();
    }
}
</script>
<script>
function exportbReport(format) {
    // إنشاء رابط مع معلمة التصدير
    const url = `manage_books.php?export=${format}`;

    // إنشاء عنصر <a> خفي لتنزيل الملف
    const link = document.createElement('a');
    link.href = url;
    link.download = `books_report.${format}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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
</script>