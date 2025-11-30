<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$auth = new Auth();
$pageTitle = "الرئيسية | نظام حجز المستشفيات";
$currentPage = 'home';

// جلب إحصائيات النظام لعرضها
$stats = [
    'doctors' => 0,
    'appointments' => 0,
    'patients' => 0
];

try {
    $conn = $db->getConnection();
    
    // إحصائيات الأطباء النشطين
    $result = $db->query("SELECT COUNT(*) FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE u.is_active = 1");
    $stats['doctors'] = $result->fetch_row()[0];
    
    // إحصائيات المواعيد اليوم
    $result = $db->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
    $stats['appointments'] = $result->fetch_row()[0];
    
    // إحصائيات المرضى المسجلين
    $result = $db->query("SELECT COUNT(*) FROM patients");
    $stats['patients'] = $result->fetch_row()[0];
    
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<div class="home-page">
    <!-- قسم الهيرو -->
    <section class="hero-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">نظام إدارة الحجوزات الطبية</h1>
                    <p class="lead mb-4">نقدم لكم حلولاً متكاملة لإدارة المواعيد الطبية بكل سهولة وكفاءة</p>
                    <div class="d-flex gap-3">
                        <?php if (!$auth->isLoggedIn()): ?>
                            <a href="register.php" class="btn btn-primary btn-lg px-4">إنشاء حساب جديد</a>
                            <a href="#features" class="btn btn-outline-secondary btn-lg px-4">استكشف الخدمات</a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($_SESSION['redirect'] ?? 'dashboard.php') ?>" 
                               class="btn btn-primary btn-lg px-4">
                               الانتقال إلى لوحة التحكم
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="profiles/system_img.png" alt="صورة نظام الحجز" class="img-fluid">
                </div>
            </div>
        </div>
    </section>
 
    <!-- قسم الإحصائيات -->
    <section class="stats-section py-5">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="stat-card p-4 rounded-3 shadow-sm">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-user-md fa-3x text-primary"></i>
                        </div>
                        <h3 class="stat-number display-5 fw-bold"><?= $stats['doctors'] ?></h3>
                        <p class="stat-label text-muted">طبيب متخصص</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card p-4 rounded-3 shadow-sm">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-calendar-check fa-3x text-success"></i>
                        </div>
                        <h3 class="stat-number display-5 fw-bold"><?= $stats['appointments'] ?></h3>
                        <p class="stat-label text-muted">موعد اليوم</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card p-4 rounded-3 shadow-sm">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-users fa-3x text-info"></i>
                        </div>
                        <h3 class="stat-number display-5 fw-bold"><?= $stats['patients'] ?></h3>
                        <p class="stat-label text-muted">مريض مسجل</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- قسم المميزات -->
    <section id="features" class="features-section py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title fw-bold">مميزات النظام</h2>
                <p class="section-subtitle text-muted">نقدم لكم أفضل الحلول لإدارة العيادات الطبية</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 h-100 rounded-3 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-calendar-check fa-2x text-primary"></i>
                        </div>
                        <h3 class="feature-title h4">حجز مواعيد أونلاين</h3>
                        <p class="feature-desc text-muted">
                            احجز موعدك مع الطبيب في أي وقت ومن أي مكان خلال دقائق معدودة
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 h-100 rounded-3 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-file-medical fa-2x text-success"></i>
                        </div>
                        <h3 class="feature-title h4">السجل الطبي الإلكتروني</h3>
                        <p class="feature-desc text-muted">
                            جميع سجلاتك الطبية في مكان واحد وآمن ويمكن الوصول إليه بسهولة
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 h-100 rounded-3 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-bell fa-2x text-warning"></i>
                        </div>
                        <h3 class="feature-title h4">تذكيرات المواعيد</h3>
                        <p class="feature-desc text-muted">
                            نظام تذكير آلي بالمواعيد عبر الرسائل النصية والبريد الإلكتروني
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 h-100 rounded-3 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-chart-line fa-2x text-info"></i>
                        </div>
                        <h3 class="feature-title h4">تقارير وإحصائيات</h3>
                        <p class="feature-desc text-muted">
                            لوحات تحكم متكاملة مع تقارير وإحصائيات مفصلة عن الأداء
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 h-100 rounded-3 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-mobile-alt fa-2x text-danger"></i>
                        </div>
                        <h3 class="feature-title h4">واجهة متجاوبة</h3>
                        <p class="feature-desc text-muted">
                            نظام يعمل بكفاءة على جميع الأجهزة من هواتف وحواسيب
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 h-100 rounded-3 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-shield-alt fa-2x text-dark"></i>
                        </div>
                        <h3 class="feature-title h4">أمان وحماية</h3>
                        <p class="feature-desc text-muted">
                            نظام آمن مع تشفير البيانات وحماية خصوصية المرضى
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// تفعيل التحقق من النموذج
(function () {
    'use strict'
    
    // استعلام عن جميع النماذج التي نريد تطبيق التحقق عليها
    var forms = document.querySelectorAll('.needs-validation')
    
    // التكرار عليها ومنع الإرسال
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>