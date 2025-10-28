<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
// التحقق من صلاحيات المدير
$auth = new Auth();
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

$appointment_id = $_GET['id'] ?? 0;
$appointment = [];
// جلب بيانات الموعد
$stmt = $db->prepare("SELECT a.*, 
                     u.full_name as patient_name, 
                     du.full_name as doctor_name,
                     s.name as specialty_name
                     FROM appointments a
                     JOIN patients p ON a.patient_id = p.patient_id
                     JOIN users u ON p.user_id = u.user_id
                     JOIN doctors d ON a.doctor_id = d.doctor_id
                     JOIN users du ON d.user_id = du.user_id
                     JOIN specialties s ON d.specialty_id = s.specialty_id
                     WHERE a.appointment_id = ?");
$stmt->bind_param('i',$appointment_id);           
$stmt->execute();
$appointment =$stmt->get_result()->fetch_assoc();
//  $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    header("Location: " . BASE_PATH . "/admin/appointments.php");
    exit();
}

// جلب السجل الطبي إذا كان الموعد مكتملاً
$medical_record = null;
if ($appointment['status'] === 'completed') {
    $stmt = $db->prepare("SELECT * FROM medical_records WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $medical_record = $stmt->fetch(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="view-appointment py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>تفاصيل الموعد</h1>
            <a href="<?php echo BASE_PATH; ?>/admin/appointments.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>عودة للقائمة
            </a>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">معلومات الموعد</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th>رقم الموعد</th>
                                <td><?php echo $appointment['appointment_id']; ?></td>
                            </tr>
                            <tr>
                                <th>المريض</th>
                                <td><?php echo $appointment['patient_name']; ?></td>
                            </tr>
                            <tr>
                                <th>الطبيب</th>
                                <td>د. <?php echo $appointment['doctor_name']; ?></td>
                            </tr>
                            <tr>
                                <th>التخصص</th>
                                <td><?php echo $appointment['specialty_name']; ?></td>
                            </tr>
                            <tr>
                                <th>التاريخ</th>
                                <td><?php echo arabicDate($appointment['appointment_date']); ?></td>
                            </tr>
                            <tr>
                                <th>الوقت</th>
                                <td><?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>الحالة</th>
                                <td>
                                    <?php 
                                    $status_class = [
                                        'pending' => 'warning',
                                        'confirmed' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $status_class[$appointment['status']]; ?>">
                                        <?php 
                                        $status_text = [
                                            'pending' => 'قيد الانتظار',
                                            'confirmed' => 'مؤكد',
                                            'completed' => 'مكتمل',
                                            'cancelled' => 'ملغى'
                                        ];
                                        echo $status_text[$appointment['status']]; 
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>ملاحظات</th>
                                <td><?php echo $appointment['notes'] ?: 'لا يوجد ملاحظات'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">الإجراءات</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            <?php if ($appointment['status'] === 'pending'): ?>
                                <a href="<?php echo BASE_PATH; ?>/admin/confirm-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                   class="btn btn-primary">
                                   <i class="fas fa-check-circle me-2"></i>تأكيد الموعد
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($appointment['status'] === 'confirmed'): ?>
                                <a href="<?php echo BASE_PATH; ?>/admin/complete-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                   class="btn btn-success">
                                   <i class="fas fa-check-double me-2"></i>إكمال الموعد
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($appointment['status'] !== 'cancelled' && $appointment['status'] !== 'completed'): ?>
                                <a href="<?php echo BASE_PATH; ?>/admin/cancel-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟')">
                                   <i class="fas fa-times-circle me-2"></i>إلغاء الموعد
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?php echo BASE_PATH; ?>/admin/edit-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                               class="btn btn-warning">
                               <i class="fas fa-edit me-2"></i>تعديل الموعد
                            </a>
                            
                            <a href="<?php echo BASE_PATH; ?>/admin/print-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                               class="btn btn-secondary"
                               target="_blank">
                               <i class="fas fa-print me-2"></i>طباعة الموعد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($medical_record): ?>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">السجل الطبي</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6>التشخيص:</h6>
                        <p><?php echo nl2br($medical_record['diagnosis']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>العلاج:</h6>
                        <p><?php echo nl2br($medical_record['prescription']); ?></p>
                    </div>
                    <div class="col-12">
                        <h6>التحاليل المطلوبة:</h6>
                        <p><?php echo nl2br($medical_record['tests'] ?: 'لا يوجد تحاليل مطلوبة'); ?></p>
                    </div>
                    <div class="col-12">
                        <h6>ملاحظات إضافية:</h6>
                        <p><?php echo nl2br($medical_record['notes'] ?: 'لا يوجد ملاحظات إضافية'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>