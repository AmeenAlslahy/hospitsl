<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

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

$current_month = date('m');
$current_year = date('Y');

// جلب المواعيد لهذا الشهر
$appointments = $db->query("
    SELECT a.*, u.full_name as patient_name, p.patient_id
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    WHERE a.doctor_id = $doctor_id
    AND MONTH(a.appointment_date) = $current_month
    AND YEAR(a.appointment_date) = $current_year
    ORDER BY a.appointment_date, a.start_time
")->fetch_all(MYSQLI_ASSOC);

// تنظيم المواعيد حسب التاريخ
$schedule = [];
foreach ($appointments as $appointment) {
    $date = $appointment['appointment_date'];
    if (!isset($schedule[$date])) {
        $schedule[$date] = [];
    }
    $schedule[$date][] = $appointment;
}

require_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جدول المواعيد</title>
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
        background-color: #f5f7fa;
        color: var(--dark-text);
    }

    .schedule-container {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--secondary-color);
        margin: 0;
    }

    .month-navigation {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .month-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--dark-text);
        min-width: 200px;
        text-align: center;
    }

    .nav-button {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: white;
        border: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .nav-button:hover {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .calendar-header {
        text-align: center;
        font-weight: 600;
        padding: 0.75rem;
        background-color: var(--primary-color);
        color: white;
        border-radius: var(--border-radius);
    }

    .calendar-day {
        background-color: white;
        border-radius: var(--border-radius);
        padding: 0.75rem;
        min-height: 120px;
        box-shadow: var(--box-shadow);
        transition: all 0.2s;
        position: relative;
        border: 1px solid #eee;
    }

    .calendar-day:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .calendar-day.today {
        border: 2px solid var(--primary-color);
    }

    .calendar-day.empty {
        background-color: #f9f9f9;
        box-shadow: none;
        border: 1px dashed #ddd;
    }

    .day-number {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
    }

    .appointments-count {
        font-size: 0.8rem;
        color: var(--primary-color);
        background-color: rgba(67, 97, 238, 0.1);
        padding: 0.25rem 0.5rem;
        border-radius: 20px;
        display: inline-block;
        margin-bottom: 0.5rem;
    }

    .appointments-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .appointment-item {
        background-color: #f8f9fa;
        padding: 0.5rem;
        border-radius: 6px;
        font-size: 0.85rem;
        transition: all 0.2s;
        cursor: pointer;
        position: relative;
        padding-right: 1.5rem;
    }

    .appointment-item:hover {
        background-color: rgba(67, 97, 238, 0.1);
    }

    .appointment-item::before {
        content: '';
        position: absolute;
        right: 0.5rem;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: var(--success-color);
    }

    .appointment-time {
        font-weight: 600;
        color: var(--dark-text);
    }

    .appointment-patient {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .view-all {
        text-align: center;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: var(--primary-color);
        cursor: pointer;
    }

    /* عرض تفاصيل الموعد في المودال */
    .modal-appointment-details {
        padding: 1.5rem;
    }

    .modal-patient-info {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .patient-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 600;
        margin-left: 1rem;
    }

    .patient-details h4 {
        margin-bottom: 0.25rem;
    }

    .patient-id {
        color: var(--light-text);
        font-size: 0.9rem;
    }

    .appointment-meta {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .meta-item {
        background-color: #f8f9fa;
        padding: 0.75rem;
        border-radius: var(--border-radius);
    }

    .meta-label {
        font-size: 0.8rem;
        color: var(--light-text);
        margin-bottom: 0.25rem;
    }

    .meta-value {
        font-weight: 500;
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

    /* التكيف مع الشاشات الصغيرة */
    @media (max-width: 992px) {
        .calendar-grid {
            gap: 0.5rem;
        }
        
        .calendar-day {
            min-height: 100px;
            padding: 0.5rem;
        }
    }

    @media (max-width: 768px) {
        .calendar-header {
            font-size: 0.8rem;
            padding: 0.5rem;
        }
        
        .day-number {
            font-size: 0.9rem;
        }
        
        .appointments-count {
            display: none;
        }
        
        .appointment-item {
            font-size: 0.7rem;
            padding: 0.3rem;
        }
    }

    @media (max-width: 576px) {
        .calendar-grid {
            grid-template-columns: repeat(1, 1fr);
        }
        
        .calendar-day.empty {
            display: none;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .month-navigation {
            width: 100%;
            justify-content: space-between;
        }
    }
    </style>
</head>
<body>
    <div class="schedule-container">
        <div class="page-header">
            <h1 class="page-title">جدول المواعيد</h1>
            <div class="month-navigation">
                <button class="nav-button">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <div class="month-title"><?= date('F Y') ?></div>
                <button class="nav-button">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
        </div>
        
        <div class="calendar">
            <div class="calendar-grid">
                <!-- أيام الأسبوع -->
                <div class="calendar-header">الأحد</div>
                <div class="calendar-header">السبت</div>
                <div class="calendar-header">الجمعة</div>
                <div class="calendar-header">الخميس</div>
                <div class="calendar-header">الأربعاء</div>
                <div class="calendar-header">الثلاثاء</div>
                <div class="calendar-header">الإثنين</div>
                
                <?php
                $first_day = date('N', strtotime(date('Y-m-01')));
                $days_in_month = date('t');
                
                // الخلايا الفارغة في بداية الشهر
                for ($i = 1; $i < $first_day; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
                
                // أيام الشهر
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $date = date('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
                    $has_appointments = isset($schedule[$date]);
                    $is_today = ($date == date('Y-m-d'));
                    
                    echo '<div class="calendar-day ' . ($is_today ? 'today' : '') . '">';
                    echo '<div class="day-number">' . $day . '</div>';
                    
                    if ($has_appointments) {
                        echo '<div class="appointments-count">' . count($schedule[$date]) . ' مواعيد</div>';
                        echo '<div class="appointments-list">';
                        
                        // عرض أول موعدين فقط
                        $displayed_appointments = array_slice($schedule[$date], 0, 2);
                        foreach ($displayed_appointments as $appointment) {
                            echo '<div class="appointment-item" data-appointment-id="' . $appointment['appointment_id'] . '">';
                            echo '<span class="appointment-time">' . date('H:i', strtotime($appointment['start_time'])) . '</span> ';
                            echo '<span class="appointment-patient">' . htmlspecialchars($appointment['patient_name']) . '</span>';
                            echo '</div>';
                        }
                        
                        // إذا كان هناك أكثر من موعدين
                        if (count($schedule[$date]) > 2) {
                            echo '<div class="view-all">+' . (count($schedule[$date]) - 2) . ' المزيد</div>';
                        }
                        
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                
                // الخلايا الفارغة في نهاية الشهر
                $remaining_days = 7 - (($first_day + $days_in_month - 1) % 7);
                if ($remaining_days < 7) {
                    for ($i = 0; $i < $remaining_days; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- مودال تفاصيل الموعد -->
    <div class="modal fade" id="appointmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل الموعد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-appointment-details">
                    <!-- سيتم ملؤه بواسطة JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-primary">فتح ملف المريض</button>
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
    
    // إعداد معاينة الموعد
    document.querySelectorAll('.appointment-item').forEach(item => {
        item.addEventListener('click', function() {
            const appointmentId = this.getAttribute('data-appointment-id');
            // هنا يمكنك جلب تفاصيل الموعد عبر AJAX أو استخدام بيانات موجودة
            showAppointmentDetails(appointmentId);
        });
    });
    
    // عرض تفاصيل الموعد في المودال
    function showAppointmentDetails(appointmentId) {
        fetch('get_appointment_details.php?id=' + appointmentId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.querySelector('.modal-appointment-details').innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
                } else {
                    document.querySelector('.modal-appointment-details').innerHTML = `
                        <div class="modal-patient-info">
                            <div class="patient-avatar">${getInitials(data.patient_name)}</div>
                            <div class="patient-details">
                                <h4>${data.patient_name}</h4>
                                <span class="patient-id">#PAT-${data.patient_id}</span>
                            </div>
                        </div>
                        <div class="appointment-meta">
                            <div class="meta-item">
                                <div class="meta-label">التاريخ والوقت</div>
                                <div class="meta-value">${data.appointment_date} - ${data.start_time}</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">المدة</div>
                                <div class="meta-value">${data.duration || 'غير محددة'}</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">الحالة</div>
                                <div class="meta-value"><span class="status-badge">${data.status}</span></div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">نوع الزيارة</div>
                                <div class="meta-value">${data.visit_type || ''}</div>
                            </div>
                        </div>
                        <div class="appointment-notes">
                            <h5>ملاحظات</h5>
                            <p>${data.notes || 'لا توجد ملاحظات'}</p>
                        </div>
                    `;
                }
                const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
                modal.show();
            });
    }
    
    // التنقل بين الأشهر
    document.querySelectorAll('.nav-button').forEach(button => {
        button.addEventListener('click', function() {
            // هنا يمكنك إضافة منطق للتنقل بين الأشهر
            alert('سيتم تنفيذ التنقل بين الأشهر في التطبيق الكامل');
        });
    });
    </script>
</body>
</html>