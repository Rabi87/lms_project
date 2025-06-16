<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرسائل الشخصية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --unread-bg: #e3f2fd;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Tajawal', sans-serif;
        }
        
        .email-app {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
            overflow: hidden;
            height: calc(100vh - 100px);
        }
        
        .email-sidebar {
            background-color: var(--light-color);
            border-right: 1px solid #eaeaea;
            height: 100%;
            padding: 20px 0;
        }
        
        .email-content {
            padding: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .email-header {
            padding: 20px;
            border-bottom: 1px solid #eaeaea;
            background: white;
        }
        
        .email-list {
            flex: 1;
            overflow-y: auto;
            border-bottom: 1px solid #eaeaea;
        }
        
        .email-preview {
            padding: 15px 20px;
            border-bottom: 1px solid #eaeaea;
            transition: all 0.2s;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .email-preview:hover {
            background-color: #f8f9fa;
        }
        
        .email-preview.unread {
            background-color: var(--unread-bg);
            font-weight: 500;
        }
        
        .email-preview.selected {
            background-color: #e3f2fd;
        }
        
        .email-preview .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 15px;
        }
        
        .email-body {
            padding: 30px;
            overflow-y: auto;
            flex: 1;
            background-color: #f9f9f9;
        }
        
        .email-actions {
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #eaeaea;
            display: flex;
            gap: 10px;
        }
        
        .sidebar-item {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s;
            border-right: 3px solid transparent;
        }
        
        .sidebar-item:hover, .sidebar-item.active {
            background-color: rgba(67, 97, 238, 0.1);
            border-right-color: var(--primary-color);
        }
        
        .sidebar-item .badge {
            margin-right: auto;
            margin-left: 10px;
        }
        
        .unread-badge {
            background-color: var(--primary-color);
        }
        
        .email-time {
            color: #6c757d;
            font-size: 0.85rem;
            margin-right: auto;
        }
        
        .email-sender {
            font-weight: 600;
            min-width: 150px;
        }
        
        .email-subject {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0 15px;
        }
        
        .message-content {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            line-height: 1.8;
        }
        
        .message-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .message-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .compose-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 100;
        }
        
        @media (max-width: 768px) {
            .email-sidebar {
                display: none;
            }
            
            .email-preview .avatar {
                display: none;
            }
        }
    </style>
