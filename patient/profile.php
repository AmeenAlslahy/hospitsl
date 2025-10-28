<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'patient') {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$patient_id = getPatientId($_SESSION['user_id'], $db);
$patient = getPatientById($patient_id, $db);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);

    // تحقق من الحقول المطلوبة
    if (empty($full_name) || empty($email) || empty($phone)) {
        $error = "جميع الحقول مطلوبة.";
    } else {
        // تحقق من صحة البريد الإلكتروني
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "البريد الإلكتروني غير صالح.";
        } else {
            // تحديث بيانات المستخدم في جدول users
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $phone, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $success = "تم تحديث البيانات بنجاح.";
                // تحديث البيانات المعروضة
                $patient['full_name'] = $full_name;
                $patient['email'] = $email;
                $patient['phone'] = $phone;
            } else {
                $error = "حدث خطأ أثناء تحديث البيانات.";
            }
        }
    }
}

$pageTitle = "الملف الشخصي";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <h1>الملف الشخصي</h1>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">الاسم الكامل</label>
            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($patient['full_name']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">رقم الهاتف</label>
            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
        </div>
        <!-- أضف المزيد من الحقول حسب الحاجة -->
        <button type="submit" class="btn btn-primary">تحديث البيانات</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>