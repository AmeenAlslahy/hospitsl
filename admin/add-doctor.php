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

// جلب جميع التخصصات
$specialties = $db->query("SELECT * FROM specialties WHERE is_active = TRUE")->fetch_all(MYSQLI_ASSOC);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // التحقق من الحقول المطلوبة
        $required = ['username', 'password', 'full_name', 'email', 'phone', 'specialty_id', 'license_number', 'qualification', 'consultation_fee', 'working_hours_start', 'working_hours_end'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("جميع الحقول المطلوبة يجب تعبئتها.");
            }
        }

        // التحقق من صحة البريد الإلكتروني
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("البريد الإلكتروني غير صالح.");
        }

        // التحقق من تكرار اسم المستخدم أو البريد الإلكتروني
        $stmt = $db->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $_POST['username'], $_POST['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("اسم المستخدم أو البريد الإلكتروني مستخدم مسبقًا.");
        }

        // معالجة رفع الصورة
        $profile_picture = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            if (!in_array($file_ext, $allowed_types)) {
                throw new Exception("نوع الصورة غير مدعوم. الأنواع المسموحة: jpg, jpeg, png, gif");
            }
            if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
                throw new Exception("حجم الصورة يجب ألا يتجاوز 2 ميجابايت.");
            }
            $upload_dir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_name = 'doctor_' . time() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $file_name)) {
                $profile_picture = $file_name;
            }
        }

        $db->beginTransaction();

        // 1. إضافة مستخدم جديد
        $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users 
                            (username, password, email, full_name, phone, role, profile_picture) 
                            VALUES (?, ?, ?, ?, ?, 'doctor', ?)");
        $stmt->bind_param("ssssss", 
            $_POST['username'],
            $hashed_password,
            $_POST['email'],
            $_POST['full_name'],
            $_POST['phone'],
            $profile_picture
        );
        $stmt->execute();
        $user_id = $stmt->insert_id;

        // 2. إضافة بيانات الطبيب
        $available_days = isset($_POST['available_days']) ? implode(',', $_POST['available_days']) : '';
        $stmt = $db->prepare("INSERT INTO doctors 
                            (user_id, specialty_id, license_number, qualification, 
                             years_of_experience, consultation_fee, available_days, 
                             working_hours_start, working_hours_end, experience, bio) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissidsssss",
            $user_id,
            $_POST['specialty_id'],
            $_POST['license_number'],
            $_POST['qualification'],
            $_POST['years_of_experience'],
            $_POST['consultation_fee'],
            $available_days,
            $_POST['working_hours_start'],
            $_POST['working_hours_end'],
            $_POST['experience'],
            $_POST['bio']
        );
        $stmt->execute();
        $doctor_id = $stmt->insert_id;

        $db->commit();

        $_SESSION['success'] = "تم إضافة الطبيب بنجاح";
        header("Location: view-doctor.php?id=" . $doctor_id);
        exit();

    } catch (Exception $e) {
        if ($db->getConnection()->errno) $db->rollback();
        $error = "حدث خطأ أثناء الإضافة: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="add-doctor py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>إضافة طبيب جديد</h1>
            <a href="<?php echo BASE_PATH; ?>/admin/doctors.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>عودة للقائمة
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row g-4">
                <!-- العمود الأول - المعلومات الشخصية -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">المعلومات الشخصية</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="username" class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="invalid-feedback">يرجى إدخال اسم المستخدم</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                <div class="invalid-feedback">كلمة المرور يجب أن تكون 8 أحرف على الأقل</div>
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                                <div class="invalid-feedback">يرجى إدخال الاسم الكامل</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">يرجى إدخال بريد إلكتروني صحيح</div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                                <div class="invalid-feedback">يرجى إدخال رقم الهاتف</div>
                            </div>

                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">صورة الطبيب</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- العمود الثاني - المعلومات المهنية -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">المعلومات المهنية</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="specialty_id" class="form-label">التخصص <span class="text-danger">*</span></label>
                                <select class="form-select" id="specialty_id" name="specialty_id" required>
                                    <option value="">اختر التخصص</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                        <option value="<?php echo $specialty['specialty_id']; ?>">
                                            <?php echo htmlspecialchars($specialty['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">يرجى اختيار التخصص</div>
                            </div>

                            <div class="mb-3">
                                <label for="license_number" class="form-label">رقم الرخصة الطبية <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="license_number" name="license_number" required>
                                <div class="invalid-feedback">يرجى إدخال رقم الرخصة</div>
                            </div>

                            <div class="mb-3">
                                <label for="years_of_experience" class="form-label">سنوات الخبرة</label>
                                <input type="number" class="form-control" id="years_of_experience" name="years_of_experience" min="0" value="0">
                            </div>

                            <div class="mb-3">
                                <label for="consultation_fee" class="form-label">رسوم الكشف (ريال) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" min="0" step="0.01" required>
                                <div class="invalid-feedback">يرجى إدخال قيمة صحيحة</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">أيام العمل <span class="text-danger">*</span></label>
                                <div class="days-checkbox">
                                    <?php 
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
                                                   value="<?php echo $key; ?>">
                                            <label class="form-check-label" for="day_<?php echo $key; ?>">
                                                <?php echo $day; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="working_hours_start" class="form-label">بداية الدوام <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="working_hours_start" name="working_hours_start" value="08:00" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="working_hours_end" class="form-label">نهاية الدوام <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="working_hours_end" name="working_hours_end" value="16:00" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- العمود الثالث - معلومات إضافية -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">معلومات إضافية</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="qualification" class="form-label">المؤهلات العلمية <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="qualification" name="qualification" rows="3" required></textarea>
                                <div class="invalid-feedback">يرجى إدخال المؤهلات العلمية</div>
                            </div>

                            <div class="mb-3">
                                <label for="experience" class="form-label">الخبرات العملية</label>
                                <textarea class="form-control" id="experience" name="experience" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="bio" class="form-label">سيرة ذاتية مختصرة</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>حفظ الطبيب الجديد
                </button>
                <button type="reset" class="btn btn-outline-secondary btn-lg ms-2">
                    <i class="fas fa-undo me-2"></i>إعادة تعيين
                </button>
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