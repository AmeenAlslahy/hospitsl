<?php
// تضمين ملفات التهيئة والاعتمادات الأساسية
require_once __DIR__ . '/includes/config.php'; // ملف الإعدادات العامة
require_once __DIR__ . '/includes/db.php'; // ملف اتصال قاعدة البيانات
require_once __DIR__ . '/includes/functions.php'; // ملف الدوال المساعدة
require_once __DIR__ . '/includes/auth.php'; // ملف المصادقة والتحقق من الهوية

// إنشاء كائنات للتعامل مع قاعدة البيانات ونظام المصادقة
// $db = new Database(); // إنشاء كائن قاعدة بيانات
$auth = new Auth(); // إنشاء كائن مصادقة

// إذا كان المستخدم مسجل دخول بالفعل، يتم توجيهه للصفحة الرئيسية
if ($auth->isLoggedIn()) {
    header("Location: " . BASE_PATH . "/index.php");
    exit(); // إنهاء تنفيذ السكريبت بعد التوجيه
}

// معالجة طلب تسجيل مستخدم جديد (عند إرسال النموذج)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // بدء معاملة (transaction) في قاعدة البيانات لضمان سلامة البيانات
        $db->beginTransaction();

        // 1. مرحلة التحقق من صحة البيانات المدخلة
        $required_fields = ['name', 'email', 'phone', 'password', 'confirm_password']; // الحقول المطلوبة
        $errors = []; // مصفوفة لتخزين الأخطاء

        // التحقق من وجود جميع الحقول المطلوبة
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = "حقل " . getFieldName($field) . " مطلوب";
            }
        }

        // التحقق من صحة البريد الإلكتروني باستخدام دالة filter_var المضمنة في PHP
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "البريد الإلكتروني غير صالح";
        }

        // التحقق من تطابق كلمة المرور وتأكيدها
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $errors[] = "كلمة المرور وتأكيدها غير متطابقتين";
        }

        // التحقق من طول كلمة المرور (8 أحرف على الأقل)
        if (strlen($_POST['password']) < 8) {
            $errors[] = "كلمة المرور يجب أن تكون 8 أحرف على الأقل";
        }

        // التحقق من الموافقة على الشروط والأحكام
        if (!isset($_POST['agree'])) {
            $errors[] = "يجب الموافقة على الشروط والأحكام";
        }
        $username = $_POST['username']; //generateUsername($_POST['name']);
        // 2. التحقق من عدم وجود مستخدم مسجل بنفس البريد أو الهاتف
        // باستخدام دالة مساعدة checkPhoneAndEmail
        // if (checkPhoneAndEmail($username,$_POST['email'],$db)) {
        //     $errors[] = "البريد الإلكتروني أو رقم الهاتف مسجل مسبقاً";
        // }
        
        // 3. إذا لم توجد أخطاء، يتم متابعة عملية التسجيل
        if (empty($errors)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $user_id = $auth->register(sanitize($username),sanitize($_POST['password']),sanitize($_POST['email']),sanitize($_POST['name']),sanitize($_POST['phone']),'patient');
            
            // if($auth->login($_POST['email'], $_POST['password']))
            if ($auth->isLoggedIn())
            {
                // توجيه المستخدم حسب دوره
                header("Location: " . getDashboardUrl($auth->getUserRole()));
                exit();
            }
            // تسجيل الدخول التلقائي للمستخدم الجديد
            // if ($auth->isLoggedIn()) {
            //     header("Location: " . BASE_PATH . "/patient/dashboard.php");
            //     exit();
            // }
        }

    } catch (Exception $e) {
        $db->rollback(); // التراجع عن المعاملة في حالة حدوث خطأ
        $errors[] = "حدث خطأ أثناء التسجيل: " . $e->getMessage();
        error_log("Registration Error: " . $e->getMessage()); // تسجيل الخطأ في سجلات الخادم
    }
}

// تضمين رأس الصفحة (header)
require_once __DIR__ . '/includes/header.php';
?>
<?php
// ... [الكود PHP السابق يبقى كما هو بدون تغيير] ...
?>
<!-- 
<style>
:root {
    --primary-color: #3498db;
    --secondary-color: #2c3e50;
    --success-color: #28a745;
    --info-color: #17a2b8;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --light-color: #f8f9fa;
    --dark-color: #343a40;
}

.register-page {
    background-color: #f5f7fa;
    min-height: 100vh;
}

.card {
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    border: none;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
}

