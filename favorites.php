<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';


if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_redirect'] = 'favorites.php';
    header("Location: login.php");
    exit;
}

// جلب الكتب المفضلة
$user_id = $_SESSION['user_id'];
$query = "
    SELECT b.* 
    FROM books b
    JOIN favorite_books fb ON b.id = fb.book_id
    WHERE fb.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container my-5">
    <h1 class="text-center mb-5"><i class="fas fa-heart text-danger"></i> كتبي المفضلة</h1>
    
    <?php if($result->num_rows > 0): ?>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php while($book = $result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card h-100 shadow">
                        <img src="<?= BASE_URL.$book['cover_image'] ?>" 
                             class="card-img-top" 
                             alt="غلاف الكتاب"
                             style="height: 300px; object-fit: cover;">
                             
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($book['author']) ?></p>
                            
                            <button class="btn btn-danger btn-sm remove-favorite" 
                                    data-book-id="<?= $book['id'] ?>">
                                <i class="fas fa-trash"></i> إزالة من المفضلة
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> لم تقم بإضافة أي كتب إلى المفضلة بعد
        </div>
    <?php endif; ?>
</div>

<script>
// معالجة إزالة من المفضلة
$(document).on('click', '.remove-favorite', function() {
    const bookId = $(this).data('book-id');
    
    $.ajax({
        url: 'toggle_favorite.php',
        method: 'POST',
        data: {
            book_id: bookId,
            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
        },
        success: function(response) {
            if(response.success) {
                // إزالة البطاقة من الواجهة
                $(`[data-book-id="${bookId}"]`).closest('.col').remove();
                
                Swal.fire({
                    icon: 'success',
                    title: 'تمت الإزالة',
                    text: 'تمت إزالة الكتاب من المفضلة'
                });
            }
        }
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>