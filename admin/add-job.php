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

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $closing_date = trim($_POST['closing_date'] ?? '');

    // تحقق من الحقول المطلوبة
    if (!$title || !$department || !$description || !$requirements || !$closing_date) {
        $error = "جميع الحقول مطلوبة.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO jobs 
                (title, department, description, requirements, posted_date, closing_date) 
                VALUES (?, ?, ?, ?, CURDATE(), ?)");
            $stmt->bind_param("sssss", $title, $department, $description, $requirements, $closing_date);
            if ($stmt->execute()) {
                $_SESSION['success'] = "تم إضافة الوظيفة بنجاح";
                header("Location: " . BASE_PATH . "/admin/jobs.php");
                exit();
            } else {
                $error = "حدث خطأ أثناء إضافة الوظيفة.";
            }
        } catch (Exception $e) {
            $error = "حدث خطأ أثناء إضافة الوظيفة: " . $e->getMessage();
        }
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
        
        <?php if (isset($error) && $error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">المسمى الوظيفي</label>
                            <input type="text" class="form-control" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">القسم</label>
                            <select class="form-select" name="department" required>
                                <option value="">اختر القسم</option>
                                <option value="الطب" <?php if(($_POST['department'] ?? '') == 'الطب') echo 'selected'; ?>>الطب</option>
                                <option value="التمريض" <?php if(($_POST['department'] ?? '') == 'التمريض') echo 'selected'; ?>>التمريض</option>
                                <option value="الإدارة" <?php if(($_POST['department'] ?? '') == 'الإدارة') echo 'selected'; ?>>الإدارة</option>
                                <option value="المختبر" <?php if(($_POST['department'] ?? '') == 'المختبر') echo 'selected'; ?>>المختبر</option>
                                <option value="الأشعة" <?php if(($_POST['department'] ?? '') == 'الأشعة') echo 'selected'; ?>>الأشعة</option>
                                <option value="الصيدلية" <?php if(($_POST['department'] ?? '') == 'الصيدلية') echo 'selected'; ?>>الصيدلية</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">وصف الوظيفة</label>
                            <textarea class="form-control" name="description" rows="5" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">المتطلبات</label>
                            <textarea class="form-control" name="requirements" rows="5" required><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">آخر موعد للتقديم</label>
                            <input type="date" class="form-control" name="closing_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required value="<?php echo htmlspecialchars($_POST['closing_date'] ?? ''); ?>">
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