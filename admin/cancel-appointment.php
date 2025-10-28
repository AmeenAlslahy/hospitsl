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

// التحقق من وجود الموعد
$stmt = $db->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
$stmt->bind_param('i', $appointment_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header("Location: " . BASE_PATH . "/admin/appointments.php");
    exit();
}

// إلغاء الموعد
try {
    $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?");
    $stmt->bind_param('i', $appointment_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "تم إلغاء الموعد بنجاح";
        
        // إرسال إشعار للمريض
        sendNotification($appointment['patient_id'], "تم إلغاء موعدك مع الطبيب");
    } else {
        throw new Exception('حدث خطأ أثناء إلغاء الموعد');
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: " . BASE_PATH . "/admin/view-appointment.php?id=$appointment_id");
exit();