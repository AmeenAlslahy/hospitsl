<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$auth = new Auth();
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

$appointment_id = $_GET['id'] ?? 0;

// جلب بيانات الموعد الحالية
$stmt = $db->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
$stmt->bind_param('i', $appointment_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header("Location: " . BASE_PATH . "/admin/appointments.php");
    exit();
}

// جلب قائمة الأطباء
$doctors = $db->query("SELECT d.doctor_id, u.full_name, s.name as specialty 
                      FROM doctors d
                      JOIN users u ON d.user_id = u.user_id
                      JOIN specialties s ON d.specialty_id = s.specialty_id
                      ORDER BY u.full_name");

// جلب قائمة المرضى
$patients = $db->query("SELECT p.patient_id, u.full_name 
                       FROM patients p
                       JOIN users u ON p.user_id = u.user_id
                       ORDER BY u.full_name");

// معالجة تحديث الموعد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $patient_id = intval($_POST['patient_id']);
        $doctor_id = intval($_POST['doctor_id']);
        $appointment_date = $db->escape($_POST['appointment_date']);
        $notes = $db->escape($_POST['notes'] ?? '');

        $stmt = $db->prepare("UPDATE appointments 
                             SET patient_id = ?, doctor_id = ?, 
                                 appointment_date = ?, notes = ?
                             WHERE appointment_id = ?");
        $stmt->bind_param('iissi', $patient_id, $doctor_id, $appointment_date, $notes, $appointment_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "تم تحديث الموعد بنجاح";
            header("Location: " . BASE_PATH . "/admin/view-appointment.php?id=$appointment_id");
            exit();
        } else {
            throw new Exception('حدث خطأ أثناء تحديث الموعد');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="edit-appointment py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>تعديل الموعد</h1>
            <a href="<?php echo BASE_PATH; ?>/admin/view-appointment.php?id=<?php echo $appointment_id; ?>" 
               class="btn btn-secondary">
               <i class="fas fa-arrow-left me-2"></i>عودة
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="patient_id" class="form-label">المريض</label>
                            <select class="form-select" id="patient_id" name="patient_id" required>
                                <option value="">اختر المريض</option>
                                <?php while ($patient = $patients->fetch_assoc()): ?>
                                    <option value="<?php echo $patient['patient_id']; ?>"
                                        <?php echo $patient['patient_id'] == $appointment['patient_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="doctor_id" class="form-label">الطبيب</label>
                            <select class="form-select" id="doctor_id" name="doctor_id" required>
                                <option value="">اختر الطبيب</option>
                                <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                    <option value="<?php echo $doctor['doctor_id']; ?>"
                                        data-specialty="<?php echo htmlspecialchars($doctor['specialty']); ?>"
                                        <?php echo $doctor['doctor_id'] == $appointment['doctor_id'] ? 'selected' : ''; ?>>
                                        د. <?php echo htmlspecialchars($doctor['full_name']); ?> - <?php echo htmlspecialchars($doctor['specialty']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="appointment_date" class="form-label">تاريخ ووقت الموعد</label>
                            <input type="datetime-local" class="form-control" id="appointment_date" 
                                   name="appointment_date" required
                                   value="<?php echo date('Y-m-d\TH:i', strtotime($appointment['appointment_date'])); ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">الحالة</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo getAppointmentStatusText($appointment['status']); ?>" disabled>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ التغييرات
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>