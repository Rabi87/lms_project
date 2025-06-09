<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// إعداد الترقيم
$records_per_page = 20;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $records_per_page;

try {
    // استعلام مع الترقيم
    $sql = "
        SELECT SQL_CALC_FOUND_ROWS
            p.payment_id,
            p.user_id,
            p.amount,
            p.payment_date,
            p.status AS payment_status,
            p.transaction_id,
            u.name AS user_name
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        ORDER BY p.payment_date DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = $result->fetch_all(MYSQLI_ASSOC);

    // حساب عدد الصفحات
    $total_payments = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
    $total_pages = ceil($total_payments / $records_per_page);

} catch (Exception $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>

<div class="card ">
    <div class="card-header bg-gradient-primary text-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-file-invoice-dollar me-2"></i> سجلات الدفع
            </h5>
        </div>
    </div>

    <div class="card-body p-0">
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <?php if (empty($payments)): ?>
            <div class="alert alert-info m-3">لا توجد سجلات دفع لعرضها</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>#</th>
                            <th>المستخدم</th>
                            <th>المبلغ (ل.س)</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th>رقم المعاملة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['payment_id']) ?></td>
                            <td>
                                <?= !empty($payment['user_name']) 
                                    ? htmlspecialchars($payment['user_name'])
                                    : 'مستخدم غير معروف (ID: ' . htmlspecialchars($payment['user_id']) . ')' ?>
                            </td>
                            <td><?= number_format($payment['amount'], 2) ?></td>
                            <td>
                                <?= $payment['payment_date'] 
                                    ? date('Y/m/d H:i', strtotime($payment['payment_date']))
                                    : '--' ?>
                            </td>
                            <td>
                                <?php
                                $status = $payment['payment_status'];
                                $badgeClass = [
                                    'pending' => 'warning',
                                    'completed' => 'success',
                                    'failed' => 'danger'
                                ][$status] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>">
                                    <?= $status ?>
                                </span>
                            </td>
                            <td><?= $payment['transaction_id'] ?? '--' ?></td>
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
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>&section=payment_logs">
                                &laquo;
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&section=payment_logs"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>&section=payment_logs">
                                &raquo;
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>