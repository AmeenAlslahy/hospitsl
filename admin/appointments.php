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

// جلب قائمة المواعيد
try{
        $result = $db->query("SELECT a.*, 
                         p.full_name as patient_name, 
                         du.full_name as doctor_name,
                         s.name as specialty
                         FROM appointments a
                         JOIN patients pt ON a.patient_id = pt.patient_id
                         JOIN users p ON pt.user_id = p.user_id
                         JOIN doctors d ON a.doctor_id = d.doctor_id
                         JOIN users du ON d.user_id = du.user_id
                         JOIN specialties s ON d.specialty_id = s.specialty_id
                         ORDER BY a.appointment_date DESC, a.start_time DESC");
        $appointments =$result->fetch_all(MYSQLI_ASSOC);
        // while ($row = $result->fetch_assoc()) {
        //     $appointments[] = $row;   
        // }
// $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }  catch(Exception $e){ echo "error";}


require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-appointments py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>إدارة المواعيد</h1>
            <div>
                <a href="<?php echo BASE_PATH; ?>/admin/add-appointment.php" class="btn btn-primary me-2">
                    <i class="fas fa-plus me-2"></i>إضافة موعد
                </a>
                <a href="<?php echo BASE_PATH; ?>/admin/calendar.php" class="btn btn-secondary">
                    <i class="fas fa-calendar-alt me-2"></i>عرض التقويم
                </a>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المريض</th>
                                <th>الطبيب</th>
                                <th>التخصص</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <?php if(isset($appointments)): ?>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo $appointment['appointment_id']; ?></td>
                                <td><?php echo $appointment['patient_name']; ?></td>
                                <td>د. <?php echo $appointment['doctor_name']; ?></td>
                                <td><?php echo $appointment['specialty']; ?></td>
                                <td><?php echo arabicDate($appointment['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($appointment['start_time'])); ?></td>
                                <td>
                                    <?php 
                                    $status_class = [
                                        'pending' => 'warning',
                                        'confirmed' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $status_class[$appointment['status']]; ?>">
                                        <?php 
                                        $status_text = [
                                            'pending' => 'قيد الانتظار',
                                            'confirmed' => 'مؤكد',
                                            'completed' => 'مكتمل',
                                            'cancelled' => 'ملغى'
                                        ];
                                        echo $status_text[$appointment['status']]; 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_PATH; ?>/admin/view-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                       class="btn btn-sm btn-info" title="عرض">
                                       <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/edit-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                       class="btn btn-sm btn-warning" title="تعديل">
                                       <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/delete-appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                       class="btn btn-sm btn-danger" title="حذف"
                                       onclick="return confirm('هل أنت متأكد من حذف هذا الموعد؟')">
                                       <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>