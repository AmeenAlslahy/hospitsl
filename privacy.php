<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
$auth = new Auth();
require_once __DIR__ . '/includes/header.php';
?>

<div class="privacy-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h1 class="text-center">سياسة الخصوصية</h1>
                    </div>
                    <div class="card-body">
                        <div class="privacy-content">
                            <h3 class="mb-3">1. جمع المعلومات</h3>
                            <p>نجمع المعلومات التالية لتحسين تجربة استخدامك لموقعنا:</p>
                            <ul>
                                <li>الاسم الكامل ومعلومات الاتصال عند التسجيل</li>
                                <li>التاريخ الطبي عند حجز المواعيد</li>
                                <li>سجل المواعيد والزيارات</li>
                            </ul>
                            
                            <h3 class="mb-3 mt-4">2. استخدام المعلومات</h3>
                            <p>نستخدم المعلومات التي نجمعها للأغراض التالية:</p>
                            <ul>
                                <li>توفير الخدمات الطبية المطلوبة</li>
                                <li>تحسين جودة الخدمات</li>
                                <li>إرسال التذكيرات والإشعارات</li>
                                <li>الأغراض الإحصائية دون الكشف عن الهوية</li>
                            </ul>
                            
                            <h3 class="mb-3 mt-4">3. حماية المعلومات</h3>
                            <p>نحن نستخدم إجراءات أمنية متقدمة لحماية بياناتك:</p>
                            <ul>
                                <li>تشفير البيانات الحساسة</li>
                                <li>أنظمة حماية من الاختراق</li>
                                <li>الوصول المحدود للموظفين</li>
                            </ul>
                            
                            <h3 class="mb-3 mt-4">4. مشاركة المعلومات</h3>
                            <p>لا نبيع أو نشارك معلوماتك الشخصية مع أطراف خارجية إلا في الحالات التالية:</p>
                            <ul>
                                <li>بموافقتك الصريحة</li>
                                <li>لأغراض طبية طارئة</li>
                                <li>عندما يقتضي القانون ذلك</li>
                            </ul>
                            
                            <h3 class="mb-3 mt-4">5. حقوقك</h3>
                            <p>لديك الحق في:</p>
                            <ul>
                                <li>الوصول إلى بياناتك الشخصية</li>
                                <li>طلب تصحيح المعلومات غير الدقيقة</li>
                                <li>طلب حذف بياناتك في بعض الحالات</li>
                            </ul>
                            
                            <div class="text-center mt-5">
                                <a href="<?php echo BASE_PATH; ?>/contact.php" class="btn btn-primary px-4">اتصل بنا للاستفسار</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>