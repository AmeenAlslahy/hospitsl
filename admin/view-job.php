<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$auth = new Auth();
$db = new Database();
// بدء الجلسة إذا لم تبدأ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من الصلاحيات
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    $_SESSION['error'] = "ليس لديك صلاحية الوصول إلى هذه الصفحة";
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

// جلب معرف الوظيفة مع التحقق الشديد
$job_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => [
        'min_range' => 1,
        'default' => 0
    ]
]);

if ($job_id === 0) {
    $_SESSION['error'] = "معرف الوظيفة غير صالح";
    header("Location: " . BASE_PATH . "/admin/jobs.php");
    exit();
}

try {
    // جلب بيانات الوظيفة
    $stmt = $db->prepare(
        "SELECT j.*, 
         j.title as department_name,
         (SELECT COUNT(*) FROM job_applications WHERE job_id = j.job_id) as applications_count
         FROM jobs j
         WHERE j.job_id = ?"
    );
    $stmt->bind_param('i',$job_id);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    
    if (!$job) {
        $_SESSION['error'] = "الوظيفة غير موجودة";
        header("Location: " . BASE_PATH . "/admin/jobs.php");
        exit();
    }
    
    // جلب المتقدمين (إذا لزم الأمر)
    $applications = [];
    if ($job['applications_count'] > 0) {
        $stmt = $db->prepare(
            "SELECT a.*
             FROM job_applications a
             
             WHERE a.job_id = ?"  );
        $stmt->bind_param('i',$job_id);
        $stmt->execute();
        $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
} catch (Exception $e) {
    error_log("خطأ في عرض الوظيفة: " . $e->getMessage());
    $_SESSION['error'] ="حدث خطأ تقني. الرجاء المحاولة لاحقاً".$e->getMessage();
    header("Location: " . BASE_PATH . "/admin/jobs.php");
    exit();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="view-job py-4">
    <div class="container">
        <?php displayFlashMessages(); ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-1">
                    <i class="fas fa-briefcase text-primary me-2"></i>
                    <?php echo htmlspecialchars($job['title']); ?>
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/admin/dashboard.php">لوحة التحكم</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/admin/jobs.php">الوظائف</a></li>
                        <li class="breadcrumb-item active" aria-current="page">تفاصيل الوظيفة</li>
                    </ol>
                </nav>
            </div>
            <a href="<?= BASE_PATH ?>/admin/jobs.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>عودة
            </a>
        </div>
        
        <div class="row g-4">
            <!-- قسم تفاصيل الوظيفة -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <span class="badge bg-<?= $job['status'] === 'open' ? 'success' : 'secondary' ?>">
                                <?= $job['status'] === 'open' ? 'مفتوحة' : 'مغلقة' ?>
                            </span>
                        </div>
                        <div class="text-muted">
                            <i class="fas fa-building me-1"></i>
                            <?php echo htmlspecialchars($job['department']); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="job-meta d-flex flex-wrap gap-3 mb-4 p-3 bg-light rounded">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                <span>تاريخ النشر: <?php echo arabicDate($job['posted_date']); ?></span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <span>آخر موعد: <?php echo arabicDate($job['closing_date']); ?></span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users text-primary me-2"></i>
                                <span>عدد المتقدمين: <?php echo count($applications); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">
                                <i class="fas fa-file-alt me-2"></i>وصف الوظيفة
                            </h5>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">
                                <i class="fas fa-clipboard-check me-2"></i>المتطلبات
                            </h5>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="<?= BASE_PATH ?>/admin/edit-job.php?id=<?= $job['job_id'] ?>" 
                               class="btn btn-warning px-4">
                               <i class="fas fa-edit me-2"></i>تعديل
                            </a>
                            <form method="POST" action="<?= BASE_PATH ?>/admin/delete-job.php" class="d-inline">
                                <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>">
                                <button type="submit" class="btn btn-danger px-4"
                                        onclick="return confirm('هل أنت متأكد من حذف هذه الوظيفة؟ سيتم حذف جميع الطلبات المرتبطة بها')">
                                    <i class="fas fa-trash me-2"></i>حذف
                                </button>
                            </form>
                            <?php if(count($applications) > 0): ?>
                            <a href="#applications" class="btn btn-primary px-4">
                                <i class="fas fa-users me-2"></i>عرض المتقدمين
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- قسم المعلومات الجانبية -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات سريعة</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-map-marker-alt me-2 text-muted"></i>المكان</span>
                                <span><?= htmlspecialchars($job['location'] ?? 'غير محدد') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-briefcase me-2 text-muted"></i>نوع الوظيفة</span>
                                <span><?= htmlspecialchars($job['job_type'] ?? 'غير محدد') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-money-bill-wave me-2 text-muted"></i>الراتب</span>
                                <span><?= htmlspecialchars($job['salary'] ?? 'غير محدد') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-user-tie me-2 text-muted"></i>الخبرة المطلوبة</span>
                                <span><?= htmlspecialchars($job['experience_needed'] ?? 'غير محدد') ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <?php if(count($applications) > 0): ?>
                <div class="card border-0 shadow-sm mt-4" id="applications">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>أحدث المتقدمين</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach( $applications as $app): ?>
                            <a href="<?= BASE_PATH ?>/admin/view-application.php?id=<?= $app['application_id'] ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($app['applicant_name']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($app['applicant_email']) ?></small>
                                </div>
                                <span class="badge bg-light text-dark">
                                    <?= arabicDate($app['applied_at']) ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                            <?php if(count($applications) > 5): ?>
                            <a href="<?= BASE_PATH ?>/admin/job-applications.php?job_id=<?= $job['job_id'] ?>" 
                               class="list-group-item list-group-item-action text-center text-primary">
                                عرض جميع المتقدمين (<?= count($applications) ?>)
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>