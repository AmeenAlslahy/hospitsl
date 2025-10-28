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

// جلب قائمة فصائل الدم من ملف الإعدادات
$blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

// معالجة إضافة مريض جديد
$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جمع البيانات من النموذج
    $form_data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'blood_type' => $_POST['blood_type'] ?? '',
        'height' => $_POST['height'] ?? '',
        'weight' => $_POST['weight'] ?? '',
        'allergies' => trim($_POST['allergies'] ?? ''),
        'medical_history' => trim($_POST['medical_history'] ?? ''),
        'insurance_provider' => trim($_POST['insurance_provider'] ?? ''),
        'insurance_policy' => trim($_POST['insurance_policy'] ?? '')
    ];

    // التحقق من صحة البيانات
    if (empty($form_data['full_name'])) {
        $errors['full_name'] = 'حقل الاسم الكامل مطلوب';
    }

    if (empty($form_data['email'])) {
        $errors['email'] = 'حقل البريد الإلكتروني مطلوب';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'البريد الإلكتروني غير صالح';
    }

    if (empty($form_data['phone'])) {
        $errors['phone'] = 'حقل الهاتف مطلوب';
    }

    if (empty($form_data['password'])) {
        $errors['password'] = 'حقل كلمة المرور مطلوب';
    } elseif (strlen($form_data['password']) < 8) {
        $errors['password'] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
    }

    // إذا لا يوجد أخطاء، proceed with database operations
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // التحقق من عدم وجود البريد الإلكتروني مسبقاً
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $form_data['email']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['email'] = 'البريد الإلكتروني مسجل مسبقاً';
                throw new Exception('البريد الإلكتروني مسجل مسبقاً');
            }

            // 1. إضافة مستخدم جديد
            $hashed_password = password_hash($form_data['password'], PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users 
                                (username, password, email, full_name, phone, role, profile_picture) 
                                VALUES (?, ?, ?, ?, ?, 'patient', 'default-patient.png')");
            $stmt->bind_param("sssss", 
                $form_data['email'], // استخدام البريد كاسم مستخدم
                $hashed_password,
                $form_data['email'],
                $form_data['full_name'],
                $form_data['phone']
            );
            $stmt->execute();
            $user_id = $stmt->insert_id;

            // 2. إضافة المريض
            $stmt = $db->prepare("INSERT INTO patients 
                                (user_id, blood_type, height, weight, allergies, medical_history, insurance_provider, insurance_policy_number) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isddssss", 
                $user_id,
                $form_data['blood_type'],
                $form_data['height'],
                $form_data['weight'],
                $form_data['allergies'],
                $form_data['medical_history'],
                $form_data['insurance_provider'],
                $form_data['insurance_policy']
            );
            $stmt->execute();

            // 3. رفع صورة المريض إذا وجدت
            if (!empty($_FILES['profile_picture']['name'])) {
                $upload_dir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // التحقق من أن الملف صورة
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['profile_picture']['type'];
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception('نوع الملف غير مسموح به. يسمح فقط بصور JPEG, PNG أو GIF');
                }

                // التحقق من حجم الملف (2MB كحد أقصى)
                $max_size = 2 * 1024 * 1024;
                if ($_FILES['profile_picture']['size'] > $max_size) {
                    throw new Exception('حجم الصورة كبير جداً. الحد الأقصى 2MB');
                }

                // إنشاء اسم فريد للملف
                $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $file_name = 'patient_' . $user_id . '_' . time() . '.' . strtolower($file_ext);
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
                    $db->query("UPDATE users SET profile_picture = '$file_name' WHERE user_id = $user_id");
                }
            }

            $db->commit();

            $_SESSION['success'] = "تم إضافة المريض " . htmlspecialchars($form_data['full_name']) . " بنجاح";
            header("Location: " . BASE_PATH . "/admin/view-patient.php?id=" . $user_id);
            exit();

        } catch (Exception $e) {
            $db->rollback();
            $errors['general'] = "حدث خطأ أثناء إضافة المريض: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="add-patient py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-user-plus me-2"></i>إضافة مريض جديد</h1>
            <a href="<?php echo BASE_PATH; ?>/admin/patients.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>عودة للقائمة
            </a>
        </div>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
        <?php endif; ?>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" novalidate>
                    <div class="row g-3">
                        <!-- العمود الأول - المعلومات الشخصية -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>المعلومات الشخصية</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                               name="full_name" value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>" required>
                                        <?php if (isset($errors['full_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['full_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                               name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                                        <?php if (isset($errors['email'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                                               name="phone" value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" required>
                                        <?php if (isset($errors['phone'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                               name="password" required>
                                        <?php if (isset($errors['password'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                        <?php endif; ?>
                                        <small class="form-text text-muted">يجب أن تكون 8 أحرف على الأقل</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">صورة المريض</label>
                                        <input type="file" class="form-control" name="profile_picture" accept="image/jpeg, image/png, image/gif">
                                        <small class="form-text text-muted">يسمح بصور JPEG, PNG أو GIF بحجم أقصى 2MB</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- العمود الثاني - المعلومات الطبية -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>المعلومات الطبية</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">فصيلة الدم</label>
                                        <select class="form-select" name="blood_type">
                                            <option value="">اختر فصيلة الدم</option>
                                            <?php foreach ($blood_types as $type): ?>
                                                <option value="<?php echo $type; ?>" <?php echo ($form_data['blood_type'] ?? '') === $type ? 'selected' : ''; ?>>
                                                    <?php echo $type; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">الطول (سم)</label>
                                            <input type="number" class="form-control" name="height" 
                                                   value="<?php echo htmlspecialchars($form_data['height'] ?? ''); ?>" min="0" step="0.1">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">الوزن (كجم)</label>
                                            <input type="number" class="form-control" name="weight" 
                                                   value="<?php echo htmlspecialchars($form_data['weight'] ?? ''); ?>" min="0" step="0.1">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">الحساسيات (إن وجدت)</label>
                                        <textarea class="form-control" name="allergies" rows="2"><?php echo htmlspecialchars($form_data['allergies'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">التاريخ المرضي (إن وجد)</label>
                                        <textarea class="form-control" name="medical_history" rows="3"><?php echo htmlspecialchars($form_data['medical_history'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- العمود الثالث - معلومات التأمين -->
                        <div class="col-12">
                            <div class="card border-0 mt-3">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>معلومات التأمين الصحي</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">شركة التأمين</label>
                                            <input type="text" class="form-control" name="insurance_provider" 
                                                   value="<?php echo htmlspecialchars($form_data['insurance_provider'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">رقم وثيقة التأمين</label>
                                            <input type="text" class="form-control" name="insurance_policy" 
                                                   value="<?php echo htmlspecialchars($form_data['insurance_policy'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- زر الحفظ -->
                        <div class="col-12 mt-4">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-undo me-2"></i>إعادة تعيين
                                </button>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-2"></i>حفظ المريض
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// التحقق من صحة النموذج قبل الإرسال
document.querySelector('form').addEventListener('submit', function(e) {
    let valid = true;
    
    // التحقق من الحقول المطلوبة
    const requiredFields = ['full_name', 'email', 'phone', 'password'];
    requiredFields.forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        }
    });
    
    // التحقق من صحة البريد الإلكتروني
    const email = document.querySelector('[name="email"]');
    if (email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        email.classList.add('is-invalid');
        valid = false;
    }
    
    // التحقق من طول كلمة المرور
    const password = document.querySelector('[name="password"]');
    if (password.value && password.value.length < 8) {
        password.classList.add('is-invalid');
        valid = false;
    }
    
    // التحقق من حجم ونوع الصورة
    const profilePic = document.querySelector('[name="profile_picture"]');
    if (profilePic.files.length > 0) {
        const file = profilePic.files[0];
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!validTypes.includes(file.type)) {
            alert('نوع الملف غير مسموح به. يسمح فقط بصور JPEG, PNG أو GIF');
            valid = false;
        }
        
        if (file.size > maxSize) {
            alert('حجم الصورة كبير جداً. الحد الأقصى 2MB');
            valid = false;
        }
    }
    
    if (!valid) {
        e.preventDefault();
    }
});

// إزالة حالة الخطأ عند البدء بالكتابة
document.querySelectorAll('.is-invalid').forEach(input => {
    input.addEventListener('input', function() {
        this.classList.remove('is-invalid');
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>