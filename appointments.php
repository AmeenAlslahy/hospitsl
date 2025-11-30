<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$auth = new Auth();
// $db = new Database();

// التحقق من تسجيل الدخول
if (!$auth->isLoggedIn()) {
    header("Location: login.php?redirect=appointments.php");
    exit();
}
$patient_id = getPatientId($_SESSION['user_id'],$db);

// معالجة بيانات الحجز
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // تنظيف المدخلات
        
        $doctor_id = (int)$_POST['doctor'];
        $date = $db->escape($_POST['date']);
        $time = $db->escape($_POST['time']);
        $notes = $db->escape($_POST['notes'] ?? '');

        // التحقق من البيانات
        if (empty($doctor_id) || empty($date) || empty($time)) {
            throw new Exception('الرجاء إدخال جميع البيانات المطلوبة');
        }

        // التحقق من تاريخ الموعد
        $today = date('Y-m-d');
        if ($date < $today) {
            throw new Exception('لا يمكن حجز موعد في تاريخ مضى');
        }

        // التحقق من أن المستخدم مريض
        if ($auth->getUserRole() !== 'patient') {
            throw new Exception('فقط المرضى يمكنهم حجز المواعيد');
        }

        // التحقق من توفر الطبيب
        $doctor_check = $db->prepare("SELECT d.doctor_id FROM doctors d 
                                    JOIN users u ON d.user_id = u.user_id 
                                    WHERE d.doctor_id = ? AND u.is_active = 1");
        $doctor_check->bind_param("i", $doctor_id);
        $doctor_check->execute();
        
        if ($doctor_check->get_result()->num_rows === 0) {
            throw new Exception('الطبيب غير موجود أو غير نشط');
        }

        // حساب وقت الانتهاء (ساعة واحدة بعد وقت البداية)
        $end_time = date('H:i:s', strtotime($time) + 3600);

        // التحقق من عدم وجود تعارض في المواعيد
        $appointment_check = $db->prepare("SELECT appointment_id FROM appointments 
                                         WHERE doctor_id = ? AND appointment_date = ? 
                                         AND ((start_time <= ? AND end_time > ?) 
                                         OR (start_time < ? AND end_time >= ?))");
        $appointment_check->bind_param("isssss", $doctor_id, $date, $time, $time, $end_time, $end_time);
        $appointment_check->execute();
        
        if ($appointment_check->get_result()->num_rows > 0) {
            throw new Exception('هذا الموعد محجوز مسبقاً، الرجاء اختيار وقت آخر');
        }

        // بدء معاملة قاعدة البيانات
        $db->beginTransaction();

        try {
            // إدخال الموعد
            $stmt = $db->prepare("INSERT INTO appointments 
                                 (patient_id, doctor_id, appointment_date, start_time, end_time, status, notes) 
                                 VALUES (?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->bind_param("iissss", $patient_id, $doctor_id, $date, $time, $end_time, $notes);
            
            if (!$stmt->execute()) {
                throw new Exception('حدث خطأ أثناء حجز الموعد');
            }

            $db->commit();
            $_SESSION['success'] = 'تم حجز الموعد بنجاح، سنقوم بالتواصل معك لتأكيد الحجز';
            header("Location: appointments.php");
            exit();

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: appointments.php");
        exit();
    }
}

// جلب قائمة الأطباء النشطين
$doctors = [];
$query = "SELECT d.doctor_id, u.full_name, s.name as specialty 
          FROM doctors d
          JOIN users u ON d.user_id = u.user_id
          JOIN specialties s ON d.specialty_id = s.specialty_id
          WHERE u.is_active = 1 AND d.is_accepting_new_patients = 1
          ORDER BY u.full_name";

$result = $db->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// تحديد الطبيب إذا كان هناك معرف طبيب في الرابط
$selected_doctor = isset($_GET['doctor']) ? (int)$_GET['doctor'] : 0;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - حجز موعد</title>
    <link rel="stylesheet" href="<?= ASSETS_PATH ?>/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="<?= ASSETS_PATH ?>/css/all.min.css">
    <style>
        .doctor-card {
            transition: transform 0.3s;
        }
        .doctor-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>حجز موعد طبي</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="doctor" class="form-label">اختر الطبيب</label>
                                <select class="form-select" id="doctor" name="doctor" required>
                                    <option value="">-- اختر الطبيب --</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?= $doctor['doctor_id'] ?>" 
                                        <?= ($selected_doctor == $doctor['doctor_id']) ? 'selected' : '' ?>>
                                        <?= "د. {$doctor['full_name']} - {$doctor['specialty']}" ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="date" class="form-label">تاريخ الموعد</label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           min="<?= date('Y-m-d') ?>" 
                                           max="<?= date('Y-m-d', strtotime('+3 months')) ?>" 
                                           required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="time" class="form-label">وقت الموعد</label>
                                    <select class="form-select" id="time" name="time" required>
                                        <option value="">-- اختر الوقت --</option>
                                        <?php for ($i = 9; $i <= 16; $i++): ?>
                                            <option value="<?= sprintf("%02d:00", $i) ?>">
                                                <?= sprintf("%02d:00", $i) ?> صباحاً
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3 mt-3">
                                <label for="notes" class="form-label">ملاحظات إضافية</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="أي معلومات إضافية تريد إبلاغ الطبيب بها"></textarea>
                            </div>
                            
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check-circle me-2"></i> تأكيد الحجز
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script src="<?= ASSETS_PATH ?>/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // تعطيل أيام العطلة الأسبوعية (مثال: الجمعة)
        document.getElementById('date').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            if (selectedDate.getDay() === 5) { // 5 = الجمعة
                alert('العيادة مغلقة يوم الجمعة، الرجاء اختيار يوم آخر');
                this.value = '';
            }
        });

        // جلب الأوقات المتاحة عند اختيار الطبيب والتاريخ
        const doctorSelect = document.getElementById('doctor');
        const dateInput = document.getElementById('date');
        const timeSelect = document.getElementById('time');
        
        function fetchAvailableTimes() {
            if (doctorSelect.value && dateInput.value) {
                // هنا يمكنك إضافة AJAX لجلب الأوقات المتاحة من السيرفر
                console.log('جلب الأوقات المتاحة للطبيب', doctorSelect.value, 'في تاريخ', dateInput.value);
            }
        }
        
        doctorSelect.addEventListener('change', fetchAvailableTimes);
        dateInput.addEventListener('change', fetchAvailableTimes);
    });
    </script>
</body>
</html>