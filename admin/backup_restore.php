<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__ . '/../includes/config.php';

// التحقق من صلاحيات المشرف
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = 'ليست لديك صلاحية الوصول لهذه الصفحة.';
    header('Location: ../index.php');
    exit();
}

// إنشاء مجلد النسخ الاحتياطي إذا لم يكن موجوداً
$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    if (!mkdir($backupDir, 0755, true)) {
        $_SESSION['error'] = 'فشل في إنشاء مجلد النسخ الاحتياطي. تحقق من الصلاحيات.';
        header('Location: dashboard.php?section=bk');
        exit();
    }
}

// التحقق من إمكانية الكتابة للمجلد
if (!is_writable($backupDir)) {
    $_SESSION['error'] = 'مجلد النسخ الاحتياطي غير قابل للكتابة.';
    header('Location: dashboard.php?section=bk');
    exit();
}

// معالجة طلب النسخ الاحتياطي
if (isset($_POST['backup'])) {
    $backupType = $_POST['backup_type'] ?? 'database';
    
    try {
        if ($backupType === 'database') {
            // نسخ قاعدة البيانات
            $backupFile = $backupDir . '/db_backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // التحقق من وجود مسار mysqldump
            $mysqldumpPath = trim(shell_exec('which mysqldump')) ?: 'mysqldump';
            
            // بناء الأمر مع استخدام المسار الصحيح
            $command = sprintf(
                '%s --user=%s --password=%s --host=%s %s > %s 2>&1',
                escapeshellarg($mysqldumpPath),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_NAME),
                escapeshellarg($backupFile)
            );
            
            // تنفيذ الأمر مع التقاط الإخراج
            exec($command, $output, $return_var);
            
            if ($return_var !== 0) {
                throw new Exception('فشل في إنشاء نسخة قاعدة البيانات: ' . implode("\n", $output));
            }
            
            // التحقق من وجود الملف فعلياً
            if (!file_exists($backupFile) || filesize($backupFile) === 0) {
                throw new Exception('تم تنفيذ الأمر ولكن الملف الناتج فارغ أو غير موجود');
            }
            
            $_SESSION['success'] = 'تم إنشاء نسخة قاعدة البيانات بنجاح: ' . basename($backupFile);
        } 
        elseif ($backupType === 'files') {
            // نسخ الملفات
            ini_set('memory_limit', '512M');
            set_time_limit(0);
            
            $backupFile = $backupDir . '/files_backup_' . date('Y-m-d_H-i-s') . '.zip';
            $rootPath = realpath(__DIR__ . '/..');
            $backupRealPath = realpath($backupDir);
            
            $zip = new ZipArchive();
            if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception('لا يمكن إنشاء ملف ZIP.');
            }
            
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $fileDir = dirname($filePath);
                    
                    // تخطي مجلد النسخ الاحتياطي نفسه
                    if (strpos($fileDir, $backupRealPath) === 0) {
                        continue;
                    }
                    
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    
                    if ($zip->addFile($filePath, $relativePath) === false) {
                        throw new Exception('فشل في إضافة الملف: ' . $filePath);
                    }
                }
            }
            
            if (!$zip->close()) {
                throw new Exception('فشل في إغلاق ملف ZIP.');
            }
            
            $_SESSION['success'] = 'تم إنشاء نسخة الملفات بنجاح: ' . basename($backupFile);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    echo '<script>window.location.href = "dashboard.php?section=bk";</script>';
    exit();
}
// معالجة طلب الاسترجاع
if (isset($_POST['restore'])) {
    if (!isset($_FILES['restore_file']) || $_FILES['restore_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = 'يرجى اختيار ملف صالح للاسترجاع.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $file = $_FILES['restore_file'];
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $tempFile = $file['tmp_name'];
    
    try {
        if ($fileType === 'sql') {
            // استرجاع قاعدة البيانات
            $command = "mysql --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " < " . $tempFile;
            system($command, $output);
            
            if ($output !== 0) {
                throw new Exception('فشل في استرجاع قاعدة البيانات.');
            }
            
            $_SESSION['success'] = 'تم استرجاع قاعدة البيانات بنجاح من: ' . $file['name'];
        } 
        elseif ($fileType === 'zip') {
            // استرجاع الملفات
            $zip = new ZipArchive();
            if ($zip->open($tempFile) !== TRUE) {
                throw new Exception('لا يمكن فتح ملف ZIP.');
            }
            
            // استخراج الملفات للمجلد الرئيسي
            $rootPath = realpath(__DIR__ . '/..');
            $zip->extractTo($rootPath);
            $zip->close();
            
            $_SESSION['success'] = 'تم استرجاع الملفات بنجاح من: ' . $file['name'];
        } 
        else {
            throw new Exception('نوع الملف غير مدعوم. يرجى استخدام .sql أو .zip');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<div class="container mt-4">
    <div class="card border-0 shadow-sm">
        <!-- رأس البطاقة -->
        <div class="card-header bg-gradient-primary text-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-database me-2"></i> النسخ الاحتياطي والاسترجاع
            </h5>
        </div>

        <div class="card-body p-4">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="fas fa-check-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">نجاح!</h5>
                    <p class="mb-0"><?= $_SESSION['success'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">خطأ!</h5>
                    <p class="mb-0"><?= $_SESSION['error'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- نموذج النسخ الاحتياطي -->
            <form method="post" class="mb-5">
                <h4 class="text-muted mb-4">
                    <i class="fas fa-save me-2"></i> إنشاء نسخة احتياطية
                </h4>
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">اختر نوع النسخة:</label>
                    <div class="input-group">
                        <select name="backup_type" class="form-select">
                            <option value="database">قاعدة البيانات</option>
                            <option value="files">الملفات</option>
                        </select>
                        <button type="submit" name="backup" class="btn btn-primary rounded-pill">
                            <i class="fas fa-download me-2"></i> إنشاء
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle me-2"></i>
                        سيتم حفظ النسخ الاحتياطية في مجلد /backups
                    </small>
                </div>
            </form>

            <!-- نموذج الاسترجاع -->
            <form method="post" enctype="multipart/form-data">
                <h4 class="text-muted mb-4">
                    <i class="fas fa-upload me-2"></i> استرجاع نسخة
                </h4>
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">اختر ملف النسخة:</label>
                    <div class="input-group">
                        <input type="file" name="restore_file" class="form-control" required>
                        <button type="submit" name="restore" class="btn btn-warning rounded-pill">
                            <i class="fas fa-upload me-2"></i> استرجاع
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                        استرجاع قاعدة البيانات سيتجاوز البيانات الحالية. كن حذراً!
                    </small>
                </div>
            </form>

            <!-- قسم النسخ الاحتياطية الموجودة -->
            <div class="mt-5">
                <h4 class="text-muted mb-4">
                    <i class="fas fa-archive me-2"></i> النسخ الاحتياطية المتاحة
                </h4>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>اسم الملف</th>
                                <th>النوع</th>
                                <th>الحجم</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $backupFiles = glob($backupDir . '/*.{sql,zip}', GLOB_BRACE);
                            usort($backupFiles, function($a, $b) {
                                return filemtime($b) - filemtime($a);
                            });
                            
                            foreach ($backupFiles as $file):
                                $filename = basename($file);
                                $filetype = pathinfo($file, PATHINFO_EXTENSION);
                                $filesize = round(filesize($file) / 1024);
                                $filetime = date('Y-m-d H:i:s', filemtime($file));
                            ?>
                            <tr>
                                <td><?= $filename ?></td>
                                <td><?= strtoupper($filetype) ?></td>
                                <td><?= $filesize ?> KB</td>
                                <td><?= $filetime ?></td>
                                <td>
                                    <a href="../backups/<?= $filename ?>" 
                                       class="btn btn-sm btn-success"
                                       download>
                                        <i class="fas fa-download"></i> تحميل
                                    </a>
                                    <button class="btn btn-sm btn-danger delete-backup" 
                                            data-file="<?= $filename ?>">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($backupFiles)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-3"></i>
                                    <h5>لا توجد نسخ احتياطية</h5>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة تأكيد الحذف -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">تأكيد الحذف</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف النسخة الاحتياطية <strong id="fileName"></strong>؟</p>
                <p class="text-danger"><i class="fas fa-exclamation-circle me-2"></i>لا يمكن التراجع عن هذا الإجراء.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">حذف</button>
            </div>
        </div>
    </div>
</div>

<!-- الأنماط المخصصة -->
<style>
.card {
    border-radius: 12px;
    overflow: hidden;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.form-select,
.form-control {
    border-radius: 8px !important;
}

.btn-rounded {
    border-radius: 50px;
}

.alert {
    border-radius: 8px;
    border: none;
}

.table th {
    background-color: #f8f9fa;
}
</style>

<script>
// حذف النسخ الاحتياطية
document.querySelectorAll('.delete-backup').forEach(button => {
    button.addEventListener('click', function() {
        const fileName = this.getAttribute('data-file');
        document.getElementById('fileName').textContent = fileName;
        
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
        
        document.getElementById('confirmDelete').onclick = function() {
            fetch('delete_backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `file_name=${encodeURIComponent(fileName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('فشل في حذف الملف: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء محاولة الحذف');
            });
        };
    });
});
</script>