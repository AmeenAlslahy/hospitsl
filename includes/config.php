<?php

// إعدادات الموقع وقاعدة البيانات
const CONFIG = [
    'SITE_NAME'   => 'نظام حجز المستشفيات',
    'SITE_URL'    => 'http://localhost/hospital-system',
    'DB_HOST'     => 'localhost',
    'DB_NAME'     => 'hospital_management_system',
    'DB_USER'     => 'root',
    'DB_PASS'     => '',
    'TIMEZONE'    => 'Asia/Riyadh',
    'BASE_PATH'   => '/hospital-system',
];

// تعريف الثوابت من المصفوفة
foreach (CONFIG as $key => $value) {
    if (!defined($key)) define($key, $value);
}

// ضبط المنطقة الزمنية
date_default_timezone_set(TIMEZONE);

// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// إعدادات المسارات
$paths = [
    'UPLOADS_DIR'  => __DIR__ . '/../uploads',
    'UPLOADS_PATH' => BASE_PATH . '/uploads',
    'ASSETS_PATH'  => BASE_PATH . '/assets',
];

foreach ($paths as $key => $value) {
    if (!defined($key)) define($key, $value);
}
?>