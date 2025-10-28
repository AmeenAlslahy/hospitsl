<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$patient_id = $_GET['id'] ?? 0;
$doctor_id = getDoctorId($_SESSION['user_id'],$db);

// التحقق من أن المريض لديه مواعيد مع هذا الطبيب
$valid_patient = $db->query("
    SELECT 1 FROM appointments 
    WHERE patient_id = $patient_id AND doctor_id = $doctor_id
    LIMIT 1
")->num_rows > 0;

if (!$valid_patient) {
    header('Location: patients.php');
    exit;
}

// جلب معلومات المريض
$patient = $db->query("
    SELECT u.full_name, u.phone, p.* 
    FROM patients p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.patient_id = $patient_id
")->fetch_assoc();

// جلب السجلات الطبية
$records = $db->query("
    SELECT * FROM medical_records
    WHERE patient_id = $patient_id AND doctor_id = $doctor_id
    ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="patient-records-page">
    <h1>السجل الطبي للمريض: <?= htmlspecialchars($patient['full_name']) ?></h1>
    
    <div class="patient-info">
        <p><strong>رقم الهاتف:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
        <p><strong>فصيلة الدم:</strong> <?= htmlspecialchars($patient['blood_type'] ?? '---') ?></p>
        <p><strong>الحساسيات:</strong> <?= htmlspecialchars($patient['allergies'] ?? '---') ?></p>
    </div>
    
    <div class="actions">
        <a href="add_record.php?patient_id=<?= $patient_id ?>" class="btn btn-primary">إضافة سجل جديد</a>
    </div>
    
    <div class="records-list">
        <?php if (!empty($records)): ?>
            <?php foreach ($records as $record): ?>
            <div class="record-card">
                <div class="record-header">
                    <h3><?= htmlspecialchars($record['record_type']) ?></h3>
                    <span class="date"><?= date('Y-m-d H:i', strtotime($record['created_at'])) ?></span>
                    <span class="badge <?= $record['is_confidential'] ? 'badge-danger' : 'badge-info' ?>">
                        <?= $record['is_confidential'] ? 'سري' : 'عادي' ?>
                    </span>
                </div>
                <div class="record-body">
                    <p><strong>التشخيص:</strong> <?= nl2br(htmlspecialchars($record['diagnosis'])) ?></p>
                    <?php if (!empty($record['prescription'])): ?>
                    <p><strong>الوصفة:</strong> <?= nl2br(htmlspecialchars($record['prescription'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="record-actions">
                    <a href="edit_record.php?id=<?= $record['record_id'] ?>" class="btn btn-sm btn-secondary">تعديل</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>لا توجد سجلات طبية لهذا المريض بعد</p>
        <?php endif; ?>
    </div>
</div>

<style>
    /* doctor.css */
.doctor-dashboard {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.stats-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin: 30px 0;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    margin-top: 0;
    color: #555;
}

.stat-card p {
    font-size: 24px;
    font-weight: bold;
    margin: 10px 0 0;
    color: #2c3e50;
}

.today-appointments {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-top: 30px;
}

.today-appointments table {
    width: 100%;
    border-collapse: collapse;
}

.today-appointments th, 
.today-appointments td {
    padding: 12px 15px;
    text-align: right;
    border-bottom: 1px solid #eee;
}

.today-appointments th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-success {
    background-color: #28a745;
    color: white;
}

.badge-secondary {
    background-color: #6c757d;
    color: white;
}

.badge-danger {
    background-color: #dc3545;
    color: white;
}

.badge-info {
    background-color: #17a2b8;
    color: white;
}

/* الصفحات الأخرى */
.appointments-page,
.patients-page,
.patient-records-page,
.add-record-page,
.schedule-page,
.profile-page {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 1200px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 20px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.form-actions {
    margin-top: 30px;
    text-align: left;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background-color: #3498db;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 14px;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* تقويم المواعيد */
.calendar {
    margin-top: 20px;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
}

.calendar-header {
    text-align: center;
    font-weight: bold;
    padding: 10px;
    background: #f8f9fa;
}

.calendar-day {
    min-height: 100px;
    padding: 10px;
    border: 1px solid #eee;
    border-radius: 5px;
}

.calendar-day.empty {
    background: #f9f9f9;
}

.calendar-day.today {
    background: #e3f2fd;
    border-color: #bbdefb;
}

.day-number {
    font-weight: bold;
    margin-bottom: 5px;
}

.appointments-count {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.appointment-item {
    font-size: 12px;
    padding: 2px 0;
    border-bottom: 1px dashed #eee;
}

/* بطاقات السجلات الطبية */
.record-card {
    border: 1px solid #eee;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
}

.record-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>