<?php
require_once 'config.php';

// تنسيق التاريخ
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// تنسيق الوقت
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

// الحصول على معرف المريض من معرف المستخدم
function getPatientId($user_id, $db) {
    $stmt = $db->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['patient_id'];
    }
    return null;
}

// chek of phon and email
function checkPhoneAndEmail($username,$email,$db){
   $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1");
   $stmt->bind_param('ss',$username,$email);
   $stmt->execute();
   if ($stmt->get_result()->num_rows > 0)
    {return true;}
   else {return false;}
}

// الحصول على معلومات الطبيب
function getDoctorById($doctor_id, $db) {
    $result = $db->query("SELECT d.*, u.full_name, u.email, u.phone 
                         FROM doctors d
                         JOIN users u ON d.user_id = u.user_id
                         WHERE d.doctor_id = $doctor_id");
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

// الحصول على معلومات المريض
function getPatientById($patient_id, $db) {
    if (!is_numeric($patient_id) || $patient_id <= 0) {
        throw new InvalidArgumentException("معرف المريض غير صالح");
    }
    
    try {
        $stmt = $db->preparedQuery(
            "SELECT p.*, u.full_name, u.email, u.phone 
             FROM patients p
             JOIN users u ON p.user_id = u.user_id
             WHERE p.patient_id = ?",
            [$patient_id]
        );
        
        $patient = $stmt->get_result()->fetch_assoc();
        
        // التحقق من صلاحيات الوصول
        if (!hasAccessToPatient($_SESSION['user_id'], $patient_id)) {
            throw new Exception("غير مصرح لك بالوصول إلى هذه البيانات");
        }
        
        return $patient;
    } catch (Exception $e) {
        error_log("Error fetching patient: " . $e->getMessage());
        return null;
    }
}
// function getPatientById($patient_id, $db) {
//     $result = $db->query("SELECT p.*, u.full_name, u.email, u.phone 
//                          FROM patients p
//                          JOIN users u ON p.user_id = u.user_id
//                          WHERE p.patient_id = $patient_id");
//     return $result->num_rows > 0 ? $result->fetch_assoc() : null;
// }

// تحويل النص إلى صيغة آمنة
function sanitize($text) {
    return htmlspecialchars(trim(stripslashes($text)), ENT_QUOTES, 'UTF-8');
}

/**
 * عرض رسائل النظام (نجاح/خطأ/تحذير)
 */
function displayFlashMessages() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show">';
        echo htmlspecialchars($_SESSION['success']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show">';
        echo htmlspecialchars($_SESSION['error']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['warning'])) {
        echo '<div class="alert alert-warning alert-dismissible fade show">';
        echo htmlspecialchars($_SESSION['warning']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['warning']);
    }
}
/**
 * تنسيق رقم الهاتف
 */
function formatPhoneNumber($phone) {
    $phone = htmlspecialchars($phone);
    return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
}

/**
 * التحقق من صحة معرف الطبيب
 */
function validateDoctorId($id) {
    return is_numeric($id) && $id > 0;
}
/**
 * تحويل التاريخ إلى صيغة عربية
 */
function arabicDate($date) {
    if (empty($date)) {
        return 'غير محدد';
    }

    $months = [
        'Jan' => 'يناير',
        'Feb' => 'فبراير',
        'Mar' => 'مارس',
        'Apr' => 'أبريل',
        'May' => 'مايو',
        'Jun' => 'يونيو',
        'Jul' => 'يوليو',
        'Aug' => 'أغسطس',
        'Sep' => 'سبتمبر',
        'Oct' => 'أكتوبر',
        'Nov' => 'نوفمبر',
        'Dec' => 'ديسمبر'
    ];

    $english_date = date('d M Y', strtotime($date));
    foreach ($months as $en => $ar) {
        $english_date = str_replace($en, $ar, $english_date);
    }

    return $english_date;
}

/**
 * تنسيق أيام العمل
 */
function formatAvailableDays($days) {
    if (empty($days)) {
        return 'غير محدد';
    }

    $arabicDays = [
        'saturday' => 'السبت',
        'sunday' => 'الأحد',
        'monday' => 'الإثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة'
    ];

    $daysArray = explode(',', $days);
    $formatted = [];

    foreach ($daysArray as $day) {
        if (isset($arabicDays[$day])) {
            $formatted[] = $arabicDays[$day];
        }
    }

    return implode('، ', $formatted);
}


function getProfileImage($image) {
    // التحقق من أن اسم الملف آمن
    if (!empty($image) && preg_match('/^[a-zA-Z0-9_-]+\.(jpg|jpeg|png|gif)$/', $image)) {
        $imagePath = UPLOADS_DIR . '/profiles/' . basename($image);
        if (file_exists($imagePath) && is_file($imagePath)) {
            return UPLOADS_PATH . '/profiles/' . basename($image);
        }
    }
    return ASSETS_PATH . '/images/default-patient.png';
}


function getDoctorImage($image) {
    if ($image && file_exists(__DIR__ . '/../uploads/profiles/' . $image)) {
        return ASSETS_PATH . '/../uploads/profiles/' . $image;
    }
    return ASSETS_PATH . '/images/doctor-default.jpg';
}

function shortenText($text, $length = 100) {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . '...';
    }
    return $text;
}

