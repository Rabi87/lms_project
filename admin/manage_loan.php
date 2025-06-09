<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/config.php';
// التحقق من صلاحيات المستخدم
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}
if ($_SESSION['user_type'] !== 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// جلب جميع طلبات الاستعارة مع تفاصيل المستخدم والكتاب
$records_per_page = 6; // عدد السجلات لكل صفحة
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $records_per_page;

$stmt = $conn->prepare("
    SELECT SQL_CALC_FOUND_ROWS
        br.id AS request_id,
        u.name AS user_name,
        b.title AS book_title,
        br.request_date,
        br.status,
        br.type
    FROM 
        borrow_requests br
    JOIN 
        users u ON br.user_id = u.id
    JOIN 
        books b ON br.book_id = b.id
    ORDER BY 
        br.request_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// حساب عدد الصفحات
$total_requests = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_pages = ceil($total_requests / $records_per_page);

// جلب إحصائيات الطلبات
$stats_sql = "
    SELECT 
        SUM(CASE WHEN type = 'purchase' THEN 1 ELSE 0 END) AS total_purchases,
        SUM(CASE WHEN type = 'purchase' AND status = 'pending' THEN 1 ELSE 0 END) AS pending_purchases,
        SUM(CASE WHEN type = 'borrow' THEN 1 ELSE 0 END) AS total_borrows,
        SUM(CASE WHEN type = 'borrow' AND status = 'pending' THEN 1 ELSE 0 END) AS pending_borrows
    FROM borrow_requests
";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// دوال مساعدة
require __DIR__ . '/../includes/functions.php';
?>

<style>
/* أنماط CSS من manage_books.php */
.card {
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.pagination .page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
}
</style>
<!-- بطاقات الإحصائيات -->
<div class="row mb-4">
    <!-- طلبات الشراء -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-gradient-success text-white py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-shopping-cart me-2"></i> طلبات الشراء
                </h5>
            </div>
            <div class="card-body text-center py-4">
                <h2 class="display-5 mb-3 fw-bold text-success">
                    <?= $stats['total_purchases'] ?>
                </h2>
                <small class="text-muted">(قيد المعالجة: <?= $stats['pending_purchases'] ?>)</small>
            </div>
        </div>
    </div>

    <!-- طلبات الاستعارة -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-gradient-primary text-white py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-book me-2"></i> طلبات الاستعارة
                </h5>
            </div>
            <div class="card-body text-center py-4">
                <h2 class="display-5 mb-3 fw-bold text-primary">
                    <?= $stats['total_borrows'] ?>
                </h2>
                <small class="text-muted">(قيد المعالجة: <?= $stats['pending_borrows'] ?>)</small>
            </div>
        </div>
    </div>
</div>



    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-gradient-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-hand-holding me-2"></i> إدارة طلبات الاستعارة
                </h5>
            </div>
        </div>

        <div class="card-body p-0">
            <?php include __DIR__ . '/../includes/alerts.php'; ?>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>#</th>
                            <th>المستخدم</th>
                            <th>الكتاب</th>
                            <th>تاريخ الطلب</th>
                            <th>العملية</th>
                            <th>الحالة</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $index => $request): ?>
                        <tr>
                            <td><?= ($current_page - 1) * $records_per_page + $index + 1 ?></td>
                            <td><?= htmlspecialchars($request['user_name']) ?></td>
                            <td><?= htmlspecialchars($request['book_title']) ?></td>
                            <td><?= date('Y/m/d H:i', strtotime($request['request_date'])) ?></td>
                            <td>
                                <span class="badge bg-<?= getTypeColor($request['type']) ?>">
                                    <?= getTypeText($request['type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= getStatusColor($request['status']) ?>">
                                    <?= getStatusText($request['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="<?= BASE_URL ?>admin/process_request.php"
                                    class="d-flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                    <select name="action" class="form-select form-select-sm" required>
                                        <option value="approve"
                                            <?= $request['status'] === 'approved' ? 'disabled' : '' ?>>موافقة</option>
                                        <option value="reject"
                                            <?= $request['status'] === 'rejected' ? 'disabled' : '' ?>>رفض</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
                            <a class="page-link" 
                            href="?page=<?= $current_page - 1 ?>
                            &section=ops" aria-label="السابق">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&section=ops"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>&section=ops" aria-label="التالي">
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
// تأكيد الإجراءات الهامة (مثال: الحذف)
function confirmAction(e) {
    if (!confirm('هل أنت متأكد من تنفيذ هذا الإجراء؟')) {
        e.preventDefault();
    }
}
</script>