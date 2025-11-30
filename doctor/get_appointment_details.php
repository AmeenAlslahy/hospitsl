<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'doctor') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing appointment ID']);
    exit;
}

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
        echo json_encode(['error' => 'Appointment not found']);
        exit;
    }
    
    $appointment = $result->fetch_assoc();
    echo json_encode($appointment);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}