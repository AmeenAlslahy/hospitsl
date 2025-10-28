<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$db = new Database();
$patient_id = getPatientId($_SESSION['user_id'], $db);

// فلترة الفحوصات
$filter = $_GET['filter'] ?? 'pending'; // pending, completed

try {
    $query = "SELECT t.*, d.full_name as doctor_name, 
              DATE_FORMAT(t.request_date, '%Y-%m-%d') as request_date_formatted,
              DATE_FORMAT(t.due_date, '%Y-%m-%d') as due_date_formatted
              FROM patient_tests t
              JOIN doctors d ON t.doctor_id = d.doctor_id
              WHERE t.patient_id = ?";
    
    if ($filter === 'pending') {
        $query .= " AND t.status = 'pending'";
    } elseif ($filter === 'completed') {
        $query .= " AND t.status = 'completed'";
    }
    
    $query .= " ORDER BY t.due_date ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Tests error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في جلب الفحوصات";
}

$pageTitle = "الفحوصات المطلوبة";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>الفحوصات المطلوبة</h1>
        <div>
            <a href="<?php echo BASE_PATH; ?>/patient/dashboard.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>لوحة التحكم
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadTestModal">
                <i class="fas fa-upload me-2"></i>رفع نتيجة فحص
            </button>
        </div>
    </div>

    <!-- فلترة الفحوصات -->
    <div class="row mb-4">
        <div class="col">
            <div class="btn-group" role="group">
                <a href="?filter=pending" class="btn btn-outline-primary <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    المعلقة
                </a>
                <a href="?filter=completed" class="btn btn-outline-primary <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                    المكتملة
                </a>
            </div>
        </div>
    </div>

    <!-- قائمة الفحوصات -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($tests)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم الفحص</th>
                                <th>الطبيب</th>
                                <th>تاريخ الطلب</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tests as $test): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                    <td>د. <?php echo htmlspecialchars($test['doctor_name']); ?></td>
                                    <td><?php echo formatDate($test['request_date_formatted']); ?></td>
                                    <td><?php echo formatDate($test['due_date_formatted']); ?></td>
                                    <td>
                                        <?php if ($test['status'] === 'pending'): ?>
                                            <span class="badge bg-warning text-dark">قيد الانتظار</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">مكتمل</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="test-details.php?id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            التفاصيل
                                        </a>
                                        <?php if ($test['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-success upload-result" 
                                                    data-test-id="<?php echo $test['test_id']; ?>">
                                                رفع النتيجة
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-flask fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">لا توجد فحوصات <?php echo $filter === 'pending' ? 'معلقة' : 'مكتملة'; ?></h4>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- نافذة رفع نتيجة الفحص -->
<div class="modal fade" id="uploadTestModal" tabindex="-1" aria-labelledby="uploadTestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadTestModalLabel">رفع نتيجة فحص</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="upload-test-result.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="testSelect" class="form-label">اختر الفحص</label>
                        <select class="form-select" id="testSelect" name="test_id" required>
                            <option value="">اختر الفحص</option>
                            <?php foreach ($tests as $test): ?>
                                <?php if ($test['status'] === 'pending'): ?>
                                    <option value="<?php echo $test['test_id']; ?>">
                                        <?php echo htmlspecialchars($test['test_name']); ?> 
                                        (طلب في <?php echo formatDate($test['request_date_formatted']); ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="testFile" class="form-label">ملف نتيجة الفحص</label>
                        <input class="form-control" type="file" id="testFile" name="test_file" accept=".pdf,.jpg,.jpeg,.png" required>
                        <div class="form-text">يمكنك رفع ملفات PDF أو صور (JPG, PNG)</div>
                    </div>
                    <div class="mb-3">
                        <label for="testNotes" class="form-label">ملاحظات (اختياري)</label>
                        <textarea class="form-control" id="testNotes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">رفع النتيجة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// كود JavaScript لإدارة رفع نتائج الفحوصات
document.addEventListener('DOMContentLoaded', function() {
    // فتح نافذة الرفع عند النقر على زر رفع النتيجة
    document.querySelectorAll('.upload-result').forEach(btn => {
        btn.addEventListener('click', function() {
            const testId = this.getAttribute('data-test-id');
            document.getElementById('testSelect').value = testId;
            
            const modal = new bootstrap.Modal(document.getElementById('uploadTestModal'));
            modal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>