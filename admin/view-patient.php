<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$auth = new Auth();
// التحقق من صلاحيات المدير أو الطبيب
if (!in_array($_SESSION['role'], ['admin', 'doctor'])) {
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

$patient_id = (int)($_GET['id'] ?? 0);

// جلب بيانات المريض
$stmt = $db->prepare("SELECT 
                        p.*, 
                        u.full_name, 
                        u.email, 
                        u.phone, 
                        u.profile_picture,
                        u.created_at,
                        u.last_login
                     FROM patients p 
                     JOIN users u ON p.user_id = u.user_id
                     WHERE p.patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

if (!$patient) {
    $_SESSION['error'] = "المريض غير موجود";
    header("Location: " . BASE_PATH . "/admin/patients.php");
    exit();
}

// جلب مواعيد المريض
$stmt = $db->prepare("SELECT 
                        a.*, 
                        d.doctor_id,
                        u.full_name as doctor_name, 
                        s.name as specialty_name,
                        s.image as specialty_image
                     FROM appointments a
                     JOIN doctors d ON a.doctor_id = d.doctor_id
                     JOIN users u ON d.user_id = u.user_id
                     JOIN specialties s ON d.specialty_id = s.specialty_id
                     WHERE a.patient_id = ?
                     ORDER BY a.appointment_date DESC, a.start_time DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// جلب السجلات الطبية
$stmt = $db->prepare("SELECT 
                        mr.*, 
                        u.full_name as doctor_name,
                        s.name as specialty_name
                     FROM medical_records mr
                     JOIN doctors d ON mr.doctor_id = d.doctor_id
                     JOIN users u ON d.user_id = u.user_id
                     JOIN specialties s ON d.specialty_id = s.specialty_id
                     WHERE mr.patient_id = ?
                     ORDER BY mr.created_at DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$medical_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="view-patient py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-user-injured me-2"></i>
                بيانات المريض: <?php echo htmlspecialchars($patient['full_name']); ?>
            </h1>
            <div>
                <a href="<?php echo BASE_PATH; ?>/admin/patients.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>عودة للقائمة
                </a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="<?php echo BASE_PATH; ?>/admin/edit-patient.php?id=<?php echo $patient_id; ?>" class="btn btn-outline-primary ms-2">
                        <i class="fas fa-edit me-2"></i>تعديل
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div class="row g-4">
            <!-- البطاقة الشخصية -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-id-card me-2"></i>
                            البطاقة الشخصية
                        </h5>
                        <span class="badge bg-info">رقم المريض: <?php echo $patient_id; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="<?php echo getProfileImage($patient['profile_picture']); ?>" 
                                 class="rounded-circle border" 
                                 width="150" 
                                 height="150" 
                                 alt="صورة المريض"
                                 onerror="this.src='<?php echo ASSETS_PATH; ?>/images/default-patient.png'">
                        </div>
                        
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%"><i class="fas fa-user me-2"></i>الاسم الكامل</th>
                                <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-envelope me-2"></i>البريد الإلكتروني</th>
                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-phone me-2"></i>رقم الهاتف</th>
                                <td><?php echo formatPhoneNumber($patient['phone']); ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-calendar-plus me-2"></i>تاريخ التسجيل</th>
                                <td><?php echo arabicDate($patient['created_at']); ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-sign-in-alt me-2"></i>آخر دخول</th>
                                <td><?php echo $patient['last_login'] ? arabicDateTime($patient['last_login']) : 'غير معروف'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- المعلومات الطبية -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-heartbeat me-2"></i>
                            المعلومات الطبية
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%"><i class="fas fa-tint me-2"></i>فصيلة الدم</th>
                                <td>
                                    <?php if ($patient['blood_type']): ?>
                                        <span class="badge bg-danger"><?php echo htmlspecialchars($patient['blood_type']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">غير محدد</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-ruler-vertical me-2"></i>الطول</th>
                                <td><?php echo $patient['height'] ? htmlspecialchars($patient['height']) . ' سم' : '<span class="text-muted">غير محدد</span>'; ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-weight me-2"></i>الوزن</th>
                                <td><?php echo $patient['weight'] ? htmlspecialchars($patient['weight']) . ' كجم' : '<span class="text-muted">غير محدد</span>'; ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-allergies me-2"></i>الحساسيات</th>
                                <td><?php echo $patient['allergies'] ? nl2br(htmlspecialchars($patient['allergies'])) : '<span class="text-muted">لا يوجد</span>'; ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-procedures me-2"></i>حالة التأمين</th>
                                <td>
                                    <?php if ($patient['insurance_provider']): ?>
                                        <span class="badge bg-success"><?php echo htmlspecialchars($patient['insurance_provider']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">غير مؤمن</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- التاريخ المرضي -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            التاريخ المرضي
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($patient['medical_history']): ?>
                            <div class="medical-history">
                                <?php echo nl2br(htmlspecialchars($patient['medical_history'])); ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>لا يوجد تاريخ مرضي مسجل</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- تبويب المواعيد والسجلات الطبية -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" id="patientTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab">
                            <i class="fas fa-calendar-alt me-2"></i>
                            المواعيد (<?php echo count($appointments); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="records-tab" data-bs-toggle="tab" data-bs-target="#records" type="button" role="tab">
                            <i class="fas fa-file-medical me-2"></i>
                            السجلات الطبية (<?php echo count($medical_records); ?>)
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="patientTabsContent">
                    <!-- قسم المواعيد -->
                    <div class="tab-pane fade show active" id="appointments" role="tabpanel">
                        <?php if ($appointments): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>الطبيب</th>
                                            <th>التخصص</th>
                                            <th>التاريخ</th>
                                            <th>الوقت</th>
                                            <th>الحالة</th>
                                            <th>إجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo $appointment['appointment_id']; ?></td>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/admin/view-doctor.php?id=<?php echo $appointment['doctor_id']; ?>">
                                                    د. <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['specialty_name']); ?></td>
                                            <td><?php echo arabicDate($appointment['appointment_date']); ?></td>
                                            <td>
                                                <?php echo date('h:i A', strtotime($appointment['start_time'])); ?> - 
                                                <?php echo date('h:i A', strtotime($appointment['end_time'])); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_class = [
                                                    'pending' => 'warning',
                                                    'confirmed' => 'primary',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    'no_show' => 'dark'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $status_class[$appointment['status']] ?? 'secondary'; ?>">
                                                    <?php 
                                                    $status_text = [
                                                        'pending' => 'قيد الانتظار',
                                                        'confirmed' => 'مؤكد',
                                                        'completed' => 'مكتمل',
                                                        'cancelled' => 'ملغى',
                                                        'no_show' => 'لم يحضر'
                                                    ];
                                                    echo $status_text[$appointment['status']] ?? 'غير معروف'; 
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/admin/view-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-calendar-times fa-2x mb-3"></i>
                                <h5>لا توجد مواعيد مسجلة</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- قسم السجلات الطبية -->
                    <div class="tab-pane fade" id="records" role="tabpanel">
                        <?php if ($medical_records): ?>
                            <div class="accordion" id="medicalRecordsAccordion">
                                <?php foreach ($medical_records as $record): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="recordHeading<?php echo $record['record_id']; ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recordCollapse<?php echo $record['record_id']; ?>">
                                            <span class="me-3">
                                                <i class="fas fa-file-medical-alt"></i> 
                                                السجل رقم #<?php echo $record['record_id']; ?>
                                            </span>
                                            <span class="badge bg-secondary me-2">
                                                <?php echo arabicDate($record['created_at']); ?>
                                            </span>
                                            <span class="badge bg-primary">
                                                د. <?php echo htmlspecialchars($record['doctor_name']); ?>
                                            </span>
                                        </button>
                                    </h2>
                                    <div id="recordCollapse<?php echo $record['record_id']; ?>" class="accordion-collapse collapse" data-bs-parent="#medicalRecordsAccordion">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-diagnoses me-2"></i>التشخيص:</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                                                    
                                                    <h6 class="mt-4"><i class="fas fa-prescription me-2"></i>الوصفة الطبية:</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($record['prescription'] ?: 'لا يوجد')); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-vial me-2"></i>الفحوصات:</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($record['tests'] ?: 'لا يوجد')); ?></p>
                                                    
                                                    <h6 class="mt-4"><i class="fas fa-notes-medical me-2"></i>ملاحظات إضافية:</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($record['notes'] ?: 'لا يوجد')); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-file-medical fa-2x mb-3"></i>
                                <h5>لا توجد سجلات طبية</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>