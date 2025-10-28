<?php
// includes/install_db.php

define('INSTALL_LOCK_FILE', __DIR__.'/../install.lock');

// التحقق من أن التثبيت لم يتم مسبقاً
if (file_exists(INSTALL_LOCK_FILE)) {
    die('تم تثبيت قاعدة البيانات مسبقاً.');
}

require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';

try {
    // إنشاء الجداول
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (...) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS specialties (...) ENGINE=InnoDB",
        // بقية الجداول كما ذكرنا سابقاً
    ];

    foreach ($tables as $sql) {
        if (!$db->query($sql)) {
            throw new Exception("خطأ في إنشاء الجداول: " . $db->error);
        }
    }

    // إضافة بيانات أولية إن لزم الأمر
    $initial_data = [
        "INSERT INTO specialties (name) VALUES ('طب عام'), ('جراحة')",
        // بيانات أولية أخرى
    ];

    foreach ($initial_data as $sql) {
        $db->query($sql);
    }

    // إنشاء ملف القفل
    file_put_contents(INSTALL_LOCK_FILE, 'تم التثبيت في: '.date('Y-m-d H:i:s'));

    echo "تم تثبيت قاعدة البيانات بنجاح!";
    
} catch (Exception $e) {
    die("حدث خطأ أثناء التثبيت: ".$e->getMessage());
}
/*

table users
user_id     username    full_name   password    email    phone  role    

*/