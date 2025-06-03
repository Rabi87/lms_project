<?php
session_start();
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// جلب الطلبات مع تفاصيل العناصر
$stmt = $conn->prepare("
    SELECT 
        o.id AS order_id,
        o.total,
        o.created_at,
        o.status,
        oi.book_id,
        oi.quantity,
        oi.price AS item_price,
        b.title,
        b.cover_image
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN books b ON oi.book_id = b.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// تنظيم البيانات في مصفوفة
$orders = [];
while ($row = $result->fetch_assoc()) {
    $order_id = $row['order_id'];
    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'total' => $row['total'],
            'created_at' => $row['created_at'],
            'status' => $row['status'],
            'items' => []
        ];
    }
    $orders[$order_id]['items'][] = $row;
}
?>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.badge {
    font-size: 0.9rem;
    padding: 0.5em 0.75em;
}
</style>


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

<?php if (isset($_SESSION['success'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'شكرا لك.. !',
    text: '<?= $_SESSION['success'] ?>'
});
</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>


<div class="container my-5">
    <h1 class="text-center mb-5">💼 طلباتي السابقة</h1>

    <?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>رقم الطلب</th>
                    <th>التاريخ</th>
                    <th>المجموع</th>
                    <th>الحالة</th>
                    <th>الكتب</th>
                </tr>
            </thead>
            <tbody>
                <!-- عرض البيانات -->
                <?php foreach ($orders as $order_id => $order): ?>
                <tr>
                    <td>#<?= $order_id ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                    <td><?= number_format($order['total']) ?> ل.س</td>
                    <td>
                        <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 'warning' ?>">
                            <?= $order['status'] ?>
                        </span>
                    </td>
                    <td>
                        <?php foreach ($order['items'] as $item): ?>
                        <div class="mb-2">
                           
                            <?= $item['title'] ?>
                            (الكمية: <?= $item['quantity'] ?>)
                        </div>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i> لا توجد طلبات سابقة
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>