<?php
require_once __DIR__ . '/includes/config.php';

// إذا كان المستخدم مسجل دخول بالفعل، توجيهه للصفحة الرئيسية
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

// معالجة طلب استعادة كلمة المرور
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    // هنا يتم إرسال رابط استعادة كلمة المرور
    // هذا مثال فقط - في التطبيق الحقيقي يجب إرسال بريد إلكتروني
    $success = true; // افتراضيًا نجاح العملية
    
    if ($success) {
        $message = 'تم إرسال رابط استعادة كلمة المرور إلى بريدك الإلكتروني';
    } else {
        $error = 'البريد الإلكتروني غير مسجل في نظامنا';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="forgot-password-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="mb-0">استعادة كلمة المرور</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($message)): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php elseif (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <p class="mb-4">أدخل عنوان البريد الإلكتروني المرتبط بحسابك وسنرسل لك رابطًا لاستعادة كلمة المرور.</p>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-paper-plane me-2"></i>إرسال رابط الاستعادة
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <p class="text-center mb-0">تذكرت كلمة المرور؟ 
                            <a href="<?php echo BASE_PATH; ?>/login.php" class="text-decoration-none">سجل دخول الآن</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>