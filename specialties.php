<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';

// إنشاء الكائنات الأساسية
$auth = new Auth();
// $db = new Database();

// التحقق من تسجيل الدخول (اختياري حسب متطلباتك)
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// جلب التخصصات من قاعدة البيانات
try {
    $stmt = $db->query("SELECT * FROM specialties");
    
    // استبدال fetchAll() بالطريقة الصحيحة لـ MySQLi
    $specialties = [];
    while ($row = $stmt->fetch_assoc()) {
        $specialties[] = $row;
    }
} catch (Exception $e) {
    // معالجة الأخطاء بشكل أفضل
    die("<div class='alert alert-danger container mt-5'>خطأ في جلب البيانات: " . htmlspecialchars($e->getMessage()) . "</div>");
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="specialties-page py-5">
    <div class="container">
        <h1 class="text-center mb-5">التخصصات الطبية</h1>
        
        <?php if (empty($specialties)): ?>
            <div class="alert alert-info text-center">لا توجد تخصصات مسجلة حالياً</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($specialties as $specialty): ?>
                <div class="col-md-4">
                    <div class="card specialty-card h-100 border-0 shadow-sm">
                        <?php if (!empty($specialty['image'])): ?>
                            <img src="<?php echo htmlspecialchars(ASSETS_PATH . '/images/specialties/' . $specialty['image']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($specialty['name']); ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-clinic-medical fa-4x text-secondary"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h3 class="card-title"><?php echo htmlspecialchars($specialty['name']); ?></h3>
                            <p class="card-text"><?php echo htmlspecialchars($specialty['description']); ?></p>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <a href="<?php echo htmlspecialchars(BASE_PATH . '/doctors.php?specialty=' . $specialty['specialty_id']); ?>" 
                               class="btn btn-primary w-100">
                               <i class="fas fa-user-md me-2"></i>عرض الأطباء
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>