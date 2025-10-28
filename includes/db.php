<?php
// تضمين ملف الإعدادات الذي يحتوي على ثوابت اتصال قاعدة البيانات
require_once 'config.php';

class Database {
    private $connection; // خاصية لتخزين اتصال قاعدة البيانات
    
    // المُنشئ - يتم استدعاؤه تلقائياً عند إنشاء كائن من الفئة
    public function __construct() {
        $this->connect(); // استدعاء دالة الاتصال
    }
    
    // دالة للحصول على آخر معرف تم إدراجه
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    // دالة خاصة لإنشاء اتصال بقاعدة البيانات
    private function connect() {
        // إنشاء اتصال جديد مع قاعدة البيانات باستخدام الثوابت من ملف الإعدادات
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // التحقق من وجود أخطاء في الاتصال
        if ($this->connection->connect_error) {
            die("فشل الاتصال بقاعدة البيانات: " . $this->connection->connect_error);
        }
        
        // تعيين ترميز الأحرف لضمان التعامل الصحيح مع اللغة العربية
        $this->connection->set_charset("utf8");
    }
    
    // دالة لإتمام العملية (commit) في حالة استخدام Transactions
    public function commit() {
        return $this->connection->commit();
    }

    // دالة للحصول على كائن الاتصال مباشرةً
    public function getConnection() {
        return $this->connection;
    }
    
    // دالة لتنفيذ استعلام SQL عادي
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    // دالة لإعداد استعلام مُجهز (prepared statement)
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    // دالة لتنفيذ استعلام مُجهز مع معاملات (parameters)
    public function preparedQuery($sql, $params = []) {
        try {
            // إعداد الاستعلام
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("إعداد الاستعلام فشل: " . $this->connection->error);
            }
            
            // إذا كانت هناك معاملات (parameters)
            if (!empty($params)) {
                $types = '';
                // تحديد نوع كل معامل
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i'; // عدد صحيح
                    } elseif (is_double($param)) {
                        $types .= 'd'; // عدد عشري
                    } else {
                        $types .= 's'; // نص
                    }
                }
                
                // ربط المعاملات مع الاستعلام
                $stmt->bind_param($types, ...$params);
            }
            
            // تنفيذ الاستعلام
            $stmt->execute();
            return $stmt;
        } catch (Exception $e) {
            // تسجيل الخطأ في سجلات الخادم
            error_log("خطأ في الاستعلام: " . $e->getMessage());
            throw new Exception("خطأ في معالجة البيانات");
        }
    }

    // دالة لتنظيف القيم وحمايتها من الهجمات قبل استخدامها في الاستعلامات وتستخدم مع الاستعلامات العادية
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
    
    // دالة للحصول على آخر معرف تم إدراجه (بديل لـ lastInsertId)
    public function insertId() {
        return $this->connection->insert_id;
    }
    
    // دوال Transactions الجديدة
    
    // بدء عملية Transaction
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    // التراجع عن عملية Transaction
    public function rollback() {
        return $this->connection->rollback();
    }
    
    // المُدمّر - يتم استدعاؤه تلقائياً عند تدمير الكائن
    public function __destruct() {
        // إغلاق اتصال قاعدة البيانات إذا كان مفتوحاً
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
}
// إنشاء كائن قاعدة بيانات لاستخدامه في التطبيق
$db = new Database();
?>