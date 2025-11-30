<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth =new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'doctor') {
    $_SESSION['redirect_url'] = BASE_PATH . '/doctor/dashboard.php';
    $_SESSION['error_message'] = 'يجب تسجيل الدخول كطبيب للوصول إلى هذه الصفحة';
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}
$doctor_id = getDoctorId($_SESSION['user_id'],$db);
$filter = $_GET['filter'] ?? 'upcoming';

// جلب المواعيد حسب الفلتر
$query = "SELECT a.*, u.full_name as patient_name 
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          WHERE a.doctor_id = $doctor_id AND a.status  <> 'cancelled'";

// switch ($filter) {
//     case 'past':
//         $query .= " AND a.appointment_date < CURDATE()";
//         break;
//     case 'today':
//         $query .= " AND a.appointment_date = CURDATE()";
//         break;
//     default:
//         $query .= " AND a.appointment_date >= CURDATE()";
// }

$query .= " ORDER BY a.appointment_date, a.start_time";
$appointments = $db->query($query)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-appointments py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>إدارة المواعيد</h1>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>المريض</th>
                                <th>السبب</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <?php if(isset($appointments)): ?>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo arabicDate($appointment['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($appointment['start_time'])); ?></td>
                                <td><?php echo $appointment['patient_name']; ?></td>
                                <td><?php echo $appointment['notes']; ?></td>
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
                                    <a href="patient_records.php?id=<?= $appointment['patient_id'] ?>" class="btn btn-sm btn-info">السجل الطبي</a>
                                    
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


<?php require_once __DIR__ . '/../includes/footer.php'; ?>