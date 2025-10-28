<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    header("Location: login.php");
    exit();
}

// التحقق من CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'طلب غير صالح';
    header("Location: job-applications.php");
    exit();
}

// جلب البيانات حسب عوامل التصفية
$status_filter = isset($_POST['status']) ? $db->escape($_POST['status']) : '';
$job_filter = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;

$sql = "SELECT ja.*, j.title as job_title 
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.job_id
        WHERE 1=1";

if (!empty($status_filter)) {
    $sql .= " AND ja.status = '$status_filter'";
}

if ($job_filter > 0) {
    $sql .= " AND ja.job_id = $job_filter";
}

$sql .= " ORDER BY ja.applied_at DESC";

try {
    $applications = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "حدث خطأ أثناء جلب البيانات للتصدير";
    header("Location: job-applications.php");
    exit();
}

// تحديد نوع التصدير
$format = isset($_POST['format']) ? $_POST['format'] : 'excel';

// التصدير حسب الصيغة المطلوبة
switch ($format) {
    case 'csv':
        exportToCSV($applications);
        break;
    case 'pdf':
        exportToPDF($applications);
        break;
    case 'excel':
    default:
        exportToExcel($applications);
}

function exportToExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="طلبات_التوظيف_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr>
            <th>رقم الطلب</th>
            <th>اسم المتقدم</th>
            <th>الوظيفة</th>
            <th>البريد الإلكتروني</th>
            <th>الهاتف</th>
            <th>تاريخ التقديم</th>
            <th>الحالة</th>
          </tr>";
    
    foreach ($data as $row) {
        echo "<tr>
                <td>{$row['application_id']}</td>
                <td>{$row['applicant_name']}</td>
                <td>{$row['job_title']}</td>
                <td>{$row['applicant_email']}</td>
                <td>{$row['applicant_phone']}</td>
                <td>" . formatDate($row['applied_at']) . "</td>
                <td>" . getApplicationStatusText($row['status']) . "</td>
              </tr>";
    }
    
    echo "</table>";
    exit;
}

function exportToCSV($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="طلبات_التوظيف_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // كتابة العناوين
    fputcsv($output, [
        'رقم الطلب',
        'اسم المتقدم',
        'الوظيفة',
        'البريد الإلكتروني',
        'الهاتف',
        'تاريخ التقديم',
        'الحالة'
    ]);
    
    // كتابة البيانات
    foreach ($data as $row) {
        fputcsv($output, [
            $row['application_id'],
            $row['applicant_name'],
            $row['job_title'],
            $row['applicant_email'],
            $row['applicant_phone'],
            formatDate($row['applied_at']),
            getApplicationStatusText($row['status'])
        ]);
    }
    
    fclose($output);
    exit;
}

function exportToPDF($data) {
    require_once __DIR__ . '/../tcpdf/tcpdf.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('نظام التوظيف');
    $pdf->SetTitle('طلبات التوظيف');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    
    $html = '<h1 style="text-align:center;">طلبات التوظيف</h1>
             <p style="text-align:center;">تاريخ التقرير: ' . date('Y-m-d') . '</p>
             <table border="1" cellpadding="4">
               <tr style="background-color:#f2f2f2;">
                 <th width="10%">رقم الطلب</th>
                 <th width="20%">اسم المتقدم</th>
                 <th width="20%">الوظيفة</th>
                 <th width="15%">البريد الإلكتروني</th>
                 <th width="15%">الهاتف</th>
                 <th width="10%">تاريخ التقديم</th>
                 <th width="10%">الحالة</th>
               </tr>';
    
    foreach ($data as $row) {
        $html .= '<tr>
                    <td>' . $row['application_id'] . '</td>
                    <td>' . $row['applicant_name'] . '</td>
                    <td>' . $row['job_title'] . '</td>
                    <td>' . $row['applicant_email'] . '</td>
                    <td>' . $row['applicant_phone'] . '</td>
                    <td>' . formatDate($row['applied_at']) . '</td>
                    <td>' . getApplicationStatusText($row['status']) . '</td>
                  </tr>';
    }
    
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('طلبات_التوظيف_' . date('Y-m-d') . '.pdf', 'D');
    exit;
}