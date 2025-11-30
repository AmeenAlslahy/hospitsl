<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$db = new Database();
// $user_id = $auth->getCurrentUserId();
$patient_id = getPatientId($_SESSION['user_id'], $db);

$pageTitle = "نظام التبرع بالدم";
$currentPage = 'blood-donation';
include 'includes/header.php';

// معالجة طلب التبرع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_donation'])) {
    try {
        // التحقق من CSRF Token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('طلب غير صالح');
        }

        // تنظيف المدخلات
        $donation_date = $db->escape($_POST['donation_date']);
        $blood_type = $db->escape($_POST['blood_type'] ?? '');
        $notes = $db->escape($_POST['notes'] ?? '');

        // التحقق من صحة التاريخ
        if (empty($donation_date) || strtotime($donation_date) < strtotime('today')) {
            throw new Exception('يجب اختيار تاريخ صالح في المستقبل');
        }

        // الحصول على فصيلة الدم من سجل المريض إذا لم يتم تحديدها
        if (empty($blood_type)) {
            $stmt = $db->prepare("SELECT blood_type FROM patients WHERE patient_id = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $patient = $result->fetch_assoc();
            $blood_type = $patient['blood_type'] ?? '';
            
            if (empty($blood_type)) {
                throw new Exception('لم يتم تحديد فصيلة الدم في ملفك الشخصي');
            }
        }

        // إضافة طلب التبرع باستخدام prepared statement
        $stmt = $db->prepare("INSERT INTO blood_donations 
                            (donor_id, donation_date, blood_type, notes) 
                            VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $patient_id, $donation_date, $blood_type, $notes);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "تم تسجيل طلب التبرع بنجاح، شكرًا لك";
            header("Location: blood-donation.php");
            exit();
        } else {
            throw new Exception('حدث خطأ أثناء تسجيل طلب التبرع');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: blood-donation.php");
        exit();
    }
}

// إنشاء CSRF Token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// الحصول على سجل التبرعات السابقة
try {
    $stmt = $db->prepare("SELECT * FROM blood_donations 
                         WHERE donor_id = ?
                         ORDER BY donation_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $donations = $stmt->get_result();
} catch (Exception $e) {
    error_log("Error fetching donations: " . $e->getMessage());
    $donations = [];
}

// الحصول على احتياجات بنك الدم
try {
    $needs = $db->query("SELECT blood_type, COUNT(*) as count 
                        FROM blood_donations 
                        WHERE status = 'completed'
                        AND donation_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                        GROUP BY blood_type");
    
    $blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $blood_counts = array_fill_keys($blood_types, 0);
    
    while ($need = $needs->fetch_assoc()) {
        if (array_key_exists($need['blood_type'], $blood_counts)) {
            $blood_counts[$need['blood_type']] = $need['count'];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching blood needs: " . $e->getMessage());
    $blood_counts = array_fill_keys($blood_types, 0);
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="text-center mb-5">
                <i class="fas fa-tint text-danger me-2"></i>نظام التبرع بالدم
            </h1>
            
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
            
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-danger text-white">
                            <h2 class="h5 mb-0">
                                <i class="fas fa-plus-circle me-2"></i>تقديم طلب تبرع جديد
                            </h2>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="mb-3">
                                    <label for="donation_date" class="form-label">تاريخ التبرع المطلوب</label>
                                    <input type="date" class="form-control" id="donation_date" name="donation_date" required 
                                           min="<?php echo date('Y-m-d'); ?>"
                                           max="<?php echo date('Y-m-d', strtotime('+3 months')); ?>">
                                    <div class="form-text">يمكنك حجز موعد خلال الـ 3 أشهر القادمة</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="blood_type" class="form-label">فصيلة الدم</label>
                                    <select class="form-select" id="blood_type" name="blood_type">
                                        <option value="">-- اختر فصيلة الدم --</option>
                                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $type): ?>
                                            <option value="<?php echo $type; ?>" 
                                                <?php echo ($blood_type ?? '') === $type ? 'selected' : ''; ?>>
                                                <?php echo $type; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">اترك هذا الحقل فارغًا لاستخدام فصيلة الدم المسجلة في ملفك</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="notes" class="form-label">ملاحظات (اختياري)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="أي معلومات إضافية تريد إضافتها"></textarea>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="submit_donation" class="btn btn-danger btn-lg">
                                        <i class="fas fa-heartbeat me-2"></i>تقديم طلب التبرع
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h2 class="h5 mb-0">
                                <i class="fas fa-history me-2"></i>سجل تبرعاتي السابقة
                            </h2>
                        </div>
                        <div class="card-body">
                            <?php if ($donations && $donations->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>التاريخ</th>
                                                <th>الفصيلة</th>
                                                <th>الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($donation = $donations->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo formatDate($donation['donation_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($donation['blood_type']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $statusClass = [
                                                            'pending' => 'warning',
                                                            'completed' => 'success',
                                                            'rejected' => 'danger'
                                                        ][$donation['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                                            <?php 
                                                            echo [
                                                                'pending' => 'قيد الانتظار',
                                                                'completed' => 'مكتمل',
                                                                'rejected' => 'مرفوض'
                                                            ][$donation['status']] ?? $donation['status'];
                                                            ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>لا توجد تبرعات سابقة
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h2 class="h5 mb-0">
                                <i class="fas fa-chart-line me-2"></i>احتياجات بنك الدم
                            </h2>
                        </div>
                        <div class="card-body">
                            <div class="blood-levels">
                                <?php foreach ($blood_types as $type): 
                                    $percentage = min($blood_counts[$type] * 5, 100); // 20 تبرع = 100%
                                ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-bold"><?php echo $type; ?></span>
                                            <span><?php echo $blood_counts[$type]; ?> / 20</span>
                                        </div>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar 
                                                <?php echo $percentage >= 100 ? 'bg-success' : 'bg-danger'; ?>" 
                                                role="progressbar" 
                                                style="width: <?php echo $percentage; ?>%" 
                                                aria-valuenow="<?php echo $blood_counts[$type]; ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="20">
                                                <?php if ($percentage > 30) echo $blood_counts[$type]; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                المستوى الأمثل: 20 تبرع لكل فصيلة خلال 3 أشهر
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>