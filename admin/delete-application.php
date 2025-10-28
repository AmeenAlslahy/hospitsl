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

if (isset($_GET['id'])) {
    try {
        $application_id = intval($_GET['id']);
        
        // جلب مسار السيرة الذاتية لحذف الملف
        $stmt = $db->prepare("SELECT cv_path FROM job_applications WHERE application_id = ?");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        
        if ($application) {
            // حذف ملف السيرة الذاتية إذا كان موجوداً
            if (!empty($application['cv_path']) && file_exists(__DIR__ . '/../' . $application['cv_path'])) {
                unlink(__DIR__ . '/../' . $application['cv_path']);
            }
            
            // حذف الطلب من قاعدة البيانات
            $stmt = $db->prepare("DELETE FROM job_applications WHERE application_id = ?");
            $stmt->bind_param("i", $application_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "تم حذف الطلب بنجاح";
            } else {
                throw new Exception('حدث خطأ أثناء حذف الطلب');
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

header("Location: job-applications.php");
exit();