<!-- <?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (isset($_GET['doctor_id']) && isset($_GET['date'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $date = $_GET['date'];
    $db = new Database();
    
    // الأوقات المحجوزة لهذا الطبيب في التاريخ المحدد
    $booked = $db->query("SELECT appointment_time 
                          FROM appointments 
                          WHERE doctor_id = $doctor_id 
                          AND appointment_date = '$date'
                          AND status != 'cancelled'");
    
    $booked_times = [];
    while ($row = $booked->fetch_assoc()) {
        $booked_times[] = $row['appointment_time'];
    }
    
    // الأوقات المتاحة (من 9 صباحًا إلى 5 مساءً بفاصل 30 دقيقة)
    $available_times = [];
    $start = strtotime('09:00');
    $end = strtotime('17:00');
    
    for ($time = $start; $time <= $end; $time += 1800) { // 1800 ثانية = 30 دقيقة
        $time_str = date('H:i', $time);
        
        if (!in_array($time_str, $booked_times)) {
            $available_times[] = $time_str;
        }
    }
    
    echo json_encode($available_times);
} else {
    echo json_encode([]);
}*/
?> 