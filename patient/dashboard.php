<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
// التحقق من صحة تسجيل الدخول
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'patient') {
    $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
    $_SESSION['error'] = "يجب تسجيل الدخول كمريض للوصول إلى هذه الصفحة";
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

// إنشاء كائن الاتصال بقاعدة البيانات
// $db = new Database();
$patient_id = getPatientId($_SESSION['user_id'], $db);

$upcoming_appointments=[];
// جلب إحصائيات المريض
try {
    // المواعيد القادمة
    $upcoming_stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments 
                                 WHERE patient_id = ? 
                                 AND appointment_date >= CURDATE() 
                                 AND status = 'confirmed'");
    $upcoming_stmt->bind_param("i", $patient_id);
    $upcoming_stmt->execute();
    $upcoming_count = $upcoming_stmt->get_result()->fetch_assoc()['count'];

    // السجلات الطبية
    // $records_stmt = $db->prepare("SELECT COUNT(*) as count FROM medical_records 
    //                             WHERE patient_id = ?");
    // $records_stmt->bind_param("i", $patient_id);
    // $records_stmt->execute();
    // $records_count = $records_stmt->get_result()->fetch_assoc()['count'];

    // الفحوصات المطلوبة
    // $tests_stmt = $db->prepare("SELECT COUNT(*) as count FROM patient_tests 
    //                           WHERE patient_id = ? 
    //                           AND status = 'pending'");
    // $tests_stmt->bind_param("i", $patient_id);
    // $tests_stmt->execute();
    // $tests_count = $tests_stmt->get_result()->fetch_assoc()['count'];

    // المواعيد القادمة (التفاصيل)
    // $query = "SELECT a.*,                          
    //                      du.full_name as doctor_name,
    //                      s.name as specialty,
    //                     DATE_FORMAT(a.appointment_date, '%Y-%m-%d') as formatted_date,
    //                     TIME_FORMAT(a.start_time, '%H:%i') as start_time_formatted,  
    //                     TIME_FORMAT(a.end_time, '%H:%i') as end_time_formatted                                FROM appointments a
    //                      JOIN patients pt ON a.patient_id = pt.patient_id
    //                      JOIN users p ON pt.user_id = p.user_id
    //                      JOIN doctors d ON a.doctor_id = d.doctor_id
    //                      JOIN users du ON d.user_id = du.user_id
    //                      JOIN specialties s ON d.specialty_id = s.specialty_id
    //                      WHERE pt.patient_id = ?
    $appointments_stmt = $db->prepare("SELECT a.*,                          
                         du.full_name as doctor_name,
                         s.name as specialty_name,
                        DATE_FORMAT(a.appointment_date, '%Y-%m-%d') as formatted_date,
                        TIME_FORMAT(a.start_time, '%H:%i') as start_time_formatted,  
                        TIME_FORMAT(a.end_time, '%H:%i') as end_time_formatted                                FROM appointments a
                         JOIN patients pt ON a.patient_id = pt.patient_id
                         JOIN users p ON pt.user_id = p.user_id
                         JOIN doctors d ON a.doctor_id = d.doctor_id
                         JOIN users du ON d.user_id = du.user_id
                         JOIN specialties s ON d.specialty_id = s.specialty_id
                                     WHERE a.patient_id = ?
                                     AND a.appointment_date >= CURDATE()
                                     AND a.status = 'confirmed'
                                     ORDER BY formatted_date DESC, a.start_time DESC
                                     LIMIT 5");
    $appointments_stmt->bind_param("i", $patient_id);
    $appointments_stmt->execute();
    $upcoming_appointments = $appointments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // الفحوصات المطلوبة (التفاصيل)
    // $tests_details_stmt = $db->prepare("SELECT t.*, d.full_name as doctor_name
    //                                   FROM patient_tests t
    //                                   JOIN doctors d ON t.doctor_id = d.doctor_id
    //                                   WHERE t.patient_id = ?
    //                                   AND t.status = 'pending'
    //                                   ORDER BY t.due_date
    //                                   LIMIT 5");
    // $tests_details_stmt->bind_param("i", $patient_id);
    // $tests_details_stmt->execute();
    // $pending_tests = $tests_details_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في جلب بيانات لوحة التحكم";
}

// تعيين عنوان الصفحة
$pageTitle = "لوحة تحكم المريض";

// تضمين رأس الصفحة
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ستايلات مخصصة للوحة التحكم -->
<style>
    .patient-dashboard {
        background-color: #f8f9fa;
        min-height: 100vh;
    }
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-card i {
        margin-bottom: 15px;
    }
    .stat-card h3 {
        color: #495057;
        font-size: 1.2rem;
    }
    .stat-card p {
        font-size: 2.5rem;
        font-weight: bold;
        margin: 15px 0;
    }
    .appointment-item, .test-item {
        border-left: 4px solid #4e73df;
        margin-bottom: 15px;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    .appointment-item.cancelled {
        border-left-color: #e74a3b;
    }
    .appointment-item.completed {
        border-left-color: #1cc88a;
    }
    .badge-status {
        font-size: 0.8rem;
        padding: 5px 10px;
        border-radius: 20px;
    }
</style>

<div class="patient-dashboard py-4">
    <div class="container">
        <!-- رسائل النظام -->
        <?php displayFlashMessages(); ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">مرحباً بك، <?php echo htmlspecialchars($_SESSION['username']."  ".$_SESSION['user_id'] ?? 'المريض'); ?></h1>
            <a href="<?php echo BASE_PATH; ?>/appointments.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>حجز موعد جديد
            </a>
        </div>
        
        <!-- بطاقات الإحصائيات -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="fas fa-calendar-check fa-3x text-primary"></i>
                    <h3>المواعيد القادمة</h3>
                    <p class="mb-3"><?php echo $upcoming_count ?? 0; ?></p>
                    <a href="<?php echo BASE_PATH; ?>/patient/appointments.php" class="btn btn-outline-primary">عرض الكل</a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="fas fa-file-medical fa-3x text-success"></i>
                    <h3>السجلات الطبية</h3>
                    <p class="mb-3"><?php echo $records_count ?? 0; ?></p>
                    <a href="<?php echo BASE_PATH; ?>/patient/medical-records.php" class="btn btn-outline-success">عرض الكل</a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="fas fa-flask fa-3x text-danger"></i>
                    <h3>الفحوصات المطلوبة</h3>
                    <p class="mb-3"><?php echo $tests_count ?? 0; ?></p>
                    <a href="<?php echo BASE_PATH; ?>/patient/tests.php" class="btn btn-outline-danger">عرض الكل</a>
                </div>
            </div>
        </div>
        
        <!-- قسم المواعيد والفحوصات -->
        <div class="row g-4">
            <!-- المواعيد القادمة -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>مواعيدي القادمة</h5>
                        <a href="<?php echo BASE_PATH; ?>/patient/appointments.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcoming_appointments)): ?>
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                <div class="appointment-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">
                                            د. <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($appointment['specialty_name']); ?></small>
                                        </h6>
                                        <span class="badge bg-primary badge-status">مؤكد</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-end">
                                        <div>
                                            <p class="mb-1">
                                                <i class="fas fa-calendar-day me-2"></i>
                                                <?php echo formatDate($appointment['formatted_date']); ?>
                                            </p>
                                            <p class="mb-0">
                                                <i class="fas fa-clock me-2"></i>
                                                <?php echo $appointment['formatted_date']; ?>
                                            </p>
                                        </div>
                                        <div>
                                            <a href="<?php echo BASE_PATH; ?>/patient/cancel-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟')">
                                               إلغاء
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">لا توجد مواعيد قادمة</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- الفحوصات المطلوبة -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-flask me-2"></i>الفحوصات المطلوبة</h5>
                        <a href="<?php echo BASE_PATH; ?>/patient/tests.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pending_tests)): ?>
                            <?php foreach ($pending_tests as $test): ?>
                                <div class="test-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">
                                            <?php echo htmlspecialchars($test['test_name']); ?>
                                            <small class="text-muted d-block">
                                                طلب بواسطة: د. <?php echo htmlspecialchars($test['doctor_name']); ?>
                                            </small>
                                        </h6>
                                        <span class="badge bg-warning text-dark badge-status">قيد الانتظار</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-end">
                                        <div>
                                            <p class="mb-1">
                                                <i class="fas fa-calendar-day me-2"></i>
                                                يجب إجراؤها قبل: <?php echo formatDate($test['due_date']); ?>
                                            </p>
                                            <?php if (!empty($test['notes'])): ?>
                                                <p class="mb-0 text-muted">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <?php echo shortenText(htmlspecialchars($test['notes']), 50); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="<?php echo BASE_PATH; ?>/patient/test-details.php?id=<?php echo $test['test_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                               التفاصيل
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                                <p class="text-muted">لا توجد فحوصات مطلوبة</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
<script>
// يمكن إضافة أي JavaScript مخصص هنا
document.addEventListener('DOMContentLoaded', function() {
    // تأكيد الإلغاء للمواعيد
    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('هل أنت متأكد من إلغاء هذا الموعد؟')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>