.password-strength .progress {
    height: 8px;
    border-radius: 4px;
}

.password-suggestions {
    background-color: var(--light-color);
    border-radius: 8px;
    padding: 10px;
    margin-top: 10px;
    border: 1px solid #eee;
}

.password-suggestion {
    cursor: pointer;
    padding: 5px;
    margin: 3px 0;
    border-radius: 4px;
    transition: all 0.2s;
}

.password-suggestion:hover {
    background-color: var(--primary-color);
    color: white;
}

.username-generator {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.username-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.username-option {
    background-color: var(--light-color);
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #ddd;
}

.username-option:hover {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.toggle-password {
    border-color: #ced4da;
}

.toggle-password:hover {
    background-color: #e9ecef;
}

/* تحسينات للشاشات الصغيرة */
@media (max-width: 768px) {
    .username-generator {
        flex-direction: column;
    }
}
</style> -->

<div class="register-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="mb-0">تسجيل حساب جديد</h2>
                        <p class="mb-0 small">انضم إلى نظامنا الصحي واحصل على أفضل الخدمات</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">يوجد أخطاء في التسجيل:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="registerForm" novalidate>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" 
                                           required minlength="3">
                                    <div class="invalid-feedback">يجب إدخال اسم صحيح (3 أحرف على الأقل)</div>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="username" class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                                           required minlength="3" pattern="[a-zA-Z0-9_]+">
                                    <div class="invalid-feedback">يجب إدخال اسم مستخدم صحيح (أحرف انجليزية، أرقام و _ فقط)</div>
                                    
                                    <div class="username-generator mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="generateUsername">
                                            <i class="fas fa-magic me-1"></i> توليد اسم مستخدم
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="checkUsername">
                                            <i class="fas fa-check me-1"></i> التحقق من التوفر
                                        </button>
                                    </div>
                                    
                                    <div class="username-options" id="usernameOptions"></div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">يجب إدخال بريد إلكتروني صحيح</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" 
                                           required pattern="[0-9]{9,9}">
                                    <div class="invalid-feedback">يجب إدخال رقم هاتف صحيح (9 ارقامًا)</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="password" class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               required minlength="8">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">كلمة المرور يجب أن تكون 8 أحرف على الأقل</div>
                                    <div class="password-strength mt-2">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small class="text-muted">قوة كلمة المرور: <span id="strengthText">ضعيفة</span></small>
                                    </div>
                                    
                                    <div class="password-suggestions mt-2">
                                        <small class="d-block mb-2">اقتراحات لكلمات مرور قوية:</small>
                                        <div class="password-suggestion" onclick="useSuggestedPassword(this)"><?php echo generateStrongPassword(); ?></div>
                                        <div class="password-suggestion" onclick="useSuggestedPassword(this)"><?php echo generateStrongPassword(); ?></div>
                                        <div class="password-suggestion" onclick="useSuggestedPassword(this)"><?php echo generateStrongPassword(); ?></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">يجب أن تتطابق كلمة المرور مع الحقل السابق</div>
                                    <div class="password-match mt-2">
                                        <small id="passwordMatchText" class="text-muted"></small>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="agree" name="agree" required>
                                        <label class="form-check-label" for="agree">
                                            أوافق على <a href="<?php echo BASE_PATH; ?>/terms.php" target="_blank">الشروط والأحكام</a> 
                                            و <a href="<?php echo BASE_PATH; ?>/privacy.php" target="_blank">سياسة الخصوصية</a>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="invalid-feedback">يجب الموافقة على الشروط والأحكام</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5 py-2">
                                    <i class="fas fa-user-plus me-2"></i>تسجيل الحساب
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-2">لديك حساب بالفعل؟</p>
                            <a href="<?php echo BASE_PATH; ?>/login.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>سجل دخول الآن
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// التحقق من صحة النموذج قبل الإرسال
document.getElementById('registerForm').addEventListener('submit', function(event) {
    const form = event.target;
    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
});

// إظهار/إخفاء كلمة المرور
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const input = this.previousElementSibling;
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

// قياس قوة كلمة المرور
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strength = calculatePasswordStrength(password);
    const progressBar = document.querySelector('.password-strength .progress-bar');
    const strengthText = document.getElementById('strengthText');
    
    if (!progressBar || !strengthText) return; // تحقق إضافي
    
    if (password.length === 0) {
        progressBar.style.width = '0%';
        progressBar.className = 'progress-bar';
        strengthText.textContent = '';
        strengthText.className = '';
        return;
    }
    
    progressBar.style.width = strength.percentage + '%';
    progressBar.className = 'progress-bar ' + strength.class;
    
    // تحديث نص قوة كلمة المرور
    if (strength.percentage < 40) {
        strengthText.textContent = 'ضعيفة';
        strengthText.className = 'text-danger';
    } else if (strength.percentage < 70) {
        strengthText.textContent = 'متوسطة';
        strengthText.className = 'text-warning';
    } else {
        strengthText.textContent = 'قوية';
        strengthText.className = 'text-success';
    }
    
    // التحقق من تطابق كلمة المرور
    checkPasswordMatch();
});

