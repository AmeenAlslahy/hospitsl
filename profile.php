<?php
require_once __DIR__ . '/includes/config.php';

// إذا لم يكن المستخدم مسجل الدخول، توجيهه لصفحة تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="profile-page py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="profile-image mb-3">
                            <img src="<?php echo ASSETS_PATH; ?>/images/user-default.png" 
                                 class="rounded-circle" 
                                 width="150" 
                                 height="150" 
                                 alt="صورة المستخدم">
                        </div>
                        
                        <h3 class="mb-1"><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                        <p class="text-muted mb-3">
                            <?php 
                            if ($_SESSION['role'] === 'admin') {
                                echo 'مدير النظام';
                            } elseif ($_SESSION['role'] === 'doctor') {
                                echo 'طبيب';
                            } else {
                                echo 'مريض';
                            }
                            ?>
                        </p>
                        
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="fas fa-envelope me-2 text-primary"></i> <?php echo $_SESSION['username']; ?></li>
                            <li class="mb-2"><i class="fas fa-phone me-2 text-primary"></i> 05XXXXXXXX</li>
                            <li class="mb-2"><i class="fas fa-calendar-alt me-2 text-primary"></i> تاريخ التسجيل: <?php echo date('Y-m-d'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" href="#personal" data-bs-toggle="tab">المعلومات الشخصية</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#appointments" data-bs-toggle="tab">مواعيدي</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#security" data-bs-toggle="tab">الأمان</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="personal">
                                <form>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">الاسم الكامل</label>
                                            <input type="text" class="form-control" value="اسم المستخدم" readonly>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">البريد الإلكتروني</label>
                                            <input type="email" class="form-control" value="<?php echo $_SESSION['username']; ?>" readonly>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">رقم الهاتف</label>
                                            <input type="tel" class="form-control" value="05XXXXXXXX">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">تاريخ الميلاد</label>
                                            <input type="date" class="form-control">
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <label class="form-label">العنوان</label>
                                            <textarea class="form-control" rows="2"></textarea>
                                        </div>
                                        
                                        <div class="col-12 mt-3">
                                            <button type="submit" class="btn btn-primary px-4">حفظ التغييرات</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="tab-pane fade" id="appointments">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>رقم الموعد</th>
                                                <th>الطبيب</th>
                                                <th>التاريخ</th>
                                                <th>الوقت</th>
                                                <th>الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>#1234</td>
                                                <td>د. أحمد محمد</td>
                                                <td>2023-06-15</td>
                                                <td>10:00 ص</td>
                                                <td><span class="badge bg-success">مكتمل</span></td>
                                            </tr>
                                            <tr>
                                                <td>#1235</td>
                                                <td>د. سارة عبدالله</td>
                                                <td>2023-06-20</td>
                                                <td>02:00 م</td>
                                                <td><span class="badge bg-warning text-dark">قيد الانتظار</span></td>
                                            </tr>
                                            <tr>
                                                <td>#1236</td>
                                                <td>د. خالد علي</td>
                                                <td>2023-06-25</td>
                                                <td>11:00 ص</td>
                                                <td><span class="badge bg-primary">مؤكد</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="security">
                                <form>
                                    <div class="mb-3">
                                        <label class="form-label">كلمة المرور الحالية</label>
                                        <input type="password" class="form-control">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">كلمة المرور الجديدة</label>
                                        <input type="password" class="form-control">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                                        <input type="password" class="form-control">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary px-4">تغيير كلمة المرور</button>
                                </form>
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