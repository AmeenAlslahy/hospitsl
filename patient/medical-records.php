<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$db = new Database();
$patient_id = getPatientId($_SESSION['user_id'], $db);

// معالجة معاملات البحث والتصفية
$filters = [
    'record_type' => $_GET['record_type'] ?? '',
    'severity' => $_GET['severity'] ?? '',
    'doctor_id' => $_GET['doctor_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'newest'
];

try {
    // بناء استعلام SQL ديناميكي مع التصفية
    $query = "SELECT 
                m.record_id, m.diagnosis, m.prescription, m.tests, m.notes,
                m.created_at, m.updated_at, m.record_type, m.severity,
                m.follow_up_date, m.is_confidential,
                d.doctor_id, u.full_name as doctor_name, 
                s.name as specialty_name, s.specialty_id,
                a.appointment_id, a.appointment_date, a.status as appointment_status,
                DATE_FORMAT(m.created_at, '%Y-%m-%d') as formatted_date,
                TIMESTAMPDIFF(DAY, m.created_at, NOW()) as days_ago
             FROM medical_records m
             JOIN doctors d ON m.doctor_id = d.doctor_id
             JOIN users u ON d.user_id = u.user_id
             JOIN specialties s ON d.specialty_id = s.specialty_id
             LEFT JOIN appointments a ON m.appointment_id = a.appointment_id
             WHERE m.patient_id = ?";
    
    $params = [$patient_id];
    $types = "i";

    // تطبيق الفلاتر
    if (!empty($filters['record_type'])) {
        $query .= " AND m.record_type = ?";
        $params[] = $filters['record_type'];
        $types .= "s";
    }

    if (!empty($filters['severity'])) {
        $query .= " AND m.severity = ?";
        $params[] = $filters['severity'];
        $types .= "s";
    }

    if (!empty($filters['doctor_id']) && is_numeric($filters['doctor_id'])) {
        $query .= " AND m.doctor_id = ?";
        $params[] = $filters['doctor_id'];
        $types .= "i";
    }

    if (!empty($filters['date_from'])) {
        $query .= " AND m.created_at >= ?";
        $params[] = $filters['date_from'] . ' 00:00:00';
        $types .= "s";
    }

    if (!empty($filters['date_to'])) {
        $query .= " AND m.created_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59';
        $types .= "s";
    }

    if (!empty($filters['search'])) {
        $query .= " AND (m.diagnosis LIKE ? OR m.prescription LIKE ? OR m.notes LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        $types .= "sss";
    }

    // تطبيق الترتيب
    switch ($filters['sort']) {
        case 'oldest':
            $query .= " ORDER BY m.created_at ASC";
            break;
        case 'severity':
            $query .= " ORDER BY FIELD(m.severity, 'عالي', 'متوسط', 'منخفض')";
            break;
        case 'doctor':
            $query .= " ORDER BY u.full_name ASC";
            break;
        default:
            $query .= " ORDER BY m.created_at DESC";
    }

    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // جلب قائمة الأطباء للتصفية باستخدام prepared statement
    $doctors = [];
    $doctors_stmt = $db->prepare("SELECT d.doctor_id, u.full_name 
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id
        JOIN medical_records m ON d.doctor_id = m.doctor_id
        WHERE m.patient_id = ?
        GROUP BY d.doctor_id");
    $doctors_stmt->bind_param("i", $patient_id);
    $doctors_stmt->execute();
    $doctors = $doctors_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Medical records error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في جلب السجلات الطبية";
}

$pageTitle = "السجلات الطبية";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- إضافة ستايل مخصص -->
<style>
    :root {
        --medical-primary: #2c7be5;
        --medical-secondary: #6e84a3;
        --medical-success: #00d97e;
        --medical-danger: #e63757;
        --medical-warning: #f6c343;
        --medical-info: #39afd1;
        --medical-light: #f9fafd;
        --medical-dark: #12263f;
    }
    
    body {
        background-color: #f5f7fa;
        font-family: 'Tajawal', sans-serif;
    }
    
    .medical-card {
        border-radius: 10px;
        border-left: 4px solid var(--medical-primary);
        transition: all 0.3s ease;
        margin-bottom: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .medical-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .medical-card.confidential {
        border-left-color: var(--medical-danger);
        background-color: rgba(230, 55, 87, 0.03);
    }
    
    .medical-header {
        padding: 1rem 1.5rem;
        cursor: pointer;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .medical-content {
        background-color: white;
        padding: 1.5rem;
        border-radius: 8px;
        margin-top: 1rem;
    }
    
    .medical-content h5 {
        color: var(--medical-primary);
        font-weight: 600;
        margin-bottom: 1rem;
        position: relative;
        padding-bottom: 0.5rem;
    }
    
    .medical-content h5:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 50px;
        height: 2px;
        background-color: var(--medical-primary);
    }
    
    .badge-severity-high {
        background-color: var(--medical-danger);
    }
    
    .badge-severity-medium {
        background-color: var(--medical-warning);
        color: var(--medical-dark);
    }
    
    .badge-severity-low {
        background-color: var(--medical-success);
    }
    
    .doctor-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--medical-primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-left: 10px;
    }
    
    .filter-btn {
        position: relative;
    }
    
    .filter-btn.active:after {
        content: '';
        position: absolute;
        top: -5px;
        right: -5px;
        width: 10px;
        height: 10px;
        background-color: var(--medical-danger);
        border-radius: 50%;
        border: 2px solid white;
    }
    
    .empty-state {
        padding: 3rem;
        text-align: center;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #d1d7e0;
        margin-bottom: 1.5rem;
    }
    
    .timeline-date {
        font-size: 0.85rem;
        color: var(--medical-secondary);
    }
    
    .prescription-item {
        padding: 0.5rem 0;
        border-bottom: 1px dashed #eee;
    }
    
    .prescription-item:last-child {
        border-bottom: none;
    }
    
    @media (max-width: 768px) {
        .medical-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .doctor-info {
            margin-bottom: 0.5rem;
        }
    }
</style>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-primary fw-bold">
                <i class="fas fa-file-medical-alt me-2"></i>سجلاتي الطبية
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/patient/dashboard.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active" aria-current="page">السجلات الطبية</li>
                </ol>
            </nav>
        </div>
        
        <div>
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="fas fa-sliders-h me-2"></i>تصفية
                <?php if (array_filter($filters)): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">فلاتر نشطة</span>
                    </span>
                <?php endif; ?>
            </button>
            <a href="<?php echo BASE_PATH; ?>/patient/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>عودة
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- شريط البحث والترتيب -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <form method="get" class="search-form">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" name="search" 
                                   placeholder="ابحث في التشخيص، الوصفة، الملاحظات..." 
                                   value="<?php echo htmlspecialchars($filters['search']); ?>">
                            <button class="btn btn-primary" type="submit">
                                بحث
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-3">
                    <select class="form-select" name="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>
                            <i class="fas fa-sort-amount-down me-2"></i>الأحدث أولاً
                        </option>
                        <option value="oldest" <?php echo $filters['sort'] === 'oldest' ? 'selected' : ''; ?>>
                            <i class="fas fa-sort-amount-up me-2"></i>الأقدم أولاً
                        </option>
                        <option value="severity" <?php echo $filters['sort'] === 'severity' ? 'selected' : ''; ?>>
                            <i class="fas fa-exclamation-triangle me-2"></i>حسب الأهمية
                        </option>
                        <option value="doctor" <?php echo $filters['sort'] === 'doctor' ? 'selected' : ''; ?>>
                            <i class="fas fa-user-md me-2"></i>حسب الطبيب
                        </option>
                    </select>
                </div>
                
                <div class="col-md-3 text-md-end">
                    <div class="d-flex align-items-center justify-content-md-end">
                        <span class="badge bg-primary rounded-pill me-3">
                            <?php echo count($records); ?> سجلات
                        </span>
                        <?php if (array_filter($filters)): ?>
                            <a href="medical_records.php" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-times me-1"></i> إعادة تعيين
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($records)): ?>
        <div class="medical-records-list">
            <?php foreach ($records as $record): ?>
                <div class="card medical-card <?php echo $record['is_confidential'] ? 'confidential' : ''; ?>">
                    <div class="medical-header d-flex justify-content-between align-items-center" 
                         data-bs-toggle="collapse" href="#recordDetails<?php echo $record['record_id']; ?>">
                        <div class="d-flex align-items-center">
                            <div class="doctor-avatar">
                                <?php echo mb_substr($record['doctor_name'], 0, 1); ?>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">د. <?php echo htmlspecialchars($record['doctor_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($record['specialty_name']); ?></small>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <div class="text-end me-3">
                                <div class="fw-bold"><?php echo formatDate($record['formatted_date']); ?></div>
                                <small class="timeline-date"><?php echo timeAgo($record['created_at']); ?></small>
                            </div>
                            
                            <div>
                                <?php if ($record['is_confidential']): ?>
                                    <span class="badge bg-danger me-2">
                                        <i class="fas fa-lock me-1"></i> سري
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($record['record_type']): ?>
                                    <span class="badge bg-info me-2">
                                        <?php echo htmlspecialchars($record['record_type']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($record['severity']): ?>
                                    <span class="badge badge-severity-<?php echo getSeverityClass($record['severity']); ?>">
                                        <?php echo htmlspecialchars($record['severity']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div id="recordDetails<?php echo $record['record_id']; ?>" class="collapse">
                        <div class="medical-content">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-4">
                                        <h5><i class="fas fa-diagnosis me-2"></i>التشخيص</h5>
                                        <div class="px-3">
                                            <?php echo formatMedicalText($record['diagnosis']); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($record['tests'])): ?>
                                        <div class="mb-4">
                                            <h5><i class="fas fa-flask me-2"></i>التحاليل المطلوبة</h5>
                                            <div class="px-3">
                                                <?php echo formatMedicalText($record['tests']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-lg-6">
                                    <?php if (!empty($record['prescription'])): ?>
                                        <div class="mb-4">
                                            <h5><i class="fas fa-prescription-bottle-alt me-2"></i>الوصفة الطبية</h5>
                                            <div class="px-3">
                                                <?php echo formatPrescriptionText($record['prescription']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($record['follow_up_date']): ?>
                                        <div class="mb-4">
                                            <h5><i class="fas fa-calendar-check me-2"></i>موعد المتابعة</h5>
                                            <div class="px-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-clock me-2 text-primary"></i>
                                                    <span class="me-3"><?php echo formatDate($record['follow_up_date']); ?></span>
                                                    <?php if (strtotime($record['follow_up_date']) < time()): ?>
                                                        <span class="badge bg-danger">منتهي</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">قيد الانتظار</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($record['notes'])): ?>
                                <div class="mb-4">
                                    <h5><i class="fas fa-notes-medical me-2"></i>ملاحظات إضافية</h5>
                                    <div class="px-3">
                                        <?php echo formatMedicalText($record['notes']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                                <div>
                                    <?php if ($record['appointment_id']): ?>
                                        <span class="badge bg-secondary me-2">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo formatDate($record['appointment_date']); ?>
                                        </span>
                                        <span class="badge bg-<?php echo getAppointmentStatusBadge($record['appointment_status']); ?>">
                                            <?php echo $record['appointment_status']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <a href="view-record.php?id=<?php echo $record['record_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary me-2">
                                        <i class="fas fa-file-pdf me-1"></i> تصدير PDF
                                    </a>
                                    
                                    <?php if ($record['follow_up_date']): ?>
                                        <a href="<?php echo BASE_PATH; ?>/patient/book_appointment.php?doctor_id=<?php echo $record['doctor_id']; ?>&follow_up=<?php echo $record['record_id']; ?>" 
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-calendar-plus me-1"></i> حجز متابعة
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-file-medical"></i>
            <h4 class="text-muted mb-3">لا توجد سجلات طبية</h4>
            <p class="text-muted mb-4">
                <?php echo array_filter($filters) ? 'لم يتم العثور على سجلات تطابق معايير البحث' : 'سيتم عرض السجلات الطبية هنا بعد زيارتك للأطباء'; ?>
            </p>
            <?php if (array_filter($filters)): ?>
                <a href="medical_records.php" class="btn btn-primary px-4">
                    <i class="fas fa-undo me-2"></i>عرض جميع السجلات
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal لتصفية السجلات -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="filterModalLabel">
                    <i class="fas fa-sliders-h me-2"></i>تصفية السجلات الطبية
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="get" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="recordType" class="form-label">نوع السجل</label>
                                <select class="form-select" id="recordType" name="record_type">
                                    <option value="">الكل</option>
                                    <option value="تشخيص" <?php echo $filters['record_type'] === 'تشخيص' ? 'selected' : ''; ?>>تشخيص</option>
                                    <option value="علاج" <?php echo $filters['record_type'] === 'علاج' ? 'selected' : ''; ?>>علاج</option>
                                    <option value="متابعة" <?php echo $filters['record_type'] === 'متابعة' ? 'selected' : ''; ?>>متابعة</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="severity" class="form-label">درجة الأهمية</label>
                                <select class="form-select" id="severity" name="severity">
                                    <option value="">الكل</option>
                                    <option value="منخفض" <?php echo $filters['severity'] === 'منخفض' ? 'selected' : ''; ?>>منخفض</option>
                                    <option value="متوسط" <?php echo $filters['severity'] === 'متوسط' ? 'selected' : ''; ?>>متوسط</option>
                                    <option value="عالي" <?php echo $filters['severity'] === 'عالي' ? 'selected' : ''; ?>>عالي</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="doctor" class="form-label">الطبيب</label>
                                <select class="form-select" id="doctor" name="doctor_id">
                                    <option value="">الكل</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['doctor_id']; ?>" 
                                            <?php echo $filters['doctor_id'] == $doctor['doctor_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($doctor['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="dateRange" class="form-label">الفترة الزمنية</label>
                                <div class="input-daterange input-group">
                                    <input type="date" class="form-control" name="date_from" 
                                           value="<?php echo htmlspecialchars($filters['date_from']); ?>" placeholder="من تاريخ">
                                    <span class="input-group-text">إلى</span>
                                    <input type="date" class="form-control" name="date_to" 
                                           value="<?php echo htmlspecialchars($filters['date_to']); ?>" placeholder="إلى تاريخ">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>تطبيق التصفية
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
// دالة مساعدة لتحديد لون Badge بناءً على درجة الأهمية
function getSeverityClass($severity) {
    switch ($severity) {
        case 'عالي': return 'high';
        case 'متوسط': return 'medium';
        case 'منخفض': return 'low';
        default: return 'secondary';
    }
}

// دالة مساعدة لتنسيق النص الطبي
function formatMedicalText($text) {
    if (empty($text)) {
        return '<div class="text-muted fst-italic">لا يوجد</div>';
    }
    
    // تحويل النص إلى فقرات إذا احتوى على فواصل أسطر
    $text = nl2br(htmlspecialchars($text));
    
    // تمييز الأدوية والجرعات
    $text = preg_replace('/(\d+\s*(mg|ml|جرعة|حبة|قرص))/i', '<span class="text-primary fw-bold">$1</span>', $text);
    
    // تمييز التعليمات المهمة
    $text = preg_replace('/(قبل|بعد|مع|بدون)\s*(الطعام|الأكل)/i', '<span class="text-success fw-bold">$1 $2</span>', $text);
    
    return $text;
}

// دالة مساعدة لتنسيق الوصفة الطبية
function formatPrescriptionText($text) {
    if (empty($text)) {
        return '<div class="text-muted fst-italic">لا يوجد</div>';
    }
    
    $lines = explode("\n", $text);
    $output = '';
    
    foreach ($lines as $line) {
        if (!empty(trim($line))) {
            $line = htmlspecialchars($line);
            // تمييز الجرعات
            $line = preg_replace('/(\d+\s*(mg|ml|جرعة|حبة|قرص))/i', '<span class="text-primary fw-bold">$1</span>', $line);
            $output .= '<div class="prescription-item">' . $line . '</div>';
        }
    }
    
    return $output;
}

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
?>