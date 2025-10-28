<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'doctor') {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$doctor_id = getDoctorId($_SESSION['user_id'], $db);

// معالجة البحث والتصفية
$search    = $_GET['search']    ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to']   ?? '';
$status    = $_GET['status']    ?? '';

// دالة مساعدة لبناء الاستعلام والفلاتر
function buildPrescriptionsQuery($doctor_id, $search, $date_from, $date_to, $status, &$params, &$types) {
    $query = "SELECT p.*, 
              u.full_name as patient_name,
              pt.patient_id,
              ph.name as pharmacy_name,
              DATE_FORMAT(p.created_at, '%Y-%m-%d') as prescription_date,
              (SELECT COUNT(*) FROM prescription_items pi WHERE pi.prescription_id = p.prescription_id) as items_count
              FROM prescriptions p
              JOIN patients pt ON p.patient_id = pt.patient_id
              JOIN users u ON pt.user_id = u.user_id
              LEFT JOIN pharmacies ph ON p.pharmacy_id = ph.pharmacy_id
              WHERE p.doctor_id = ?";
    $params = [$doctor_id];
    $types = "i";

    if (!empty($search)) {
        $query .= " AND (u.full_name LIKE ? OR p.prescription_code LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }
    if (!empty($date_from)) {
        $query .= " AND p.created_at >= ?";
        $params[] = $date_from . ' 00:00:00';
        $types .= "s";
    }
    if (!empty($date_to)) {
        $query .= " AND p.created_at <= ?";
        $params[] = $date_to . ' 23:59:59';
        $types .= "s";
    }
    if (!empty($status)) {
        $query .= " AND p.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    $query .= " ORDER BY p.created_at DESC";
    return $query;
}

// التحقق من وجود الجدول أولاً
$table_exists = $db->query("SHOW TABLES LIKE 'prescriptions'")->num_rows > 0;

$prescriptions = [];
if (!$table_exists) {
    $_SESSION['error'] = "جدول الوصفات الطبية غير موجود. يرجى تشغيل ملف التهيئة أولاً.";
} else {
    try {
        $params = [];
        $types = '';
        $query = buildPrescriptionsQuery($doctor_id, $search, $date_from, $date_to, $status, $params, $types);

        $stmt = $db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        error_log("Prescriptions error: " . $e->getMessage());
        $_SESSION['error'] = "حدث خطأ في جلب الوصفات الطبية";
        $prescriptions = [];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الوصفات الطبية</title>
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

    .prescriptions-container {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--secondary-color);
        position: relative;
        padding-bottom: 0.75rem;
    }

    .page-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        right: 0;
        width: 80px;
        height: 4px;
        background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        border-radius: 2px;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: var(--border-radius);
        font-weight: 500;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
        border: none;
    }

    .btn-primary:hover {
        background-color: #3a56e8;
        transform: translateY(-2px);
        box-shadow: 0 2px 10px rgba(67, 97, 238, 0.3);
    }

    .btn-outline-secondary {
        background-color: transparent;
        border: 1px solid var(--light-text);
        color: var(--dark-text);
    }

    .btn-outline-secondary:hover {
        background-color: #f1f1f1;
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

    .search-box {
        display: flex;
        gap: 0.5rem;
    }

    .search-box .form-control {
        flex: 1;
    }

    .prescriptions-list {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        overflow: hidden;
    }

    .prescription-item {
        padding: 1.5rem;
        border-bottom: 1px solid #eee;
        transition: all 0.2s;
    }

    .prescription-item:hover {
        background-color: #f9f9f9;
    }

    .prescription-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .prescription-code {
        font-weight: 600;
        color: var(--primary-color);
    }

    .prescription-date {
        color: var(--light-text);
        font-size: 0.9rem;
    }

    .prescription-patient {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .prescription-pharmacy {
        color: var(--light-text);
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .prescription-status {
        display: inline-block;
        padding: 0.35rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-new {
        background-color: rgba(67, 97, 238, 0.1);
        color: var(--primary-color);
    }

    .status-filled {
        background-color: rgba(76, 201, 240, 0.1);
        color: var(--success-color);
    }

    .status-cancelled {
        background-color: rgba(239, 35, 60, 0.1);
        color: var(--danger-color);
    }

    .prescription-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }

    .items-count {
        background-color: rgba(67, 97, 238, 0.1);
        color: var(--primary-color);
        padding: 0.25rem 0.5rem;
        border-radius: 20px;
        font-size: 0.8rem;
        display: inline-block;
    }

    .no-prescriptions {
        padding: 3rem;
        text-align: center;
    }

    .no-prescriptions-icon {
        font-size: 3rem;
        color: var(--light-text);
        opacity: 0.5;
        margin-bottom: 1rem;
    }

    .no-prescriptions p {
        color: var(--light-text);
    }

    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }
        
        .prescription-header {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .prescription-actions {
            flex-wrap: wrap;
        }
    }
    </style>
</head>
<body>
    <div class="prescriptions-container">
        <div class="page-header">
            <h1 class="page-title">الوصفات الطبية</h1>
            <a href="<?php echo BASE_PATH; ?>/doctor/add_prescription.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> وصفة جديدة
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="filters-card">
            <form method="get" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="form-label">بحث</label>
                        <div class="search-box">
                            <input type="text" name="search" class="form-control" placeholder="ابحث بالمريض أو رقم الوصفة" value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label class="form-label">حالة الوصفة</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">الكل</option>
                            <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>جديدة</option>
                            <option value="filled" <?php echo $status === 'filled' ? 'selected' : ''; ?>>مكتملة</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>ملغاة</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="form-label">من تاريخ</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="filter-group">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="filter-group" style="align-self: flex-end;">
                        <?php if ($search || $date_from || $date_to || $status): ?>
                            <a href="prescriptions.php" class="btn btn-outline-secondary" style="width: 100%;">
                                <i class="fas fa-undo me-2"></i> إعادة تعيين
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="prescriptions-list">
            <?php if (!empty($prescriptions)): ?>
                <?php foreach ($prescriptions as $prescription): ?>
                    <div class="prescription-item">
                        <div class="prescription-header">
                            <div>
                                <div class="prescription-code">#<?php echo htmlspecialchars($prescription['prescription_code'] ?? 'PR-' . $prescription['prescription_id']); ?></div>
                                <div class="prescription-date"><?php echo htmlspecialchars($prescription['prescription_date']); ?></div>
                            </div>
                            <span class="prescription-status status-<?php echo htmlspecialchars($prescription['status']); ?>">
                                <?php 
                                switch($prescription['status']) {
                                    case 'new': echo 'جديدة'; break;
                                    case 'filled': echo 'مكتملة'; break;
                                    case 'cancelled': echo 'ملغاة'; break;
                                    default: echo htmlspecialchars($prescription['status']); 
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="prescription-patient">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($prescription['patient_name']); ?>
                            <span class="items-count"><?php echo $prescription['items_count']; ?> أدوية</span>
                        </div>
                        
                        <?php if (!empty($prescription['pharmacy_name'])): ?>
                            <div class="prescription-pharmacy">
                                <i class="fas fa-clinic-medical me-2"></i><?php echo htmlspecialchars($prescription['pharmacy_name']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($prescription['notes'])): ?>
                            <div class="prescription-notes">
                                <i class="fas fa-notes-medical me-2"></i><?php echo htmlspecialchars($prescription['notes']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="prescription-actions">
                            <a href="<?php echo BASE_PATH; ?>/doctor/view_prescription.php?id=<?php echo $prescription['prescription_id']; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-eye me-2"></i> عرض التفاصيل
                            </a>
                            <a href="<?php echo BASE_PATH; ?>/doctor/print_prescription.php?id=<?php echo $prescription['prescription_id']; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-print me-2"></i> طباعة
                            </a>
                            <?php if ($prescription['status'] === 'new'): ?>
                                <a href="<?php echo BASE_PATH; ?>/doctor/edit_prescription.php?id=<?php echo $prescription['prescription_id']; ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-edit me-2"></i> تعديل
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-prescriptions">
                    <div class="no-prescriptions-icon">
                        <i class="fas fa-prescription-bottle-alt"></i>
                    </div>
                    <h4>لا توجد وصفات طبية</h4>
                    <p><?php echo ($search || $date_from || $date_to || $status) ? 'لم يتم العثور على وصفات تطابق معايير البحث' : 'سيتم عرض الوصفات الطبية هنا عند إنشائها'; ?></p>
                    <?php if ($search || $date_from || $date_to || $status): ?>
                        <a href="prescriptions.php" class="btn btn-primary mt-3">
                            <i class="fas fa-undo me-2"></i> عرض جميع الوصفات
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>