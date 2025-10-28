<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    header("Location: login.php");
    exit();
}

// $db = new Database();
$pageTitle = "إدارة طلبات التوظيف";
$currentPage = 'job-applications';
include __DIR__ . '/../includes/header.php';

// معالجة تغيير حالة الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
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

// معالجة حذف الطلب
if (isset($_GET['delete'])) {
    try {
        $application_id = intval($_GET['delete']);
        
        // جلب مسار السيرة الذاتية لحذف الملف
        $stmt = $db->prepare("SELECT cv_path FROM job_applications WHERE application_id = ?");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        
        if ($application) {
            // حذف ملف السيرة الذاتية إذا كان موجوداً
            if (!empty($application['cv_path']) && file_exists(__DIR__ . '/' . $application['cv_path'])) {
                unlink(__DIR__ . '/' . $application['cv_path']);
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
    
    header("Location: job-applications.php");
    exit();
}

// إنشاء CSRF Token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// جلب طلبات التوظيف مع فلترة
$status_filter = isset($_GET['status']) ? $db->escape($_GET['status']) : '';
$job_filter = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$search_query = isset($_GET['search']) ? $db->escape($_GET['search']) : '';

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
    $applications = $db->query($sql);
} catch (Exception $e) {
    error_log("Error fetching job applications: " . $e->getMessage());
    $applications = [];
}

// جلب الوظائف للفلترة
try {
    $jobs = $db->query("SELECT job_id, title FROM jobs ORDER BY title");
} catch (Exception $e) {
    error_log("Error fetching jobs: " . $e->getMessage());
    $jobs = [];
}
?>

<div class="job-applications-page">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-user-tie me-2"></i>إدارة طلبات التوظيف
            </h1>
            
            <div class="d-flex gap-2">
                <a href="jobs.php" class="btn btn-outline-primary">
                    <i class="fas fa-briefcase me-2"></i>الوظائف الشاغرة
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-file-export me-2"></i>تصدير البيانات
                </button>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- فلترة الطلبات -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">حالة الطلب</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">جميع الحالات</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>قيد المراجعة</option>
                            <option value="reviewed" <?php echo $status_filter === 'reviewed' ? 'selected' : ''; ?>>تمت المراجعة</option>
                            <option value="interviewed" <?php echo $status_filter === 'interviewed' ? 'selected' : ''; ?>>تمت المقابلة</option>
                            <option value="hired" <?php echo $status_filter === 'hired' ? 'selected' : ''; ?>>تم التوظيف</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>مرفوض</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="job_id" class="form-label">الوظيفة</label>
                        <select class="form-select" id="job_id" name="job_id">
                            <option value="0">جميع الوظائف</option>
                            <?php if ($jobs && $jobs->num_rows > 0): ?>
                                <?php while ($job = $jobs->fetch_assoc()): ?>
                                    <option value="<?php echo $job['job_id']; ?>" 
                                        <?php echo $job_filter == $job['job_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="search" class="form-label">بحث</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="ابحث باسم المتقدم أو البريد أو الهاتف..."
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i> تصفية
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- جدول طلبات التوظيف -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>المتقدم</th>
                                <th>الوظيفة</th>
                                <th>البريد الإلكتروني</th>
                                <th>الهاتف</th>
                                <th>تاريخ التقديم</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($applications && $applications->num_rows > 0): ?>
                                <?php while ($app = $applications->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $app['application_id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-2">
                                                    <i class="fas fa-user-circle fa-lg text-muted"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div><?php echo htmlspecialchars($app['applicant_name']); ?></div>
                                                    <small class="text-muted">
                                                        <?php echo timeAgo($app['applied_at']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                        <td><?php echo htmlspecialchars($app['applicant_email']); ?></td>
                                        <td><?php echo htmlspecialchars($app['applicant_phone']); ?></td>
                                        <td><?php echo formatDate($app['applied_at']); ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'reviewed' => 'primary',
                                                'interviewed' => 'info',
                                                'hired' => 'success',
                                                'rejected' => 'danger'
                                            ][$app['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php 
                                                echo [
                                                    'pending' => 'قيد المراجعة',
                                                    'reviewed' => 'تمت المراجعة',
                                                    'interviewed' => 'تمت المقابلة',
                                                    'hired' => 'تم التوظيف',
                                                    'rejected' => 'مرفوض'
                                                ][$app['status']] ?? $app['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="<?= htmlspecialchars(BASE_PATH) ?>/admin/view-application.php?id=<?= htmlspecialchars($app['application_id']) ?>" 
                                               class="btn btn-sm btn-info" title="عرض">
                                               <i class="fas fa-eye"></i>
                                            </a>

                                                
                                                <button class="btn btn-sm btn-outline-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#statusModal"
                                                        data-app-id="<?php echo $app['application_id']; ?>"
                                                        data-app-status="<?php echo $app['status']; ?>"
                                                        data-app-notes="<?php echo htmlspecialchars($app['notes'] ?? ''); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <a href="job-applications.php?delete=<?php echo $app['application_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('هل أنت متأكد من حذف هذا الطلب؟');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">لا توجد طلبات توظيف</h5>
                                        <p class="text-muted">لم يتم العثور على طلبات توظيف تطابق معايير البحث</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal لعرض تفاصيل الطلب -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewModalLabel">تفاصيل طلب التوظيف</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">المتقدم:</h6>
                        <h5 id="viewAppName"></h5>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">الوظيفة:</h6>
                        <h5 id="viewJobTitle"></h5>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">البريد الإلكتروني:</h6>
                        <p id="viewAppEmail"></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">رقم الهاتف:</h6>
                        <p id="viewAppPhone"></p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">تاريخ التقديم:</h6>
                        <p id="viewAppDate"></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">الحالة:</h6>
                        <p><span id="viewAppStatus" class="badge"></span></p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h6 class="text-muted">السيرة الذاتية:</h6>
                    <a id="viewAppCv" href="#" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-download me-2"></i> تحميل السيرة الذاتية
                    </a>
                </div>
                
                <div class="mb-4">
                    <h6 class="text-muted">خطاب التغطية:</h6>
                    <div id="viewAppCover" class="bg-light p-3 rounded"></div>
                </div>
                
                <div>
                    <h6 class="text-muted">ملاحظات:</h6>
                    <div id="viewAppNotes" class="bg-light p-3 rounded"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal لتحديث حالة الطلب -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="statusModalLabel">تحديث حالة الطلب</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="application_id" id="statusAppId">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">الحالة الجديدة</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">قيد المراجعة</option>
                            <option value="reviewed">تمت المراجعة</option>
                            <option value="interviewed">تمت المقابلة</option>
                            <option value="hired">تم التوظيف</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="update_status" class="btn btn-success">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal لتصدير البيانات -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="exportModalLabel">تصدير طلبات التوظيف</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="export-job-applications.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="mb-3">
                        <label for="export_status" class="form-label">حالة الطلب</label>
                        <select class="form-select" id="export_status" name="status">
                            <option value="">جميع الحالات</option>
                            <option value="pending">قيد المراجعة</option>
                            <option value="reviewed">تمت المراجعة</option>
                            <option value="interviewed">تمت المقابلة</option>
                            <option value="hired">تم التوظيف</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="export_job_id" class="form-label">الوظيفة</label>
                        <select class="form-select" id="export_job_id" name="job_id">
                            <option value="0">جميع الوظائف</option>
                            <?php if ($jobs && $jobs->num_rows > 0): ?>
                                <?php while ($job = $jobs->fetch_assoc()): ?>
                                    <option value="<?php echo $job['job_id']; ?>">
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="export_format" class="form-label">صيغة الملف</label>
                        <select class="form-select" id="export_format" name="format">
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="pdf">PDF (.pdf)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تصدير البيانات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// تفعيل modal عرض التفاصيل
document.getElementById('viewModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const modal = this;
    
    modal.querySelector('#viewAppName').textContent = button.getAttribute('data-app-name');
    modal.querySelector('#viewJobTitle').textContent = button.getAttribute('data-job-title');
    modal.querySelector('#viewAppEmail').textContent = button.getAttribute('data-app-email');
    modal.querySelector('#viewAppPhone').textContent = button.getAttribute('data-app-phone');
    modal.querySelector('#viewAppDate').textContent = button.getAttribute('data-app-date');
    
    const status = button.getAttribute('data-app-status');
    const statusText = {
        'pending': 'قيد المراجعة',
        'reviewed': 'تمت المراجعة',
        'interviewed': 'تمت المقابلة',
        'hired': 'تم التوظيف',
        'rejected': 'مرفوض'
    }[status] || status;
    
    const statusClass = {
        'pending': 'bg-warning',
        'reviewed': 'bg-primary',
        'interviewed': 'bg-info',
        'hired': 'bg-success',
        'rejected': 'bg-danger'
    }[status] || 'bg-secondary';
    
    const statusBadge = modal.querySelector('#viewAppStatus');
    statusBadge.textContent = statusText;
    statusBadge.className = 'badge ' + statusClass;
    
    modal.querySelector('#viewAppCv').href = button.getAttribute('data-app-cv');
    modal.querySelector('#viewAppCover').textContent = button.getAttribute('data-app-cover') || 'لا يوجد خطاب تغطية';
    modal.querySelector('#viewAppNotes').textContent = button.getAttribute('data-app-notes') || 'لا توجد ملاحظات';
});

// تفعيل modal تحديث الحالة
document.getElementById('statusModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const modal = this;
    
    modal.querySelector('#statusAppId').value = button.getAttribute('data-app-id');
    modal.querySelector('#status').value = button.getAttribute('data-app-status');
    modal.querySelector('#notes').value = button.getAttribute('data-app-notes') || '';
});
</script>