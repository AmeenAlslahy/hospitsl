<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// دالة لإرجاع استجابة JSON والخروج
function jsonResponse($data) {
    echo json_encode($data);
    exit;
}

// دالة للتحقق من صلاحية الطبيب
function requireDoctor($auth) {
    if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'doctor') {
        jsonResponse(['error' => 'Unauthorized']);
    }
}

// دالة للتحقق من وجود متغير في GET
function requireGet($key) {
    if (!isset($_GET[$key])) {
        jsonResponse(['error' => "Missing $key"]);
    }
}

$auth = new Auth();
requireDoctor($auth);
requireGet('id');

$appointment_id = (int)$_GET['id'];
$doctor_id = getDoctorId($_SESSION['user_id'], $db);

try {
    $stmt = $db->prepare("
        SELECT 
            a.*, 
            u.full_name as patient_name, 
            p.patient_id,
            TIMEDIFF(a.end_time, a.start_time) as duration
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.user_id
        WHERE a.appointment_id = ? AND a.doctor_id = ?
    ");
    $stmt->bind_param('ii', $appointment_id, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        jsonResponse(['error' => 'Appointment not found']);
    }

    $appointment = $result->fetch_assoc();
    jsonResponse($appointment);

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()]);
}