<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$auth = new Auth();
// بدء الجلسة إذا لم تكن بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



// إذا كان المستخدم مسجل دخول بالفعل، توجيهه للصفحة المناسبة
if ($auth->isLoggedIn()) {
    header("Location: " . getDashboardUrl($auth->getUserRole()));
    exit();
}

// تهيئة متغيرات الصفحة
$error = '';
$username = '';
$remember = false;
$loginAttempts = 0;

// التحقق من عدد محاولات الدخول الفاشلة
if (isset($_SESSION['login_attempts'])) {
    $loginAttempts = $_SESSION['login_attempts'];
    if ($loginAttempts >= 5) {
        $error = 'لقد تجاوزت عدد المحاولات المسموح بها. يرجى المحاولة لاحقاً.';
    }
}

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $loginAttempts < 5) {
    try {
        // جمع وتنقية البيانات
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // التحقق من صحة البيانات
        if (empty($username) || empty($password)) {
            $error = 'اسم المستخدم وكلمة المرور مطلوبان';
        } else {
            // محاولة تسجيل الدخول
            if ($auth->login($username, $password, $remember)) {
                // إعادة تعيين عداد المحاولات الفاشلة
                unset($_SESSION['login_attempts']);
                
                // تسجيل محاولة الدخول الناجحة
                logLoginAttempt($db, $username, true, $_SERVER['REMOTE_ADDR']);
                
                // توجيه المستخدم حسب دوره
                header("Location: " . getDashboardUrl($auth->getUserRole()));
                exit();
            } else {
                // زيادة عداد المحاولات الفاشلة
                $_SESSION['login_attempts'] = $loginAttempts + 1;
                
                // تسجيل محاولة الدخول الفاشلة
                logLoginAttempt($db, $username, false, $_SERVER['REMOTE_ADDR']);
                
                $error = 'بيانات الدخول غير صحيحة. المحاولات المتبقية: ' . (5 - ($loginAttempts + 1));
            }
        }
    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        $error = 'حدث خطأ أثناء عملية الدخول. يرجى المحاولة لاحقاً.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="login-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="mb-0">تسجيل الدخول</h2>
                        <p class="mb-0 small">مرحباً بعودتك إلى نظامنا الصحي</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="loginForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember" <?php echo $remember ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="remember">تذكرني</label>
                                </div>
                                
                                <a href="<?php echo BASE_PATH . '/forgot-password.php'; ?>" class="text-decoration-none">نسيت كلمة المرور؟</a>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2" <?php echo $loginAttempts >= 5 ? 'disabled' : ''; ?>>
                                <i class="fas fa-sign-in-alt me-2"></i>تسجيل الدخول
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-2">ليس لديك حساب؟</p>
                            <a href="<?php echo BASE_PATH . '/register.php'; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>إنشاء حساب جديد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// إظهار/إخفاء كلمة المرور
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const input = this.parentNode.querySelector('input');
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});

// التحقق من صحة النموذج قبل الإرسال
document.getElementById('loginForm').addEventListener('submit', function(event) {
    const form = event.target;
    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';

/**
 * تسجيل محاولات الدخول في قاعدة البيانات
 */
function logLoginAttempt($db, $username, $success, $ipAddress) {
    try {
        $stmt = $db->prepare("INSERT INTO login_attempts 
                             (username, success, ip_address, user_agent) 
                             VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", 
            $username,
            $success,
            $ipAddress,
            $_SERVER['HTTP_USER_AGENT']
        );
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}