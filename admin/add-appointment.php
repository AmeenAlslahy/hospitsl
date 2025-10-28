<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();

// التحقق من الصلاحيات (admin أو receptionist)
$allowed_roles = ['admin', 'receptionist'];
if (!$auth->isLoggedIn() || !in_array($auth->getUserRole(), $allowed_roles)) {
    $_SESSION['error'] = "ليس لديك صلاحية الوصول إلى هذه الصفحة";
    header("Location: " . BASE_PATH . "/admin/appointments.php");
    exit();
}

// توليد CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// جلب بيانات المرضى والأطباء
try {
    $patients = getPatients($db);
    $doctors = getDoctors($db);
} catch(Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في جلب البيانات";
    header("Location: " . BASE_PATH . "/admin/appointments.php");
    exit();
}

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $patient_id = $_POST['patient_id'] ?? '';
    $doctor_id = $_POST['doctor_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // تحقق من CSRF
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "رمز الحماية غير صالح.";
        header("Location: " . BASE_PATH . "/admin/add-appointment.php");
        exit();
    }

    // تحقق من الحقول المطلوبة
    if (!$patient_id || !$doctor_id || !$appointment_date || !$appointment_time) {
        $_SESSION['error'] = "جميع الحقول مطلوبة.";
        header("Location: " . BASE_PATH . "/admin/add-appointment.php");
        exit();
    }

    // تحقق من عدم تداخل الموعد
    $stmt = $db->prepare("SELECT 1 FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND start_time = ? AND status <> 'cancelled' LIMIT 1");
    $stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "الطبيب لديه موعد آخر في هذا الوقت.";
        header("Location: " . BASE_PATH . "/admin/add-appointment.php");
        exit();
    }

    // إضافة الموعد
    $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, status, notes) VALUES (?, ?, ?, ?, 'confirmed', ?)");
    $stmt->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $notes);
    if ($stmt->execute()) {
        $_SESSION['success'] = "تم إضافة الموعد بنجاح.";
        header("Location: " . BASE_PATH . "/admin/appointments.php");
        exit();
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء إضافة الموعد.";
        header("Location: " . BASE_PATH . "/admin/add-appointment.php");
        exit();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="add-appointment py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="appointment-form">
                    <div class="form-header text-center">
                        <h2 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>إضافة موعد جديد</h2>
                    </div>
                    <div class="card-body p-4">
                        <?php displayFlashMessages(); ?>
                        <?php if (empty($patients) || empty($doctors)): ?>
                            <div class="alert alert-warning text-center">
                                يجب إضافة مرضى وأطباء أولاً قبل تحديد موعد جديد.
                            </div>
                        <?php else: ?>
                        <form method="POST" id="appointmentForm" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div class="row g-4">
                                <!-- حقل المريض -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">المريض</label>
                                        <select class="form-select select2-patient" name="patient_id" required>
                                            <option value="">اختر المريض</option>
                                            <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['patient_id']; ?>">
                                                <?php echo htmlspecialchars($patient['full_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">ابحث عن المريض باستخدام الاسم</div>
                                    </div>
                                </div>
                                <!-- حقل الطبيب -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">الطبيب</label>
                                        <select class="form-select select2-doctor" name="doctor_id" required>
                                            <option value="">اختر الطبيب</option>
                                            <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['doctor_id']; ?>">
                                                د. <?php echo htmlspecialchars($doctor['full_name']); ?> - <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">اختر الطبيب حسب التخصص</div>
                                    </div>
                                </div>
                                <!-- حقل التاريخ -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">تاريخ الموعد</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control datepicker" name="appointment_date" placeholder="اختر التاريخ" required>
                                            <span class="input-group-text bg-white"><i class="fas fa-calendar-alt"></i></span>
                                        </div>
                                        <div class="form-text">اختر تاريخ اليوم أو أي تاريخ لاحق</div>
                                    </div>
                                </div>
                                <!-- حقل الوقت -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">وقت الموعد</label>
                                        <div id="timeSlotsContainer" class="time-slots-container">
                                            <div class="text-center py-3 text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                يرجى اختيار الطبيب وتاريخ الموعد أولاً
                                            </div>
                                        </div>
                                        <input type="hidden" name="appointment_time" id="selectedTime" required>
                                    </div>
                                </div>
                                <!-- حقل الملاحظات -->
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">ملاحظات</label>
                                        <textarea class="form-control" name="notes" rows="3" placeholder="أي ملاحظات إضافية حول الموعد..."></textarea>
                                    </div>
                                </div>
                                <!-- زر الإرسال -->
                                <div class="col-12 mt-4">
                                    <div class="d-flex justify-content-between">
                                        <a href="<?php echo BASE_PATH; ?>/admin/appointments.php" class="btn btn-outline-secondary px-4">
                                            <i class="fas fa-arrow-left me-2"></i>رجوع
                                        </a>
                                        <button type="submit" class="btn btn-submit px-4" id="submitBtn">
                                            <i class="fas fa-save me-2"></i>حفظ الموعد
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.ar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // تهيئة Select2
    $('.select2-patient, .select2-doctor').select2({
        placeholder: 'اختر',
        language: {
            noResults: function() { return "لا توجد نتائج"; },
            searching: function() { return "جاري البحث..."; }
        },
        width: '100%'
    });

    // تهيئة Datepicker
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        startDate: new Date(),
        language: 'ar'
    });

    // جلب الأوقات المتاحة عند تغيير الطبيب أو التاريخ
    $('[name="doctor_id"], [name="appointment_date"]').change(function() {
        const doctor_id = $('[name="doctor_id"]').val();
        const appointment_date = $('[name="appointment_date"]').val();

        if (doctor_id && appointment_date) {
            $('#timeSlotsContainer').html(`
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2">جاري تحميل الأوقات المتاحة...</p>
                </div>
            `);

            $.ajax({
                url: '<?php echo BASE_PATH; ?>/admin/ajax/get_available_times.php',
                method: 'POST',
                data: { 
                    doctor_id: doctor_id,
                    appointment_date: appointment_date,
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                },
                dataType: 'json',
                success: function(response) {
                    let html = '';
                    if (response.success) {
                        if (response.available_times.length > 0) {
                            html += '<div class="row">';
                            response.available_times.forEach(time => {
                                const displayTime = formatTime(time);
                                html += `
                                    <div class="col-6 col-md-4 col-lg-3 time-slot">
                                        <input type="radio" name="appointment_time_radio" id="time_${time}" value="${time}">
                                        <label for="time_${time}" class="text-center">${displayTime}</label>
                                    </div>
                                `;
                            });
                            html += '</div>';
                        } else {
                            html = `
                                <div class="alert alert-warning text-center">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    لا توجد أوقات متاحة لهذا اليوم، يرجى اختيار تاريخ آخر
                                </div>
                            `;
                        }
                        $('#timeSlotsContainer').html(html);

                        // تحديث الحقل المخفي عند اختيار الوقت
                        $('input[name="appointment_time_radio"]').change(function() {
                            $('#selectedTime').val($(this).val());
                        });
                    } else {
                        $('#timeSlotsContainer').html(`
                            <div class="alert alert-danger text-center">
                                <i class="fas fa-times-circle me-2"></i>
                                ${response.message || 'حدث خطأ أثناء جلب الأوقات المتاحة'}
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#timeSlotsContainer').html(`
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-times-circle me-2"></i>
                            حدث خطأ في الاتصال بالخادم
                        </div>
                    `);
                }
            });
        }
    });

    // دالة لتنسيق الوقت
    function formatTime(time) {
        const [hours, minutes] = time.split(':');
        const period = hours >= 12 ? 'م' : 'ص';
        const hours12 = hours % 12 || 12;
        return `${hours12}:${minutes} ${period}`;
    }

    // التحقق من النموذج قبل الإرسال
    $('#appointmentForm').submit(function(e) {
        if (!$('#selectedTime').val()) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'يرجى اختيار وقت للموعد',
                confirmButtonText: 'حسناً'
            });
        } else {
            $('#submitBtn').prop('disabled', true);
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>