<?php>
<style>
footer {
    background-color: var(--secondary-color);
    color: white;
    padding: 2rem 0;
    margin-top: 3rem;
}

.footer-links {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    justify-content: center;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: var(--transition);
}

.footer-links a:hover {
    color: white;
}
</style>
    </main>
    
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h4>روابط سريعة</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo BASE_PATH; ?>/index.php">الرئيسية</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/specialties.php">التخصصات الطبية</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/doctors.php">الأطباء</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/appointments.php">حجز موعد</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4">
                    <h4>خدمات المستشفى</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo BASE_PATH; ?>/blood-donation.php">بنك الدم</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/jobs.php">الوظائف</a></li>
                        <li><a href="#">الأسئلة الشائعة</a></li>
                        <li><a href="<?php echo BASE_PATH; ?>/contact.php">اتصل بنا</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4">
                    <h4>معلومات التواصل</h4>
                    <address>
                        <p><i class="fas fa-map-marker-alt"></i> العنوان: إب، الظهار، شارع الملكة اروى</p>
                        <p><i class="fas fa-phone"></i> الهاتف: 713555262</p>
                        <p><i class="fas fa-envelope"></i> البريد: alslahyamyn95@gmail.com</p>
                    </address>
                </div>
            </div>
            
            <div class="copyright text-center">
                <p>جميع الحقوق محفوظة لدى امين الصلاحي &copy; <?php echo date('Y'); ?> - نظام حجز المستشفيات</p>
            </div>
        </div>
    </footer>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_PATH; ?>/assets/js/main.js"></script>
</body>
</html>