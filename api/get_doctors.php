<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموحة']);
    exit;
}

$specialty_id = filter_input(INPUT_GET, 'specialty_id', FILTER_VALIDATE_INT);

if (!$specialty_id) {
    echo json_encode(['success' => false, 'message' => 'معرف التخصص غير صالح']);
    exit;
}

try {
    $query = "SELECT d.doctor_id, u.full_name, s.name as specialty, 
                     d.qualification, d.bio, d.consultation_fee,
                     d.years_of_experience, u.profile_picture,
                     d.available_days, d.working_hours_start, d.working_hours_end
              FROM doctors d
              JOIN users u ON d.user_id = u.user_id
              JOIN specialties s ON d.specialty_id = s.specialty_id
              WHERE d.specialty_id = ? 
              AND u.is_active = 1 
              AND d.is_accepting_new_patients = 1
              ORDER BY u.full_name";

    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $specialty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $doctors = [];
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }

    echo json_encode([
        'success' => true,
        'doctors' => $doctors
    ]);

} catch (Exception $e) {
    error_log("Error in get_doctors: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في الخادم']);
}