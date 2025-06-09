<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require __DIR__ . '/../includes/config.php';
// التحقق من صلاحية المستخدم
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("الوصول مرفوض!");
}

// ------- معالجة الإضافة -------
if (isset($_POST['add_category'])) {
    $category_name = $conn->real_escape_string(trim($_POST['category_name']));
    
    if (empty($category_name)) {
        $_SESSION['error'] = "يجب إدخال اسم التصنيف";
        header("Location: .");
        exit();
    }

    $sql = "INSERT INTO categories (category_name) VALUES ('$category_name')";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "تمت الإضافة بنجاح";
    } else {
        $_SESSION['error'] = "فشل في الإضافة: " . $conn->error;
    }
    
    
    echo '<script>window.location.href = "dashboard.php?section=categories";</script>';
    exit();
}

// ------- معالجة التعديل -------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $category_id = (int)$_POST['category_id'];
    $category_name = $conn->real_escape_string(trim($_POST['category_name']));
    
    if (empty($category_name)) {
        $_SESSION['error'] = "يجب إدخال اسم التصنيف";
        header("Location:admin/dashboard.php?section=categories");
        exit();
    }

    $sql = "UPDATE categories SET category_name = '$category_name' 
            WHERE category_id = $category_id";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "تم التحديث بنجاح";
    } else {
        $_SESSION['error'] = "فشل في التحديث: " . $conn->error;
    }
    
      echo '<script>window.location.href = "dashboard.php?section=categories";</script>';
    exit();
}

// ------- معالجة الحذف -------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];
    
    $sql = "DELETE FROM categories WHERE category_id = $category_id";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "تم الحذف بنجاح";
    } else {
        $_SESSION['error'] = "فشل في الحذف: " . $conn->error;
    }
    
      echo '<script>window.location.href = "dashboard.php?section=categories";</script>';
    exit();
}

// ------- جلب البيانات -------
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$edit_data = [];

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $result = $conn->query("SELECT * FROM categories WHERE category_id = $edit_id");
    $edit_data = $result->fetch_assoc();
}
ob_end_flush();
?>
<!-- واجهة المستخدم -->
<div class="container mt-4">
    <div class="row">
        <!-- نموذج الإدارة -->
        <div class="col-md-5 mb-4">
            <div class="card">
                <div class="card-header">
                    <?= isset($_GET['edit']) ? 'تعديل التصنيف' : 'إضافة تصنيف جديد' ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if (isset($_GET['edit'])): ?>
                            <input type="hidden" name="category_id" value="<?= htmlspecialchars($edit_data['category_id'] ?? '') ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>اسم التصنيف</label>
                            <input type="text" 
                                   name="category_name" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($edit_data['category_name'] ?? '') ?>" 
                                   required>
                        </div>
                        
                        <div class="mt-3">
                            <?php if (isset($_GET['edit'])): ?>
                                <button type="submit" name="update_category" class="btn btn-warning">
                                    <i class="fas fa-save"></i> حفظ التعديلات
                                </button>
                                <a href="dashboard.php?section=categories" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                            <?php else: ?>
                                <button type="submit" name="add_category" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> إضافة جديد
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- قائمة التصنيفات -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    قائمة التصنيفات
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>التصنيف</th>
                                    <th width="150">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $categories->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                                    <td>
                                        <a href="dashboard.php?section=categories&edit=<?= $row['category_id'] ?>" 
                                           class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="category_id" value="<?= $row['category_id'] ?>">
                                            <button type="submit" name="delete_category" 
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('هل أنت متأكد؟')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