// استرجاع بيانات المرضى من قاعدة البيانات
function getPatients($db) {
    try {
        $query = "SELECT 
                    p.patient_id,
                    u.user_id,
                    u.full_name AS patient_name,
                    u.email,
                    u.phone,
                    u.gender,
                    p.blood_type,
                    p.height,
                    p.weight,
                    p.medical_history,
                    DATE_FORMAT(u.date_of_birth, '%Y-%m-%d') AS date_of_birth,
                    DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') AS created_at
                  FROM 
                    patients p
                  JOIN 
                    users u ON p.user_id = u.user_id
                  ORDER BY 
                    u.full_name ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
    } catch(Exception $e) {
        error_log("Error fetching patients: " . $e->getMessage());
        return [];
    }
}
// استرجاع بيانات الأطباء من قاعدة البيانات

function getDoctors($db) {
    try {
        $query = "SELECT 
                    d.doctor_id,
                    u.user_id,
                    u.full_name AS doctor_name,
                    u.email,
                    u.phone,
                    u.gender,
                    s.specialty_name,
                    d.qualification,
                    d.experience_years,
                    d.consultation_fee,
                    d.available_days,
                    d.bio,
                    DATE_FORMAT(d.created_at, '%Y-%m-%d %H:%i:%s') AS created_at
                  FROM 
                    doctors d
                  JOIN 
                    users u ON d.user_id = u.user_id
                  JOIN 
                    specialties s ON d.specialty_id = s.specialty_id
                  ORDER BY 
                    u.full_name ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
    } catch(Exception $e) {
        error_log("Error fetching doctors: " . $e->getMessage());
        return [];
    }
}
function getDashboardUrl($role) {
    switch ($role) {
        case 'admin': return BASE_PATH . '/admin/dashboard.php';
        case 'doctor': return BASE_PATH . '/doctor/dashboard.php';
        case 'patient': return BASE_PATH . '/patient/dashboard.php';
        default: return BASE_PATH . '/index.php';
    }
}
function generateUsername($fullName) {
    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fullName));
    $random = rand(100, 999);
    return substr($username, 0, 15) . $random;
}
function getFieldName($field) {
    $names = [
        'name' => 'الاسم الكامل',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'password' => 'كلمة المرور',
        'confirm_password' => 'تأكيد كلمة المرور'
    ];
    return $names[$field] ?? $field;
}

function getDoctorId($user_id,$db) {
    $stmt = $db->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['doctor_id'];
}
// function display_alerts() {
//     if (isset($_SESSION['success'])) {
//         echo '<div class="alert alert-success alert-dismissible fade show">
//                 ' . htmlspecialchars($_SESSION['success']) . '
//                 <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
//               </div>';
//         unset($_SESSION['success']);
//     }
    
//     if (isset($_SESSION['error'])) {
//         echo '<div class="alert alert-danger alert-dismissible fade show">
//                 ' . htmlspecialchars($_SESSION['error']) . '
//                 <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
//               </div>';
//         unset($_SESSION['error']);
//     }
    
//     if (isset($_SESSION['warning'])) {
//         echo '<div class="alert alert-warning alert-dismissible fade show">
//                 ' . htmlspecialchars($_SESSION['warning']) . '
//                 <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
//               </div>';
//         unset($_SESSION['warning']);
//     }
// }

function format_date($date_string, $format = 'Y-m-d') {
    if (empty($date_string)) {
        return 'غير محدد';
    }
    
    try {
        $date = new DateTime($date_string);
        return $date->format($format);
    } catch (Exception $e) {
        error_log("Error formatting date: " . $e->getMessage());
        return $date_string;
    }
}
function send_job_application_notification($job_id, $applicant_name, $applicant_email) {
    global $db;
    
    try {
        // الحصول على معلومات الوظيفة
        $stmt = $db->prepare("SELECT title, department FROM jobs WHERE job_id = ?");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $job = $stmt->get_result()->fetch_assoc();
        
        if (!$job) {
            error_log("Job not found for notification: $job_id");
            return false;
        }
        
        // إعداد محتوى البريد الإلكتروني
        $to = "hr@yourhospital.com"; // أو أي بريد HR
        $subject = "طلب توظيف جديد: " . $job['title'];
        
        $message = "
        <html>
        <head>
            <title>طلب توظيف جديد</title>
        </head>
        <body>
            <h2>طلب توظيف جديد</h2>
            <p><strong>الوظيفة:</strong> {$job['title']} ({$job['department']})</p>
            <p><strong>المتقدم:</strong> $applicant_name</p>
            <p><strong>البريد الإلكتروني:</strong> $applicant_email</p>
            <p><strong>تاريخ التقديم:</strong> " . date('Y-m-d H:i') . "</p>
        </body>
        </html>
        ";
        
        // إعداد headers للبريد الإلكتروني
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@yourhospital.com" . "\r\n";
        
        // إرسال البريد
        // $mail_sent = mail($to, $subject, $message, $headers);
        
        // if (!$mail_sent) {
        //     error_log("Failed to send job application notification email");
        //     return false;
        // }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error sending job application notification: " . $e->getMessage());
        return false;
    }
}


