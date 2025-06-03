<?php
session_start();
require __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    die('يجب تسجيل الدخول');
}

$user_id = $_SESSION['user_id'];
$fav_query = "
    SELECT b.* 
    FROM books b
    JOIN favorite_books fb ON b.id = fb.book_id
    WHERE fb.user_id = $user_id
";
$fav_result = $conn->query($fav_query);
$favorite_books = $fav_result->fetch_all(MYSQLI_ASSOC);
?>

<?php if (!empty($favorite_books)): ?>
    <h2 class="text-center mb-4">كتبي المفضلة <i class="fas fa-heart text-danger"></i></h2>
    <div class="owl-carousel owl-theme" id="favoritesCarousel">
        <?php foreach ($favorite_books as $book): ?>
            <div class="item" data-book-id="<?= $book['id'] ?>">
                <div class="card h-100 shadow">
                    <?php if(!empty($book['cover_image'])): ?>
                        <img src="<?= BASE_URL . $book['cover_image'] ?>" 
                             class="card-img-top" 
                             alt="غلاف الكتاب"
                             style="height: 300px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                        <div class="d-flex justify-content-between mt-3">
                            <button class="btn btn-sm btn-danger toggle-favorite" 
                                    data-book-id="<?= $book['id'] ?>">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center">لم تقم بإضافة أي كتب إلى المفضلة بعد</div>
<?php endif; ?>