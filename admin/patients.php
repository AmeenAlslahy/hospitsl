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

// جلب قائمة المرضى
try {
    // استخدم query() مباشرة مع MySQLi
    $result = $db->query("SELECT p.*, u.full_name, u.email, u.phone 
                         FROM patients p 
                         JOIN users u ON p.user_id = u.user_id");
    
    $patients =$result->fetch_all(MYSQLI_ASSOC);
    // while ($row = $result->fetch_assoc()) {
    //     $patients[] = $row;
    // }
} catch(Exception $e) {
    die("حدث خطأ في قاعدة البيانات: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-patients py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>إدارة المرضى</h1>
            <a href="<?php echo BASE_PATH; ?>/admin/add-patient.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>إضافة مريض جديد
            </a>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الهاتف</th>
                                <th>فصيلة الدم</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo $patient['patient_id']; ?></td>
                                <td><?php echo $patient['full_name']; ?></td>
                                <td><?php echo $patient['email']; ?></td>
                                <td><?php echo $patient['phone']; ?></td>
                                <td><?php echo $patient['blood_type'] ?? 'غير محدد'; ?></td>
                                <td>
                                    <a href="<?php echo BASE_PATH; ?>/admin/view-patient.php?id=<?php echo $patient['patient_id']; ?>" 
                                       class="btn btn-sm btn-info" title="عرض">
                                       <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/edit-patient.php?id=<?php echo $patient['patient_id']; ?>" 
                                       class="btn btn-sm btn-warning" title="تعديل">
                                       <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/delete-patient.php?id=<?php echo $patient['patient_id']; ?>" 
                                       class="btn btn-sm btn-danger" title="حذف"
                                       onclick="return confirm('هل أنت متأكد من حذف هذا المريض؟')">
                                       <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>