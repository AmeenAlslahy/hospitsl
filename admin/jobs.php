<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// التحقق من صلاحيات المدير بنفس طريقة إضافة الطبيب
$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    $_SESSION['error'] = "ليس لديك صلاحية الوصول إلى هذه الصفحة";
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

$db = new Database();

// معالجة حذف الوظيفة بنفس أسلوب معاملات قاعدة البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job'])) {
    try {
        $db->beginTransaction();
        
        $job_id = (int)$_POST['job_id'];
        
        // 1. حذف المتقدمين للوظيفة أولاً (إذا كان هناك جدول منفصل)
        $stmt = $db->preparedQuery("DELETE FROM job_applications WHERE job_id = ?", [$job_id]);
        
        // 2. حذف الوظيفة
        $stmt = $db->preparedQuery("DELETE FROM jobs WHERE job_id = ?", [$job_id]);
        
        $db->commit();
        
        $_SESSION['success'] = "تم حذف الوظيفة بنجاح";
        header("Location: " . BASE_PATH . "/admin/jobs.php");
        exit();
        
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error'] = "حدث خطأ أثناء حذف الوظيفة: " . $e->getMessage();
        error_log("Delete Job Error: " . $e->getMessage());
        header("Location: " . BASE_PATH . "/admin/jobs.php");
        exit();
    }
}

// جلب قائمة الوظائف بنفس أسلوب جلب البيانات
try {
    $stmt = $db->preparedQuery(
        "SELECT j.*, COUNT(a.application_id) as applications_count 
         FROM jobs j
         LEFT JOIN job_applications a ON j.job_id = a.job_id
         GROUP BY j.job_id
         ORDER BY j.posted_date DESC"
    );
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "حدث خطأ أثناء جلب بيانات الوظائف";
    error_log("Fetch Jobs Error: " . $e->getMessage());
    $jobs = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-jobs py-4">
    <div class="container">
        <!-- عرض رسائل النظام بنفس الأسلوب -->
        <?php displayFlashMessages(); ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-briefcase me-2"></i> إدارة الوظائف</h1>
            <a href="<?= BASE_PATH ?>/admin/add-job.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> إضافة وظيفة جديدة
            </a>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?php if (empty($jobs)): ?>
                    <div class="alert alert-info">لا توجد وظائف متاحة حالياً</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">المسمى الوظيفي</th>
                                <th width="15%">القسم</th>
                                <th width="15%">تاريخ النشر</th>
                                <th width="15%">تاريخ الإغلاق</th>
                                <th width="10%">المتقدمين</th>
                                <th width="10%">الحالة</th>
                                <th width="20%">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?= $job['job_id'] ?></td>
                                <td><?= htmlspecialchars($job['title']) ?></td>
                                <td><?= htmlspecialchars($job['department']) ?></td>
                                <td><?= arabicDate($job['posted_date']) ?></td>
                                <td><?= arabicDate($job['closing_date']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $job['applications_count'] > 0 ? 'primary' : 'secondary' ?>">
                                        <?= $job['applications_count'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($job['status'] === 'open' && strtotime($job['closing_date']) >= time()): ?>
                                        <span class="badge bg-success">مفتوحة</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">مغلقة</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= BASE_PATH ?>/admin/view-job.php?id=<?= $job['job_id'] ?>" 
                                           class="btn btn-info" title="عرض" data-bs-toggle="tooltip">
                                           <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_PATH ?>/admin/edit-job.php?id=<?= $job['job_id'] ?>" 
                                           class="btn btn-warning" title="تعديل" data-bs-toggle="tooltip">
                                           <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="<?= BASE_PATH ?>/admin/jobs.php" class="d-inline">
                                            <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>">
                                            <input type="hidden" name="delete_job" value="1">
                                            <button type="submit" class="btn btn-danger" 
                                                    title="حذف" data-bs-toggle="tooltip"
                                                    onclick="return confirmDelete()">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php if ($job['applications_count'] > 0): ?>
                                        <a href="<?= BASE_PATH ?>/admin/job-applications.php?job_id=<?= $job['job_id'] ?>" 
                                           class="btn btn-primary" title="المتقدمين" data-bs-toggle="tooltip">
                                           <i class="fas fa-users"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// تفعيل أدوات التلميح
$(document).ready(function(){
    $('[data-bs-toggle="tooltip"]').tooltip();
});

// تأكيد الحذف بنفس أسلوب إدارة الأطباء
function confirmDelete() {
    return confirm('هل أنت متأكد من حذف هذه الوظيفة؟ سيتم حذف جميع البيانات المرتبطة بها.');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';