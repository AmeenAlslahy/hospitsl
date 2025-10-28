<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$db = new Database();
$pageTitle = "لوحة تحكم الإدارة";
$currentPage = 'dashboard';
include __DIR__ . '/../includes/header.php';

// جلب إحصائيات النظام باستخدام prepared statements
try {
    // إحصائيات المرضى
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM patients");
    $stmt->execute();
    $result = $stmt->get_result();
    $patients_count = $result->fetch_assoc()['count'];

    // إحصائيات الأطباء النشطين
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM doctors d 
                         JOIN users u ON d.user_id = u.user_id 
                         WHERE u.is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $doctors_count = $result->fetch_assoc()['count'];

    // إحصائيات المواعيد
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments");
    $stmt->execute();
    $result = $stmt->get_result();
    $appointments_count = $result->fetch_assoc()['count'];

    // مواعيد اليوم
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments 
                         WHERE appointment_date = CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $today_appointments = $result->fetch_assoc()['count'];

    // آخر 10 مواعيد
    $stmt = $db->prepare("SELECT a.*, 
                         p.full_name as patient_name, 
                         du.full_name as doctor_name,
                         s.name as specialty
                         FROM appointments a
                         JOIN patients pt ON a.patient_id = pt.patient_id
                         JOIN users p ON pt.user_id = p.user_id
                         JOIN doctors d ON a.doctor_id = d.doctor_id
                         JOIN users du ON d.user_id = du.user_id
                         JOIN specialties s ON d.specialty_id = s.specialty_id
                         ORDER BY a.appointment_date DESC, a.start_time DESC
                         LIMIT 10");
    $stmt->execute();
    $appointments = $stmt->get_result();

    // آخر 10 طلبات توظيف
    $stmt = $db->prepare("SELECT j.*, a.* 
                         FROM job_applications a
                         JOIN jobs j ON a.job_id = j.job_id
                         ORDER BY a.applied_at DESC
                         LIMIT 10");
    $stmt->execute();
    $applications = $stmt->get_result();

} catch (Exception $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في جلب بيانات لوحة التحكم";
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-tachometer-alt me-2"></i>لوحة تحكم الإدارة
        </h1>
        <div class="text-muted">
            آخر تحديث: <?php echo date('Y/m/d H:i'); ?>
        </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted fw-normal">المرضى المسجلين</h6>
                            <h2 class="mb-0"><?php echo number_format($patients_count); ?></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-users text-primary fa-2x"></i>
                        </div>
                    </div>
                    <a href="patients.php" class="btn btn-sm btn-outline-primary mt-3 w-100">
                        عرض الكل <i class="fas fa-arrow-left ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted fw-normal">الأطباء النشطين</h6>
                            <h2 class="mb-0"><?php echo number_format($doctors_count); ?></h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-user-md text-success fa-2x"></i>
                        </div>
                    </div>
                    <a href="doctors.php" class="btn btn-sm btn-outline-success mt-3 w-100">
                        عرض الكل <i class="fas fa-arrow-left ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted fw-normal">إجمالي المواعيد</h6>
                            <h2 class="mb-0"><?php echo number_format($appointments_count); ?></h2>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="fas fa-calendar-check text-info fa-2x"></i>
                        </div>
                    </div>
                    <a href="appointments.php" class="btn btn-sm btn-outline-info mt-3 w-100">
                        عرض الكل <i class="fas fa-arrow-left ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted fw-normal">مواعيد اليوم</h6>
                            <h2 class="mb-0"><?php echo number_format($today_appointments); ?></h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-clock text-warning fa-2x"></i>
                        </div>
                    </div>
                    <a href="today_appointments.php" class="btn btn-sm btn-outline-warning mt-3 w-100">
                        عرض الكل <i class="fas fa-arrow-left ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- آخر المواعيد وطلبات التوظيف -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-day me-2"></i>آخر المواعيد
                        </h5>
                        <a href="appointments.php" class="btn btn-sm btn-light">
                            عرض الكل <i class="fas fa-arrow-left ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if ($appointments && $appointments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>المريض</th>
                                        <th>الطبيب</th>
                                        <th>التاريخ</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($app = $appointments->fetch_assoc()): ?>
                                        <tr class="cursor-pointer" onclick="window.location='view-appointment.php?id=<?php echo $app['appointment_id']; ?>'">
                                            <td><?php echo htmlspecialchars($app['patient_name']); ?></td>
                                            <td>
                                                <div>د. <?php echo htmlspecialchars($app['doctor_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($app['specialty']); ?></small>
                                            </td>
                                            <td>
                                                <div><?php echo formatDate($app['appointment_date']); ?></div>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($app['start_time'])); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = [
                                                    'pending' => 'warning',
                                                    'confirmed' => 'primary',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger'
                                                ][$app['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php 
                                                    echo [
                                                        'pending' => 'قيد الانتظار',
                                                        'confirmed' => 'مؤكد',
                                                        'completed' => 'مكتمل',
                                                        'cancelled' => 'ملغى'
                                                    ][$app['status']] ?? $app['status'];
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد مواعيد مسجلة</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-briefcase me-2"></i>طلبات التوظيف الحديثة
                        </h5>
                        <a href="job_applications.php" class="btn btn-sm btn-light">
                            عرض الكل <i class="fas fa-arrow-left ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if ($applications && $applications->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>الوظيفة</th>
                                        <th>المتقدم</th>
                                        <th>التاريخ</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($app = $applications->fetch_assoc()): ?>
                                        <tr class="cursor-pointer" onclick="window.location='job_application.php?id=<?php echo $app['application_id']; ?>'">
                                            <td><?php echo htmlspecialchars($app['title']); ?></td>
                                            <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                                            <td><?php echo formatDate($app['applied_at']); ?></td>
                                            <td>
                                                <?php 
                                                $statusClass = [
                                                    'pending' => 'warning',
                                                    'reviewed' => 'primary',
                                                    'interviewed' => 'info',
                                                    'hired' => 'success',
                                                    'rejected' => 'danger'
                                                ][$app['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php 
                                                    echo [
                                                        'pending' => 'قيد المراجعة',
                                                        'reviewed' => 'تمت المراجعة',
                                                        'interviewed' => 'تمت المقابلة',
                                                        'hired' => 'تم التوظيف',
                                                        'rejected' => 'مرفوض'
                                                    ][$app['status']] ?? $app['status'];
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد طلبات توظيف حديثة</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// جعل الصفوف قابلة للنقر
document.querySelectorAll('.cursor-pointer').forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', function() {
        window.location = this.getAttribute('onclick').match(/'(.*?)'/)[1];
    });
});
</script>