<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
// التحقق من صلاحيات الطبيب
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'doctor') {
    $_SESSION['redirect_url'] = BASE_PATH . '/doctor/dashboard.php';
    $_SESSION['error_message'] = 'يجب تسجيل الدخول كطبيب للوصول إلى هذه الصفحة';
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$doctor_id = getDoctorId($user_id, $db);

// جلب إحصائيات الطبيب
try {
    // إحصائيات المواعيد
    $today = date('Y-m-d');
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    
    // استعلامات الإحصائيات
    $stats = [
        'today_appointments' => $db->query("SELECT COUNT(*) FROM appointments 
                                           WHERE doctor_id = $doctor_id 
                                           AND appointment_date = '$today'")->fetch_row()[0],
        
        'week_appointments' => $db->query("SELECT COUNT(*) FROM appointments 
                                          WHERE doctor_id = $doctor_id 
                                          AND appointment_date BETWEEN '$startOfWeek' AND '$endOfWeek'")->fetch_row()[0],
        
        'new_patients' => $db->query("SELECT COUNT(DISTINCT patient_id) FROM appointments 
                                     WHERE doctor_id = $doctor_id 
                                     AND appointment_date BETWEEN DATE_SUB('$today', INTERVAL 7 DAY) AND '$today'")->fetch_row()[0],
        
        'today_records' => $db->query("SELECT COUNT(*) FROM medical_records 
                                      WHERE doctor_id = $doctor_id 
                                      AND DATE(created_at) = '$today'")->fetch_row()[0]
    ];
    
    // مواعيد اليوم
    $today_appointments = $db->query("SELECT a.*, p.patient_id, u.full_name as patient_name
                                     FROM appointments a
                                     JOIN patients p ON a.patient_id = p.patient_id
                                     JOIN users u ON p.user_id = u.user_id
                                     WHERE a.doctor_id = $doctor_id
                                     AND a.appointment_date = '$today'
                                     ORDER BY a.start_time ASC")->fetch_all(MYSQLI_ASSOC);
    
    // قائمة المرضى الذين لديهم مواعيد
    $patients = $db->query("SELECT DISTINCT p.patient_id, u.full_name
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.patient_id
                           JOIN users u ON p.user_id = u.user_id
                           WHERE a.doctor_id = $doctor_id
                           AND a.appointment_date >= DATE_SUB('$today', INTERVAL 30 DAY)
                           ORDER BY u.full_name")->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Doctor dashboard error: " . $e->getMessage());
    $_SESSION['error_message'] = 'حدث خطأ في جلب بيانات لوحة التحكم';
}

require_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الطبيب</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3a0ca3;
        --accent-color: #f72585;
        --success-color: #4cc9f0;
        --info-color: #4895ef;
        --warning-color: #f8961e;
        --danger-color: #ef233c;
        --light-bg: #f8f9fa;
        --dark-text: #2b2d42;
        --light-text: #8d99ae;
        --border-radius: 12px;
        --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    body {
        font-family: 'Tajawal', sans-serif;
        background-color: var(--light-bg);
        color: var(--dark-text);
    }

    .doctor-dashboard {
        padding: 2rem 0;
    }

    .page-header {
        margin-bottom: 2.5rem;
        position: relative;
    }

    .page-title {
        font-weight: 700;
        color: var(--secondary-color);
        position: relative;
        display: inline-block;
        margin-bottom: 1.5rem;
    }

    .page-title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        right: 0;
        width: 70px;
        height: 4px;
        background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        border-radius: 2px;
    }

    /* بطاقات الإحصائيات */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        box-shadow: var(--box-shadow);
        border-left: 4px solid var(--primary-color);
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--info-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin-left: 1rem;
        flex-shrink: 0;
    }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark-text);
        line-height: 1;
        margin-bottom: 0.3rem;
    }

    .stat-label {
        color: var(--light-text);
        font-size: 0.95rem;
        margin-bottom: 0.3rem;
    }

    .stat-trend {
        font-size: 0.8rem;
        color: var(--light-text);
    }

    .stat-trend .up {
        color: #4cc9f0;
    }

    .stat-trend .down {
        color: #ef233c;
    }

    /* جدول المواعيد */
    .appointments-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: white;
    }

    .card-header h3 {
        margin: 0;
        font-weight: 600;
        color: var(--dark-text);
        font-size: 1.25rem;
    }

    .card-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-sm {
        padding: 0.35rem 0.75rem;
        font-size: 0.85rem;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border: none;
        color: white;
    }

    .btn-primary:hover {
        background-color: #3a56e8;
        transform: translateY(-2px);
        box-shadow: 0 2px 10px rgba(67, 97, 238, 0.3);
    }

    .btn-outline-primary {
        background-color: transparent;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
    }

    .btn-outline-primary:hover {
        background-color: rgba(67, 97, 238, 0.1);
    }

    .appointment-row {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f5f5f5;
        transition: all 0.2s;
    }

    .appointment-row:hover {
        background-color: rgba(67, 97, 238, 0.05);
    }

    .appointment-time {
        width: 120px;
        flex-shrink: 0;
    }

    .time {
        display: block;
        font-weight: 600;
        color: var(--dark-text);
    }

    .duration {
        font-size: 0.8rem;
        color: var(--light-text);
    }

    .patient-info {
        flex: 1;
        display: flex;
        align-items: center;
    }

    .patient-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-left: 1rem;
        flex-shrink: 0;
    }

    .patient-details {
        flex: 1;
    }

    .patient-details a {
        color: var(--dark-text);
        font-weight: 500;
        text-decoration: none;
        display: block;
        margin-bottom: 0.2rem;
    }

    .patient-details a:hover {
        color: var(--primary-color);
    }

    .patient-id {
        font-size: 0.8rem;
        color: var(--light-text);
    }

    .appointment-status {
        width: 100px;
        text-align: center;
        flex-shrink: 0;
    }

    .status-badge {
        display: inline-block;
        padding: 0.35rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-badge.مؤكد {
        background: rgba(76, 201, 240, 0.1);
        color: #4cc9f0;
    }

    .status-badge.منتهي {
        background: rgba(141, 153, 174, 0.1);
        color: #8d99ae;
    }

    .status-badge.ملغي {
        background: rgba(239, 35, 60, 0.1);
        color: #ef233c;
    }

    .status-badge.متوقع {
        background: rgba(73, 80, 87, 0.1);
        color: #495057;
    }

    .appointment-actions {
        margin-right: 1rem;
    }

    .btn-action {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        background: rgba(67, 97, 238, 0.1);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-action:hover {
        background: var(--primary-color);
        color: white;
    }

    .no-appointments {
        padding: 3rem 2rem;
        text-align: center;
    }

    .no-appointments-icon {
        font-size: 3rem;
        color: var(--light-text);
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .no-appointments h5 {
        color: var(--light-text);
        font-weight: 500;
    }

    /* الإجراءات السريعة */
    .quick-actions {
        margin-top: 2rem;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 1.5rem;
        position: relative;
        padding-bottom: 0.75rem;
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        right: 0;
        width: 50px;
        height: 3px;
        background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        border-radius: 2px;
    }

    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
    }

    .action-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: var(--box-shadow);
        text-decoration: none;
        color: var(--dark-text);
        border: 1px solid transparent;
    }

    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-color: rgba(67, 97, 238, 0.2);
    }

    .action-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--info-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
    }

    .action-title {
        font-weight: 500;
        font-size: 1rem;
    }

    /* التكيف مع الشاشات الصغيرة */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .appointment-row {
            flex-wrap: wrap;
            padding: 1rem;
        }
        
        .appointment-time, .appointment-status {
            width: 50%;
            margin-bottom: 0.5rem;
        }
        
        .patient-info {
            width: 100%;
            margin-bottom: 0.5rem;
        }
        
        .appointment-actions {
            margin-right: 0;
            margin-left: auto;
        }
        
        .actions-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .actions-grid {
            grid-template-columns: 1fr;
        }
        
        .card-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .card-actions {
            margin-top: 0.5rem;
            width: 100%;
            justify-content: flex-end;
        }
    }
    </style>
