<?php

// إعدادات الموقع
define('SITE_NAME', 'نظام حجز المستشفيات');
define('SITE_URL', 'http://your-subdomain.epizy.com');
// إعدادات قاعدة البيانات
// اسم السيرفر
define('DB_HOST', 'sqlXXX.epizy.com');
// اسم قاعدة البيانات
define('DB_NAME', 'if0_40403261_hospital_db '); // تم التحديث ليتوافق مع قاعدة البيانات التي تم إنشاؤها
define('DB_USER', 'if0_40403261'); // تم التحديث ليتوافق مع المستخدم الذي تم إنشاؤه
define('DB_PASS', 'iAWWPN4W2OnhO'); // تم التحديث ليتوافق مع كلمة المرور التي تم إنشاؤها

// إعدادات أخرى
define('TIMEZONE', 'Asia/Riyadh');
date_default_timezone_set(TIMEZONE);

// تجريبي
define('BASE_PATH', ''); // تم التحديث ليتوافق مع التشغيل على الجذر

// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// مسار الملفات المرفوعة (مسار فعلي على السيرفر)
define('UPLOADS_DIR', __DIR__ . '/../uploads');

// رابط الملفات المرفوعة (URL للوصول عبر المتصفح)
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// مسار الصور الافتراضية
define('ASSETS_PATH', BASE_PATH . '/assets');
?>
