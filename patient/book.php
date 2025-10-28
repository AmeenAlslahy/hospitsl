<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
// file_put_contents('api_debug.log', print_r($_GET, true) . PHP_EOL, FILE_APPEND);
$auth = new Auth();
function requirePatient($auth) {
    if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'patient') {
        $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
        $_SESSION['error'] = "يجب تسجيل الدخول كمريض للوصول إلى هذه الصفحة";
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
}
// ثم استخدمها:
requirePatient($auth);

$patient_id = getPatientId($_SESSION['user_id'], $db);

// جلب التخصصات والأطباء
try {
    $specialties = $db->query("SELECT * FROM specialties WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);
    
    // جلب الأطباء مع معلومات إضافية
    $doctors_query = "SELECT d.doctor_id, u.full_name, s.name as specialty, 
                      d.consultation_fee, d.years_of_experience, d.bio,
                      d.available_days, d.working_hours_start, d.working_hours_end,
                      u.profile_picture, s.specialty_id, d.rating
                      FROM doctors d
                      JOIN users u ON d.user_id = u.user_id
                      JOIN specialties s ON d.specialty_id = s.specialty_id
                      WHERE u.is_active = 1 AND d.is_accepting_new_patients = 1
                      ORDER BY s.name, u.full_name";
    
    $doctors_result = $db->query($doctors_query);
    $doctors = $doctors_result ? $doctors_result->fetch_all(MYSQLI_ASSOC) : [];
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في جلب البيانات من النظام";
    header('Location: ' . BASE_PATH . '/patient/dashboard.php');
    exit;
}

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
    $appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    $is_follow_up = isset($_POST['is_follow_up']) ? 1 : 0;
    $original_appointment_id = filter_input(INPUT_POST, 'original_appointment_id', FILTER_VALIDATE_INT);

    // التحقق من البيانات
    $errors = [];
    if (!$doctor_id) $errors[] = "الرجاء اختيار الطبيب";
    if (!$appointment_date) $errors[] = "الرجاء اختيار تاريخ الموعد";
    if (!$start_time) $errors[] = "الرجاء اختيار وقت الموعد";
    
    if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "لا يمكن حجز موعد في تاريخ قديم";
    }

    if (empty($errors)) {
        try {
            // حساب وقت الانتهاء
            $stmt = $db->prepare("SELECT s.average_consultation_time 
                                 FROM doctors d
                                 JOIN specialties s ON d.specialty_id = s.specialty_id
                                 WHERE d.doctor_id = ?");
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $consultation_time = $result->fetch_assoc()['average_consultation_time'] ?? 30;
            
            $end_time = date('H:i:s', strtotime("+{$consultation_time} minutes", strtotime($start_time)));

            // التحقق من توفر الموعد
            $stmt = $db->prepare("SELECT appointment_id FROM appointments 
                                 WHERE doctor_id = ? 
                                 AND appointment_date = ? 
                                 AND ((start_time <= ? AND end_time > ?) 
                                      OR (start_time < ? AND end_time >= ?)
                                      OR (start_time >= ? AND end_time <= ?))");
            $stmt->bind_param("isssssss", $doctor_id, $appointment_date, 
                             $start_time, $start_time,
                             $end_time, $end_time,
                             $start_time, $end_time);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "هذا الموعد محجوز مسبقاً، يرجى اختيار وقت آخر";
            } else {
                // حجز الموعد
                $stmt = $db->prepare("INSERT INTO appointments 
                                    (patient_id, doctor_id, appointment_date, 
                                     start_time, end_time, notes, status,
                                     is_follow_up, original_appointment_id,
                                     created_at, updated_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())");
                $stmt->bind_param("iissssii", $patient_id, $doctor_id, $appointment_date, 
                                $start_time, $end_time, $notes,
                                $is_follow_up, $original_appointment_id);
                
                if ($stmt->execute()) {
                    $appointment_id = $stmt->insert_id;
                    
                    // إرسال إشعار
                    sendNotification($db, $doctor_id, "طلب موعد جديد", 
                                   "لديك طلب موعد جديد من مريض", 'appointment', $appointment_id);
                    
                    // إرسال رسالة تأكيد
                    sendAppointmentConfirmation($patient_id, $appointment_id, $db);
                    
                    $_SESSION['success'] = "تم طلب حجز الموعد بنجاح، سيتم تأكيده من قبل الطبيب";
                    header('Location: ' . BASE_PATH . '/patient/appointments.php');
                    exit;
                } else {
                    $errors[] = "حدث خطأ أثناء محاولة حجز الموعد";
                }
            }
        } catch (Exception $e) {
            error_log("Booking error: " . $e->getMessage());
            $errors[] = "حدث خطأ في النظام: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

$pageTitle = "حجز موعد جديد";
require_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/patient.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            background-color: #e9ecef;
            color: #6c757d;
            font-weight: bold;
        }
        .step.active {
            background-color: #4a6fa5;
            color: white;
        }
        .doctor-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .doctor-card.selected {
            border-color: #4a6fa5;
            background-color: #f8f9fa;
        }
        .time-slot {
            padding: 8px 12px;
            margin: 5px;
            border-radius: 5px;
            background-color: #f1f8ff;
            cursor: pointer;
            transition: all 0.2s;
        }
        .time-slot:hover {
            background-color: #d0e3ff;
        }
        .time-slot.selected {
            background-color: #4a6fa5;
            color: white;
        }
        .confirmation-details {
            background-color: #f8f9fa;
            border-left: 4px solid #4a6fa5;
        }
        .loading-spinner {
            display: none;
            color: #4a6fa5;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-calendar-plus me-2"></i> حجز موعد جديد</h3>
                </div>
                <div class="card-body">
                    <?php displayFlashMessages(); ?>
                    
                    <form method="POST" id="bookingForm" novalidate>
                        <!-- خطوات الحجز -->
                        <div class="steps-container mb-4 text-center">
                            <div class="d-flex justify-content-center mb-3">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <div class="step <?php echo $i === 1 ? 'active' : ''; ?>" data-step="<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </div>
                                    <?php if ($i < 4): ?>
                                        <div class="step-connector"></div>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div class="step-indicator">
                                <span id="stepDescription" class="fw-bold text-primary">اختر التخصص الطبي</span>
                            </div>
                        </div>
                        
                        <!-- الخطوة 1: اختيار التخصص -->
                        <div class="step-content" data-step="1">
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="fas fa-stethoscope me-2"></i>اختر التخصص المطلوب</h5>
                                <div class="row">
                                    <?php foreach ($specialties as $spec): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card doctor-card specialty-card" 
                                                 data-specialty-id="<?php echo $spec['specialty_id']; ?>"
                                                 data-avg-time="<?php echo $spec['average_consultation_time'] ?? 30; ?>">
                                                <div class="card-body text-center">
                                                    <?php if (!empty($spec['image'])): ?>
                                                        <img src="<?php echo BASE_PATH; ?>/uploads/specialties/<?php echo $spec['image']; ?>" 
                                                             alt="<?php echo $spec['name']; ?>" 
                                                             class="img-fluid rounded-circle mb-3" 
                                                             style="width: 80px; height: 80px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" 
                                                             style="width: 80px; height: 80px;">
                                                            <i class="fas fa-user-md fa-2x text-secondary"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <h6><?php echo htmlspecialchars($spec['name']); ?></h6>
                                                    <?php if ($spec['is_surgical']): ?>
                                                        <span class="badge bg-danger">جراحي</span>
                                                    <?php endif; ?>
                                                    <p class="text-muted small mt-2">
                                                        مدة الاستشارة: <?php echo $spec['average_consultation_time'] ?? 30; ?> دقيقة
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <input type="hidden" id="specialty" name="specialty_id" value="">
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-primary next-step">
                                    التالي <i class="fas fa-arrow-left ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- الخطوة 2: اختيار الطبيب -->
                        <div class="step-content d-none" data-step="2">
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="fas fa-user-md me-2"></i>اختر الطبيب</h5>
                                <div class="mb-3">
                                    <input type="text" id="doctorSearch" class="form-control" placeholder="ابحث عن طبيب...">
                                </div>
                                <div id="doctorsContainer" class="row">
                                    <!-- سيتم تعبئتها عبر JavaScript -->
                                </div>
                            </div>
                            <input type="hidden" id="doctor" name="doctor_id" value="">
                            
                            <div class="doctor-details mb-4 p-3 border rounded d-none" id="doctorDetails">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <img id="doctorImage" src="<?php echo BASE_PATH; ?>/assets/images/default-doctor.png" 
                                             alt="صورة الطبيب" class="img-thumbnail mb-3">
                                        <div class="rating mb-2">
                                            <span id="doctorRating"></span>
                                            <small class="text-muted" id="doctorReviews"></small>
                                        </div>
                                        <div class="fee">
                                            <span class="badge bg-success" id="doctorFee"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <h4 id="doctorName"></h4>
                                        <h6 class="text-muted" id="doctorSpecialty"></h6>
                                        <p class="text-muted" id="doctorExperience"></p>
                                        <div class="availability mb-3">
                                            <h6><i class="fas fa-calendar-alt me-2"></i>أيام العمل:</h6>
                                            <p id="doctorAvailability"></p>
                                        </div>
                                        <div class="bio">
                                            <h6><i class="fas fa-info-circle me-2"></i>معلومات إضافية:</h6>
                                            <p id="doctorBio"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_follow_up" name="is_follow_up">
                                <label class="form-check-label" for="is_follow_up">
                                    هذا الموعد متابعة لموعد سابق
                                </label>
                            </div>
                            
                            <div id="originalAppointmentContainer" class="mb-3 d-none">
                                <label class="form-label">اختر الموعد الأصلي</label>
                                <select class="form-select" id="original_appointment_id" name="original_appointment_id">
                                    <option value="">اختر الموعد الأصلي</option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary prev-step">
                                    <i class="fas fa-arrow-right me-2"></i> السابق
                                </button>
                                <button type="button" class="btn btn-primary next-step">
                                    التالي <i class="fas fa-arrow-left ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- الخطوة 3: اختيار التاريخ والوقت -->
                        <div class="step-content d-none" data-step="3">
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="fas fa-clock me-2"></i>حدد التاريخ والوقت</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">تاريخ الموعد</label>
                                        <input type="date" class="form-control" id="appointment_date" 
                                               name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">وقت الموعد</label>
                                        <div class="input-group">
                                            <select class="form-select" id="start_time" name="start_time" required disabled>
                                                <option value="">اختر الوقت بعد تحديد التاريخ</option>
                                            </select>
                                            <span class="input-group-text loading-spinner" id="timeLoading">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    مدة الاستشارة المتوقعة: <span id="consultationDuration">30</span> دقيقة
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">ملاحظات (اختياري)</label>
                                    <textarea class="form-control" name="notes" rows="3" 
                                              placeholder="أي معلومات إضافية تريد إضافتها..."></textarea>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary prev-step">
                                    <i class="fas fa-arrow-right me-2"></i> السابق
                                </button>
                                <button type="button" class="btn btn-primary next-step">
                                    التالي <i class="fas fa-arrow-left ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- الخطوة 4: تأكيد الحجز -->
                        <div class="step-content d-none" data-step="4">
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="fas fa-check-circle me-2 text-success"></i>تأكيد معلومات الحجز</h5>
                                <div class="confirmation-details p-4 mb-4">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div id="confirmSpecialty" class="mb-3"></div>
                                            <div id="confirmDoctor" class="mb-3"></div>
                                            <div id="confirmDate" class="mb-3"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div id="confirmTime" class="mb-3"></div>
                                            <div id="confirmDuration" class="mb-3"></div>
                                            <div id="confirmFollowUp" class="mb-3"></div>
                                        </div>
                                    </div>
                                    <div id="confirmNotes" class="mt-3 p-3 bg-light rounded"></div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    سيتم مراجعة طلب الحجز من قبل الطبيب وسيتم إعلامك بتأكيد الموعد عبر البريد الإلكتروني أو الرسائل النصية.
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary prev-step">
                                    <i class="fas fa-arrow-right me-2"></i> السابق
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check-circle me-2"></i> تأكيد الحجز
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_PATH = '<?php echo BASE_PATH; ?>';
let selectedSpecialty = null;
let selectedDoctor = null;
let availableDoctors = <?php echo json_encode($doctors); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // عناصر DOM الرئيسية
    const form = document.getElementById('bookingForm');
    const steps = document.querySelectorAll('.step');
    const stepContents = document.querySelectorAll('.step-content');
    const stepDescription = document.getElementById('stepDescription');
    let currentStep = 1;
    
    const stepDescriptions = {
        1: "اختر التخصص الطبي",
        2: "اختر الطبيب",
        3: "حدد التاريخ والوقت",
        4: "تأكيد معلومات الحجز"
    };

    // اختيار التخصص
    document.querySelectorAll('.specialty-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.specialty-card').forEach(c => {
                c.classList.remove('selected');
            });
            this.classList.add('selected');
            selectedSpecialty = this.dataset.specialtyId;
            document.getElementById('specialty').value = selectedSpecialty;
            filterDoctorsBySpecialty(selectedSpecialty);
        });
    });

    // تصفية الأطباء حسب التخصص
    function filterDoctorsBySpecialty(specialtyId) {
        const filteredDoctors = availableDoctors.filter(doctor => 
            doctor.specialty_id == specialtyId
        );
        renderDoctors(filteredDoctors);
    }

    // عرض الأطباء
    function renderDoctors(doctors) {
        const container = document.getElementById('doctorsContainer');
        container.innerHTML = '';
        
        if (doctors.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-4">
                    <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                    <p class="text-muted">لا يوجد أطباء متاحين لهذا التخصص</p>
                </div>
            `;
            return;
        }
        
        doctors.forEach(doctor => {
            const doctorCard = document.createElement('div');
            doctorCard.className = 'col-md-6 mb-3';
            doctorCard.innerHTML = `
                <div class="card doctor-card" data-doctor-id="${doctor.doctor_id}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <img src="${doctor.profile_picture ? 
                                    `${BASE_PATH}/uploads/profiles/${doctor.profile_picture}` : 
                                    `${BASE_PATH}/assets/images/default-doctor.png`}" 
                                     alt="صورة الطبيب" class="img-fluid rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <h6 class="mb-1">د. ${doctor.full_name}</h6>
                                <p class="text-muted small mb-1">${doctor.specialty}</p>
                                <div class="d-flex justify-content-between">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-star text-warning"></i> ${doctor.rating || 'جديد'}
                                    </span>
                                    <span class="badge bg-success">
                                        ${doctor.consultation_fee} ريال
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(doctorCard);
            
            // إضافة حدث النقر لبطاقة الطبيب
            doctorCard.querySelector('.doctor-card').addEventListener('click', function() {
                document.querySelectorAll('.doctor-card').forEach(c => {
                    c.classList.remove('selected');
                });
                this.classList.add('selected');
                selectedDoctor = this.dataset.doctorId;
                document.getElementById('doctor').value = selectedDoctor;
                showDoctorDetails(selectedDoctor);
            });
        });
    }

    // عرض تفاصيل الطبيب
    function showDoctorDetails(doctorId) {
        const doctor = availableDoctors.find(d => d.doctor_id == doctorId);
        if (!doctor) return;
        
        const detailsContainer = document.getElementById('doctorDetails');
        detailsContainer.classList.remove('d-none');
        
        document.getElementById('doctorImage').src = 
            doctor.profile_picture ? 
            `${BASE_PATH}/uploads/profiles/${doctor.profile_picture}` : 
            `${BASE_PATH}/assets/images/default-doctor.png`;
        
        document.getElementById('doctorName').textContent = `د. ${doctor.full_name}`;
        document.getElementById('doctorSpecialty').textContent = doctor.specialty;
        document.getElementById('doctorExperience').textContent = `خبرة: ${doctor.years_of_experience} سنة`;
        document.getElementById('doctorFee').textContent = `${doctor.consultation_fee} ريال`;
        document.getElementById('doctorRating').innerHTML = 
            `<i class="fas fa-star text-warning"></i> ${doctor.rating || 'جديد'}`;
        
        const availableDays = getArabicDays(doctor.available_days);
        document.getElementById('doctorAvailability').textContent = 
            `${availableDays} من ${doctor.working_hours_start} إلى ${doctor.working_hours_end}`;
        
        document.getElementById('doctorBio').textContent = 
            doctor.bio || 'لا توجد معلومات إضافية متاحة';
    }

    // تحويل أيام الأسبوع إلى عربية
    function getArabicDays(daysString) {
        if (!daysString) return 'غير محدد';
        
        const daysMap = {
            '1': 'الإثنين',
            '2': 'الثلاثاء',
            '3': 'الأربعاء',
            '4': 'الخميس',
            '5': 'الجمعة',
            '6': 'السبت',
            '7': 'الأحد'
        };
        
        return daysString.split(',').map(day => daysMap[day]).join('، ');
    }

    // البحث عن الأطباء
    document.getElementById('doctorSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const filteredDoctors = availableDoctors.filter(doctor => 
            selectedSpecialty ? 
            doctor.specialty_id == selectedSpecialty && 
            (doctor.full_name.toLowerCase().includes(searchTerm) || 
            doctor.specialty.toLowerCase().includes(searchTerm)) :
            doctor.full_name.toLowerCase().includes(searchTerm) || 
            doctor.specialty.toLowerCase().includes(searchTerm)
        );
        renderDoctors(filteredDoctors);
    });

    // تغيير حالة حقل المتابعة
    document.getElementById('is_follow_up').addEventListener('change', function() {
        const doctorSelect = document.getElementById('doctor');
        if (this.checked && doctorSelect.value) {
            loadPreviousAppointments(doctorSelect.value);
        } else {
            document.getElementById('originalAppointmentContainer').classList.add('d-none');
        }
    });

    // جلب المواعيد السابقة للمتابعة
    function loadPreviousAppointments(doctorId) {
        showLoading('جلب المواعيد السابقة...');
        
        fetch(`${BASE_PATH}/api/get_previous_appointments.php?doctor_id=${doctorId}&patient_id=<?php echo $patient_id; ?>`)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('original_appointment_id');
                select.innerHTML = '<option value="">اختر الموعد الأصلي</option>';
                
                if (data.success && data.appointments.length > 0) {
                    data.appointments.forEach(app => {
                        const option = document.createElement('option');
                        option.value = app.id;
                        option.textContent = app.display;
                        select.appendChild(option);
                    });
                    document.getElementById('originalAppointmentContainer').classList.remove('d-none');
                } else {
                    document.getElementById('originalAppointmentContainer').classList.add('d-none');
                    showError('لا توجد مواعيد سابقة مع هذا الطبيب');
                    document.getElementById('is_follow_up').checked = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('حدث خطأ أثناء جلب المواعيد السابقة');
            })
            .finally(() => Swal.close());
    }

    // جلب الأوقات المتاحة عند اختيار التاريخ
    // في ملف صفحة الحجز
