<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$auth = new Auth();
$pageTitle = "أطباؤنا المتخصصون";
$currentPage = 'doctors';
include __DIR__ . '/includes/header.php';

// جلب بيانات الأطباء من قاعدة البيانات
try {
    $db = new Database();
    $stmt = $db->prepare("SELECT d.doctor_id, u.full_name, u.profile_picture, 
                         s.name AS specialty, d.bio, d.years_of_experience,
                         d.consultation_fee, d.license_number
                         FROM doctors d
                         JOIN users u ON d.user_id = u.user_id
                         JOIN specialties s ON d.specialty_id = s.specialty_id
                         WHERE u.is_active = 1
                         ORDER BY d.years_of_experience DESC");
    $stmt->execute();
    $doctors = $stmt->get_result();
} catch (Exception $e) {
    error_log("Error fetching doctors: " . $e->getMessage());
    $doctors = [];
}
?>

<style>
:root {
    --primary-color: #3498db;
    --secondary-color: #2c3e50;
    --accent-color: #e74c3c;
    --light-bg: #f8f9fa;
    --dark-text: #2c3e50;
    --light-text: #7f8c8d;
    --success-color: #27ae60;
    --border-radius: 10px;
    --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.doctors-page {
    background-color: var(--light-bg);
    padding: 3rem 0;
}

.page-header {
    margin-bottom: 3rem;
    text-align: center;
}

.page-title {
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 1rem;
    position: relative;
    display: inline-block;
}

.page-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(to right, var(--primary-color), var(--accent-color));
    border-radius: 3px;
}

.page-subtitle {
    color: var(--light-text);
    font-size: 1.1rem;
}

.filter-container {
    max-width: 600px;
    margin: 0 auto 2.5rem;
}

.doctor-card {
    border: none;
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: var(--box-shadow);
    height: 100%;
}

.doctor-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.doctor-image-container {
    position: relative;
    overflow: hidden;
    height: 250px;
}

.doctor-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.doctor-card:hover .doctor-image {
    transform: scale(1.05);
}

.experience-badge {
    position: absolute;
    bottom: 15px;
    left: 15px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
}

.card-body {
    padding: 1.5rem;
}

.doctor-name {
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 0.5rem;
}

.specialty-badge {
    background-color: rgba(52, 152, 219, 0.1);
    color: var(--primary-color);
    font-weight: 500;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    display: inline-block;
    margin-bottom: 1rem;
}

.doctor-bio {
    color: var(--light-text);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.doctor-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.license-number {
    color: var(--light-text);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
}

.consultation-fee {
    color: var(--success-color);
    font-weight: 700;
    font-size: 1.1rem;
}

.card-footer {
    background: white;
    border-top: none;
    padding: 0 1.5rem 1.5rem;
}

.btn-profile {
    border-color: var(--primary-color);
    color: var(--primary-color);
    transition: all 0.3s;
}

.btn-profile:hover {
    background-color: var(--primary-color);
    color: white;
}

.btn-book {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    transition: all 0.3s;
}

.btn-book:hover {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
}

.no-doctors {
    padding: 3rem;
    text-align: center;
    border-radius: var(--border-radius);
    background-color: white;
    box-shadow: var(--box-shadow);
}

.no-doctors-icon {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .doctor-image-container {
        height: 200px;
    }
    
    .page-title {
        font-size: 1.8rem;
    }
}
</style>

<div class="doctors-page">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">فريق الأطباء المتخصصين</h1>
            <p class="page-subtitle">نخبة من أفضل الأطباء في مختلف التخصصات الطبية</p>
        </div>
        
        <!-- فلترة الأطباء حسب التخصص -->
        <div class="filter-container">
            <div class="input-group shadow-sm rounded-pill">
                <span class="input-group-text bg-white border-0 ps-3">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" class="form-control border-0 py-2" id="doctorFilter" placeholder="ابحث باسم الطبيب أو التخصص...">
                <button class="btn btn-primary rounded-pill px-4" type="button" id="filterButton">
                    بحث
                </button>
            </div>
        </div>
        
        <div class="row g-4" id="doctorsContainer">
            <?php if ($doctors && $doctors->num_rows > 0): ?>
                <?php while ($doctor = $doctors->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6 doctor-col">
                        <div class="card doctor-card">
                            <div class="doctor-image-container">
                                <img src="<?php echo getDoctorImage($doctor['profile_picture']); ?>" 
                                     class="doctor-image" 
                                     alt="صورة الدكتور <?php echo htmlspecialchars($doctor['full_name']); ?>"
                                     loading="lazy">
                                <div class="experience-badge">
                                    <i class="fas fa-award me-1"></i> <?php echo $doctor['years_of_experience']; ?>+ سنوات خبرة
                                </div>
                            </div>
                            <div class="card-body">
                                <h3 class="doctor-name">
                                    <?php echo htmlspecialchars($doctor['full_name']); ?>
                                </h3>
                                <span class="specialty-badge">
                                    <?php echo htmlspecialchars($doctor['specialty']); ?>
                                </span>
                                <p class="doctor-bio">
                                    <?php echo htmlspecialchars(shortenText($doctor['bio'], 120)); ?>
                                </p>
                                <div class="doctor-meta">
                                    <div class="license-number">
                                        <i class="fas fa-id-card me-2"></i>
                                        <?php echo $doctor['license_number']; ?>
                                    </div>
                                    <div class="consultation-fee">
                                        <?php echo $doctor['consultation_fee']; ?> ر.س
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-grid gap-2">
                                    <a href="<?php echo BASE_PATH; ?>/doctor-profile.php?id=<?php echo $doctor['doctor_id']; ?>" 
                                       class="btn btn-profile">
                                       <i class="fas fa-user-md me-2"></i>الملف الشخصي
                                    </a>
                                    <a href="<?php echo BASE_PATH; ?>/appointments.php?doctor=<?php echo $doctor['doctor_id']; ?>" 
                                       class="btn btn-book">
                                       <i class="fas fa-calendar-check me-2"></i>حجز موعد
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="no-doctors">
                        <div class="no-doctors-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h4 class="mb-3">لا يوجد أطباء متاحين حالياً</h4>
                        <p class="text-muted">سيتم إضافة بيانات الأطباء قريباً</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// فلترة الأطباء بدون إعادة تحميل الصفحة
document.getElementById('filterButton').addEventListener('click', filterDoctors);
document.getElementById('doctorFilter').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') filterDoctors();
});

function filterDoctors() {
    const filter = document.getElementById('doctorFilter').value.toLowerCase();
    const doctorCols = document.querySelectorAll('.doctor-col');
    let visibleCount = 0;
    
    doctorCols.forEach(col => {
        const text = col.textContent.toLowerCase();
        if (text.includes(filter)) {
            col.style.display = 'block';
            visibleCount++;
        } else {
            col.style.display = 'none';
        }
    });
    
    // عرض رسالة إذا لم يتم العثور على نتائج
    const noResults = document.getElementById('noResults');
    if (visibleCount === 0) {
        if (!noResults) {
            const container = document.getElementById('doctorsContainer');
            container.innerHTML = `
                <div class="col-12" id="noResults">
                    <div class="no-doctors">
                        <div class="no-doctors-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4 class="mb-3">لا توجد نتائج مطابقة للبحث</h4>
                        <p class="text-muted">حاول استخدام كلمات بحث مختلفة</p>
                    </div>
                </div>
            `;
        }
    } else if (noResults) {
        noResults.remove();
    }
}
</script>