<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// استعلام لاسترجاع تصنيفات المستخدم المفضلة
$stmt = $conn->prepare("
    SELECT c.category_id, c.category_name 
    FROM user_categories uc
    JOIN categories c ON uc.category_id = c.category_id
    WHERE uc.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$userCategories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// إذا لم يختر المستخدم أي تصنيفات
if (empty($userCategories)) {
    echo '<div class="alert alert-info mt-4">';
    echo 'لم تختر أي تصنيفات مفضلة بعد. <a href="user/dashboard.php?section=favorit">اختر تصنيفاتك المفضلة</a>';
    echo '</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// استخراج معرفات التصنيفات فقط
$categoryIds = array_column($userCategories, 'category_id');

// استعلام لاسترجاع الكتب المقترحة
$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
$types = str_repeat('i', count($categoryIds));

$query = "
    SELECT b.id, b.title, b.author, b.cover_image, b.description, b.evaluation
    FROM books b
    WHERE b.category_id IN ($placeholders)
    ORDER BY b.evaluation DESC
    LIMIT 12
";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$categoryIds);
$stmt->execute();
$recommendedBooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container mt-5">
    <h2 class="mb-4">الكتب المقترحة لك</h2>

    <!-- عرض التصنيفات المختارة -->
    <div class="mb-4">
        <h5>تصنيفاتك المفضلة:</h5>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($userCategories as $category): ?>
                <span class="badge bg-primary p-2"><?= htmlspecialchars($category['category_name']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- عرض الكتب المقترحة -->
    <?php if (!empty($recommendedBooks)): ?>
        <div class="row">
            <?php foreach ($recommendedBooks as $book): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <img src="<?= htmlspecialchars($book['cover_image']) ?>" class="card-img-top"
                            alt="<?= htmlspecialchars($book['title']) ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars($book['author']) ?></p>

                            <div class="rating">
                                <?php
                                $rating = $book['evaluation'];
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="details.php?id=<?= $book['id'] ?>" class="btn btn-primary w-100">
                                عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            لا توجد كتب متاحة في التصنيفات المفضلة لديك حالياً.
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>