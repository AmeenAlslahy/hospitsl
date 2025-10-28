<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (isset($_GET['specialty_id'])) {
    $specialty_id = intval($_GET['specialty_id']);
    $db = new Database();
    
    $doctors = $db->query("SELECT d.doctor_id, u.full_name, d.qualification 
                           FROM doctors d
                           JOIN users u ON d.user_id = u.user_id
                           WHERE d.specialty_id = $specialty_id
                           ORDER BY u.full_name");
    
    $result = [];
    while ($doctor = $doctors->fetch_assoc()) {
        $result[] = $doctor;
    }
    
    echo json_encode($result);
} else {
    echo json_encode([]);
}
?>