<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="error-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="error-icon mb-4">
                    <i class="fas fa-exclamation-triangle fa-5x text-danger"></i>
                </div>
                <h1 class="display-4 mb-3">404 - الصفحة غير موجودة</h1>
                <p class="lead mb-4">عذرًا، الصفحة التي تبحث عنها غير موجودة أو قد تم نقلها.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?php echo BASE_PATH; ?>/index.php" class="btn btn-primary px-4">
                        <i class="fas fa-home me-2"></i>العودة للصفحة الرئيسية
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/contact.php" class="btn btn-outline-primary px-4">
                        <i class="fas fa-envelope me-2"></i>اتصل بنا
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>