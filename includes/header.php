<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'نظام حجز المستشفيات'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.5.3/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Google Fonts - Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.rtlcss.com/bootstrap/v4.5.3/js/bootstrap.min.js"></script>

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --gradient-start: #3498db;
            --gradient-end: #2c3e50;
        }

        body {
            padding-top: 70px;
            font-family: 'Tajawal', sans-serif;
        }

        .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .navbar {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            padding: 0.5rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand i {
            margin-left: 8px;
            font-size: 1.3rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            margin: 0 2px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .nav-link.active {
            color: white !important;
            font-weight: 600;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.5rem 0;
        }

        .dropdown-item {
            padding: 0.5rem 1.5rem;
            color: var(--dark-color);
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: var(--secondary-color);
            color: white !important;
        }

        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.2);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3e%3cpath stroke='rgba(255, 255, 255, 0.8)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                background-color: var(--primary-color);
                padding: 1rem;
                border-radius: 8px;
                margin-top: 0.5rem;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
            
            .nav-link {
                margin: 2px 0;
            }
        }
    </style>
</head>
<body>
    <header class="fixed-header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-hospital"></i> نظام حجز المستشفيات
                </a>
                
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'home') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/index.php">الرئيسية</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'specialties') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/specialties.php">التخصصات الطبية</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'doctors') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/doctors.php">الأطباء</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'appointments') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/appointments.php">حجز موعد</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'blood-donation') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/blood-donation.php">التبرع بالدم</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'jobs') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/jobs.php">الوظائف</a>
                        </li>                       
                        <?php if ($auth->isLoggedIn()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                                    <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                                </a>
                                <div class="dropdown-menu">
                                    <?php if ($auth->getUserRole() === 'admin'): ?>
                                        <a class="dropdown-item" href="<?php echo BASE_PATH; ?>/admin/dashboard.php">لوحة التحكم</a>
                                    <?php elseif ($auth->getUserRole() === 'doctor'): ?>
                                        <a class="dropdown-item" href="doctor/">لوحة التحكم</a>
                                    <?php elseif ($auth->getUserRole() === 'patient'): ?>
                                        <a class="dropdown-item" href="<?php echo BASE_PATH; ?>/patient/dashboard.php">لوحة التحكم</a>
                                    <?php endif; ?>
                                    <a class="dropdown-item" href="<?php echo BASE_PATH; ?>/logout.php">تسجيل الخروج</a>
                                </div>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($currentPage == 'login') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/login.php">تسجيل الدخول</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($currentPage == 'register') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/register.php">إنشاء حساب</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container">