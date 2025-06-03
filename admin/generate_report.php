<?php
session_start();
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../libs/tcpdf/vendor/autoload.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("⚠️ صلاحيات غير كافية!");
}

// جلب معايير التقرير
// معالجة المعلمات
$reportType = $_POST['report_type'] ?? '';
$startDate = $_POST['start_date'] ?? '1970-01-01';
$endDate = $_POST['end_date'] ?? date('Y-m-d');
$format = $_POST['format'] ?? 'pdf';


// استعلامات قاعدة البيانات
// استعلامات التقرير
switch ($reportType) {
    case 'users_report':
        $activityType = $_POST['user_activity'] ?? 'all';
        $query = "
            SELECT 
                u.id,
                u.name,
                u.email,
                COUNT(DISTINCT br.id) AS total_loans,
                COUNT(DISTINCT p.payment_id) AS total_purchases,
                SUM(p.amount) AS total_spent
            FROM users u
            LEFT JOIN borrow_requests br ON u.id = br.user_id
            LEFT JOIN payments p ON u.id = p.user_id
            WHERE 
                (br.request_date BETWEEN ? AND ? OR p.payment_date BETWEEN ? AND ?)
                " . ($activityType !== 'all' ? " AND " . ($activityType === 'loans' ? "br.id IS NOT NULL" : "p.payment_id IS NOT NULL") : "") . "
            GROUP BY u.id
        ";
        $filename = "تقرير_المستخدمين";
        $params = array_fill(0, 4, $startDate, $endDate);
        break;

    case 'books_report':
        $filterType = $_POST['book_filter'] ?? 'author';
        $filterValue = $_POST[$filterType . '_id'] ?? '';
        $query = "
            SELECT 
                b.id,
                b.title,
                a.name AS author,
                c.category_name,
                b.quantity,
                COUNT(br.id) AS total_borrows
            FROM books b
            JOIN authors a ON b.author_id = a.id
            JOIN categories c ON b.category_id = c.category_id
            LEFT JOIN borrow_requests br ON b.id = br.book_id
            WHERE 
                b.publication_date BETWEEN ? AND ?
                " . ($filterValue ? " AND " . ($filterType === 'author' ? "a.id = ?" : "c.category_id = ?") : "") . "
            GROUP BY b.id
        ";
        $filename = "تقرير_الكتب";
        $params = [$startDate, $endDate];
        if ($filterValue) $params[] = $filterValue;
        break;

    case 'financial_report':
        $query = "
            SELECT 
                SUM(w.balance) AS total_balance,
                SUM(p.amount) AS total_income,
                COUNT(p.payment_id) AS total_transactions
            FROM wallet w
            JOIN payments p ON w.user_id = p.user_id
            WHERE p.payment_date BETWEEN ? AND ?
        ";
        $filename = "التقرير_المالي";
        $params = [$startDate, $endDate];
        break;

    default:
        die("❌ نوع التقرير غير صحيح!");
}

// تنفيذ الاستعلام
$stmt = $conn->prepare($query);
$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// إنشاء التقرير
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$filename}.csv");
    $output = fopen('php://output', 'w');
    fputcsv($output, array_keys($data[0]));
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
} elseif ($format === 'pdf') {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetFont('aealarabiya', '', 12);
    $pdf->AddPage();
    
    // عنوان التقرير
    $pdf->Cell(0, 10, $filename, 0, 1, 'C', 0, '', 0, false, 'M', 'M');
    
    // جدول البيانات
    $html = '<table border="1" cellpadding="5">';
    $html .= '<tr>';
    foreach (array_keys($data[0]) as $header) {
        $html .= '<th style="background-color:#f8f9fa;">' . htmlspecialchars($header) . '</th>';
    }
    $html .= '</tr>';
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output("{$filename}.pdf", 'D');
}

exit();