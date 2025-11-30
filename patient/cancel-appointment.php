<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_PATH . '/patient/appointments.php');
    exit;
}

$db = new Database();
$appointment_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$patient_id = getPatientId($_SESSION['user_id'], $db);

// التحقق من أن الموعد يخص المريض الحالي
try {
    $stmt = $db->prepare("SELECT * FROM appointments 
                         WHERE appointment_id = ? 
                         AND patient_id = ? 
                         AND status = 'confirmed'
                         AND appointment_date >= CURDATE()");
    $stmt->bind_param("ii", $appointment_id, $patient_id);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();

    if (!$appointment) {
        $_SESSION['error'] = "الموعد غير موجود أو لا يمكن إلغاؤه";
        header('Location: ' . BASE_PATH . '/patient/appointments.php');
        exit;
    }

    // إلغاء الموعد
    $cancel_stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' 
                               WHERE appointment_id = ?");
    $cancel_stmt->bind_param("i", $appointment_id);
    $cancel_stmt->execute();

    // إرسال إشعار للإدارة (وهمي في هذا المثال)
    // يمكن استبداله بوظيفة إرسال إيميل أو إشعار حقيقي
    $notification = "تم إلغاء الموعد رقم {$appointment_id} من قبل المريض";

    $_SESSION['success'] = "تم إلغاء الموعد بنجاح";
    header('Location: ' . BASE_PATH . '/patient/appointments.php');
    exit;

} catch (Exception $e) {
    error_log("Cancel appointment error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ أثناء محاولة إلغاء الموعد";
    header('Location: ' . BASE_PATH . '/patient/appointments.php');
    exit;
}