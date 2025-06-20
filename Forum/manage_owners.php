<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/config.php';

// التحقق من وجود group_id
if (!isset($_GET['group_id'])) {
    header("Location: manage_groups.php");
    exit();
}
$group_id = intval($_GET['group_id']);

// التحقق من أن المستخدم هو المالك الأصلي
$stmt = $conn->prepare("SELECT owner_id, co_owner_id FROM users_groups WHERE group_id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result || $result['owner_id'] != $_SESSION['user_id']) {
    die("<div class='alert alert-danger m-3'>ليس لديك صلاحية لإدارة مالكي هذه المجموعة!</div>");
}

// جلب أعضاء المجموعة (لاختيار مالك مشارك)
$stmt = $conn->prepare("
    SELECT u.id, u.name 
    FROM group_members gm 
    JOIN users u ON gm.user_id = u.id 
    WHERE gm.group_id = ?
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// معالجة إضافة/إزالة المالك المشارك
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_co_owner'])) {
        $co_owner_id = intval($_POST['co_owner_id']);
        
        // تحديث المالك المشارك
        $stmt = $conn->prepare("UPDATE users_groups SET co_owner_id = ? WHERE group_id = ?");
        $stmt->bind_param("ii", $co_owner_id, $group_id);
        $stmt->execute();
        
        $_SESSION['message'] = "تم تعيين المالك المشارك بنجاح!";
    } 
    elseif (isset($_POST['remove_co_owner'])) {
        // إزالة المالك المشارك
        $stmt = $conn->prepare("UPDATE users_groups SET co_owner_id = NULL WHERE group_id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        
        $_SESSION['message'] = "تم إزالة المالك المشارك بنجاح!";
    }
    
    header("Location: manage_owners.php?group_id=" . $group_id);
    exit();
}

// جلب معلومات المالكين الحاليين
$stmt = $conn->prepare("
    SELECT 
        u1.id AS owner_id, u1.name AS owner_name,
        u2.id AS co_owner_id, u2.name AS co_owner_name
    FROM users_groups g
    LEFT JOIN users u1 ON g.owner_id = u1.id
    LEFT JOIN users u2 ON g.co_owner_id = u2.id
    WHERE g.group_id = ?
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$owners = $stmt->get_result()->fetch_assoc();

require __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h3 class="mb-4">
        إدارة مالكي المجموعة
        <a href="manage_groups.php" class="btn btn-secondary btn-sm float-left">العودة للمجموعات</a>
    </h3>

    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">المالك الأصلي</h5>
            <p class="card-text"><?= htmlspecialchars($owners['owner_name']) ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">المالك المشارك الحالي</h5>
            <?php if ($owners['co_owner_name']): ?>
                <p class="card-text"><?= htmlspecialchars($owners['co_owner_name']) ?></p>
                <form method="POST">
                    <button type="submit" name="remove_co_owner" class="btn btn-danger">
                        <i class="fas fa-user-minus"></i> إزالة المالك المشارك
                    </button>
                </form>
            <?php else: ?>
                <p class="card-text text-muted">لا يوجد مالك مشارك</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">تعيين مالك مشارك جديد</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">اختر عضواً من المجموعة:</label>
                    <select name="co_owner_id" class="form-select" required>
                        <option value="">-- اختر عضوا --</option>
                        <?php foreach ($members as $member): ?>
                            <?php if ($member['id'] != $owners['owner_id']): ?>
                                <option value="<?= $member['id'] ?>">
                                    <?= htmlspecialchars($member['name']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="set_co_owner" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> تعيين كمالك مشارك
                </button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>