<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();}
    require __DIR__ . '/../includes/config.php';

// جلب البيانات من جدول الشكاوى
$complaints = $conn->query("
    SELECT * FROM complaints 
    ORDER BY created_at DESC
");
// جلب بيانات الشكاوى مع الترقيم
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $records_per_page;

// حساب العدد الإجمالي للشكاوى
$total_complaints = $conn->query("SELECT COUNT(*) AS total FROM complaints")->fetch_assoc()['total'];
$total_pages = ceil($total_complaints / $records_per_page);

// جلب البيانات مع الترقيم
$complaints = $conn->query("
    SELECT * FROM complaints 
    ORDER BY created_at DESC
    LIMIT $records_per_page OFFSET $offset
");
?>

<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-gradient-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-exclamation-circle me-2"></i> إدارة الشكاوى
                </h5>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- الرسائل التحذيرية والنجاح -->
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

            <!-- الجدول -->
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>#</th>
                            <th>البريد الإلكتروني</th>
                            <th>نص الشكوى</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $complaints->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['complaint'])) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                            <td>
                                <span class="badge <?= $row['status'] === 'pending' ? 'bg-warning' : 'bg-success' ?>">
                                    <?= $row['status'] === 'pending' ? 'قيد المراجعة' : 'تم الحل' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($row['status'] === 'pending'): ?>
                                <a href="<?= BASE_URL ?>process.php?resolve_complaint=<?= $row['id'] ?>" 
                                   class="btn btn-success btn-sm">
                                   <i class="fas fa-check"></i> تم الحل
                                </a>
                                <?php endif; ?>
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
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>&section=complaints" aria-label="السابق">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&section=complaints"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>&section=complaints" aria-label="التالي">
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