
// require_once __DIR__ . '/includes/config.php';

// // إنهاء الجلسة
// session_unset();
// session_destroy();

// // التوجيه إلى الصفحة الرئيسية
// header('Location: index.php');
// exit;
// 

<?php
require_once __DIR__ . '/includes/config.php';

// بداية الجلسة إذا لم تكن بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تنظيف جميع بيانات الجلسة
$_SESSION = array();

// إذا كان سيتم حذف كوكي الجلسة
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// تدمير الجلسة بالكامل
session_destroy();

// إعادة التوجيه إلى الصفحة الرئيسية مع منع التخزين المؤقت
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Location: index.php');
exit();
?>