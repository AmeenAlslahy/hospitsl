<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
$auth = new Auth();
require_once __DIR__ . '/includes/header.php';

// معالجة إرسال رسالة الاتصال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // هنا يتم إرسال الرسالة (إلى البريد الإلكتروني أو قاعدة البيانات)
    $success = true; // افتراضيًا نجاح العملية
    
    if ($success) {
        echo '<div class="alert alert-success">شكرًا لك، تم استلام رسالتك وسنقوم بالرد في أقرب وقت</div>';
    } else {
        echo '<div class="alert alert-danger">حدث خطأ أثناء إرسال الرسالة، يرجى المحاولة لاحقًا</div>';
    }
}
?>

<div class="contact-page py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h1 class="mb-4">تواصل معنا</h1>
                <p class="lead">يسعدنا تلقي استفساراتكم واقتراحاتكم على مدار الساعة</p>
                
                <div class="contact-info mt-5">
                    <div class="d-flex align-items-start mb-4">
                        <i class="fas fa-map-marker-alt fa-2x text-primary me-4 mt-1"></i>
                        <div>
                            <h5>العنوان</h5>
                            <p class="mb-0">المدينة، الحي، الشارع، الجمهورية اليمنية</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mb-4">
                        <i class="fas fa-phone-alt fa-2x text-primary me-4 mt-1"></i>
                        <div>
                            <h5>الهاتف</h5>
                            <p class="mb-0">713555262</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mb-4">
                        <i class="fas fa-envelope fa-2x text-primary me-4 mt-1"></i>
                        <div>
                            <h5>البريد الإلكتروني</h5>
                            <p class="mb-0">alslahyamyn95@gmail.com</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start">
                        <i class="fas fa-clock fa-2x text-primary me-4 mt-1"></i>
                        <div>
                            <h5>ساعات العمل</h5>
                            <p class="mb-0">الأحد - الخميس: 8 صباحًا - 5 مساءً</p>
                            <p class="mb-0">الجمعة والسبت: إجازة</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card border-0 shadow">
                    <div class="card-body p-4">
                        <h2 class="mb-4">أرسل رسالة</h2>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">الاسم الكامل</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">الموضوع</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">الرسالة</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="fas fa-paper-plane me-2"></i>إرسال الرسالة
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>