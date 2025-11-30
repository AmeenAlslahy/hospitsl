<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';



// التحقق من وجود معرف الوظيفة
if (!isset($_GET['job_id'])) {
    $_SESSION['error'] = "لم يتم تحديد وظيفة للتقديم عليها";
    header("Location: jobs.php");
    exit();
}

$job_id = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);

if (!$job_id) {
    $_SESSION['error'] = "معرف الوظيفة غير صالح";
    header("Location: jobs.php");
    exit();
}

// جلب بيانات الوظيفة
try {
    $stmt = $db->prepare("SELECT j.*, COUNT(ja.application_id) as applicants_count 
                         FROM jobs j
                         LEFT JOIN job_applications ja ON j.job_id = ja.job_id
                         WHERE j.job_id = ? AND j.status = 'open' AND j.closing_date >= CURDATE()");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    
    if (!$job) {
        $_SESSION['error'] = "الوظيفة غير موجودة أو انتهى موعد التقديم";
        header("Location: jobs.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching job: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في جلب بيانات الوظيفة";
    header("Location: jobs.php");
    exit();
}

$pageTitle = "التقديم على وظيفة: " . htmlspecialchars($job['title']);
$currentPage = 'jobs';
$auth = new Auth();


// معالجة طلب التقديم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    try {
        // التحقق من CSRF Token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('طلب غير صالح (CSRF Token mismatch)');
        }

        // تنظيف المدخلات والتحقق منها
        $applicant_name = sanitize($_POST['applicant_name']);
        $applicant_email = filter_input(INPUT_POST, 'applicant_email', FILTER_SANITIZE_EMAIL);
        $applicant_phone = sanitize($_POST['applicant_phone']);
        $cover_letter = sanitize($_POST['cover_letter'] ?? '');

        // التحقق من صحة البيانات
        if (empty($applicant_name) || empty($applicant_email) || empty($applicant_phone)) {
            throw new Exception('الرجاء إدخال جميع البيانات المطلوبة');
        }

        if (!filter_var($applicant_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('البريد الإلكتروني غير صالح');
        }

        // تحميل ملف السيرة الذاتية
        $cv_path = '';
        if (isset($_FILES['cv'])) {
            $file = $_FILES['cv'];
            
            // التحقق من وجود أخطاء في التحميل
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception(get_upload_error_message($file['error']));
            }

            $allowed_types = [
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
            ];
            $max_size = 2 * 1024 * 1024; // 2MB

            // التحقق من حجم الملف
            if ($file['size'] > $max_size) {
                throw new Exception('حجم الملف يجب أن لا يتجاوز 2MB');
            }

            // التحقق من نوع الملف
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            
            if (!array_key_exists($mime, $allowed_types)) {
                throw new Exception('يجب أن يكون الملف من نوع PDF أو Word');
            }

            // إنشاء مجلد التحميل إذا لم يكن موجوداً
            $upload_dir = __DIR__ . '/uploads/cvs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // إنشاء اسم فريد للملف
            $file_ext = $allowed_types[$mime];
            $file_name = sprintf('%s_%s.%s',
                date('YmdHis'),
                bin2hex(random_bytes(8)),
                $file_ext
            );
            $target_path = $upload_dir . $file_name;

            // نقل الملف إلى المجلد المحدد
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $cv_path = 'uploads/cvs/' . $file_name;
            } else {
                throw new Exception('حدث خطأ أثناء تحميل الملف');
            }
        } else {
            throw new Exception('يجب تحميل ملف السيرة الذاتية');
        }

        // إضافة طلب التوظيف
        $stmt = $db->prepare("INSERT INTO job_applications 
                            (job_id, applicant_name, applicant_email, applicant_phone, cv_path, cover_letter, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("isssss", $job_id, $applicant_name, $applicant_email, $applicant_phone, $cv_path, $cover_letter);
        
        if ($stmt->execute()) {
            // إرسال إشعار بالبريد الإلكتروني
        //    send_job_application_notification($job_id, $applicant_name, $applicant_email);           
            $_SESSION['success'] = "تم تقديم طلب التوظيف بنجاح، شكرًا لك";
            header("Location: jobs.php");
            exit();
        } else {
            throw new Exception('حدث خطأ أثناء تقديم الطلب: ' . $db->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST;
        // header("Location: " . BASE_PATH . "apply.php?job_id=" . $job_id);
        header("Location: apply.php?job_id=".$job_id );
        exit();
    }
    
}

// إنشاء CSRF Token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
include __DIR__ . '/includes/header.php';
?>
<?php
// ... [الكود PHP السابق يبقى كما هو بدون تغيير] ...
?>

<style>
:root {
    --primary-color: #3498db;
    --secondary-color: #2c3e50;
    --accent-color: #e74c3c;
    --light-bg: #f8f9fa;
    --dark-text: #2c3e50;
    --light-text: #7f8c8d;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --border-radius: 8px;
    --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: var(--dark-text);
    background-color: #f5f7fa;
}

.apply-page {
    min-height: 100vh;
    padding: 2rem 0;
}

.card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.card-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-bottom: none;
    padding: 1.5rem;
}

.card-header h2 {
    font-weight: 700;
    margin-bottom: 0;
    color: white;
}

.job-info {
    background-color: white;
    border-left: 4px solid var(--primary-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.job-info h3 {
    font-weight: 600;
    color: var(--secondary-color);
    margin-bottom: 1rem;
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.8em;
    border-radius: 20px;
    color: white !important;
}

.badge.bg-primary {
    background-color: var(--primary-color) !important;
}

.badge.bg-info {
    background-color: #17a2b8 !important;
}

.badge.bg-warning {
    background-color: var(--warning-color) !important;
}

.form-label {
    font-weight: 600;
    color: var(--secondary-color);
    margin-bottom: 0.5rem;
}

.form-control {
    border: 1px solid #ddd;
    border-radius: var(--border-radius);
    padding: 0.75rem 1rem;
    transition: all 0.3s;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
}

.btn-primary {
    background-color: var(--primary-color);
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s;
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    transform: translateY(-2px);
}

.btn-outline-secondary {
    border-color: var(--secondary-color);
    color: var(--secondary-color);
    transition: all 0.3s;
}

.btn-outline-secondary:hover {
    background-color: var(--secondary-color);
    color: white;
}

.invalid-feedback {
    color: var(--accent-color);
    font-size: 0.85rem;
}

.form-text {
    color: var(--light-text);
    font-size: 0.85rem;
}

.text-primary {
    color: var(--primary-color) !important;
}

.text-success {
    color: var(--success-color) !important;
}

.text-warning {
    color: var(--warning-color) !important;
}

.bg-primary {
    background-color: var(--primary-color) !important;
}

/* تصميم مخصص لزر تحميل الملفات */
.file-upload-wrapper {
    position: relative;
    margin-bottom: 1rem;
}

.file-upload-input {
    width: 0.1px;
    height: 0.1px;
    opacity: 0;
    overflow: hidden;
    position: absolute;
    z-index: -1;
}

.file-upload-label {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    background-color: var(--primary-color);
    color: white;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 500;
    text-align: center;
    border: none;
    width: 100%;
}

.file-upload-label:hover {
    background-color: var(--secondary-color);
    transform: translateY(-2px);
}

.file-upload-label i {
    margin-right: 8px;
}

.file-name {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: var(--light-text);
}

@media (max-width: 768px) {
    .card-header {
        padding: 1rem;
    }
    
    .job-info {
        padding: 1rem;
    }
    
    .file-upload-label {
        padding: 0.6rem 1rem;
    }
}
</style>

<div class="apply-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header text-white">
                        <h2 class="h5 mb-0">التقديم على وظيفة</h2>
                    </div>
                    <div class="card-body p-4">
                        <?php displayFlashMessages(); ?>
                        
                        <!-- معلومات الوظيفة -->
                        <div class="job-info mb-5 p-4">
                            <h3 class="h4"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($job['department']); ?>
                                </span>
                                <span class="badge bg-info">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo $job['applicants_count']; ?> متقدم
                                </span>
                                <span class="badge bg-warning">
                                    <i class="fas fa-clock me-1"></i>
                                    ينتهي في <?php echo format_date($job['closing_date']); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($job['salary_range'])): ?>
                                <div class="mb-3">
                                    <strong>نطاق الراتب:</strong>
                                    <span class="text-success fw-bold"><?php echo htmlspecialchars($job['salary_range']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <strong>وصف الوظيفة:</strong>
                                <p class="mt-2"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                            </div>
                            
                            <div>
                                <strong>المتطلبات:</strong>
                                <ul class="mt-2 mb-0 ps-3">
                                    <?php 
                                    $requirements = explode("\n", $job['requirements']);
                                    foreach (array_filter($requirements) as $req): 
                                    ?>
                                        <li class="mb-1"><?php echo htmlspecialchars(trim($req)); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- نموذج التقديم -->
                        <form id="applicationForm" method="post" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                            
                            <h3 class="h5 mb-4 text-primary">معلومات المتقدم</h3>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="applicant_name" class="form-label">الاسم الكامل *</label>
                                    <input type="text" class="form-control" id="applicant_name" name="applicant_name" 
                                           value="<?php echo isset($_SESSION['form_data']['applicant_name']) ? htmlspecialchars($_SESSION['form_data']['applicant_name']) : ''; ?>"
                                           required minlength="3">
                                    <div class="invalid-feedback">الرجاء إدخال اسم صحيح (3 أحرف على الأقل)</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="applicant_email" class="form-label">البريد الإلكتروني *</label>
                                    <input type="email" class="form-control" id="applicant_email" name="applicant_email" 
                                           value="<?php echo isset($_SESSION['form_data']['applicant_email']) ? htmlspecialchars($_SESSION['form_data']['applicant_email']) : ''; ?>"
                                           required>
                                    <div class="invalid-feedback">الرجاء إدخال بريد إلكتروني صحيح</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="applicant_phone" class="form-label">رقم الهاتف *</label>
                                    <input type="tel" class="form-control" id="applicant_phone" name="applicant_phone" 
                                           value="<?php echo isset($_SESSION['form_data']['applicant_phone']) ? htmlspecialchars($_SESSION['form_data']['applicant_phone']) : ''; ?>"
                                           required pattern="[0-9]{9,9}">
                                    <div class="invalid-feedback">الرجاء إدخال رقم هاتف صحيح (9 رقما)</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="cv" class="form-label">السيرة الذاتية (PDF أو Word) *</label>
                                    <div class="file-upload-wrapper">
                                        <input type="file" id="cv" name="cv" class="file-upload-input" 
                                               accept=".pdf,.doc,.docx" required>
                                        <label for="cv" class="file-upload-label">
                                            <i class="fas fa-cloud-upload-alt"></i> اختر ملف السيرة الذاتية
                                        </label>
                                        <span id="file-name" class="file-name">لم يتم اختيار ملف</span>
                                        <div class="invalid-feedback">الرجاء تحميل ملف السيرة الذاتية</div>
                                    </div>
                                    <div class="form-text">الحجم الأقصى 2MB</div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="cover_letter" class="form-label">خطاب التغطية (اختياري)</label>
                                    <textarea class="form-control" id="cover_letter" name="cover_letter" rows="5"
                                              placeholder="اخبرنا عن نفسك ولماذا أنت مناسب لهذه الوظيفة..."><?php echo isset($_SESSION['form_data']['cover_letter']) ? htmlspecialchars($_SESSION['form_data']['cover_letter']) : ''; ?></textarea>
                                    <div class="form-text">يمكنك كتابة رسالة توضح فيها خبراتك ومهاراتك</div>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <button type="submit" name="submit_application" class="btn btn-primary w-100 py-3">
                                        <i class="fas fa-paper-plane me-2"></i> تقديم الطلب
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="jobs.php" class="btn btn-outline-secondary px-4">
                        <i class="fas fa-arrow-left me-2"></i> العودة إلى قائمة الوظائف
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
unset($_SESSION['form_data']);
include __DIR__ . '/includes/footer.php'; 
?>

<script>
// التحقق من صحة النموذج قبل التقديم
(function() {
    'use strict';
    const form = document.getElementById('applicationForm');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // التحقق من حجم الملف
        const fileInput = document.getElementById('cv');
        const maxSize = 2 * 1024 * 1024; // 2MB
        
        if (fileInput.files.length > 0 && fileInput.files[0].size > maxSize) {
            event.preventDefault();
            fileInput.classList.add('is-invalid');
            document.querySelector('.invalid-feedback').style.display = 'block';
            return false;
        }
        
        form.classList.add('was-validated');
    }, false);
})();

// عرض اسم الملف المختار
document.getElementById('cv').addEventListener('change', function(e) {
    const fileName = e.target.files.length ? e.target.files[0].name : 'لم يتم اختيار ملف';
    document.getElementById('file-name').textContent = fileName;
    
    // التحقق من حجم الملف
    const maxSize = 2 * 1024 * 1024; // 2MB
    if (e.target.files.length > 0 && e.target.files[0].size > maxSize) {
        document.querySelector('.invalid-feedback').style.display = 'block';
    } else {
        document.querySelector('.invalid-feedback').style.display = 'none';
    }
});
</script>