</head>
<body>
    <div class="doctor-dashboard">
        <div class="container">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1 class="page-title">لوحة تحكم الطبيب</h1>
            </div>
            
            <!-- بطاقات الإحصائيات -->
            <div class="stats-grid">
                <!-- بطاقة مواعيد اليوم -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['today_appointments']; ?></div>
                        <div class="stat-label">مواعيد اليوم</div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up up"></i> 12% عن الأسبوع الماضي
                        </div>
                    </div>
                </div>
                
                <!-- بطاقة مواعيد الأسبوع -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['week_appointments']; ?></div>
                        <div class="stat-label">مواعيد هذا الأسبوع</div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up up"></i> 8% عن الأسبوع الماضي
                        </div>
                    </div>
                </div>
                
                <!-- بطاقة المرضى الجدد -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-procedures"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['new_patients']; ?></div>
                        <div class="stat-label">المرضى الجدد</div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-down down"></i> 5% عن الأسبوع الماضي
                        </div>
                    </div>
                </div>
                
                <!-- بطاقة السجلات اليومية -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-medical-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['today_records']; ?></div>
                        <div class="stat-label">السجلات اليومية</div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up up"></i> 15% عن الأسبوع الماضي
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- جدول مواعيد اليوم -->
            <div class="appointments-card">
                <div class="card-header">
                    <h3>مواعيد اليوم</h3>
                    <div class="card-actions">
                        <a href="<?php echo BASE_PATH; ?>/doctor/appointments.php" class="btn btn-outline-primary btn-sm">
                            عرض الكل <i class="fas fa-arrow-left ms-2"></i>
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($today_appointments)): ?>
                        <?php foreach ($today_appointments as $appointment): ?>
                            <div class="appointment-row">
                                <div class="appointment-time">
                                    <span class="time"><?php echo date('H:i', strtotime($appointment['start_time'])); ?></span>
                                    <span class="duration">30 دقيقة</span>
                                </div>
                                
                                <div class="patient-info">
                                    <div class="patient-avatar">
                                        <?php echo getInitials($appointment['patient_name']); ?>
                                    </div>
                                    <div class="patient-details">
                                        <a href="<?php echo BASE_PATH; ?>/doctor/patient.php?id=<?php echo $appointment['patient_id']; ?>">
                                            <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                        </a>
                                        <span class="patient-id">#<?php echo $appointment['patient_id']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="appointment-status">
                                    <span class="status-badge <?php echo $appointment['status']; ?>">
                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="appointment-actions">
                                    <button class="btn-action btn-start">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-appointments">
                            <div class="no-appointments-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h5>لا توجد مواعيد اليوم</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- الإجراءات السريعة -->
            <div class="quick-actions">
                <h3 class="section-title">إجراءات سريعة</h3>
                
                <div class="actions-grid">
                    <a href="<?php echo BASE_PATH; ?>/doctor/add_record.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-file-medical"></i>
                        </div>
                        <div class="action-title">إضافة سجل طبي</div>
                    </a>
                    
                    <a href="<?php echo BASE_PATH; ?>/doctor/schedule.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div class="action-title">إضافة موعد</div>
                    </a>
                    
                    <a href="<?php echo BASE_PATH; ?>/doctor/prescriptions.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-prescription-bottle-alt"></i>
                        </div>
                        <div class="action-title">وصفات طبية</div>
                    </a>
                    
                    <a href="<?php echo BASE_PATH; ?>/doctor/reports.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="action-title">تقارير إحصائية</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
    // دالة لإنشاء الأحرف الأولى من الاسم
    function getInitials(name) {
        return name.split(' ').map(part => part[0]).join('').toUpperCase();
    }
    
    // تطبيق الأحرف الأولى على جميع الصور الرمزية
    document.querySelectorAll('.patient-avatar').forEach(avatar => {
        const name = avatar.nextElementSibling.querySelector('a').textContent;
        avatar.textContent = getInitials(name);
    });
    </script>
</body>
</html>

<?php
// دالة مساعدة لحالة الموعد
function getAppointmentStatusBadge($status) {
    switch ($status) {
        case 'مؤكد': return 'success';
        case 'منتهي': return 'secondary';
        case 'ملغي': return 'danger';
        case 'متوقع': return 'info';
        default: return 'primary';
    }
}

// دالة لإنشاء الأحرف الأولى من الاسم
function getInitials($name) {
    $initials = '';
    $parts = explode(' ', $name);
    foreach ($parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
    }
    return substr($initials, 0, 2);
}
?>