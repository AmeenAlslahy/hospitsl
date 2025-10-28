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

// التحقق من وجود الموعد مع معلومات المريض
$stmt = $db->prepare("SELECT a.*, p.user_id as patient_user_id 
                     FROM appointments a
                     JOIN patients p ON a.patient_id = p.patient_id
                     WHERE a.appointment_id = ?");
$stmt->bind_param('i', $appointment_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header("Location: " . BASE_PATH . "/admin/appointments.php");
    exit();
}

// تأكيد الموعد
try {
    $stmt = $db->prepare("UPDATE appointments SET status = 'confirmed' WHERE appointment_id = ?");
    $stmt->bind_param('i', $appointment_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "تم تأكيد الموعد بنجاح";
        
        // إرسال إشعار للمريض إذا كان هناك patient_user_id
        if (!empty($appointment['patient_user_id'])) {
            $notification_sent = sendNotification(
                $appointment['patient_user_id'], 
                "تم تأكيد موعدك رقم #" . $appointment['appointment_id']
            );
            
            if (!$notification_sent) {
                error_log("Failed to send notification for appointment: " . $appointment_id);
            }
        }
    } else {
        throw new Exception('حدث خطأ أثناء تأكيد الموعد');
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: " . BASE_PATH . "/admin/view-appointment.php?id=$appointment_id");
exit();