<?php
session_start();
require __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// التحقق من وجود الرمز الفريد للمجموعة
if (!isset($_GET['code'])) {
    header("Location: /Forum/manage_groups.php");
    exit();
}

$code = $_GET['code'];
$user_id = $_SESSION['user_id'];

// جلب بيانات المجموعة باستخدام الرمز الفريد
$stmt = $conn->prepare("
    SELECT g.group_id, g.owner_id, g.co_owner_id 
    FROM users_groups g 
    WHERE g.unique_code = ?
");
$stmt->bind_param("s", $code);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();

// التحقق من إذا كان المستخدم مديراً
$isAdmin = false;
$adminCheck = $conn->query("SELECT id FROM users WHERE id = $user_id AND user_type = 'admin'");
if ($adminCheck->num_rows > 0) {
    $isAdmin = true;
}

// إذا كان المستخدم هو المالك أو المالك المشارك أو مدير
$isAllowed = ($user_id == $group['owner_id'] || 
             $user_id == $group['co_owner_id'] || 
             $isAdmin);

// معالجة إضافة الكتاب
if ($isAllowed && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_link'])){
    $bookLink = $_POST['book_link'];
    
    $stmt = $conn->prepare("
        INSERT INTO group_books (group_id, book_link, added_by) 
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("isi", $group['group_id'], $bookLink, $user_id);
    $stmt->execute();
    $_SESSION['message'] = "تم إضافة الكتاب بنجاح!";
    header("Location: /lms/Forum/group_books.php?code=" . $code);
    exit();
}

// معالجة حذف الكتاب
if ($isAllowed && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])){
    $book_id = intval($_POST['book_id']);
    
    $stmt = $conn->prepare("
        DELETE FROM group_books 
        WHERE book_id = ? AND group_id = ?
    ");
    $stmt->bind_param("ii", $book_id, $group['group_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "تم حذف الكتاب بنجاح!";
    } else {
        $_SESSION['error'] = "فشل في حذف الكتاب!";
    }
    
    header("Location: /lms/Forum/group_books.php?code=" . $code);
    exit();
}

// جلب جميع كتب المجموعة
$stmt = $conn->prepare("
    SELECT b.book_id, b.book_link, u.name as added_by, b.added_at 
    FROM group_books b
    JOIN users u ON b.added_by = u.id
    WHERE b.group_id = ?
");
$stmt->bind_param("i", $group['group_id']);
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h3>
        كتب المجموعة
        <a href="<?= BASE_URL ?>Forum/manage_groups.php" class="btn btn-secondary btn-sm float-left">العودة للمجموعات</a>
    </h3>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- نموذج إضافة كتاب (للمسموح لهم فقط) -->
    <?php if ($isAllowed): ?>
    <form method="POST" class="mb-4">
        <div class="input-group">
            <input type="url" name="book_link" class="form-control" placeholder="رابط الكتاب" required>
            <button type="submit" class="btn btn-primary">إضافة كتاب</button>
        </div>
    </form>
    <?php endif; ?>

    <!-- جدول الكتب -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>الرابط</th>
                <th>أضيف بواسطة</th>
                <th>تاريخ الإضافة</th>
                <?php if ($isAllowed): ?>
                <th>الإجراءات</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
            <tr>
                <td>
                    <a href="<?= htmlspecialchars($book['book_link']) ?>" target="_blank">
                        <?= htmlspecialchars($book['book_link']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($book['added_by']) ?></td>
                <td><?= $book['added_at'] ?></td>
                <?php if ($isAllowed): ?>
                <td>
                    <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الكتاب؟');">
                        <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                        <button type="submit" name="delete_book" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> حذف
                        </button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>