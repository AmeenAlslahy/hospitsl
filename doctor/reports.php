<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'doctor') {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$doctor_id = getDoctorId($_SESSION['user_id'], $db);

// معاملات التقرير
$report_type = $_GET['report_type'] ?? 'appointments';
$time_range = $_GET['time_range'] ?? 'month';
$custom_start = $_GET['custom_start'] ?? '';
$custom_end = $_GET['custom_end'] ?? '';

// تحديد الفترة الزمنية
$start_date = '';
$end_date = date('Y-m-d');
switch ($time_range) {
    case 'week':    $start_date = date('Y-m-d', strtotime('-1 week')); break;
    case 'month':   $start_date = date('Y-m-d', strtotime('-1 month')); break;
    case 'quarter': $start_date = date('Y-m-d', strtotime('-3 months')); break;
    case 'year':    $start_date = date('Y-m-d', strtotime('-1 year')); break;
    case 'custom':  $start_date = $custom_start; $end_date = $custom_end; break;
    default:        $start_date = date('Y-m-d', strtotime('-1 month'));
}

// دالة مساعدة للترجمة
function translateDay($day) {
    $days = [
        'Sunday' => 'الأحد', 'Monday' => 'الإثنين', 'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت'
    ];
    return $days[$day] ?? $day;
}

// جلب البيانات الإحصائية
$appointment_stats = ['total'=>0,'confirmed'=>0,'cancelled'=>0,'completed'=>0];
$prescription_stats = ['total'=>0,'new'=>0,'filled'=>0,'cancelled'=>0];
$appointments_by_day = [];
$appointments_by_specialty = [];

try {
    // إحصائيات المواعيد
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as completed
        FROM appointments
        WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?
    ");
    $stmt->bind_param('iss', $doctor_id, $start_date, $end_date);
    $stmt->execute();
    $appointment_stats = $stmt->get_result()->fetch_assoc() ?: $appointment_stats;

    // إحصائيات الوصفات الطبية
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new,
            SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as filled,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM prescriptions
        WHERE doctor_id = ? AND created_at BETWEEN ? AND ?
    ");
    $start_dt = $start_date . ' 00:00:00';
    $end_dt = $end_date . ' 23:59:59';
    $stmt->bind_param('iss', $doctor_id, $start_dt, $end_dt);
    $stmt->execute();
    $prescription_stats = $stmt->get_result()->fetch_assoc() ?: $prescription_stats;

    // توزيع المواعيد حسب اليوم
    $stmt = $db->prepare("
        SELECT DAYNAME(appointment_date) as day_name, COUNT(*) as count
        FROM appointments
        WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?
        GROUP BY day_name
        ORDER BY FIELD(day_name, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
    ");
    $stmt->bind_param('iss', $doctor_id, $start_date, $end_date);
    $stmt->execute();
    $appointments_by_day = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // توزيع المواعيد حسب التخصص
    $stmt = $db->prepare("
        SELECT s.name as specialty, COUNT(*) as count
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN medical_records m ON p.patient_id = m.patient_id
        JOIN specialties s ON m.specialty_id = s.specialty_id
        WHERE a.doctor_id = ? AND a.appointment_date BETWEEN ? AND ?
        GROUP BY specialty
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->bind_param('iss', $doctor_id, $start_date, $end_date);
    $stmt->execute();
    $appointments_by_specialty = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Reports error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في جلب البيانات الإحصائية";
}

require_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير الإحصائية</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3a0ca3;
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

    .reports-page {
        padding: 2rem;
        background-color: var(--light-bg);
        min-height: 100vh;
    }

    .page-header {
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

    .filters-card {
        background-color: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--box-shadow);
    }

    .filter-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .filter-group {
        flex: 1;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark-text);
    }

    .form-control, .form-select {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
        font-family: inherit;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: var(--border-radius);
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background-color: #3a56e8;
        transform: translateY(-2px);
        box-shadow: 0 2px 10px rgba(67, 97, 238, 0.3);
    }

    .report-section {
        margin-bottom: 3rem;
    }

    .section-title {
        font-size: 1.5rem;
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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background-color: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--box-shadow);
        text-align: center;
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 1rem;
        color: var(--light-text);
    }

    .chart-container {
        background-color: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--box-shadow);
        position: relative;
        height: 400px;
    }

    .table-container {
        background-color: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--box-shadow);
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 1rem;
        text-align: right;
        border-bottom: 1px solid #eee;
    }

    th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: var(--dark-text);
    }

    tr:hover {
        background-color: #f9f9f9;
    }

    .badge {
        display: inline-block;
        padding: 0.35rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .badge-primary {
        background-color: rgba(67, 97, 238, 0.1);
        color: var(--primary-color);
    }

    .badge-success {
        background-color: rgba(76, 201, 240, 0.1);
        color: var(--success-color);
    }

    .badge-danger {
        background-color: rgba(239, 35, 60, 0.1);
        color: var(--danger-color);
    }

    .date-range {
        font-size: 0.9rem;
        color: var(--light-text);
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
            gap: 1rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
        
        .chart-container {
            height: 300px;
        }
    }

    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
    <div class="reports-page">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">التقارير الإحصائية</h1>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="filters-card">
                <form method="get">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="form-label">نوع التقرير</label>
                            <select name="report_type" class="form-select" onchange="this.form.submit()">
                                <option value="appointments" <?php echo $report_type === 'appointments' ? 'selected' : ''; ?>>مواعيد</option>
                                <option value="prescriptions" <?php echo $report_type === 'prescriptions' ? 'selected' : ''; ?>>وصفات طبية</option>
                                <option value="patients" <?php echo $report_type === 'patients' ? 'selected' : ''; ?>>المرضى</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="form-label">الفترة الزمنية</label>
                            <select name="time_range" class="form-select" id="timeRangeSelect" onchange="toggleCustomDates()">
                                <option value="week" <?php echo $time_range === 'week' ? 'selected' : ''; ?>>آخر أسبوع</option>
                                <option value="month" <?php echo $time_range === 'month' ? 'selected' : ''; ?>>آخر شهر</option>
                                <option value="quarter" <?php echo $time_range === 'quarter' ? 'selected' : ''; ?>>آخر 3 أشهر</option>
                                <option value="year" <?php echo $time_range === 'year' ? 'selected' : ''; ?>>آخر سنة</option>
                                <option value="custom" <?php echo $time_range === 'custom' ? 'selected' : ''; ?>>مخصص</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-row" id="customDatesRow" style="<?php echo $time_range !== 'custom' ? 'display: none;' : ''; ?>">
                        <div class="filter-group">
                            <label class="form-label">من تاريخ</label>
                            <input type="date" name="custom_start" class="form-control" value="<?php echo htmlspecialchars($custom_start); ?>">
                        </div>
                        <div class="filter-group">
                            <label class="form-label">إلى تاريخ</label>
                            <input type="date" name="custom_end" class="form-control" value="<?php echo htmlspecialchars($custom_end); ?>">
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chart-bar me-2"></i>عرض التقرير
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="printReport()">
                            <i class="fas fa-print me-2"></i>طباعة
                        </button>
                    </div>
                </form>
            </div>

            <div class="date-range">
                <i class="fas fa-calendar-alt me-2"></i>
                عرض البيانات من <?php echo $start_date; ?> إلى <?php echo $end_date; ?>
            </div>

            <?php if ($report_type === 'appointments'): ?>
                <div class="report-section">
                    <h2 class="section-title">إحصائيات المواعيد</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $appointment_stats['total'] ?? 0; ?></div>
                            <div class="stat-label">إجمالي المواعيد</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $appointment_stats['confirmed'] ?? 0; ?></div>
                            <div class="stat-label">مواعيد مؤكدة</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $appointment_stats['completed'] ?? 0; ?></div>
                            <div class="stat-label">مواعيد منتهية</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $appointment_stats['cancelled'] ?? 0; ?></div>
                            <div class="stat-label">مواعيد ملغاة</div>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="appointmentsChart"></canvas>
                    </div>
                    
                    <div class="table-container">
                        <h3>توزيع المواعيد حسب اليوم</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>اليوم</th>
                                    <th>عدد المواعيد</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_appointments = $appointment_stats['total'] ?? 1;
                                foreach ($appointments_by_day as $day): 
                                    $percentage = round(($day['count'] / $total_appointments) * 100, 1);
                                ?>
                                    <tr>
                                        <td><?php echo translateDay($day['day_name']); ?></td>
                                        <td><?php echo $day['count']; ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px; background-color: #f1f1f1; border-radius: 10px;">
                                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%; background-color: var(--primary-color); border-radius: 10px; height: 100%;"></div>
                                                <span style="position: absolute; right: 10px; color: var(--dark-text);"><?php echo $percentage; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($report_type === 'prescriptions'): ?>
                <div class="report-section">
                    <h2 class="section-title">إحصائيات الوصفات الطبية</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $prescription_stats['total'] ?? 0; ?></div>
                            <div class="stat-label">إجمالي الوصفات</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $prescription_stats['new'] ?? 0; ?></div>
                            <div class="stat-label">وصفات جديدة</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $prescription_stats['filled'] ?? 0; ?></div>
                            <div class="stat-label">وصفات مكتملة</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $prescription_stats['cancelled'] ?? 0; ?></div>
                            <div class="stat-label">وصفات ملغاة</div>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="prescriptionsChart"></canvas>
                    </div>
                </div>
            <?php else: ?>
                <div class="report-section">
                    <h2 class="section-title">إحصائيات المرضى</h2>
                    
                    <div class="table-container">
                        <h3>توزيع المواعيد حسب التخصص</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>التخصص</th>
                                    <th>عدد المواعيد</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_by_specialty = array_sum(array_column($appointments_by_specialty, 'count'));
                                foreach ($appointments_by_specialty as $specialty): 
                                    $percentage = round(($specialty['count'] / $total_by_specialty) * 100, 1);
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($specialty['specialty']); ?></td>
                                        <td><?php echo $specialty['count']; ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px; background-color: #f1f1f1; border-radius: 10px;">
                                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%; background-color: var(--primary-color); border-radius: 10px; height: 100%;"></div>
                                                <span style="position: absolute; right: 10px; color: var(--dark-text);"><?php echo $percentage; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function toggleCustomDates() {
        const timeRangeSelect = document.getElementById('timeRangeSelect');
        const customDatesRow = document.getElementById('customDatesRow');
        
        if (timeRangeSelect.value === 'custom') {
            customDatesRow.style.display = 'flex';
        } else {
            customDatesRow.style.display = 'none';
        }
    }
    
    function printReport() {
        window.print();
    }
    
    // تحويل أيام الأسبوع الإنجليزية إلى العربية
    function translateDay(day) {
        const days = {
            'Sunday': 'الأحد',
            'Monday': 'الإثنين',
            'Tuesday': 'الثلاثاء',
            'Wednesday': 'الأربعاء',
            'Thursday': 'الخميس',
            'Friday': 'الجمعة',
            'Saturday': 'السبت'
        };
        return days[day] || day;
    }
    
    // إنشاء الرسوم البيانية
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($report_type === 'appointments' && !empty($appointments_by_day)): ?>
            const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
            const appointmentsChart = new Chart(appointmentsCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_map('translateDay', array_column($appointments_by_day, 'day_name'))); ?>,
                    datasets: [{
                        label: 'عدد المواعيد',
                        data: <?php echo json_encode(array_column($appointments_by_day, 'count')); ?>,
                        backgroundColor: 'rgba(67, 97, 238, 0.7)',
                        borderColor: 'rgba(67, 97, 238, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        <?php endif; ?>
        
        <?php if ($report_type === 'prescriptions' && !empty($prescription_stats)): ?>
            const prescriptionsCtx = document.getElementById('prescriptionsChart').getContext('2d');
            const prescriptionsChart = new Chart(prescriptionsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['جديدة', 'مكتملة', 'ملغاة'],
                    datasets: [{
                        data: [
                            <?php echo $prescription_stats['new'] ?? 0; ?>,
                            <?php echo $prescription_stats['filled'] ?? 0; ?>,
                            <?php echo $prescription_stats['cancelled'] ?? 0; ?>
                        ],
                        backgroundColor: [
                            'rgba(67, 97, 238, 0.7)',
                            'rgba(76, 201, 240, 0.7)',
                            'rgba(239, 35, 60, 0.7)'
                        ],
                        borderColor: [
                            'rgba(67, 97, 238, 1)',
                            'rgba(76, 201, 240, 1)',
                            'rgba(239, 35, 60, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        <?php endif; ?>
    });
    </script>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>