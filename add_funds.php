<?php
if (session_status() ===  PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$rabi = 50;

// ━━━━━━━━━━ جلب الرصيد الحالي ━━━━━━━━━━
$balance = 0.00;
try {
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $wallet = $result->fetch_assoc();
    $balance = $wallet ? (float)$wallet['balance'] : 0.00;
} catch (Exception $e) {
    error_log("Error fetching balance: " . $e->getMessage());
}

// ━━━━━━━━━━ تحديد نوع العملية ━━━━━━━━━━
$is_action = isset($_SESSION['required_amount']); // هل العملية إجبارية؟
$action = $_SESSION['action'] ?? 'topup'; // نوع العملية

// ━━━━━━━━━━ حساب المبلغ المطلوب ━━━━━━━━━━
if ($is_action) {
    // حالة إجباري: نقص رصيد لعملية محددة
    $required_amount = $_SESSION['required_amount'] ?? 25000;
    $funds = max(0, $required_amount - $balance);
    $_SESSION['funds']=$funds;
    $message = ($action === 'borrow') ? 'لإكمال الاستعارة' : 'لإكمال الشراء';
} else {
    // حالة طوعي: شحن مباشر من الصفحة الرئيسية
    $required_amount = 25000;
    $funds = 25000;
    $message = 'لشحن المحفظة';
}

// ━━━━━━━━━━ تنظيف الجلسة بعد الحساب ━━━━━━━━━━

?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-white bg-warning">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $is_action ? 'رصيد غير كافي' : 'شحن المحفظة' ?>
                    </h4>
                </div>
                <div class="card-body">
                    <!-- حالة الشحن الطوعي -->
                    <?php if (!$is_action): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-wallet me-2"></i>رصيدك الحالي: <?= number_format($balance, 2) ?> ل.س</h5>
                        <p class="mb-0">يمكنك شحن رصيدك بمبلغ 25000 ليرة لاستخدامها في الخدمات.</p>
                    </div>
                    
                    <!-- حالة عدم وجود رصيد مطلقًا -->
                    <?php elseif ($balance == 0): ?>
                    <div class="alert alert-dark">
                        <h5><i class="fas fa-wallet-slash me-2"></i>لا يوجد رصيد</h5>
                        <p class="mb-0">المحفظة فارغة. يرجى إضافة رصيد لاستخدام الخدمات.</p>
                    </div>
                    
                    <!-- حالة الرصيد غير كافي -->
                    <?php else: ?>
                    <div class="alert alert-danger">
                        <h5>رصيدك الحالي: <?= number_format($balance, 2) ?> ل.س</h5>
                        <p class="mb-0">تحتاج أن يكون رصيدك <?= number_format($required_amount) ?> ليرة على الأقل
                            <?= $message ?>.</p>
                    </div>
                    <?php endif; ?>

                    <!-- زر الدفع -->
                    <form action="payment.php" method="GET">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="funds" value="<?= $funds ?>">
                        <input type="hidden" name="rabi" value="<?= $rabi ?>">
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-coins me-2"></i>
                            <?= $is_action 
                                ? "شحن " . number_format($funds) . " ليرة" 
                                : "شحن 25000 ليرة" 
                            ?>
                        </button>
                    </form>
                    
                    <!-- رابط العودة للصفحة الرئيسية -->
                    <?php if ($is_action): ?>
                    <div class="mt-3 text-center">
                        <a href="index.php" class="text-secondary">
                            <i class="fas fa-arrow-left me-1"></i> العودة للصفحة الرئيسية
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>