// التحقق من تطابق كلمات المرور
document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchText = document.getElementById('passwordMatchText');
    
    if (confirmPassword.length === 0) {
        matchText.textContent = '';
        return;
    }
    
    if (password === confirmPassword) {
        matchText.textContent = 'كلمات المرور متطابقة';
        matchText.className = 'text-success';
    } else {
        matchText.textContent = 'كلمات المرور غير متطابقة';
        matchText.className = 'text-danger';
    }
}

function calculatePasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength += 20;
    if (password.match(/[a-z]/)) strength += 20;
    if (password.match(/[A-Z]/)) strength += 20;
    if (password.match(/[0-9]/)) strength += 20;
    if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
    
    let className = 'bg-danger';
    if (strength >= 60) className = 'bg-warning';
    if (strength >= 80) className = 'bg-success';
    
    return { percentage: strength, class: className };
}

// توليد اسم مستخدم
document.getElementById('generateUsername').addEventListener('click', function() {
    const name = document.getElementById('name').value;
    if (name.length < 3) {
        alert('الرجاء إدخال الاسم الكامل أولاً');
        return;
    }
    
    // توليد اقتراحات لأسماء المستخدمين
    const nameParts = name.split(' ');
    const firstName = nameParts[0].toLowerCase();
    const lastName = nameParts.length > 1 ? nameParts[nameParts.length - 1].toLowerCase() : '';
    
    const suggestions = [
        firstName + (lastName ? '_' + lastName : ''),
        firstName + Math.floor(Math.random() * 100),
        firstName.charAt(0) + (lastName ? lastName : 'user') + Math.floor(Math.random() * 100),
        'user_' + Math.floor(Math.random() * 1000),
        firstName + '_' + Math.floor(Math.random() * 1000)
    ];
    
    // عرض الاقتراحات
    const optionsContainer = document.getElementById('usernameOptions');
    optionsContainer.innerHTML = '';
    
    suggestions.forEach(suggestion => {
        const option = document.createElement('div');
        option.className = 'username-option';
        option.textContent = suggestion;
        option.addEventListener('click', function() {
            document.getElementById('username').value = suggestion;
            checkUsernameAvailability(suggestion);
        });
        optionsContainer.appendChild(option);
    });
});

// التحقق من توفر اسم المستخدم
document.getElementById('checkUsername').addEventListener('click', function() {
    const username = document.getElementById('username').value;
    if (username.length < 3) {
        alert('الرجاء إدخال اسم مستخدم صحيح');
        return;
    }
    
    checkUsernameAvailability(username);
});

function checkUsernameAvailability(username) {
    // هنا يمكنك إضافة اتصال AJAX للتحقق من توفر اسم المستخدم في قاعدة البيانات
    // هذا مثال للتوضيح فقط
    fetch('check_username.php?username=' + encodeURIComponent(username))
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                alert('اسم المستخدم متاح!');
            } else {
                alert('اسم المستخدم غير متاح، الرجاء اختيار اسم آخر');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // في حالة الخطأ، نفترض أن اسم المستخدم متاح للمتابعة
        });
}

// استخدام كلمة مرور مقترحة
function useSuggestedPassword(element) {
    document.getElementById('password').value = element.textContent;
    document.getElementById('confirm_password').value = element.textContent;
    
    // تحديث عرض قوة كلمة المرور
    const event = new Event('input');
    document.getElementById('password').dispatchEvent(event);
    document.getElementById('confirm_password').dispatchEvent(event);
}

// دالة مساعدة لتوليد كلمات مرور قوية
function generateStrongPassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return password;
}
</script>

<?php
// دالة PHP لتوليد كلمات مرور قوية (للاستخدام في الاقتراحات)
function generateStrongPassword() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < 12; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

require_once __DIR__ . '/includes/footer.php';
?>