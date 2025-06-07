<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/header.php';

// التحقق من الصلاحيات
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$id = (int)$_GET['id'];

// جلب بيانات الشكوى
$stmt = $conn->prepare("SELECT * FROM complaints WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('الشكوى غير موجودة');
}

$complaint = $result->fetch_assoc();
$additional_data = json_decode($complaint['additional_data'] ?? '{}', true);

$type_names = [
    'account' => 'مشكلة في الحساب',
    'payment' => 'مشكلة في الدفع',
    'book' => 'كتاب غير متوفر',
    'borrow' => 'مشكلة في الاستعارة',
    'quality' => 'جودة كتاب غير مقبولة',
    'delivery' => 'تأخر في التوصيل',
    'other' => 'شكوى أخرى'
];
$field_translations = [
    'book_title' => 'عنوان الكتاب',           
    'book_author' => 'مؤلف الكتاب',
    'due_date' => 'تاريخ الإستحقاق',
    'borrow_date' => 'تاريخ الإستعارة',
    'borrowed_book' => 'الكتاب المُستعار',
    'book_id' => 'رقم الكتاب',
    'expected_delivery' => 'تاريخ التوصيل المتوقع',
    'actual_delivery' => 'تاريخ التوصيل الفعلي',
    'payment_method' => 'طريقة الدفع',
    'payment_id' => 'رقم العملية',
    'issue_type' => 'نوع المشكلة',
    'account_issue' => 'مشكلة في الحساب',
    'book_quality_issue' => 'مشكلة الجودة',
    'delivery_delay_reason' => 'سبب التأخير',
    'account_issue_type' => 'نوع المشكلة',
    'last_success_login' => 'آخر تسجيل ناجح',
    
    // يمكن إضافة المزيد حسب الحاجة
];


// معالجة حل الشكوى إذا تم الضغط على الزر
if (isset($_POST['resolve'])) {
    $updateStmt = $conn->prepare("UPDATE complaints SET status = 'resolved' WHERE id = ?");
    $updateStmt->bind_param("i", $id);
    
    if ($updateStmt->execute()) {
        $_SESSION['success'] = "تم حل الشكوى بنجاح";
        
        header("Location: " . BASE_URL . "admin/dashboard.php?section=complaints");
        exit();
    } else {
        $error = "حدث خطأ أثناء حل الشكوى";
       
    }
}
?>

    <style>
        
        .detail-row {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: bold;
            color: #5a5c69;
            margin-bottom: 5px;
        }
        
        .detail-value {
            background-color: #f8f9fc;
            padding: 10px;
            border-radius: 5px;
            border-left: 3px solid #4e73df;
        }
        
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .detail-table th, .detail-table td {
            padding: 10px;
            border: 1px solid #e3e6f0;
            text-align: right;
        }
        
        .detail-table th {
            background-color: #eaecf4;
            width: 30%;
        }
        
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-weight: bold;
        }
        
        .btn-back {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-resolve {
            background-color: #1cc88a;
            color: white;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>تفاصيل الشكوى #<?= $id ?></h2>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
        </div>
        <?php endif; ?>
        
        <div class="detail-row">
            <div class="detail-label">البريد الإلكتروني:</div>
            <div class="detail-value"><?= htmlspecialchars($complaint['email']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">التاريخ:</div>
            <div class="detail-value"><?= date('Y-m-d H:i', strtotime($complaint['created_at'])) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">نوع الشكوى:</div>
            <div class="detail-value"><?= $type_names[$complaint['complaint_type']] ?? 'شكوى غير محددة' ?></div>
        </div>
        
        <?php if (!empty($additional_data)): ?>
        <div class="detail-row">
            <div class="detail-label">البيانات الإضافية:</div>
            <div class="detail-value">
                <table class="detail-table">
                    <tbody>
                         <?php foreach ($additional_data as $key => $value): ?>
                <tr>
                   
                    <th><?= $field_translations[$key] ?? htmlspecialchars($key) ?></th>
                    <td>
                        <?php if (is_array($value)): ?>
                            <?= implode(', ', array_map('htmlspecialchars', $value)) ?>
                        <?php else: ?>
                            <?= htmlspecialchars($value) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="detail-row">
            <div class="detail-label">تفاصيل الشكوى:</div>
            <div class="detail-value"><?= nl2br(htmlspecialchars($complaint['complaint'])) ?></div>
           
        </div>
        
        <div class="actions">
            <a href="<?= BASE_URL ?>admin/dashboard.php?section=complaints" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> رجوع
            </a>
            
            <?php if ($complaint['status'] === 'pending'): ?>
            <form method="POST" style="margin:0">
                <button type="submit" name="resolve" class="btn btn-resolve">
                    <i class="fas fa-check"></i> تم الحل
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
<?php require __DIR__ . '/../includes/footer.php'; ?>