<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$auth = new Auth();
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

$appointment_id = $_GET['id'] ?? 0;

// جلب بيانات الموعد
$stmt = $db->prepare("SELECT a.*, 
                     u.full_name as patient_name, 
                     du.full_name as doctor_name,
                     s.name as specialty_name
                     FROM appointments a
                     JOIN patients p ON a.patient_id = p.patient_id
                     JOIN users u ON p.user_id = u.user_id
                     JOIN doctors d ON a.doctor_id = d.doctor_id
                     JOIN users du ON d.user_id = du.user_id
                     JOIN specialties s ON d.specialty_id = s.specialty_id
                     WHERE a.appointment_id = ?");
$stmt->bind_param('i', $appointment_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header("Location: " . BASE_PATH . "/admin/appointments.php");
    exit();
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="موعد_' . $appointment['appointment_id'] . '.pdf"');

require_once __DIR__ . '/../tcpdf/tcpdf.php';

// إنشاء مستند PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('نظام المستشفى');
$pdf->SetTitle('موعد رقم ' . $appointment['appointment_id']);
$pdf->SetSubject('تفاصيل الموعد');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// محتوى PDF
$html = '
<style>
    .header { text-align: center; margin-bottom: 20px; }
    .logo { width: 100px; }
    .title { font-size: 18px; font-weight: bold; }
    .details { border-collapse: collapse; width: 100%; margin-top: 20px; }
    .details th { background-color: #f2f2f2; text-align: right; padding: 8px; }
    .details td { padding: 8px; border-bottom: 1px solid #ddd; }
    .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
</style>

<div class="header">
    <div class="title">نظام إدارة المستشفى</div>
    <div>تفاصيل الموعد الطبي</div>
</div>

<table class="details">
    <tr>
        <th width="30%">رقم الموعد</th>
        <td width="70%">' . $appointment['appointment_id'] . '</td>
    </tr>
    <tr>
        <th>المريض</th>
        <td>' . htmlspecialchars($appointment['patient_name']) . '</td>
    </tr>
    <tr>
        <th>الطبيب</th>
        <td>د. ' . htmlspecialchars($appointment['doctor_name']) . '</td>
    </tr>
    <tr>
        <th>التخصص</th>
        <td>' . htmlspecialchars($appointment['specialty_name']) . '</td>
    </tr>
    <tr>
        <th>تاريخ الموعد</th>
        <td>' . arabicDate($appointment['appointment_date']) . '</td>
    </tr>
    <tr>
        <th>وقت الموعد</th>
        <td>' . date('h:i A', strtotime($appointment['appointment_date'])) . '</td>
    </tr>
    <tr>
        <th>حالة الموعد</th>
        <td>' . getAppointmentStatusText($appointment['status']) . '</td>
    </tr>
    <tr>
        <th>ملاحظات</th>
        <td>' . ($appointment['notes'] ? htmlspecialchars($appointment['notes']) : 'لا يوجد ملاحظات') . '</td>
    </tr>
</table>

<div class="footer">
    تم إنشاء هذا المستند في ' . arabicDate(date('Y-m-d H:i:s')) . '<br>
    نظام إدارة المستشفى - جميع الحقوق محفوظة &copy; ' . date('Y') . '
</div>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('موعد_' . $appointment['appointment_id'] . '.pdf', 'I');
exit;