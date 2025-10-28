<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول']);
    exit;
}

$doctor_id = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT);
$patient_id = getPatientId($auth->getUserId(), $db);

if (!$doctor_id) {
    echo json_encode(['success' => false, 'message' => 'معرف الطبيب غير صالح']);
    exit;
}

try {
    $query = "SELECT appointment_id, appointment_date, start_time, status
              FROM appointments
              WHERE doctor_id = ? 
              AND patient_id = ?
              AND appointment_date <= CURDATE()
              ORDER BY appointment_date DESC, start_time DESC
              LIMIT 10";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $doctor_id, $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = [
            'id' => $row['appointment_id'],
            'date' => $row['appointment_date'],
            'time' => $row['start_time'],
            'status' => $row['status'],
            'display' => "{$row['appointment_date']} - {$row['start_time']} ({$row['status']})"
        ];
    }

    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);

} catch (Exception $e) {
    error_log("Error in get_previous_appointments: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في الخادم']);
}