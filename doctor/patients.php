<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$doctor_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';

// جلب قائمة المرضى
$query = "SELECT DISTINCT p.patient_id, u.full_name, u.phone, p.blood_type
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          WHERE a.doctor_id = $doctor_id";

if (!empty($search)) {
    $query .= " AND (u.full_name LIKE '%$search%' OR u.phone LIKE '%$search%')";
}

$query .= " ORDER BY u.full_name";
$patients = $db->query($query)->fetch_all(MYSQLI_ASSOC);

// include_header('قائمة المرضى');
include __DIR__ . '/../includes/header.php'; 
?>
<style>
.table {
    width: 100%;
    margin-bottom: 1.5rem;
    border-collapse: separate;
    border-spacing: 0;
}

.table th {
    background-color: var(--light-color);
    padding: 1rem;
    text-align: right;
    font-weight: 600;
    border-top: 1px solid #dee2e6;
}

.table td {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

</style>
<div class="patients-page">
    <h1>قائمة المرضى</h1>
    
    <div class="search-box">
        <form method="GET">
            <input type="text" name="search" placeholder="ابحث باسم المريض أو رقم الهاتف" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">بحث</button>
        </form>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>اسم المريض</th>
                <th>رقم الهاتف</th>
                <th>فصيلة الدم</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($patients as $patient): ?>
            <tr>
                <td><?= htmlspecialchars($patient['full_name']) ?></td>
                <td><?= htmlspecialchars($patient['phone']) ?></td>
                <td><?= htmlspecialchars($patient['blood_type'] ?? '---') ?></td>
                <td>
                    <a href="patient_records.php?id=<?= $patient['patient_id'] ?>" class="btn btn-sm btn-primary">عرض السجل</a>
                    <a href="add_record.php?patient_id=<?= $patient['patient_id'] ?>" class="btn btn-sm btn-success">إضافة سجل</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php';  ?>