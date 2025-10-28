<?php
// تمكين عرض الأخطاء للتصحيح (يجب تعطيله في البيئة الإنتاجية)
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// تعيين رأس JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // التحقق من طريقة الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('طريقة الطلب غير مسموحة', 405);
    }

    // التحقق من وجود المعلمات
    if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
        throw new Exception('معلمات الطلب غير مكتملة', 400);
    }

    // تنظيف المدخلات
    $doctor_id = filter_var($_GET['doctor_id'], FILTER_VALIDATE_INT);
    $date = filter_var($_GET['date'], FILTER_SANITIZE_STRING);

    if (!$doctor_id || !strtotime($date)) {
        throw new Exception('بيانات غير صالحة', 400);
    }

    // جلب معلومات الطبيب
    $stmt = $db->prepare("SELECT working_hours_start, working_hours_end, available_days 
                         FROM doctors 
                         WHERE doctor_id = ?");
    if (!$stmt) {
        throw new Exception('خطأ في إعداد الاستعلام', 500);
    }

    $stmt->bind_param("i", $doctor_id);
    if (!$stmt->execute()) {
        throw new Exception('خطأ في تنفيذ الاستعلام', 500);
    }

    $doctor = $stmt->get_result()->fetch_assoc();
    if (!$doctor) {
        throw new Exception('الطبيب غير موجود', 404);
    }

    // التحقق من أيام العمل
    $day_of_week = date('N', strtotime($date));
    $available_days = array_filter(explode(',', $doctor['available_days']));
    
    if (!in_array($day_of_week, $available_days)) {
        echo json_encode([
            'success' => true,
            'available_times' => [],
            'message' => 'الطبيب غير متاح في هذا اليوم'
        ]);
        exit;
    }

    // جلب المواعيد المحجوزة
    $stmt = $db->prepare("SELECT start_time, end_time 
                         FROM appointments 
                         WHERE doctor_id = ? 
                         AND appointment_date = ? 
                         AND status NOT IN ('cancelled', 'rejected')");
    if (!$stmt) {
        throw new Exception('خطأ في إعداد استعلام المواعيد', 500);
    }

    $stmt->bind_param("is", $doctor_id, $date);
    if (!$stmt->execute()) {
        throw new Exception('خطأ في جلب المواعيد', 500);
    }

    $booked_slots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // توليد الأوقات المتاحة
    $start = strtotime($doctor['working_hours_start']);
    $end = strtotime($doctor['working_hours_end']);
    $interval = 15 * 60; // 15 دقيقة
    $duration = 30 * 60; // 30 دقيقة مدة الاستشارة

    $available_times = [];
    for ($time = $start; $time <= $end - $duration; $time += $interval) {
        $time_end = $time + $duration;
        $is_available = true;

        foreach ($booked_slots as $slot) {
            $slot_start = strtotime($slot['start_time']);
            $slot_end = strtotime($slot['end_time']);
            
            if ($time < $slot_end && $time_end > $slot_start) {
                $is_available = false;
                break;
            }
        }

        if ($is_available) {
            $available_times[] = date('H:i', $time);
        }
    }

    // الإجابة الناجحة
    echo json_encode([
        'success' => true,
        'available_times' => $available_times,
        'consultation_duration' => $duration / 60
    ]);

} catch (Exception $e) {
    // معالجة الأخطاء
    $error_code = is_numeric($e->getCode()) ? $e->getCode() : 500;
    http_response_code($error_code);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $error_code
    ]);
    
    // تسجيل الخطأ للسجلات
    error_log("API Error [{$error_code}]: " . $e->getMessage());
    exit;
}