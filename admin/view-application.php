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

$application_id = $_GET['id'] ?? 0;

// جلب بيانات الطلب
$stmt = $db->prepare("SELECT ja.*, j.title as job_title 
                     FROM job_applications ja
                     JOIN jobs j ON ja.job_id = j.job_id
                     WHERE ja.application_id = ?");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();

if (!$application) {
    header("Location: job-applications.php");
    exit();
}

$pageTitle = "تفاصيل طلب التوظيف";
include __DIR__ . '/../includes/header.php';
?>

<div class="application-view py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">تفاصيل طلب التوظيف</h1>
            <a href="job-applications.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> رجوع
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted">المتقدم:</h5>
                        <h4><?php echo htmlspecialchars($application['applicant_name']); ?></h4>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-muted">الوظيفة:</h5>
                        <h4><?php echo htmlspecialchars($application['job_title']); ?></h4>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted">البريد الإلكتروني:</h5>
                        <p><?php echo htmlspecialchars($application['applicant_email']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-muted">رقم الهاتف:</h5>
                        <p><?php echo htmlspecialchars($application['applicant_phone']); ?></p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted">تاريخ التقديم:</h5>
                        <p><?php echo formatDate($application['applied_at']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-muted">الحالة:</h5>
                        <span class="badge bg-<?php echo getApplicationStatusClass($application['status']); ?>">
                            <?php echo getApplicationStatusText($application['status']); ?>
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="text-muted">السيرة الذاتية:</h5>
                    <a href="<?php echo htmlspecialchars($application['cv_path']); ?>" 
                       class="btn btn-outline-primary" 
                       target="_blank">
                       <i class="fas fa-download me-2"></i> تحميل السيرة الذاتية
                    </a>
                </div>

                <div class="mb-4">
                    <h5 class="text-muted">خطاب التغطية:</h5>
                    <div class="bg-light p-3 rounded">
                        <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                    </div>
                </div>

                <div>
                    <h5 class="text-muted">ملاحظات:</h5>
                    <div class="bg-light p-3 rounded">
                        <?php echo !empty($application['notes']) ? nl2br(htmlspecialchars($application['notes'])) : 'لا توجد ملاحظات'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>