function timeAgo($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'سنة',
        'm' => 'شهر',
        'w' => 'أسبوع',
        'd' => 'يوم',
        'h' => 'ساعة',
        'i' => 'دقيقة',
        's' => 'ثانية',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$string) return 'الآن';
    
    $string = array_slice($string, 0, 1);
    return 'منذ '.implode(', ', $string);
}

// ارسال ملاحظات
function sendNotification($user_id, $message) {
    global $db;
    
    try {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param('is', $user_id, $message);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}
// اعادة حالة التوضيف
function getApplicationStatusText($status) {
    $statuses = [
        'pending' => 'قيد المراجعة',
        'reviewed' => 'تمت المراجعة',
        'interviewed' => 'تمت المقابلة',
        'hired' => 'تم التوظيف',
        'rejected' => 'مرفوض'
    ];
    return $statuses[$status] ?? $status;
}


function getApplicationStatusClass($status) {
    $classes = [
        'pending' => 'warning',
        'reviewed' => 'primary',
        'interviewed' => 'info',
        'hired' => 'success',
        'rejected' => 'danger'
    ];
    return $classes[$status] ?? 'secondary';
}


function handleJobApplicationPostRequests() {
    global $db;
    
    if (isset($_POST['update_status'])) {
        try {
            verifyCsrfToken();
            
            $application_id = intval($_POST['application_id']);
            $new_status = sanitizeInput($_POST['status']);
            $notes = sanitizeInput($_POST['notes'] ?? '');
            
            if (updateApplicationStatus($application_id, $new_status, $notes)) {
                setFlashMessage('تم تحديث حالة الطلب بنجاح', 'success');
            } else {
                throw new Exception('حدث خطأ أثناء تحديث حالة الطلب');
            }
        } catch (Exception $e) {
            setFlashMessage($e->getMessage(), 'error');
        }
        
        redirect('job-applications.php');
    }
}

// تحديث حالة الطلب
function updateApplicationStatus($application_id, $status, $notes) {
    global $db;
    
    $validStatuses = ['pending', 'reviewed', 'interviewed', 'hired', 'rejected'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('حالة غير صالحة');
    }
    
    $stmt = $db->prepare("UPDATE job_applications 
                         SET status = ?, notes = ?, updated_at = NOW() 
                         WHERE application_id = ?");
    $stmt->bind_param("ssi", $status, $notes, $application_id);
    
    return $stmt->execute();
}

// معالجة حذف الطلب
function handleDeleteApplication() {
    global $db;
    
    try {
        $application_id = intval($_GET['delete']);
        $application = getApplicationById($application_id);
        
        if (!$application) {
            throw new Exception('طلب التوظيف غير موجود');
        }
        
        // حذف ملف السيرة الذاتية
        if (!empty($application['cv_path']) && file_exists(__DIR__ . '/' . $application['cv_path'])) {
            unlink(__DIR__ . '/' . $application['cv_path']);
        }
        
        // حذف الطلب من قاعدة البيانات
        $stmt = $db->prepare("DELETE FROM job_applications WHERE application_id = ?");
        $stmt->bind_param("i", $application_id);
        
        if ($stmt->execute()) {
            setFlashMessage('تم حذف الطلب بنجاح', 'success');
        } else {
            throw new Exception('حدث خطأ أثناء حذف الطلب');
        }
    } catch (Exception $e) {
        setFlashMessage($e->getMessage(), 'error');
    }
    
    redirect('job-applications.php');
}

// جلب طلبات التوظيف مع الفلترة
function getFilteredJobApplications() {
    global $db;
    
    $status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    $job_filter = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
    $search_query = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    
    $sql = "SELECT ja.*, j.title as job_title 
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.job_id
            WHERE 1=1";
    
    if (!empty($status_filter)) {
        $sql .= " AND ja.status = '$status_filter'";
    }
    
    if ($job_filter > 0) {
        $sql .= " AND ja.job_id = $job_filter";
    }
    
    if (!empty($search_query)) {
        $sql .= " AND (ja.applicant_name LIKE '%$search_query%' 
                      OR ja.applicant_email LIKE '%$search_query%'
                      OR ja.applicant_phone LIKE '%$search_query%')";
    }
    
    $sql .= " ORDER BY ja.applied_at DESC";
    
    try {
        return $db->query($sql);
    } catch (Exception $e) {
        error_log("Error fetching job applications: " . $e->getMessage());
        return [];
    }
}

// جلب تفاصيل طلب التوظيف
function getApplicationById($application_id) {
    global $db;
    
    $stmt = $db->prepare("SELECT * FROM job_applications WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// جلب جميع الوظائف النشطة
function getAllActiveJobs() {
    global $db;
    
    try {
        return $db->query("SELECT job_id, title FROM jobs WHERE status = 'active' ORDER BY title");
    } catch (Exception $e) {
        error_log("Error fetching jobs: " . $e->getMessage());
        return [];
    }
}
?>