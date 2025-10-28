<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // التحقق من CSRF Token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('طلب غير صالح');
        }

        $application_id = intval($_POST['application_id']);
        $new_status = $db->escape($_POST['status']);
        $notes = $db->escape($_POST['notes'] ?? '');

        $stmt = $db->prepare("UPDATE job_applications 
                             SET status = ?, notes = ?, updated_at = NOW() 
                             WHERE application_id = ?");
        $stmt->bind_param("ssi", $new_status, $notes, $application_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "تم تحديث حالة الطلب بنجاح";
        } else {
            throw new Exception('حدث خطأ أثناء تحديث حالة الطلب');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: job-applications.php");
    exit();
}

// إذا لم يكن الطلب POST، توجيه للصفحة الرئيسية
header("Location: job-applications.php");
exit();