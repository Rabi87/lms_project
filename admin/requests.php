<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_auth.php';

// جلب جميع الطلبات
$stmt = $conn->prepare("
    SELECT r.*, u.name AS user_name, b.title AS book_title 
    FROM borrow_requests r
    JOIN users u ON r.user_id = u.id
    JOIN books b ON r.book_id = b.id
    ORDER BY r.request_date DESC
");
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!-- جدول الطلبات -->
<table class="table">
    <thead>
        <tr>
            <th>المستخدم</th>
            <th>الكتاب</th>
            <th>تاريخ الطلب</th>
            <th>الحالة</th>
            <th>الإجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $request): ?>
        <tr>
            <td><?= htmlspecialchars($request['user_name']) ?></td>
            <td><?= htmlspecialchars($request['book_title']) ?></td>
            <td><?= date('Y/m/d H:i', strtotime($request['request_date'])) ?></td>
            <td>
                <span class="badge bg-<?= get_status_badge($request['status']) ?>">
                    <?= get_status_text($request['status']) ?>
                </span>
            </td>
            <td>
                <form method="POST" action="update_request.php">
                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                    <select name="status" class="form-select">
                        <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                        <option value="approved" <?= $request['status'] === 'approved' ? 'selected' : '' ?>>موافق</option>
                        <option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>مرفوض</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-2">تحديث</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>