<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// إذا كان المستخدم مسجل دخول بالفعل، توجيهه للصفحة الرئيسية
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

// التحقق من صحة رمز الاستعادة (في التطبيق الحقيقي يتم التحقق من قاعدة البيانات)
$token = $_GET['token'] ?? '';
$valid_token = true; // في التطبيق الحقيقي يتم التحقق من صحة الرمز

if (!$valid_token) {
    header('Location: ' . BASE_PATH . '/forgot-password.php');
    exit;
}

// معالجة تغيير كلمة المرور
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // التحقق من صحة البيانات
    $errors = [];
    
    if (empty($password)) $errors[] = 'كلمة المرور مطلوبة';
    if (strlen($password) < 8) $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
    if ($password !== $confirm_password) $errors[] = 'كلمتا المرور غير متطابقتين';
    
    if (empty($errors)) {
        // هنا يتم تحديث كلمة المرور في قاعدة البيانات
        // هذا مثال فقط - في التطبيق الحقيقي يجب استخدام استعلام آمن مع تشفير كلمة المرور
        
        $_SESSION['success_message'] = 'تم تغيير كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن';
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
}

$auth = new Auth();
require_once __DIR__ . '/includes/header.php';

?>

<div class="reset-password-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="mb-0">تغيير كلمة المرور</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور الجديدة</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">يجب أن تكون 8 أحرف على الأقل</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-save me-2"></i>حفظ كلمة المرور الجديدة
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>