async function loadAvailableTimes(doctorId, date) {
    const timeSelect = document.getElementById('start_time');
    const loadingIndicator = document.getElementById('timeLoading');
    
    try {
        timeSelect.innerHTML = '<option value="">جاري التحميل...</option>';
        timeSelect.disabled = true;
        loadingIndicator.style.display = 'inline-block';
        
        const response = await fetch(`${BASE_PATH}/api/get_available_times.php?doctor_id=${doctorId}&date=${date}`);
        
        // التحقق من حالة الاستجابة أولاً
        if (!response.ok) {
            throw new Error(`خطأ في الشبكة: ${response.status}`);
        }
        
        // التحقق من أن المحتوى هو JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error(`استجابة غير صالحة: ${text.substring(0, 100)}...`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'فشل في جلب الأوقات');
        }
        
        timeSelect.innerHTML = '';
        
        if (data.available_times && data.available_times.length > 0) {
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'اختر وقت الموعد';
            timeSelect.appendChild(defaultOption);
            
            data.available_times.forEach(time => {
                const option = document.createElement('option');
                option.value = time;
                option.textContent = time;
                timeSelect.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = data.message || 'لا توجد أوقات متاحة';
            timeSelect.appendChild(option);
        }
        
    } catch (error) {
        console.error('Error loading available times:', error);
        timeSelect.innerHTML = '<option value="">حدث خطأ أثناء جلب الأوقات</option>';
        showError(`حدث خطأ: ${error.message}`);
    } finally {
        timeSelect.disabled = false;
        loadingIndicator.style.display = 'none';
    }
}

// استخدام الدالة
document.getElementById('appointment_date').addEventListener('change', function() {
    const doctorId = document.getElementById('doctor').value;
    const date = this.value;
    
    if (doctorId && date) {
        loadAvailableTimes(doctorId, date);
    }
});

    // تحديث تفاصيل التأكيد
    function updateConfirmationDetails() {
        const specialtySelect = document.querySelector('.specialty-card.selected');
        const doctorSelect = document.querySelector('.doctor-card.selected');
        const dateInput = document.getElementById('appointment_date');
        const timeSelect = document.getElementById('start_time');
        const notesTextarea = document.querySelector('textarea[name="notes"]');
        const isFollowUp = document.getElementById('is_follow_up');
        const originalAppointment = document.getElementById('original_appointment_id');
        const durationSpan = document.getElementById('consultationDuration');
        
        if (specialtySelect) {
            document.getElementById('confirmSpecialty').innerHTML = `
                <h6><i class="fas fa-stethoscope me-2"></i>التخصص الطبي</h6>
                <p>${specialtySelect.querySelector('h6').textContent}</p>
            `;
        }
        
        if (doctorSelect) {
            document.getElementById('confirmDoctor').innerHTML = `
                <h6><i class="fas fa-user-md me-2"></i>الطبيب</h6>
                <p>${doctorSelect.querySelector('h6').textContent}</p>
            `;
        }
        
        if (dateInput.value) {
            document.getElementById('confirmDate').innerHTML = `
                <h6><i class="fas fa-calendar-day me-2"></i>التاريخ</h6>
                <p>${formatArabicDate(dateInput.value)}</p>
            `;
        }
        
        if (timeSelect.value) {
            document.getElementById('confirmTime').innerHTML = `
                <h6><i class="fas fa-clock me-2"></i>الوقت</h6>
                <p>${timeSelect.options[timeSelect.selectedIndex].text}</p>
            `;
        }
        
        document.getElementById('confirmDuration').innerHTML = `
            <h6><i class="fas fa-stopwatch me-2"></i>مدة الاستشارة</h6>
            <p>${durationSpan.textContent} دقيقة</p>
        `;
        
        if (isFollowUp.checked && originalAppointment.value) {
            document.getElementById('confirmFollowUp').innerHTML = `
                <h6><i class="fas fa-calendar-check me-2"></i>موعد المتابعة</h6>
                <p>${originalAppointment.options[originalAppointment.selectedIndex].text}</p>
            `;
        } else {
            document.getElementById('confirmFollowUp').innerHTML = '';
        }
        
        const notes = notesTextarea.value;
        document.getElementById('confirmNotes').innerHTML = notes ? 
            `<h6><i class="fas fa-notes-medical me-2"></i>ملاحظات</h6><p>${notes}</p>` : 
            '<p class="text-muted"><i class="fas fa-info-circle me-2"></i>لا توجد ملاحظات</p>';
    }
    
    // تنسيق التاريخ بالعربية
    function formatArabicDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('ar-SA', options);
    }
    
    // عرض رسالة تحميل
    function showLoading(message) {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    // عرض رسالة خطأ
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: message,
            confirmButtonColor: '#4a6fa5'
        });
    }
    
    // عرض رسالة نجاح
    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'تم بنجاح',
            text: message,
            confirmButtonColor: '#28a745',
            timer: 2000
        });
    }

    // التنقل بين الخطوات
    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', function() {
            if (validateStep(currentStep)) {
                showStep(currentStep + 1);
            }
        });
    });

    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', function() {
            showStep(currentStep - 1);
        });
    });

    // التحقق من صحة الخطوة الحالية
    function validateStep(step) {
        switch (step) {
            case 1:
                if (!selectedSpecialty) {
                    showError('الرجاء اختيار التخصص الطبي');
                    return false;
                }
                return true;
            case 2:
                if (!selectedDoctor) {
                    showError('الرجاء اختيار الطبيب');
                    return false;
                }
                
                if (document.getElementById('is_follow_up').checked && 
                    !document.getElementById('original_appointment_id').value) {
                    showError('الرجاء اختيار الموعد الأصلي للمتابعة');
                    return false;
                }
                return true;
            case 3:
                const date = document.getElementById('appointment_date');
                const time = document.getElementById('start_time');
                
                if (!date.value) {
                    showError('الرجاء اختيار تاريخ الموعد');
                    return false;
                }
                
                if (!time.value || time.disabled) {
                    showError('الرجاء اختيار وقت الموعد المتاح');
                    return false;
                }
                
                updateConfirmationDetails();
                return true;
            default:
                return true;
        }
    }

    // عرض الخطوة المحددة
    function showStep(step) {
        if (step < 1 || step > 4) return;
        
        // تحديث حالة الخطوات
        steps.forEach(s => s.classList.remove('active'));
        document.querySelector(`.step[data-step="${step}"]`).classList.add('active');
        
        // تحديث وصف الخطوة
        stepDescription.textContent = stepDescriptions[step];
        
        // إخفاء جميع محتويات الخطوات
        stepContents.forEach(sc => sc.classList.add('d-none'));
        
        // عرض محتوى الخطوة الحالية
        document.querySelector(`.step-content[data-step="${step}"]`).classList.remove('d-none');
        currentStep = step;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>