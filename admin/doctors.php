<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
// التحقق من صلاحيات المدير
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

// جلب قائمة الأطباء
$doctors = [];
$query = "SELECT d.*, u.full_name, u.email, u.phone, s.name as specialty_name 
          FROM doctors d 
          JOIN users u ON d.user_id = u.user_id
          JOIN specialties s ON d.specialty_id = s.specialty_id";

$result = $db->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    $result->free();
} else {
    error_log("خطأ في استعلام الأطباء: " . $db->getConnection()->error);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-doctors py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>إدارة الأطباء</h1>
            <a href="<?= htmlspecialchars(BASE_PATH) ?>/admin/add-doctor.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>إضافة طبيب جديد
            </a>
        </div>
        
        <?php if (empty($doctors)): ?>
            <div class="alert alert-info">لا يوجد أطباء مسجلين حالياً</div>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الاسم</th>
                                    <th>التخصص</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الهاتف</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $doctor): ?>
                                <tr>
                                    <td><?= htmlspecialchars($doctor['doctor_id']) ?></td>
                                    <td><?= htmlspecialchars($doctor['full_name']) ?></td>
                                    <td><?= htmlspecialchars($doctor['specialty_name']) ?></td>
                                    <td><?= htmlspecialchars($doctor['email']) ?></td>
                                    <td><?= htmlspecialchars($doctor['phone']) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= htmlspecialchars(BASE_PATH) ?>/admin/view-doctor.php?id=<?= htmlspecialchars($doctor['doctor_id']) ?>" 
                                               class="btn btn-sm btn-info" title="عرض">
                                               <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= htmlspecialchars(BASE_PATH) ?>/admin/edit-doctor.php?id=<?= htmlspecialchars($doctor['doctor_id']) ?>" 
                                               class="btn btn-sm btn-warning" title="تعديل">
                                               <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= htmlspecialchars(BASE_PATH) ?>/admin/delete-doctor.php?id=<?= htmlspecialchars($doctor['doctor_id']) ?>" 
                                               class="btn btn-sm btn-danger" title="حذف"
                                               onclick="return confirm('هل أنت متأكد من حذف هذا الطبيب؟')">
                                               <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>