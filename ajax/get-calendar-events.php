<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// جلب المواعيد من قاعدة البيانات
$stmt = $db->query("SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
                   u.full_name as patient_name, du.full_name as doctor_name
                   FROM appointments a
                   JOIN patients p ON a.patient_id = p.patient_id
                   JOIN users u ON p.user_id = u.user_id
                   JOIN doctors d ON a.doctor_id = d.doctor_id
                   JOIN users du ON d.user_id = du.user_id");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];
foreach ($appointments as $appointment) {
    $events[] = [
        'id' => $appointment['appointment_id'],
        'title' => 'د. ' . $appointment['doctor_name'],
        'patient' => $appointment['patient_name'],
        'start' => $appointment['appointment_date'] . 'T' . $appointment['appointment_time'],
        'color' => '#3498db'
    ];
}

echo json_encode($events);
?>