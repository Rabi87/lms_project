<?php
session_start();
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../libs/tcpdf/vendor/autoload.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("⚠️ صلاحيات غير كافية!");
}

// جلب معايير التقرير من GET
$reportType = $_GET['report_type'] ?? 'users';
$startDate = $_GET['start_date'] ?? '1970-01-01';
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// تحديد الاستعلام والعنوان ورؤوس الأعمدة بناءً على نوع التقرير
switch ($reportType) {
    case 'users':
        $title = "تقارير المستخدمين";
        $query = "SELECT 
                    id,
                    name,
                    email,
                    CASE 
                        WHEN user_type = 'admin' THEN 'مدير'
                        ELSE 'مستخدم عادي'
                    END AS user_type,
                    created_at,
                    CASE
                        WHEN status = 1 THEN 'حساب فعال'
                        ELSE 'حساب غير فعال'
                    END AS status
                 FROM users
                 WHERE created_at BETWEEN '$startDate' AND '$endDate'";
        $headers = ["ID", "الاسم", "البريد", "نوع المستخدم", "تاريخ التسجيل", "الحالة"];
        break;

    case 'books':
        $title = "تقارير الكتب";
        $query = "SELECT b.id, b.title, b.author, b.quantity, b.price, c.category_name 
                  FROM books b
                  LEFT JOIN categories c ON b.category_id = c.category_id";
        $headers = ["ID", "العنوان", "المؤلف","الكمية", "السعر", "التصنيف"];
        break;

    case 'borrow_requests':
        $title = "طلبات الإعارة";
        $query = "SELECT 
                    br.id,
                    u.name as user_name,
                    b.title as book_title,
                    br.request_date,
                    CASE 
                        WHEN br.status = 'pending' THEN 'قيد المراجعة'
                        WHEN br.status = 'approved' THEN 'تمت الموافقة'
                        ELSE 'مرفوض'
                    END AS status,
                    CASE
                        WHEN br.type = 'borrow' THEN 'استعارة'
                        WHEN br.type = 'purchase' THEN 'شراء'
                        ELSE 'تجديد' 
                    END AS request_type,
                    br.due_date
                  FROM borrow_requests br
                  JOIN users u ON br.user_id = u.id
                  JOIN books b ON br.book_id = b.id
                  WHERE br.request_date BETWEEN '$startDate' AND '$endDate'";
        $headers = ["ID", "المستخدم", "الكتاب", "تاريخ الطلب", "الحالة", "النوع", "تاريخ الاستحقاق"];
        break;

    case 'payments':
        $title = "المدفوعات";
        $query = "SELECT 
                    p.payment_id,
                    u.name as user_name,
                    b.title as book_title,
                    p.amount,
                    p.payment_date,
                    CASE
                        WHEN p.payment_type = 'borrow' THEN 'استعارة'
                        WHEN p.payment_type = 'purchase' THEN 'شراء'
                        WHEN p.payment_type = 'topup' THEN 'شحن رصيد'
                        WHEN p.payment_type = 'penalty' THEN 'غرامة تأخير'
                        ELSE p.payment_type
                    END AS payment_type
                  FROM payments p
                  LEFT JOIN users u ON p.user_id = u.id
                  LEFT JOIN books b ON p.book_id = b.id
                  WHERE p.payment_date BETWEEN '$startDate' AND '$endDate'";
        $headers = ["ID", "المستخدم", "الكتاب", "المبلغ", "تاريخ الدفع", "نوع الدفع"];
        break;

    case 'notifications':
        $title = "الإشعارات";
        $query = "SELECT n.notification_id, u.name as user_name, n.message, n.created_at, n.is_read
                  FROM notifications n
                  JOIN users u ON n.user_id = u.id
                  WHERE n.created_at BETWEEN '$startDate' AND '$endDate'";
        $headers = ["ID", "المستخدم", "الرسالة", "تاريخ الإرسال", "مقروء"];
        break;

    case 'most_borrowed_books':
        $title = "الكتب الأكثر استعارة";
        $query = "SELECT 
                    b.id,
                    b.title,
                    COUNT(DISTINCT br.id) AS total_requests
                  FROM borrow_requests br
                  JOIN books b ON br.book_id = b.id
                  WHERE br.request_date BETWEEN '$startDate' AND '$endDate'
                  GROUP BY b.id
                  ORDER BY total_requests DESC
                  LIMIT 50";
        $headers = ["ID", "العنوان", "عدد الطلبات"];
        break;

    case 'most_purchased_books':
        $title = "الكتب الأكثر شراء";
        $query = "SELECT 
                    b.id,
                    b.title,
                    COUNT(DISTINCT p.payment_id) AS total_purchases
                  FROM payments p
                  JOIN borrow_requests br ON p.request_id = br.id
                  JOIN books b ON br.book_id = b.id
                  WHERE br.type = 'purchase'
                    AND p.status = 'completed'
                    AND br.request_date BETWEEN '$startDate' AND '$endDate'
                  GROUP BY b.id
                  ORDER BY total_purchases DESC
                  LIMIT 100";
        $headers = ["ID", "العنوان", "عدد المبيعات"];
        break;

    case 'most_active_users':
        $title = "المستخدمين الأكثر طلباً";
        $query = "SELECT u.id, u.name, COUNT(br.id) AS total_requests 
                  FROM borrow_requests br
                  JOIN users u ON br.user_id = u.id
                  WHERE br.request_date BETWEEN '$startDate' AND '$endDate'
                  GROUP BY u.id
                  ORDER BY total_requests DESC
                  LIMIT 10";
        $headers = ["ID", "الاسم", "عدد الطلبات"];
        break;

    default:
        die("❌ نوع التقرير غير صحيح!");
}

// تنفيذ الاستعلام
$result = $conn->query($query);
if (!$result) {
    die("❌ خطأ في الاستعلام: " . $conn->error);
}

// إنشاء PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('نظام المكتبة');
$pdf->SetTitle('تقرير النظام');
$pdf->SetSubject('تقرير النظام');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetFont('aealarabiya', '', 12);
$pdf->AddPage();

// عنوان التقرير
$pdf->SetFontSize(16);
$pdf->Cell(0, 10, $title, 0, 1, 'C');
$pdf->SetFontSize(12);
$pdf->Cell(0, 10, "الفترة: $startDate إلى $endDate", 0, 1, 'C');
$pdf->Ln(5);

// إنشاء الجدول
$html = '<table border="1" cellpadding="5">';
// الرأس
$html .= '<tr style="background-color:#f2f2f2;font-weight:bold;">';
foreach ($headers as $header) {
    $html .= '<th>' . $header . '</th>';
}
$html .= '</tr>';

// البيانات
while ($row = $result->fetch_assoc()) {
    $html .= '<tr>';
    foreach ($row as $cell) {
        // إزالة أي تنسيق HTML من الخلية
        $cleanCell = strip_tags($cell);
        $html .= '<td>' . $cleanCell . '</td>';
    }
    $html .= '</tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('report.pdf', 'D');
exit();