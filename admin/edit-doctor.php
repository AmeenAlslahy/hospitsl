<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
// التحقق من صلاحيات المدير
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

$doctor_id = $_GET['id'] ?? 0;

// جلب بيانات الطبيب الحالية
$stmt = $db->prepare("SELECT 
                        d.*, 
                        u.full_name, 
                        u.email, 
                        u.phone, 
                        u.profile_picture,
                        s.name as specialty_name
                     FROM doctors d 
                     JOIN users u ON d.user_id = u.user_id
                     JOIN specialties s ON d.specialty_id = s.specialty_id
                     WHERE d.doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor) {
    header("Location: " . BASE_PATH . "/admin/doctors.php");
    exit();
}

// جلب جميع التخصصات للقائمة المنسدلة
$specialties = $db->query("SELECT * FROM specialties WHERE is_active = TRUE")->fetch_all(MYSQLI_ASSOC);

// معالجة تحديث البيانات
// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // بدء المعاملة
        $db->getConnection()->begin_transaction();

        // تحديث بيانات المستخدم
        $stmt = $db->getConnection()->prepare("UPDATE users SET 
                             full_name = ?, 
                             email = ?, 
                             phone = ? 
                             WHERE user_id = ?");
        $stmt->bind_param("sssi", 
            $_POST['full_name'],
            $_POST['email'],
            $_POST['phone'],
            $doctor['user_id']
        );
        $stmt->execute();

        // تحويل أيام العمل إلى سلسلة نصية
        $available_days = isset($_POST['available_days']) ? implode(',', $_POST['available_days']) : '';

        // تحديث بيانات الطبيب
        $stmt = $db->getConnection()->prepare("UPDATE doctors SET 
                             specialty_id = ?, 
                             license_number = ?, 
                             qualification = ?, 
                             years_of_experience = ?, 
                             consultation_fee = ?, 
                             available_days = ?, 
                             working_hours_start = ?, 
                             working_hours_end = ?, 
                             experience = ?, 
                             bio = ? 
                             WHERE doctor_id = ?");
        $stmt->bind_param("issidsssssi",
            $_POST['specialty_id'],
            $_POST['license_number'],
            $_POST['qualification'],
            $_POST['years_of_experience'],
            $_POST['consultation_fee'],
            $available_days,
            $_POST['working_hours_start'],
            $_POST['working_hours_end'],
            $_POST['experience'],
            $_POST['bio'],
            $doctor_id
        );
        $stmt->execute();

        // معالجة رفع الصورة
        if (!empty($_FILES['profile_picture']['name'])) {
            $upload_dir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $file_name = 'doctor_' . $doctor_id . '_' . time() . '.' . $file_ext;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $file_name)) {
                // حذف الصورة القديمة إذا وجدت
                if (!empty($doctor['profile_picture']) && file_exists($upload_dir . $doctor['profile_picture'])) {
                    unlink($upload_dir . $doctor['profile_picture']);
                }
                
                $db->getConnection()->query("UPDATE users SET profile_picture = '$file_name' WHERE user_id = " . $doctor['user_id']);
            }
        }

        // تأكيد المعاملة
        $db->getConnection()->commit();
        
        $_SESSION['success'] = "تم تحديث بيانات الطبيب بنجاح";
        header("Location: view-doctor.php?id=" . $doctor_id);
        exit();

    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة الخطأ
        if ($db->getConnection()) {
            $db->getConnection()->rollback();
        }
        $_SESSION['error'] = "حدث خطأ أثناء التحديث: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="edit-doctor py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>تعديل بيانات الطبيب</h1>
            <a href="<?php echo BASE_PATH; ?>/admin/view-doctor.php?id=<?php echo $doctor_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>عودة
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">المعلومات الشخصية</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 text-center">
                                <?php if (!empty($doctor['profile_picture'])): ?>
                                    <img src="<?php echo UPLOADS_PATH . '/profiles/' . htmlspecialchars($doctor['profile_picture']); ?>" 
                                         class="rounded-circle mb-3" width="150" height="150" alt="صورة الطبيب">
                                <?php else: ?>
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" 
                                         style="width:150px; height:150px; margin: 0 auto;">
                                        <i class="fas fa-user-md fa-3x text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">الاسم الكامل</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($doctor['full_name']); ?>" required>
                                <div class="invalid-feedback">يرجى إدخال الاسم الكامل</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                                <div class="invalid-feedback">يرجى إدخال بريد إلكتروني صحيح</div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">رقم الهاتف</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($doctor['phone']); ?>" required>
                                <div class="invalid-feedback">يرجى إدخال رقم الهاتف</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">المعلومات المهنية</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="specialty_id" class="form-label">التخصص</label>
                                <select class="form-select" id="specialty_id" name="specialty_id" required>
                                    <option value="">اختر التخصص</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                        <option value="<?php echo $specialty['specialty_id']; ?>" 
                                            <?php echo $specialty['specialty_id'] == $doctor['specialty_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($specialty['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">يرجى اختيار التخصص</div>
                            </div>

                            <div class="mb-3">
                                <label for="license_number" class="form-label">رقم الرخصة الطبية</label>
                                <input type="text" class="form-control" id="license_number" name="license_number" 
                                       value="<?php echo htmlspecialchars($doctor['license_number'] ?? ''); ?>" required>
                                <div class="invalid-feedback">يرجى إدخال رقم الرخصة</div>
                            </div>

                            <div class="mb-3">
                                <label for="years_of_experience" class="form-label">سنوات الخبرة</label>
                                <input type="number" class="form-control" id="years_of_experience" name="years_of_experience" 
                                       value="<?php echo htmlspecialchars($doctor['years_of_experience'] ?? '0'); ?>" min="0" required>
                            </div>

                            <div class="mb-3">
                                <label for="consultation_fee" class="form-label">رسوم الكشف (ريال)</label>
                                <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" 
                                       value="<?php echo htmlspecialchars($doctor['consultation_fee'] ?? '0'); ?>" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">أيام وساعات العمل</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">أيام العمل</label>
                                <div class="days-checkbox">
                                    <?php 
                                    $available_days = explode(',', $doctor['available_days'] ?? '');
                                    $days = [
                                        'saturday' => 'السبت',
                                        'sunday' => 'الأحد',
                                        'monday' => 'الإثنين',
                                        'tuesday' => 'الثلاثاء',
                                        'wednesday' => 'الأربعاء',
                                        'thursday' => 'الخميس',
                                        'friday' => 'الجمعة'
                                    ];
                                    foreach ($days as $key => $day): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="day_<?php echo $key; ?>" 
                                                   name="available_days[]" 
                                                   value="<?php echo $key; ?>"
                                                   <?php echo in_array($key, $available_days) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="day_<?php echo $key; ?>">
                                                <?php echo $day; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="working_hours_start" class="form-label">بداية الدوام</label>
                                    <input type="time" class="form-control" id="working_hours_start" name="working_hours_start" 
                                           value="<?php echo htmlspecialchars($doctor['working_hours_start'] ?? '08:00'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="working_hours_end" class="form-label">نهاية الدوام</label>
                                    <input type="time" class="form-control" id="working_hours_end" name="working_hours_end" 
                                           value="<?php echo htmlspecialchars($doctor['working_hours_end'] ?? '16:00'); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">معلومات إضافية</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="qualification" class="form-label">المؤهلات العلمية</label>
                                <textarea class="form-control" id="qualification" name="qualification" rows="3" required><?php 
                                    echo htmlspecialchars($doctor['qualification'] ?? ''); 
                                ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="experience" class="form-label">الخبرات العملية</label>
                                <textarea class="form-control" id="experience" name="experience" rows="3"><?php 
                                    echo htmlspecialchars($doctor['experience'] ?? ''); 
                                ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="bio" class="form-label">سيرة ذاتية مختصرة</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3"><?php 
                                    echo htmlspecialchars($doctor['bio'] ?? ''); 
                                ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>حفظ التعديلات
                </button>
                <a href="<?php echo BASE_PATH; ?>/admin/view-doctor.php?id=<?php echo $doctor_id; ?>" class="btn btn-outline-secondary btn-lg ms-2">
                    <i class="fas fa-times me-2"></i>إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// تفعيل التحقق من النماذج
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>