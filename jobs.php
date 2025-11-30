<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$auth = new Auth();
$pageTitle = "الوظائف الشاغرة";
$currentPage = 'jobs';
include __DIR__ . '/includes/header.php';

// معالجة معايير البحث
$search = isset($_GET['search']) ? trim($db->escape($_GET['search'])) : '';
$department = isset($_GET['department']) ? $db->escape($_GET['department']) : '';

// بناء استعلام SQL مع فلترة
$sql = "SELECT j.*, COUNT(ja.application_id) as applicants_count 
        FROM jobs j
        LEFT JOIN job_applications ja ON j.job_id = ja.job_id
        WHERE j.status = 'open' AND j.closing_date >= CURDATE() ";

if (!empty($search)) {
    $sql .= "AND (j.title LIKE '%$search%' OR j.description LIKE '%$search%' OR j.requirements LIKE '%$search%') ";
}

if (!empty($department)) {
    $sql .= "AND j.department = '$department' ";
}

$sql .= "GROUP BY j.job_id ORDER BY j.posted_date DESC";

// الحصول على الوظائف الشاغرة
try {
    $jobs = $db->query($sql);
} catch (Exception $e) {
    error_log("Error fetching jobs: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في جلب بيانات الوظائف";
    $jobs = [];
}
?>

<div class="jobs-page">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold mb-3">الوظائف الشاغرة</h1>
            <p class="lead text-muted">انضم إلى فريق عملنا المتميز</p>
        </div>

        <?php displayFlashMessages(); ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <!-- فلترة الوظائف -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="ابحث بالوظيفة أو الكلمة المفتاحية"
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="department">
                                    <option value="">جميع الأقسام</option>
                                    <?php
                                    $departments = $db->query("SELECT DISTINCT department FROM jobs WHERE status = 'open' ORDER BY department");
                                    if ($departments && $departments->num_rows > 0) {
                                        while ($dept = $departments->fetch_assoc()) {
                                            $selected = $department === $dept['department'] ? 'selected' : '';
                                            echo "<option value=\"" . htmlspecialchars($dept['department']) . "\" $selected>" . 
                                                 htmlspecialchars($dept['department']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i> تصفية
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- قائمة الوظائف -->
                <?php if ($jobs && $jobs->num_rows > 0): ?>
                    <div class="jobs-list">
                        <?php while ($job = $jobs->fetch_assoc()): ?>
                            <div class="card shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h3 class="h5 mb-1"><?php echo htmlspecialchars($job['title']); ?></h3>
                                            <div class="d-flex gap-2 mb-2">
                                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                                    <?php echo htmlspecialchars($job['department']); ?>
                                                </span>
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    <i class="fas fa-users me-1"></i>
                                                    <?php echo $job['applicants_count']; ?> متقدم
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-muted small">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo format_date($job['posted_date']); ?>
                                            </div>
                                            <div class="text-danger small">
                                                <i class="fas fa-clock me-1"></i>
                                                ينتهي في <?php echo format_date($job['closing_date']); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <h4 class="h6 text-muted">وصف الوظيفة:</h4>
                                        <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                                    </div>

                                    <div class="mb-4">
                                        <h4 class="h6 text-muted">المتطلبات:</h4>
                                        <ul class="list-unstyled">
                                            <?php 
                                            $requirements = explode("\n", $job['requirements']);
                                            foreach (array_filter($requirements) as $req): 
                                            ?>
                                                <li class="mb-1">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    <?php echo htmlspecialchars(trim($req)); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if (!empty($job['salary_range'])): ?>
                                            <div class="text-success fw-bold">
                                                <i class="fas fa-money-bill-wave me-2"></i>
                                                <?php echo htmlspecialchars($job['salary_range']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <a href="apply.php?job_id=<?php echo $job['job_id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i> التقدم للوظيفة
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-briefcase fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted">لا توجد وظائف شاغرة حالياً</h4>
                            <p class="text-muted">يمكنك متابعتنا لمعرفة أحدث الوظائف المتاحة</p>
                            <button class="btn btn-outline-primary" id="subscribeBtn">
                                <i class="fas fa-bell me-2"></i> اشترك لتصلك الإشعارات
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- نصائح للتقديم -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="h6 mb-0">
                            <i class="fas fa-lightbulb me-2"></i>نصائح للتقديم الناجح
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="tipsAccordion">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#tipOne">
                                        <span class="badge bg-primary bg-opacity-10 text-primary me-3">1</span>
                                        إعداد سيرة ذاتية مميزة
                                    </button>
                                </h2>
                                <div id="tipOne" class="accordion-collapse collapse" data-bs-parent="#tipsAccordion">
                                    <div class="accordion-body small">
                                        - ركز على الإنجازات والأرقام<br>
                                        - استخدم أفعال قوية مثل (طورت، قمت، أنشأت)<br>
                                        - احرص على أن تكون خالية من الأخطاء
                                    </div>
                                </div>
                            </div>
                            
                            <!-- باقي نصائح التقديم -->
                        </div>
                    </div>
                </div>

                <!-- إحصائيات الوظائف -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h3 class="h6 mb-0">
                            <i class="fas fa-chart-pie me-2"></i>إحصائيات الوظائف
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stats = $db->query("SELECT department, COUNT(*) as count 
                                               FROM jobs 
                                               WHERE status = 'open' AND closing_date >= CURDATE()
                                               GROUP BY department
                                               ORDER BY count DESC");
                        } catch (Exception $e) {
                            error_log("Error fetching job stats: " . $e->getMessage());
                            $stats = [];
                        }
                        ?>

                        <?php if ($stats && $stats->num_rows > 0): ?>
                            <div class="job-stats">
                                <?php 
                                $max_count = 0;
                                $stats_data = [];
                                while ($stat = $stats->fetch_assoc()) {
                                    $stats_data[] = $stat;
                                    if ($stat['count'] > $max_count) {
                                        $max_count = $stat['count'];
                                    }
                                }
                                
                                foreach ($stats_data as $stat): 
                                    $width = $max_count > 0 ? ($stat['count'] / $max_count) * 100 : 0;
                                ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1 small">
                                            <span><?php echo htmlspecialchars($stat['department']); ?></span>
                                            <span class="fw-bold"><?php echo $stat['count']; ?> وظيفة</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $width; ?>%" 
                                                 aria-valuenow="<?php echo $stat['count']; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="<?php echo $max_count; ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد إحصائيات متاحة حالياً
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- وظائف مميزة -->
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h3 class="h6 mb-0">
                            <i class="fas fa-star me-2"></i>وظائف مميزة
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $featured_jobs = $db->query("SELECT job_id, title, department 
                                                    FROM jobs 
                                                    WHERE status = 'open' AND closing_date >= CURDATE()
                                                    ORDER BY posted_date DESC
                                                    LIMIT 3");
                        ?>
                        
                        <?php if ($featured_jobs && $featured_jobs->num_rows > 0): ?>
                            <ul class="list-unstyled">
                                <?php while ($fjob = $featured_jobs->fetch_assoc()): ?>
                                    <li class="mb-2 pb-2 border-bottom">
                                        <a href="job-details.php?id=<?php echo $fjob['job_id']; ?>" class="text-decoration-none">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><?php echo htmlspecialchars($fjob['title']); ?></span>
                                                <span class="badge bg-warning bg-opacity-10 text-warning small"><?php echo htmlspecialchars($fjob['department']); ?></span>
                                            </div>
                                        </a>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                            <a href="jobs.php?filter=featured" class="btn btn-outline-warning btn-sm w-100 mt-2">
                                عرض جميع الوظائف المميزة
                            </a>
                        <?php else: ?>
                            <div class="text-center text-muted py-2">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد وظائف مميزة حالياً
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>