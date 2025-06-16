<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require __DIR__ . '/../includes/config.php';

// ━━━━━━━━━━ التحقق من الصلاحيات ━━━━━━━━━━
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}
 $stmt_rental = $conn->prepare("SELECT value FROM settings WHERE name='rental_price'");
        $stmt_rental->execute();
        $result_rental = $stmt_rental->get_result();
        $row_rental = $result_rental->fetch_assoc();
        $rental_price = $row_rental['value'];
        $stmt_rental->close();

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user1 = $result->fetch_assoc();
    $user2=$user1['name'];

// ━━━━━━━━━━ جلب بيانات المحفظة ━━━━━━━━━━
$balance = 0.00;
try {
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $wallet = $result->fetch_assoc();
    $balance = $wallet ? (float)$wallet['balance'] : 0.00;
} catch (Exception $e) {
    error_log("Wallet Error: " . $e->getMessage());
}

// ━━━━━━━━━━ جلب الإشعارات (غير المقروءة فقط) ━━━━━━━━━━
$notifications=[];
$stmt = $conn->prepare("
    SELECT * 
    FROM notifications 
    WHERE user_id = ? 
    AND is_read = 0 
    AND (expires_at IS NULL OR expires_at > NOW())
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// ━━━━━━━━━━ جلب آخر دخول للمستخدم ━━━━━━━━━━
$last_login = 'غير متوفر';
try {
    $stmt = $conn->prepare("SELECT created_at FROM activity_logs 
                           WHERE user = ? AND event_type = 'login_success' 
                           ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user2);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $last_login = date('Y-m-d H:i:s', strtotime($row['created_at']));
    }
} catch (Exception $e) {
    error_log("Last Login Error: " . $e->getMessage());
}

// ━━━━━━━━━━ جلب آخر عملية شحن للمحفظة ━━━━━━━━━━
$last_deposit = [
    'amount' => 0.00,
    'date' => 'غير متوفر'
];
try {
    $stmt = $conn->prepare("SELECT amount, payment_date 
                           FROM payments 
                           WHERE user_id = ? AND payment_type = 'topup' 
                           ORDER BY payment_id DESC LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $last_deposit['amount'] = (float)$row['amount'];
        $last_deposit['date'] = date('Y-m-d H:i:s', strtotime($row['payment_date']));
    }
} catch (Exception $e) {
    error_log("Last Deposit Error: " . $e->getMessage());
}
?>

<?php if (isset($_SESSION['error'])): ?>
<script>
Swal.fire({
    icon: 'warning',
    title: 'انتبه.. !',
    text: '<?= $_SESSION['error'] ?>'
});
</script>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>


<div class="container mt-4">
    <!-- بطاقة الرصيد المحدثة -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-wallet me-2"></i> الرصيد
                    </h5>
                </div>

                <div class="card-body text-center py-4">
                    <?php if ($balance == 0.00): ?>
                    <div class="alert alert-warning py-2 mb-0 small d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1">لا يوجد رصيد متاح</h5>
                            <p class="mb-0">قم بإضافة رصيد لبدء استخدام الخدمات</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <h2 class="display-5 mb-3 fw-bold <?= $balance <  $rental_price  ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($balance, 2) ?> 
                                        </h2>
                    <?php if ($balance <  $rental_price ): ?>
                    <div class="alert alert-danger py-2 mb-0 small">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        الرصيد الحالي لا يكفي للاستعارة (الحد الأدنى  <?= $rental_price ?>ليرة)
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-light text-center py-2">
                    <div class="card-footer bg-light text-center py-2">
    <form action="<?= BASE_URL ?>payment.php" method="GET">
        <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill" name="charge">
            <i class="fas fa-coins me-1"></i> شحن الرصيد
        </button>
    </form>
</div>
                </div>
            </div>
        </div>
    
