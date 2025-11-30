<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$auth = new Auth();
// التحقق من صلاحيات المدير
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

// معالجة إضافة وظيفة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $department = $_POST['department'] ?? '';
    $description = $_POST['description'] ?? '';
    $requirements = $_POST['requirements'] ?? '';
    $closing_date = $_POST['closing_date'] ?? '';
    
    try {
        $stmt = $db->prepare("INSERT INTO jobs 
                             (title, department, description, requirements, posted_date, closing_date) 
                             VALUES (?, ?, ?, ?, CURDATE(), ?)");
        $stmt->execute([$title, $department, $description, $requirements, $closing_date]);
        
        $_SESSION['success'] = "تم إضافة الوظيفة بنجاح";
        header("Location: " . BASE_PATH . "/admin/jobs.php");
        exit();
        
    } catch (PDOException $e) {
        $error = "حدث خطأ أثناء إضافة الوظيفة: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="add-job py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>إضافة وظيفة جديدة</h1>
            <a href="<?php echo BASE_PATH; ?>/admin/jobs.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>عودة للقائمة
            </a>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">المسمى الوظيفي</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">القسم</label>
                            <select class="form-select" name="department" required>
                                <option value="">اختر القسم</option>
                                <option value="الطب">الطب</option>
                                <option value="التمريض">التمريض</option>
                                <option value="الإدارة">الإدارة</option>
                                <option value="المختبر">المختبر</option>
                                <option value="الأشعة">الأشعة</option>
                                <option value="الصيدلية">الصيدلية</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">وصف الوظيفة</label>
                            <textarea class="form-control" name="description" rows="5" required></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">المتطلبات</label>
                            <textarea class="form-control" name="requirements" rows="5" required></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">آخر موعد للتقديم</label>
                            <input type="date" class="form-control" name="closing_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>حفظ الوظيفة
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>