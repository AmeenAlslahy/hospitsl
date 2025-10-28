<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'doctor') {
    $_SESSION['redirect_url'] = BASE_PATH . '/doctor/dashboard.php';
    $_SESSION['error_message'] = 'يجب تسجيل الدخول كطبيب للوصول إلى هذه الصفحة';
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}
$doctor_id = getDoctorId($_SESSION['user_id'], $db);
$filter = $_GET['filter'] ?? 'upcoming';

// جلب المواعيد حسب الفلتر باستخدام prepared statement
$where = "a.doctor_id = ? AND a.status <> 'cancelled'";
switch ($filter) {
    case 'past':
        $where .= " AND a.appointment_date < CURDATE()";
        break;
    case 'today':
        $where .= " AND a.appointment_date = CURDATE()";
        break;
    default:
        $where .= " AND a.appointment_date >= CURDATE()";
}

$query = "SELECT a.*, u.full_name as patient_name, p.patient_id
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          WHERE $where
          ORDER BY a.appointment_date, a.start_time";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-appointments py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>إدارة المواعيد</h1>
            <div>
                <a href="?filter=upcoming" class="btn btn-outline-primary btn-sm <?= $filter == 'upcoming' ? 'active' : '' ?>">القادمة</a>
                <a href="?filter=today" class="btn btn-outline-info btn-sm <?= $filter == 'today' ? 'active' : '' ?>">اليوم</a>
                <a href="?filter=past" class="btn btn-outline-secondary btn-sm <?= $filter == 'past' ? 'active' : '' ?>">السابقة</a>
            </div>
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
                        <tbody>
                        <?php if($appointments && count($appointments) > 0): ?>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo arabicDate($appointment['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($appointment['start_time'])); ?></td>
                                <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['notes']); ?></td>
                                <td>
                                    <?php 
                                    $status_class = [
                                        'pending' => 'warning',
                                        'confirmed' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $status_text = [
                                        'pending' => 'قيد الانتظار',
                                        'confirmed' => 'مؤكد',
                                        'completed' => 'مكتمل',
                                        'cancelled' => 'ملغى'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $status_class[$appointment['status']] ?? 'secondary'; ?>">
                                        <?php echo $status_text[$appointment['status']] ?? $appointment['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="patient_records.php?id=<?= $appointment['patient_id'] ?>" class="btn btn-sm btn-info">السجل الطبي</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا توجد مواعيد لعرضها</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>