<!-- بطاقة آخر نشاط -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-gradient-info text-white py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-history me-2"></i> آخر نشاط
                </h5>
            </div>
            
            <div class="card-body py-4">
                <!-- آخر دخول -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-light p-2 rounded-circle me-3">
                            <i class="fas fa-sign-in-alt text-info fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted">آخر دخول</h6>
                            <p class="mb-0 fw-bold"><?= $last_login ?></p>
                        </div>
                    </div>
                    <span class="badge bg-info rounded-pill">
                        <i class="fas fa-check-circle me-1"></i> ناجح
                    </span>
                </div>
                
                <!-- آخر شحن -->
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-light p-2 rounded-circle me-3">
                            <i class="fas fa-money-bill-wave text-success fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted">آخر شحن</h6>
                            <?php if ($last_deposit['date'] === 'غير متوفر'): ?>
                            <p class="mb-0 fw-bold">لا توجد عمليات شحن</p>
                            <?php else: ?>
                            <p class="mb-0 fw-bold"><?= number_format($last_deposit['amount'], 2) ?> ل.س</p>
                            <small class="text-muted"><?= $last_deposit['date'] ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge bg-success rounded-pill">
                        <i class="fas fa-coins me-1"></i> ايداع
                    </span>
                </div>
            </div>
            
            <div class="card-footer bg-light text-center py-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    يتم تحديث هذه المعلومات تلقائياً
                </small>
            </div>
        </div>
    </div>
</div>


    <!-- قسم الإشعارات المحدث -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-bell me-2"></i> الإشعارات الحديثة
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
            <div class="alert alert-info m-4 d-flex align-items-center">
                <i class="fas fa-info-circle me-3 fa-2x"></i>
                <div>
                    <h5 class="alert-heading mb-1">لا توجد إشعارات جديدة</h5>
                    <p class="mb-0">سيظهر هنا أي إشعارات جديدة تتلقاها</p>
                </div>
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notif): ?>
                <div class="list-group-item list-group-item-action" 
                     data-notif="<?= $notif['notification_id'] ?>"
                     id="notif-<?= $notif['notification_id'] ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <a 
                            class="btn btn-sm btn-outline-primary"
                            onclick="markAsRead(<?= $notif['notification_id'] ?>, '<?= $notif['link'] ?>', event)">
                             X
                            </a>
                            <i class="fas fa-bell text-warning me-2"></i>
                            <span><?= htmlspecialchars($notif['message']) ?></span>
                        </div>
                        <small class="text-muted"><?= date('H:i', strtotime($notif['created_at'])) ?></small>
                    
                    <?php if ($notif['link']): ?>
                    <div class="mt-2 text-end">
                        <a href="<?= $notif['link'] ?>" 
                            class="btn btn-sm btn-outline-primary"
                            onclick="markAsRead(<?= $notif['notification_id'] ?>, '<?= $notif['link'] ?>', event)">
                            <i class="fas fa-external-link-alt me-1"></i> عرض التفاصيل
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- الأنماط المخصصة -->
<style>
.card {
    border-radius: 12px;
    overflow: hidden;
    border: none;
    transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.1);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.list-group-item {
    border-left: none;
    border-right: none;
    padding: 1.25rem 1.5rem;
    transition: all 0.3s;
}

.list-group-item:first-child {
    border-top: none;
}

.alert {
    border-radius: 10px;
    border: none;
}

.btn-outline-primary {
    border-radius: 50px;
    transition: all 0.3s;
}

/* إضافة تأثيرات للإشعار المخفي */
.notification-hidden {
    opacity: 0.4;
    text-decoration: line-through;
    background-color: #f8f9fa;
    transform: scale(0.98);
}

/* تأثير عند النقر على الإشعار */
.list-group-item:active {
    transform: scale(0.98);
}
</style>

<script>
async function markAsRead(notifId, link, event) {
    event.preventDefault();
    
    try {
        // إرسال طلب AJAX لتحديث حالة الإشعار
        const response = await fetch('/lms/admin/mark_read.php?id=' + notifId);
        const data = await response.json();
        
        if (data.success) {
            // إخفاء الإشعار بدلاً من حذفه
            const notificationElement = document.getElementById('notif-' + notifId);
            if (notificationElement) {
                // إضافة تأثير بسيط للإشعار
                notificationElement.classList.add('notification-hidden');
                
                // إخفاء زر "عرض التفاصيل"
                const btn = notificationElement.querySelector('a');
                if (btn) btn.style.display = 'none';
                
                // الانتقال إلى الصفحة بعد تأخير بسيط
                setTimeout(() => {
                    window.location.href = link;
                }, 300);
            } else {
                window.location.href = link;
            }
        } else {
            console.error('Error:', data.error);
            window.location.href = link;
        }
    } catch (error) {
        console.error('Error:', error);
        window.location.href = link;
    }
}
</script>