</head>
<body>
   <body>
    <?php
    // اتصال بقاعدة البيانات
    $host = 'localhost';
    $dbname = 'test_db';
    $username = 'phpmyadmin'; // استبدل باسم المستخدم الخاص بك
    $password = 'P@ssw0rd_123!'; // استبدل بكلمة المرور
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // جلب الرسائل من قاعدة البيانات
        $stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // جلب عدد الرسائل غير المقروءة
        $unreadStmt = $pdo->query("SELECT COUNT(*) AS unread_count FROM messages WHERE is_read = 0");
        $unreadCount = $unreadStmt->fetchColumn();
        
        // إذا تم تحديد رسالة
        $selectedMessage = null;
        if (isset($_GET['message_id'])) {
            $message_id = $_GET['message_id'];
            $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
            $stmt->execute([$message_id]);
            $selectedMessage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // تحديث الرسالة كمقروءة
            if ($selectedMessage && $selectedMessage['is_read'] == 0) {
                $updateStmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
                $updateStmt->execute([$message_id]);
                $selectedMessage['is_read'] = 1;
            }
        }
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
    
    // دالة لتحويل التاريخ إلى صيغة مقروءة
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        
        $string = array(
            'y' => 'سنة',
            'm' => 'شهر',
            'w' => 'أسبوع',
            'd' => 'يوم',
            'h' => 'ساعة',
            'i' => 'دقيقة',
            's' => 'ثانية',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
            } else {
                unset($string[$k]);
            }
        }
        
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? 'منذ ' . implode(', ', $string) : 'الآن';
    }
    ?>
    
    <!-- شريط التنقل العلوي -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-envelope me-2"></i>بريد النظام
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-inbox me-1"></i> الوارد</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-paper-plane me-1"></i> المرسلة</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-trash me-1"></i> المحذوفة</a>
                    </li>
                </ul>
                <form class="d-flex ms-3">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="بحث في الرسائل...">
                        <button class="btn btn-light" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="email-app">
            <div class="row h-100">
                <!-- القائمة الجانبية -->
                <div class="col-md-3 d-none d-md-block email-sidebar">
                    <button class="btn btn-primary w-100 mb-4">
                        <i class="fas fa-edit me-2"></i>رسالة جديدة
                    </button>
                    
                    <div class="sidebar-item active">
                        <i class="fas fa-inbox me-2"></i> صندوق الوارد
                        <span class="badge unread-badge rounded-pill"><?= $unreadCount ?></span>
                    </div>
                    <div class="sidebar-item">
                        <i class="fas fa-star me-2"></i> المهمة
                    </div>
                    <div class="sidebar-item">
                        <i class="fas fa-paper-plane me-2"></i> المرسلة
                    </div>
                    <div class="sidebar-item">
                        <i class="fas fa-file-alt me-2"></i> المسودة
                    </div>
                    <div class="sidebar-item">
                        <i class="fas fa-trash me-2"></i> المحذوفة
                        <span class="badge bg-secondary rounded-pill">0</span>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="px-3 text-muted">التصنيفات</h6>
                        <div class="sidebar-item">
                            <i class="fas fa-circle text-primary me-2"></i> العمل
                        </div>
                        <div class="sidebar-item">
                            <i class="fas fa-circle text-success me-2"></i> العملاء
                        </div>
                        <div class="sidebar-item">
                            <i class="fas fa-circle text-warning me-2"></i> الإشعارات
                        </div>
                        <div class="sidebar-item">
                            <i class="fas fa-circle text-danger me-2"></i> مهم
                        </div>
                    </div>
                </div>
                
                <!-- المحتوى الرئيسي -->
                <div class="col-md-9 email-content">
                    <div class="email-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0">الرسائل الواردة</h4>
                                <p class="text-muted mb-0">لديك <?= $unreadCount ?> رسائل غير مقروءة</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <div class="btn-group">
                                    <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-filter"></i> الفرز
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">الأحدث أولاً</a></li>
                                        <li><a class="dropdown-item" href="#">الأقدم أولاً</a></li>
                                        <li><a class="dropdown-item" href="#">حسب المرسل</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- قائمة الرسائل -->
                    <div class="email-list">
                        <?php foreach ($messages as $message): ?>
                            <a href="emails_user.php?message_id=<?= $message['id'] ?>" class="email-preview <?= $message['is_read'] ? '' : 'unread' ?>">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                                <div class="avatar">ن</div>
                                <div class="email-sender">نظام الإدارة</div>
                                <div class="email-subject">
                                    <span class="fw-bold"><?= htmlspecialchars($message['subject']) ?></span>
                                </div>
                                <div class="email-time"><?= time_elapsed_string($message['created_at']) ?></div>
                                <div class="ms-2">
                                    <i class="far fa-star text-muted"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- محتوى الرسالة -->
                    <div class="email-body">
                        <?php if ($selectedMessage): ?>
                            <div class="message-content">
                                <div class="message-header">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="mb-0"><?= htmlspecialchars($selectedMessage['subject']) ?></h4>
                                        <div class="text-muted">
                                            <?php 
                                            $date = new DateTime($selectedMessage['created_at']);
                                            echo $date->format('l، j F Y H:i');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">ن</div>
                                        <div>
                                            <div class="fw-bold">نظام الإدارة</div>
                                            <div class="text-muted">إلى: المستخدم</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="message-body">
                                    <?= nl2br(htmlspecialchars($selectedMessage['message'])) ?>
                                </div>
                                
                                <div class="message-actions">
                                    <button class="btn btn-primary">
                                        <i class="fas fa-reply me-1"></i> رد
                                    </button>
                                    <button class="btn btn-outline-primary">
                                        <i class="fas fa-share me-1"></i> تحويل
                                    </button>
                                    <button class="btn btn-outline-danger">
                                        <i class="fas fa-trash me-1"></i> حذف
                                    </button>
                                    <button class="btn btn-outline-secondary ms-auto">
                                        <i class="fas fa-print me-1"></i> طباعة
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                                <h4>اختر رسالة لعرضها</h4>
                                <p class="text-muted">لا توجد رسالة محددة</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- أزرار الإجراءات -->
                    <div class="email-actions">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">تحديد الكل</label>
                        </div>
                        
                        <button class="btn btn-outline-danger">
                            <i class="fas fa-trash me-1"></i> حذف
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="fas fa-envelope me-1"></i> تمييز كمقروء
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="fas fa-envelope-open me-1"></i> تمييز كغير مقروء
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="fas fa-tag me-1"></i> تصنيف
                        </button>
                        
                        <div class="ms-auto">
                            <span class="text-muted me-2">1-<?= count($messages) ?> من <?= count($messages) ?></span>
                            <button class="btn btn-outline-secondary">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <button class="btn btn-outline-secondary">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- زر إنشاء رسالة جديدة -->
    <a href="#" class="btn btn-primary compose-btn">
        <i class="fas fa-edit"></i>
    </a>
    
    <!-- روابط JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تفاعل الرسائل
        document.querySelectorAll('.email-preview').forEach(preview => {
            preview.addEventListener('click', function(e) {
                e.preventDefault();
                
                // إزالة التحديد من الكل وإضافة للعنصر الحالي
                document.querySelectorAll('.email-preview').forEach(p => p.classList.remove('selected'));
                this.classList.add('selected');
                
                // إذا كانت غير مقروءة، نزيل الفئة unread
                if(this.classList.contains('unread')) {
                    this.classList.remove('unread');
                }
                
                // الانتقال إلى رابط الرسالة
                window.location.href = this.getAttribute('href');
            });
        });
        
        // تحديد الكل
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.email-preview .form-check-input');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>
</body>
</html>