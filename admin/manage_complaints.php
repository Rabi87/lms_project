<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/config.php';

// جلب البيانات من جدول الشكاوى مع الترقيم
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $records_per_page;

// حساب العدد الإجمالي للشكاوى
$total_complaints = $conn->query("SELECT COUNT(*) AS total FROM complaints")->fetch_assoc()['total'];
$total_pages = ceil($total_complaints / $records_per_page);

// جلب البيانات مع الترقيم
$complaints = $conn->query("
    SELECT * FROM complaints 
    ORDER BY created_at DESC
    LIMIT $records_per_page OFFSET $offset
");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الشكاوى</title>
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #6c757d;
            --success: #1cc88a;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }
        
        body {
            font-family: 'Tahoma', 'Arial', sans-serif;
            background-color: #f8f9fc;
            margin: 0;
            padding: 0;
        }
        
        .container-fluid {
            padding: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: none;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), #2a4365);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table th {
            background-color: #eaecf4;
            color: var(--dark);
            font-weight: bold;
            padding: 12px 15px;
            border-top: 1px solid #e3e6f0;
            border-bottom: 2px solid #e3e6f0;
        }
        
        .table td {
            padding: 12px 15px;
            border-top: 1px solid #e3e6f0;
            vertical-align: middle;
        }
        
        .table tr:hover {
            background-color: #f8f9fc;
        }
        
        .badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-weight: normal;
            font-size: 0.85em;
        }
        
        .bg-warning {
            background-color: var(--warning) !important;
            color: #000;
        }
        
        .bg-success {
            background-color: var(--success) !important;
            color: #fff;
        }
        
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
        }
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        
        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
        }
        
        .btn i {
            margin-left: 5px;
        }
        
        .pagination {
            margin: 0;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .page-link {
            color: var(--primary);
        }
        
        /* مودال ثابت بدون استخدام Bootstrap JS */
      .modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 10px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    z-index: 10000;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary), #2a4365);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            background-color: #f8f9fc;
            border-radius: 0 0 10px 10px;
            border-top: 1px solid #e3e6f0;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* فلتر الشكاوى */
        .filter-container {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .filter-select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #d1d3e2;
            background-color: white;
        }
        
        /* تفاصيل الشكوى */
        .detail-row {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .detail-value {
            background-color: #f8f9fc;
            padding: 10px;
            border-radius: 5px;
            border-left: 3px solid var(--primary);
        }
        
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .detail-table th, .detail-table td {
            padding: 8px 12px;
            border: 1px solid #e3e6f0;
        }
        
        .detail-table th {
            background-color: #eaecf4;
            text-align: left;
            width: 30%;
        }
        
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-container {
                flex-direction: column;
            }
            
            .table-responsive {
                overflow-x: scroll;
            }
            
            .table th, .table td {
                padding: 8px 10px;
                font-size: 0.85rem;
            }
            
            .modal-content {
                width: 95%;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-exclamation-circle me-2"></i> إدارة الشكاوى
                </h5>
                <div class="filter-container">
                    <select class="filter-select" id="filterStatus">
                        <option value="all">جميع الشكاوى</option>
                        <option value="pending">قيد المراجعة فقط</option>
                        <option value="resolved">تم الحل فقط</option>
                    </select>
                    <input type="text" class="filter-select" id="searchInput" placeholder="بحث...">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- الرسائل التحذيرية والنجاح -->
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success m-3">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- الجدول -->
            <div class="table-responsive">
                <table class="table">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>#</th>
                            <th>البريد الإلكتروني</th>
                            <th>نوع الشكوى</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                   <tbody>
                        <?php 
                        $type_names = [
                            'account' => 'مشكلة في الحساب',
                            'payment' => 'مشكلة في الدفع',
                            'book' => 'كتاب غير متوفر',
                            'borrow' => 'مشكلة في الاستعارة',
                            'quality' => 'جودة كتاب غير مقبولة',
                            'delivery' => 'تأخر في التوصيل',
                            'other' => 'شكوى أخرى'
                        ];
                        
                        while ($row = $complaints->fetch_assoc()): 
                        ?>
                        <tr data-status="<?= $row['status'] ?>" data-id="<?= $row['id'] ?>">
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= $type_names[$row['complaint_type']] ?? 'شكوى غير محددة' ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                            <td>
                                <span class="badge <?= $row['status'] === 'pending' ? 'bg-warning' : 'bg-success' ?>">
                                    <?= $row['status'] === 'pending' ? 'قيد المراجعة' : 'تم الحل' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>admin/get_complaint_details.php?id=<?= $row['id'] ?>" 
                                class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> تفاصيل
                                </a>
                                <?php if ($row['status'] === 'pending'): ?>
                                <a href="<?= BASE_URL ?>process.php?resolve_complaint=<?= $row['id'] ?>" 
                                class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> تم الحل
                                </a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>process.php?delete_complaint=<?= $row['id'] ?>" 
                                class="btn btn-danger btn-sm"
                                onclick="return confirm('هل أنت متأكد من حذف هذه الشكوى؟')">
                                <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- الترقيم -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-light">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>&section=complaints" aria-label="السابق">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&section=complaints"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>&section=complaints" aria-label="التالي">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- مودال عرض التفاصيل -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">تفاصيل الشكوى #<span id="complaintId"></span></h5>
            <button class="close-btn" id="closeModal">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- سيتم تعبئة المحتوى هنا عبر JavaScript -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="closeBtn">إغلاق</button>
            <a href="#" class="btn btn-success" id="resolveBtn">
               <i class="fas fa-check me-1"></i> تم الحل
            </a>
        </div>
    </div>
</div>

<script>
// فلترة الشكاوى حسب الحالة
document.getElementById('filterStatus').addEventListener('change', function() {
    const status = this.value;
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        if (status === 'all') {
            row.style.display = '';
        } else {
            const rowStatus = row.getAttribute('data-status');
            row.style.display = (rowStatus === status) ? '' : 'none';
        }
    });
});

