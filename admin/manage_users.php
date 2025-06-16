<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/config.php';


// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) ){
    header("Location: " . BASE_URL . "login.php");
    exit();
}
if ($_SESSION['user_type'] != 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}


// إعداد الترقيم
$records_per_page = 6;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $records_per_page;

// جلب المستخدمين مع الترقيم
$query = "
    SELECT SQL_CALC_FOUND_ROWS
        u.id, 
        u.name, 
        u.email, 
        u.user_type, 
        u.status,
        SUM(CASE WHEN br.status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN br.status = 'approved' THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN br.status = 'rejected' THEN 1 ELSE 0 END) AS rejected
    FROM users u
    LEFT JOIN borrow_requests br ON u.id = br.user_id
    GROUP BY u.id
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

// حساب عدد الصفحات
$total_users = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_pages = ceil($total_users / $records_per_page);

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('طلب غير صالح');
    }
    $user_id = $conn->real_escape_string($_POST['user_id']);
    if (isset($_POST['delete_user'])) {
        // التحقق مما إذا كان المستخدم لديه طلبات استعارة
        $check_borrow_requests = $conn->prepare("SELECT COUNT(*) AS total_requests FROM borrow_requests WHERE user_id = ?");
        $check_borrow_requests->bind_param("i", $user_id);
        $check_borrow_requests->execute();
        $borrow_requests_result = $check_borrow_requests->get_result();
        $total_requests = $borrow_requests_result->fetch_assoc()['total_requests'];
        
        $delete_success = true;
        
        if ($total_requests > 0) {
            // حذف جميع الطلبات المرتبطة بالمستخدم
            $delete_requests_stmt = $conn->prepare("DELETE FROM borrow_requests WHERE user_id = ?");
            $delete_requests_stmt->bind_param("i", $user_id);
            $delete_success = $delete_requests_stmt->execute();
        }

        if ($delete_success) {
            // حذف المستخدم
            $delete_user_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete_user_stmt->bind_param("i", $user_id);
            if ($delete_user_stmt->execute()) {
                $_SESSION['success'] = "تم حذف الحساب";
            } else {
                $_SESSION['error'] = "فشل في حذف المستخدم";
            }
        } else {
            $_SESSION['error'] = "فشل في حذف طلبات الاستعارة، لم يتم حذف المستخدم";
        }
    } 
    elseif (isset($_POST['change_password'])) {
        // تغيير كلمة المرور
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $user_id);
        $stmt->execute();
    } 
    elseif (isset($_POST['change_status'])) {
        // تغيير الحالة (مفعل/غير مفعل)
        $status = $conn->real_escape_string($_POST['status']);
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $user_id);
        $stmt->execute();
    } 
    elseif (isset($_POST['make_admin'])) {
        // تغيير نوع المستخدم (مدير/عادي)
        $user_type = $conn->real_escape_string($_POST['user_type']);
        $stmt = $conn->prepare("UPDATE users SET user_type = ? WHERE id = ?");
        $stmt->bind_param("si", $user_type, $user_id);
        $stmt->execute();
    }
      
      echo '<script>window.location.href = "dashboard.php?section=users";</script>';
      exit();
}
?>
<style>
.input-group-sm .form-control {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.input-group-text {
    padding: 0.25rem 0.5rem;
}
</style>


<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-gradient-primary text-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-users me-2"></i> إدارة المستخدمين
            </h5>
            <!-- شريط البحث -->
            <div class="input-group input-group-sm" style="width: 200px;">
                <input type="text" id="searchEmail" class="form-control rounded-pill"
                    placeholder="ابحث بالبريد الإلكتروني">
                <span class="input-group-text bg-transparent border-0"><i class="fas fa-search"></i></span>
            </div>
             <div>
                <a href="add_user.php" class="btn btn-info btn-sm rounded-pill me-2">
                    <i class="fas fa-user-plus me-1"></i> عضو جديد
                </a>
                
            </div>

         
        </div>
    </div>

    <div class="card-body p-0">
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="sticky-top bg-light">
                    <tr>
                        <th>الاسم</th>
                        <th>البريد</th>
                        <th>النوع</th>
                        <th>الحالة</th>
                        <th>الطلبات</th>
                        <th class="text-end">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="user_type" onchange="this.form.submit()"
                                    class="form-select form-select-sm border-primary">
                                    <option value="user" <?= $user['user_type'] === 'user' ? 'selected' : '' ?>>
                                        عادي
                                    </option>
                                    <option value="admin" <?= $user['user_type'] === 'admin' ? 'selected' : '' ?>>
                                        مدير</option>
                                </select>
                                <input type="hidden" name="make_admin">
                            </form>
                        </td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="status" onchange="this.form.submit()"
                                    class="form-select form-select-sm border-info">
                                    <option value="0" <?= $user['status'] == 0 ? 'selected' : '' ?>>غير مفعل
                                    </option>
                                    <option value="1" <?= $user['status'] == 1 ? 'selected' : '' ?>>مفعل
                                    </option>
                                </select>
                                <input type="hidden" name="change_status">
                            </form>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <span class="badge bg-warning">
                                    <i class="fas fa-clock me-1"></i><?= $user['pending'] ?? 0 ?>
                                </span>
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i><?= $user['approved'] ?? 0 ?>
                                </span>
                                <span class="badge bg-danger">
                                    <i class="fas fa-times me-1"></i><?= $user['rejected'] ?? 0 ?>
                                </span>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-2">
                                <form method="post" onsubmit="return confirm('هل أنت متأكد؟');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user"
                                        class="btn btn-danger btn-sm px-3 py-1">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                <form method="post" class="d-flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="password" name="new_password" placeholder="كلمة جديدة"
                                        class="form-control form-control-sm" style="width: 120px;" required>
                                    <button type="submit" name="change_password"
                                        class="btn btn-warning btn-sm px-3 py-1">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </form>
                            </div>
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
                        <a class="page-link" href="?page=<?= $current_page - 1 ?>&section=users">
                            &laquo;
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&section=users"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page + 1 ?>&section=users">
                            &raquo;
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>


<!-- JavaScript for Confirmation -->
<script>
function confirmDelete(hasRequests) {
    let message = hasRequests ?
        "هذا المستخدم لديه طلبات استعارة. هل تريد حذف الطلبات ثم حذف المستخدم؟" :
        "هل أنت متأكد من أنك تريد حذف هذا المستخدم؟";
    return confirm(message);
}


// البحث الفوري بالبريد الإلكتروني
document.getElementById('searchEmail').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        row.style.display = email.includes(searchTerm) ? '' : 'none';
    });

    // إخفاء الترقيم عند البحث
    const pagination = document.querySelector('.pagination');
    if (pagination) {
        pagination.style.display = searchTerm ? 'none' : '';
    }
});
</script>