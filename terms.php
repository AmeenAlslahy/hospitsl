<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
$auth = new Auth();
require_once __DIR__ . '/includes/header.php';
?>

<div class="terms-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h1 class="text-center">الشروط والأحكام</h1>
                    </div>
                    <div class="card-body">
                        <div class="terms-content">
                            <h3 class="mb-3">1. مقدمة</h3>
                            <p>مرحبًا بكم في نظام حجز المستشفيات. يرجى قراءة هذه الشروط والأحكام بعناية قبل استخدام الموقع. باستخدامك للموقع، فإنك توافق على الالتزام بهذه الشروط والأحكام.</p>
                            
                            <h3 class="mb-3 mt-4">2. استخدام الموقع</h3>
                            <ul>
                                <li>يجب أن تكون 18 سنة على الأقل لاستخدام هذا الموقع.</li>
                                <li>أنت مسؤول عن الحفاظ على سرية معلومات حسابك.</li>
                                <li>يجب تقديم معلومات دقيقة وصحيحة عند التسجيل.</li>
                            </ul>
                            
                            <h3 class="mb-3 mt-4">3. حجز المواعيد</h3>
                            <ul>
                                <li>يمكنك إلغاء الموعد قبل 24 ساعة على الأقل من موعده.</li>
                                <li>التأخير أكثر من 15 دقيقة قد يؤدي إلى إلغاء الموعد.</li>
                                <li>يجب إحضار الوثائق المطلوبة عند الحضور للموعد.</li>
                            </ul>
                            
                            <h3 class="mb-3 mt-4">4. الخصوصية</h3>
                            <p>نحن نحترم خصوصيتك ونلتزم بحماية بياناتك الشخصية. راجع <a href="<?php echo BASE_PATH; ?>/privacy.php">سياسة الخصوصية</a> لمزيد من التفاصيل.</p>
                            
                            <h3 class="mb-3 mt-4">5. التعديلات</h3>
                            <p>نحتفظ بالحق في تعديل هذه الشروط في أي وقت. سيتم إعلامك بأي تغييرات جوهرية.</p>
                            
                            <div class="text-center mt-5">
                                <a href="<?php echo BASE_PATH; ?>/register.php" class="btn btn-primary px-4">العودة إلى صفحة التسجيل</a>
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