<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
$appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);

if (!$doctor_id || !$appointment_date) {
    echo json_encode(['error' => 'بيانات غير صالحة']);
    exit;
}

try {
    // الأوقات الأساسية المتاحة
    $all_times = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];
    
    // جلب المواعيد المحجوزة
    $stmt = $db->prepare("SELECT appointment_time FROM appointments 
                         WHERE doctor_id = ? AND appointment_date = ?");
    $stmt->bind_param("is", $doctor_id, $appointment_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_times = [];
    while ($row = $result->fetch_assoc()) {
        $booked_times[] = $row['appointment_time'];
    }
    
    // حساب الأوقات المتاحة
    $available_times = array_diff($all_times, $booked_times);
    
    echo json_encode([
        'available_times' => array_values($available_times)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'حدث خطأ في جلب البيانات']);
}