// بحث الشكاوى
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        row.style.display = rowText.includes(searchTerm) ? '' : 'none';
    });
});

// في قسم عرض التفاصيل
document.querySelectorAll('.view-details').forEach(button => {
    button.addEventListener('click', function() {
        const row = this.closest('tr');
        const complaintId = row.getAttribute('data-id');
        
        // استخدام المسار المطلق مع BASE_URL
        fetch('<?= BASE_URL ?>admin/get_complaint_details.php?id=' + complaintId)
            .then(response => response.json()) // تحويل الاستجابة مباشرة إلى JSON
            .then(data => {
                if (data.success)  {
                    const complaint = data.complaint;
                    // استخدام additional_data مباشرة دون تحليل إضافي
                    const additional_data = complaint.additional_data || {};
                    
                    // بناء محتوى المودال
                    let html = `
                        <div class="detail-row">
                            <div class="detail-label">البريد الإلكتروني:</div>
                            <div class="detail-value">${complaint.email}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">التاريخ:</div>
                            <div class="detail-value">${new Date(complaint.created_at).toLocaleString()}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">نوع الشكوى:</div>
                            <div class="detail-value">${complaint.type_name}</div>
                        </div>`;
                    
                    if (Object.keys(additional_data).length > 0) {
                        html += `<div class="detail-row">
                            <div class="detail-label">البيانات الإضافية:</div>
                            <div class="detail-value">
                                <table class="detail-table">
                                    <tbody>`;
                        
                        for (const [key, value] of Object.entries(additional_data)) {
                            // التحقق من نوع القيمة
                            const displayValue = Array.isArray(value) 
                                ? value.join(', ') 
                                : (value || 'لا يوجد');
                                
                            html += `<tr>
                                <th>${key}</th>
                                <td>${displayValue}</td>
                            </tr>`;
                        }
                        
                        html += `</tbody>
                                </table>
                            </div>
                        </div>`;
                    }
                    
                    html += `<div class="detail-row">
                        <div class="detail-label">تفاصيل الشكوى:</div>
                        <div class="detail-value">${complaint.complaint_details || 'لا يوجد'}</div>
                    </div>`;
                    
                    // تعبئة المحتوى
                    document.getElementById('complaintId').textContent = complaint.id;
                    document.getElementById('modalBody').innerHTML = html;
                    
                    // تحديث رابط "تم الحل"
                    document.getElementById('resolveBtn').href = 
                        `<?= BASE_URL ?>process.php?resolve_complaint=${complaint.id}`;
                    
                    // عرض المودال
                    document.getElementById('detailsModal').style.display = 'flex';
                } else {
                    alert('حدث خطأ: ' + (data.message || 'غير معروف'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال: ' + error.message);
            });
    });
});

// إغلاق المودال
document.getElementById('closeModal').addEventListener('click', closeModal);
document.getElementById('closeBtn').addEventListener('click', closeModal);

function closeModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

// إغلاق المودال بالنقر خارج المحتوى
document.getElementById('detailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
</body>
</html>