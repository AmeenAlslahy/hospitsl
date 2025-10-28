<?php
// تضمين ملفات قاعدة البيانات والوظائف المساعدة
require_once 'db.php';
require_once 'functions.php';

class Auth {
    private $db; // خاصية لتخزين كائن قاعدة البيانات
    
    // المُنشئ - يتم استدعاؤه تلقائياً عند إنشاء كائن من الفئة
    public function __construct() {
        $this->db = new Database(); // إنشاء كائن قاعدة بيانات جديد
    }
    
    // دالة تسجيل مستخدم جديد
    public function register($username, $password, $email, $full_name, $phone, $role) {
        try {
            $conn = $this->db->getConnection(); // الحصول على اتصال قاعدة البيانات
            // التحقق من عدم وجود مستخدم بنفس الاسم أو البريد الإلكتروني
            // استخدام FOR UPDATE يمنع حدوث حالة "سباق" (race condition) إذا حاول مستخدمان التسجيل بنفس البيانات في نفس اللحظة
            $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? FOR UPDATE");
            $check->bind_param("ss", $username, $email);
            $check->execute();
            $result = $check->get_result();
            
            // إذا وجد مستخدم موجود بالفعل
            if ($result->num_rows > 0) {
                $conn->rollback(); // لا حاجة للمتابعة، تراجع عن أي شيء
                return null; // مستخدم موجود بالفعل
            }
            $check->close();
            // تشفير كلمة المرور باستخدام خوارزمية BCRYPT
            // $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $hashed_password = $password;
            // إدراج المستخدم الجديد في قاعدة البيانات
            $stmt = $conn->prepare("INSERT INTO users (username, password , email, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $hashed_password, $email, $full_name, $phone, $role);
            
            // إذا تم تنفيذ الإدراج بنجاح
            if ($stmt->execute()) {
                $user_id = $conn->insert_id; // الحصول على آخر معرف تم إدراجه
                
                // إذا كان دور المستخدم "patient"، يتم إنشاء سجل مريض مرتبط
                if ($role === 'patient') {
                    $stmt = $conn->prepare("INSERT INTO patients (user_id) VALUES (?)");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                }
                $this->db->commit();

                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['redirect'] = 'patient/dashboard.php';
                return $user_id; // إرجاع معرف المستخدم الجديد
            }
        }
        catch(Exception $e) { 
            $conn->rollback();
            return false; // في حالة حدوث أي خطأ، إرجاع false
        } 
    }
    
    // دالة تسجيل الدخول
    public function login($username, $password) {
        $conn = $this->db->getConnection(); // الحصول على اتصال قاعدة البيانات
        
        // البحث عن المستخدم باستخدام اسم المستخدم
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // إذا وجد مستخدم بهذا الاسم
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc(); // جلب بيانات المستخدم
            
            // التحقق من كلمة المرور (ملاحظة: الكود الحالي يقارن كلمات المرور مباشرة بدون تشفير - غير آمن)
            //if (password_verify($password, $user['password'])) // الطريقة الصحيحة
            if ($password == $user['password']) {
                // تخزين بيانات المستخدم في الجلسة
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // تحديد الصفحة الافتراضية حسب دور المستخدم
                switch ($user['role']) {
                    case 'admin':
                        $_SESSION['redirect'] = 'admin/dashboard.php';
                        break;
                    case 'doctor':
                        $_SESSION['redirect'] = 'doctor/dashboard.php';
                        break;
                    case 'patient':
                        $_SESSION['redirect'] = 'patient/dashboard.php';
                        break;
                    default:
                        $_SESSION['redirect'] = 'index.php';
                }
                
                return true; // إرجاع true للإشارة إلى نجاح تسجيل الدخول
            }
        }
        
        return false; // إرجاع false للإشارة إلى فشل تسجيل الدخول
    }
    
    // دالة للتحقق من حالة تسجيل الدخول
    public function isLoggedIn() {
        return isset($_SESSION['user_id']); // التحقق من وجود معرف مستخدم في الجلسة
    }
    
    // دالة لتسجيل الخروج
    public function logout() {
        session_unset(); // مسح جميع متغيرات الجلسة
        session_destroy(); // تدمير الجلسة بالكامل
    }
    
    // دالة للحصول على دور المستخدم الحالي
    public function getUserRole() {
        return isset($_SESSION['role']) ? $_SESSION['role'] : null;
    }
    
    // دالة للحصول على معرف المستخدم الحالي
    public function getCurrentUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
}
// في كلاس Database

// ثم الاستخدام

?>