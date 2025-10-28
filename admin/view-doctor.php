<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth;
// التحقق من صلاحيات المدير
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

$doctor_id = $_GET['id'] ?? 0;

// جلب بيانات الطبيب
$stmt = $db->prepare("SELECT 
                        d.*, 
                        u.full_name, 
                        u.email, 
                        u.phone, 
                        u.profile_picture,
                        u.created_at, 
                        s.name as specialty_name,
                        s.image as specialty_image
                     FROM doctors d 
                     JOIN users u ON d.user_id = u.user_id
                     JOIN specialties s ON d.specialty_id = s.specialty_id
                     WHERE d.doctor_id = ?");

$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor) {
    header("Location: " . BASE_PATH . "/admin/doctors.php");
    exit();
}

// جلب مواعيد الطبيب
$stmt = $db->prepare("SELECT 
                        a.*, 
                        u.full_name as patient_name,
                        p.blood_type,
                        p.emergency_contact
                     FROM appointments a
                     JOIN patients p ON a.patient_id = p.patient_id
                     JOIN users u ON p.user_id = u.user_id
                     WHERE a.doctor_id = ?
                     ORDER BY a.appointment_date DESC, a.start_time ASC");

$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="view-doctor py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>بيانات الطبيب</h1>
            <a href="<?php echo BASE_PATH; ?>/admin/doctors.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>عودة للقائمة
            </a>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">المعلومات الشخصية</h5>
                    </div>
                    <?php require_once __DIR__ . '/../admin/show-img.php';?>
                        
                        <table class="table table-borderless">
                            <tr>
                                <th>الاسم الكامل</th>
                                <td>د. <?php echo htmlspecialchars($doctor['full_name']); ?></td>
                            </tr>
                            <tr>
                                <th>البريد الإلكتروني</th>
                                <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                            </tr>
                            <tr>
                                <th>رقم الهاتف</th>
                                <td><?php echo htmlspecialchars($doctor['phone']); ?></td>
                            </tr>
                            <tr>
                                <th>تاريخ التسجيل</th>
                                <td>
                                    <?php 
                                    if (function_exists('arabicDate')) {
                                        echo arabicDate($doctor['created_at']);
                                    } else {
                                        echo date('Y-m-d', strtotime($doctor['created_at']));
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>آخر دخول</th>
                                <td><?php echo !empty($doctor['last_login']) ? arabicDate($doctor['last_login']) : 'غير معروف'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">المعلومات المهنية</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($doctor['specialty_image'])): ?>
                        <div class="text-center mb-3">
                            <img src="<?php echo UPLOADS_PATH . '/specialties/' . htmlspecialchars($doctor['specialty_image']); ?>" 
                                 class="img-fluid rounded"
                                 alt="صورة التخصص">
                        </div>
                        <?php endif; ?>
                        
                        <table class="table table-borderless">
                            <tr>
                                <th>التخصص</th>
                                <td><?php echo htmlspecialchars($doctor['specialty_name']); ?></td>
                            </tr>
                            <tr>
                                <th>رقم الرخصة</th>
                                <td><?php echo htmlspecialchars($doctor['license_number'] ?? 'غير محدد'); ?></td>
                            </tr>
                            <tr>
                                <th>رسوم الكشف</th>
                                <td><?php echo number_format($doctor['consultation_fee'], 2); ?> ريال</td>
                            </tr>
                            <tr>
                                <th>سنوات الخبرة</th>
                                <td><?php echo $doctor['years_of_experience'] ?? '0'; ?> سنة</td>
                            </tr>
                            <tr>
                                <th>المؤهلات العلمية</th>
                                <td><?php echo nl2br(htmlspecialchars($doctor['qualification'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">الخبرات والسيرة الذاتية</h5>
                    </div>
                    <div class="card-body">
                        <h6>أيام العمل:</h6>
                        <p><?php echo formatAvailableDays($doctor['available_days']); ?></p>
                        
                        <h6 class="mt-3">ساعات العمل:</h6>
                        <p><?php echo date('h:i A', strtotime($doctor['working_hours_start'])) . ' - ' . date('h:i A', strtotime($doctor['working_hours_end'])); ?></p>
                        
                        <h6 class="mt-3">الخبرات العملية:</h6>
                        <p><?php echo nl2br(htmlspecialchars($doctor['experience'] ?: 'لا يوجد معلومات')); ?></p>
                        
                        <h6 class="mt-3">سيرة ذاتية مختصرة:</h6>
                        <p><?php echo nl2br(htmlspecialchars($doctor['bio'] ?: 'لا يوجد معلومات')); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">مواعيد الطبيب القادمة</h5>
                    <span class="badge bg-primary">
                        <?php echo count($appointments); ?> موعد
                    </span>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($appointments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>التاريخ</th>
                                    <th>الوقت</th>
                                    <th>نوع الدم</th>
                                    <th>حالة الدفع</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo $appointment['appointment_id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                        <?php if (!empty($appointment['emergency_contact'])): ?>
                                        <br><small class="text-muted">الاتصال: <?php echo htmlspecialchars($appointment['emergency_contact']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo arabicDate($appointment['appointment_date']); ?></td>
                                    <td>
                                        <?php echo date('h:i A', strtotime($appointment['start_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($appointment['end_time'])); ?>
                                    </td>
                                    <td><?php echo $appointment['blood_type'] ?? 'غير محدد'; ?></td>
                                    <td>
                                        <?php 
                                        $payment_status = [
                                            'pending' => 'قيد الانتظار',
                                            'paid' => 'مدفوع',
                                            'partially_paid' => 'مدفوع جزئياً'
                                        ];
                                        echo $payment_status[$appointment['payment_status']] ?? 'غير محدد';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = [
                                            'pending' => 'warning',
                                            'confirmed' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'no_show' => 'dark'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $status_class[$appointment['status']] ?? 'secondary'; ?>">
                                            <?php 
                                            $status_text = [
                                                'pending' => 'قيد الانتظار',
                                                'confirmed' => 'مؤكد',
                                                'completed' => 'مكتمل',
                                                'cancelled' => 'ملغى',
                                                'no_show' => 'لم يحضر'
                                            ];
                                            echo $status_text[$appointment['status']] ?? 'غير معروف'; 
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-calendar-times me-2"></i>
                        لا توجد مواعيد مسجلة لهذا الطبيب
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>