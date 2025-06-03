<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/config.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("الوصول مرفوض!");
}

ob_end_clean();

// جلب بيانات المستخدمين
$query = "
    SELECT 
        u.id, 
        u.name, 
        u.email, 
        u.user_type, 
        u.status,
        SUM(CASE WHEN br.status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN br.status = 'approved' THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN br.status = 'rejected' THEN 1 ELSE 0 END) AS rejected
    FROM users u
    LEFT JOIN borrow_requests br ON u.id = br.user_id
    GROUP BY u.id
";

$result = $conn->query($query);
$users = $result->fetch_all(MYSQLI_ASSOC);

// تحديد الصيغة
$format = $_GET['export'] ?? 'pdf';

// معالجة CSV
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_report.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    
    // كتابة العناوين
    fputcsv($output, ['الاسم', 'البريد', 'نوع المستخدم', 'الحالة', 'طلبات معلقة', 'طلبات مقبولة', 'طلبات مرفوضة']);
    
    // كتابة البيانات
    foreach ($users as $user) {
        fputcsv($output, [
            $user['name'],
            $user['email'],
            $user['user_type'],
            $user['status'] ? 'مفعل' : 'غير مفعل',
            $user['pending'],
            $user['approved'],
            $user['rejected']
        ]);
    }
    
    fclose($output);
    exit();

} elseif ($format === 'pdf') {
    require_once(__DIR__ . '/../libs/tcpdf/vendor/tecnickcom/tcpdf/tcpdf.php');
    
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetFont('aealarabiya', '', 12);
    $pdf->AddPage();
    
    // HTML للجدول
    $html = '<h1 style="text-align:center;font-family:aealarabiya;">تقرير المستخدمين</h1>';
    $html .= '<table border="1" cellpadding="5" style="font-family:aealarabiya;">';
    $html .= '<tr>
        <th>الاسم</th>
        <th>البريد</th>
        <th>نوع المستخدم</th>
        <th>الحالة</th>
        <th>طلبات معلقة</th>
        <th>طلبات مقبولة</th>
        <th>طلبات مرفوضة</th>
    </tr>';
    
    foreach ($users as $user) {
        $html .= '<tr>';
        $html .= '<td>' . $user['name'] . '</td>';
        $html .= '<td>' . $user['email'] . '</td>';
        $html .= '<td>' . $user['user_type'] . '</td>';
        $html .= '<td>' . ($user['status'] ? 'مفعل' : 'غير مفعل') . '</td>';
        $html .= '<td>' . $user['pending'] . '</td>';
        $html .= '<td>' . $user['approved'] . '</td>';
        $html .= '<td>' . $user['rejected'] . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('users_report.pdf','D');
    exit();
}

exit();