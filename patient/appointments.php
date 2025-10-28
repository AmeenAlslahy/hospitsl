<?php
// --- الإعداد والتحقق من الصلاحيات ---
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'patient') {
    $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
    $_SESSION['error'] = "يجب تسجيل الدخول كمريض للوصول إلى هذه الصفحة";
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

// $db = new Database();
$patient_id = getPatientId($_SESSION['user_id'], $db);

// --- جلب وتصنيف المواعيد ---
$filter = $_GET['filter'] ?? 'upcoming';

$appointments = []; // تعريف المتغير كمصفوفة فارغة لتجنب الأخطاء في حال فشل الاستعلام
try {
    // بناء الاستعلام بدون الإشارة إلى appointment_time
    $query = "SELECT a.*,                          
                         du.full_name as doctor_name,
                         s.name as specialty,
                        DATE_FORMAT(a.appointment_date, '%Y-%m-%d') as formatted_date,
                        TIME_FORMAT(a.start_time, '%H:%i') as start_time_formatted,  
                        TIME_FORMAT(a.end_time, '%H:%i') as end_time_formatted                                FROM appointments a
                         JOIN patients pt ON a.patient_id = pt.patient_id
                         JOIN users p ON pt.user_id = p.user_id
                         JOIN doctors d ON a.doctor_id = d.doctor_id
                         JOIN users du ON d.user_id = du.user_id
                         JOIN specialties s ON d.specialty_id = s.specialty_id
                         WHERE pt.patient_id = ?
                        --  ORDER BY formatted_date DESC, a.start_time DESC
                         ";
    switch ($filter) {
    case 'past':
        $query .= " AND a.appointment_date < CURDATE()";
        break;
    case 'cancelled':
        $query .= " AND a.status = 'cancelled'";
        break;
    default: // upcoming
        $query .= " AND a.appointment_date >= CURDATE() AND a.status = 'confirmed'";
   }
   $stmt = $db->prepare($query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $appointments = $stmt->get_result();

} catch (Exception $e) {
    error_log("Appointments error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في جلب المواعيد";
}

$pageTitle = "مواعيدي - " . ($filter === 'past' ? 'السابقة' : ($filter === 'cancelled' ? 'الملغاة' : 'القادمة'));
require_once __DIR__ . '/../includes/header.php';
?>

<?php
// دالة مساعدة لعرض شارة الحالة
function getStatusBadge($status) {
    switch ($status) {
        case 'confirmed': return '<span class="badge bg-success">مؤكد</span>';
        case 'cancelled': return '<span class="badge bg-danger">ملغى</span>';
        default: return '<span class="badge bg-warning text-dark">قيد الانتظار</span>';
    }
}
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle;?></h1>
        <a href="<?php echo BASE_PATH; ?>/appointments.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>حجز موعد جديد
        </a>
    </div>

    <!-- أزرار الفلترة (التصنيف) -->
    <div class="row mb-4">
        <div class="col">
            <div class="btn-group" role="group">
                <a href="?filter=upcoming" class="btn btn-outline-primary <?php echo $filter === 'upcoming' ? 'active' : ''; ?>">القادمة</a>
                <a href="?filter=past" class="btn btn-outline-primary <?php echo $filter === 'past' ? 'active' : ''; ?>">السابقة</a>
                <a href="?filter=cancelled" class="btn btn-outline-primary <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">الملغاة</a>
            </div>
        </div>
    </div>

    <!-- قائمة المواعيد -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($appointments && $appointments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <!-- رأس الجدول -->
                        <thead>
                            <tr>
                                <th>الطبيب</th>
                                <th>التخصص</th>
                                <th>التاريخ</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <!-- جسم الجدول -->
                        <tbody>
                            <?php while ($app = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td>د. <?php echo htmlspecialchars($app['doctor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['specialty']); ?></td>
                                    <td><?php echo formatDate($app['formatted_date']); // دالة تنسيق التاريخ ?></td>
                                    <td><?php echo getStatusBadge($app['status']); ?></td>
                                    <td>
                                        <a href="view-appointment.php?id=<?php echo $app['appointment_id']; ?>" class="btn btn-sm btn-outline-primary">التفاصيل</a>
                                        <?php if ($filter === 'upcoming' && $app['status'] === 'confirmed'): ?>
                                            <a href="cancel-appointment.php?id=<?php echo $app['appointment_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟')">إلغاء</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">لا توجد مواعيد</h4>
                    <a href="<?php echo BASE_PATH; ?>/patient/book.php" class="btn btn-primary mt-3"><i class="fas fa-plus me-2"></i>حجز موعد جديد</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>