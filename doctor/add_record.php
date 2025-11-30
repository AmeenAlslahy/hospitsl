<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$doctor_id = getDoctorId($_SESSION['user_id'],$db);
$patient_id = $_GET['patient_id'] ?? 0;

// التحقق من أن المريض لديه مواعيد مع هذا الطبيب
if ($patient_id) {
    $valid_patient = $db->query("
        SELECT 1 FROM appointments 
        WHERE patient_id = $patient_id AND doctor_id = $doctor_id
        LIMIT 1
    ")->num_rows > 0;
    
    if (!$valid_patient) {
        header('Location: patients.php');
        exit;
    }
}

// جلب مواعيد المريض مع الطبيب
$appointments = $db->query("
    SELECT a.appointment_id, a.appointment_date, a.start_time, a.status
    FROM appointments a
    WHERE a.patient_id = $patient_id AND a.doctor_id = $doctor_id
    ORDER BY a.appointment_date DESC
")->fetch_all(MYSQLI_ASSOC);

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['patient_id', 'record_type', 'diagnosis'];
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "حقل " . get_field_name($field) . " مطلوب";
        }
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO medical_records (
                patient_id, doctor_id, appointment_id, record_type, 
                diagnosis, prescription, tests, notes, 
                severity, is_confidential
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iiissssssi",
            $_POST['patient_id'],
            $doctor_id,
            $_POST['appointment_id'],
            $_POST['record_type'],
            $_POST['diagnosis'],
            $_POST['prescription'],
            $_POST['tests'],
            $_POST['notes'],
            $_POST['severity'],
            $_POST['is_confidential']);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "تمت إضافة السجل الطبي بنجاح";
            header("Location: patient_records.php?id=" . $_POST['patient_id']);
            exit;
        } else {
            $errors[] = "حدث خطأ أثناء حفظ السجل";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="add-record-page">
    <h1>إضافة سجل طبي جديد</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?= $error ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
        
        <div class="form-group">
            <label>اختر الموعد (اختياري)</label>
            <select name="appointment_id" class="form-control">
                <option value="">-- بدون موعد --</option>
                <?php foreach ($appointments as $appointment): ?>
                <option value="<?= $appointment['appointment_id'] ?>">
                    <?= date('Y-m-d', strtotime($appointment['appointment_date'])) ?> - 
                    <?= date('H:i', strtotime($appointment['start_time'])) ?> - 
                    <?= $appointment['status'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>نوع السجل *</label>
                    <select name="record_type" class="form-control" required>
                        <option value="تشخيص">تشخيص</option>
                        <option value="علاج">علاج</option>
                        <option value="متابعة">متابعة</option>
                        <option value="فحص">فحص</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>درجة الأهمية *</label>
                    <select name="severity" class="form-control" required>
                        <option value="منخفض">منخفض</option>
                        <option value="متوسط">متوسط</option>
                        <option value="عالي">عالي</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>التشخيص *</label>
            <textarea name="diagnosis" class="form-control" rows="5" required></textarea>
        </div>
        
        <div class="form-group">
            <label>الوصفة الطبية</label>
            <textarea name="prescription" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label>التحاليل المطلوبة</label>
            <textarea name="tests" class="form-control" rows="2"></textarea>
        </div>
        
        <div class="form-group">
            <label>ملاحظات إضافية</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
        
        <div class="form-check">
            <input type="checkbox" name="is_confidential" id="is_confidential" class="form-check-input">
            <label for="is_confidential" class="form-check-label">سجل سري</label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">حفظ السجل</button>
            <a href="<?= $patient_id ? "patient_records.php?id=$patient_id" : 'patients.php' ?>" class="btn btn-secondary">إلغاء</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>