<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام الفوترة الإلكترونية المتقدم - JO Invoicing</title>
    <meta name="description" content="نظام فوترة إلكترونية متطور ومتوافق مع متطلبات ضريبة القيمة المضافة والسلطات الضريبية">
    <meta name="keywords" content="فوترة إلكترونية, ضريبة القيمة المضافة, نظام محاسبي, فواتير, الأردن">

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2C5282;
            --secondary-color: #3182CE;
            --accent-color: #805AD5;
            --success-color: #38A169;
            --warning-color: #D69E2E;
            --danger-color: #E53E3E;
            --light-gray: #F7FAFC;
            --dark-gray: #2D3748;
        }

        * {
            font-family: 'Cairo', sans-serif;
        }

        body {
            line-height: 1.6;
            color: var(--dark-gray);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--accent-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 400;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-image {
            max-width: 100%;
            height: auto;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.2));
        }

        /* Feature Cards */
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            margin-right: auto;
            margin-left: auto;
        }

        .feature-icon.primary { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
        .feature-icon.success { background: linear-gradient(135deg, var(--success-color), #48BB78); }
        .feature-icon.warning { background: linear-gradient(135deg, var(--warning-color), #ECC94B); }
        .feature-icon.accent { background: linear-gradient(135deg, var(--accent-color), #9F7AEA); }

        /* Stats Section */
        .stats-section {
            background: var(--light-gray);
            padding: 5rem 0;
        }

        .stat-item {
            text-align: center;
            padding: 2rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: var(--dark-gray);
            font-weight: 500;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--success-color) 0%, #48BB78 100%);
            padding: 5rem 0;
            text-align: center;
        }

        /* Buttons */
        .btn-custom {
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            font-size: 1.1rem;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(44, 82, 130, 0.3);
            color: white;
        }

        .btn-outline-custom {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline-custom:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        /* Navbar */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .nav-link {
            color: var(--dark-gray) !important;
            font-weight: 500;
            margin: 0 1rem;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        /* Footer */
        .footer {
            background: var(--dark-gray);
            color: white;
            padding: 3rem 0 2rem;
        }

        .footer h5 {
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .footer-link {
            color: #CBD5E0;
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .feature-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }
        }

        /* Animations */
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-up.animate {
            opacity: 1;
            transform: translateY(0);
        }

        /* Custom animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .floating {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="fas fa-file-invoice me-2"></i>
                JO Invoicing
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">المميزات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('demo') }}">عرض توضيحي</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">عن النظام</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">اتصل بنا</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('login.ar') }}" class="btn btn-primary-custom btn-custom me-2">دخول</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('register.ar') }}" class="btn btn-outline-primary btn-custom">تسجيل جديد</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="hero-content">
                        <h1 class="hero-title">
                            نظام الفوترة الإلكترونية
                            <span class="text-warning">المتقدم</span>
                        </h1>
                        <p class="hero-subtitle">
                            حل شامل ومتطور لإدارة الفواتير الإلكترونية مع التوافق الكامل مع متطلبات ضريبة القيمة المضافة والسلطات الضريبية في الأردن
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ route('register.ar') }}" class="btn btn-primary-custom btn-custom">
                                <i class="fas fa-rocket me-2"></i>
                                ابدأ الآن مجاناً
                            </a>
                            <a href="{{ route('demo') }}" class="btn btn-outline-custom btn-custom">
                                <i class="fas fa-play me-2"></i>
                                عرض توضيحي
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="text-center">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 600'%3E%3Cdefs%3E%3ClinearGradient id='bg' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea'/%3E%3Cstop offset='100%25' style='stop-color:%23764ba2'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='800' height='600' fill='url(%23bg)' opacity='0.1'/%3E%3Cg transform='translate(100,100)'%3E%3Crect x='50' y='50' width='500' height='400' rx='20' fill='white' opacity='0.9'/%3E%3Crect x='80' y='100' width='200' height='20' rx='10' fill='%23667eea' opacity='0.8'/%3E%3Crect x='80' y='140' width='150' height='15' rx='7' fill='%23667eea' opacity='0.6'/%3E%3Crect x='80' y='170' width='300' height='15' rx='7' fill='%23667eea' opacity='0.4'/%3E%3Crect x='80' y='220' width='400' height='100' rx='10' fill='%23f7fafc'/%3E%3Ctext x='90' y='240' font-family='Arial' font-size='16' fill='%232d3748'%3E فاتورة رقم: INV-001%3C/text%3E%3Ctext x='90' y='270' font-family='Arial' font-size='14' fill='%234a5568'%3E التاريخ: 2024/01/15%3C/text%3E%3Ctext x='90' y='300' font-family='Arial' font-size='14' fill='%234a5568'%3E المبلغ: 1,250.00 د.أ%3C/text%3E%3Ccircle cx='450' cy='380' r='30' fill='%2338a169'/%3E%3Ctext x='435' y='385' font-family='Arial' font-size='20' fill='white'%3E✓%3C/text%3E%3C/g%3E%3C/svg%3E"
                             alt="نظام الفوترة الإلكترونية"
                             class="hero-image floating">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-item">
                        <div class="stat-number">15,000+</div>
                        <div class="stat-label">فاتورة تم إصدارها</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">عميل راضٍ</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-item">
                        <div class="stat-number">99.9%</div>
                        <div class="stat-label">معدل التوافق</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">دعم فني</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                    <h2 class="display-4 fw-bold mb-4">مميزات النظام</h2>
                    <p class="lead text-muted">
                        نقدم لك حلولاً متطورة وشاملة لإدارة الفواتير الإلكترونية بكفاءة عالية ومطابقة كاملة للمعايير المحلية والدولية
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon primary text-white">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">التوافق الكامل</h4>
                        <p class="text-muted">
                            متوافق 100% مع متطلبات ضريبة القيمة المضافة والسلطات الضريبية في الأردن ودول المنطقة
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon success text-white">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="fw-bold mb-3">تقارير متقدمة</h4>
                        <p class="text-muted">
                            تقارير تحليلية شاملة ولوحة تحكم ذكية لمراقبة الأداء المالي واتخاذ القرارات الصحيحة
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon warning text-white">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <h4 class="fw-bold mb-3">تخزين سحابي آمن</h4>
                        <p class="text-muted">
                            حفظ آمن لجميع بياناتك في السحابة مع إمكانية الوصول من أي مكان وفي أي وقت
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-icon accent text-white">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">واجهة متجاوبة</h4>
                        <p class="text-muted">
                            واجهة مستخدم حديثة ومتجاوبة تعمل بسلاسة على جميع الأجهزة والشاشات
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-card">
                        <div class="feature-icon primary text-white">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">تزامن تلقائي</h4>
                        <p class="text-muted">
                            تزامن تلقائي مع الأنظمة المحاسبية الأخرى وإرسال الفواتير للسلطات الضريبية
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-card">
                        <div class="feature-icon success text-white">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4 class="fw-bold mb-3">دعم فني 24/7</h4>
                        <p class="text-muted">
                            فريق دعم فني متخصص متاح على مدار الساعة لمساعدتك وحل أي مشكلة تواجهها
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h2 class="display-4 fw-bold mb-4">عن نظام JO Invoicing</h2>
                    <p class="lead mb-4">
                        نظام JO Invoicing هو حل شامل ومتطور لإدارة الفواتير الإلكترونية، مصمم خصيصاً لتلبية احتياجات الشركات في المنطقة العربية.
                    </p>
                    <div class="row">
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle text-success fs-5 me-3"></i>
                                <span>سهولة الاستخدام</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle text-success fs-5 me-3"></i>
                                <span>أمان عالي</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle text-success fs-5 me-3"></i>
                                <span>تحديثات مستمرة</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle text-success fs-5 me-3"></i>
                                <span>دعم فني ممتاز</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('register.ar') }}" class="btn btn-primary-custom btn-custom">
                        <i class="fas fa-arrow-left me-2"></i>
                        ابدأ رحلتك معنا
                    </a>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="text-center">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 400'%3E%3Cdefs%3E%3ClinearGradient id='chart-bg' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%2338A169'/%3E%3Cstop offset='100%25' style='stop-color:%2348BB78'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='600' height='400' fill='url(%23chart-bg)' opacity='0.1' rx='20'/%3E%3Cg transform='translate(50,50)'%3E%3Crect x='0' y='0' width='500' height='300' fill='white' rx='15' opacity='0.95'/%3E%3Ctext x='250' y='30' text-anchor='middle' font-family='Arial' font-size='18' font-weight='bold' fill='%232d3748'%3E لوحة التحكم الرئيسية%3C/text%3E%3Crect x='30' y='60' width='120' height='80' rx='10' fill='%232C5282' opacity='0.8'/%3E%3Ctext x='90' y='85' text-anchor='middle' font-family='Arial' font-size='12' fill='white'%3E إجمالي الفواتير%3C/text%3E%3Ctext x='90' y='120' text-anchor='middle' font-family='Arial' font-size='16' font-weight='bold' fill='white'%3E 1,234%3C/text%3E%3Crect x='180' y='60' width='120' height='80' rx='10' fill='%2338A169' opacity='0.8'/%3E%3Ctext x='240' y='85' text-anchor='middle' font-family='Arial' font-size='12' fill='white'%3E الإيرادات%3C/text%3E%3Ctext x='240' y='120' text-anchor='middle' font-family='Arial' font-size='16' font-weight='bold' fill='white'%3E 45,678 د.أ%3C/text%3E%3Crect x='330' y='60' width='120' height='80' rx='10' fill='%23805AD5' opacity='0.8'/%3E%3Ctext x='390' y='85' text-anchor='middle' font-family='Arial' font-size='12' fill='white'%3E العملاء%3C/text%3E%3Ctext x='390' y='120' text-anchor='middle' font-family='Arial' font-size='16' font-weight='bold' fill='white'%3E 156%3C/text%3E%3Cpath d='M 30 180 Q 100 160 150 170 T 250 160 T 350 150 T 450 140' stroke='%232C5282' stroke-width='3' fill='none'/%3E%3Ccircle cx='450' cy='140' r='4' fill='%232C5282'/%3E%3Ctext x='30' y='270' font-family='Arial' font-size='14' fill='%234a5568'%3E الأشهر الأخيرة%3C/text%3E%3C/g%3E%3C/svg%3E"
                             alt="لوحة التحكم"
                             class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                    <h2 class="display-4 fw-bold mb-4">ابدأ رحلتك في الفوترة الإلكترونية اليوم</h2>
                    <p class="lead mb-4">
                        انضم إلى المئات من الشركات التي تثق في نظامنا لإدارة فواتيرها الإلكترونية بكفاءة ومطابقة كاملة للمعايير
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('register.ar') }}" class="btn btn-light btn-custom text-success fw-bold">
                            <i class="fas fa-user-plus me-2"></i>
                            إنشاء حساب جديد
                        </a>
                        <a href="#contact" class="btn btn-outline-light btn-custom">
                            <i class="fas fa-phone me-2"></i>
                            تواصل معنا
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                    <h2 class="display-4 fw-bold mb-4">تواصل معنا</h2>
                    <p class="lead text-muted">
                        فريقنا جاهز لمساعدتك في بدء رحلتك مع نظام الفوترة الإلكترونية المتقدم
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-center p-4">
                        <div class="feature-icon primary text-white mx-auto mb-3">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5 class="fw-bold">هاتف</h5>
                        <p class="text-muted">+962 6 123 4567</p>
                    </div>
                </div>

                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-center p-4">
                        <div class="feature-icon success text-white mx-auto mb-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5 class="fw-bold">بريد إلكتروني</h5>
                        <p class="text-muted">info@jo-invoicing.com</p>
                    </div>
                </div>

                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-center p-4">
                        <div class="feature-icon accent text-white mx-auto mb-3">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5 class="fw-bold">العنوان</h5>
                        <p class="text-muted">عمان، الأردن</p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="row mt-5">
                <div class="col-lg-8 mx-auto" data-aos="fade-up">
                    <div class="card dashboard-card">
                        <div class="card-body p-4">
                            <h4 class="text-center mb-4">أرسل لنا رسالة</h4>
                            <form action="#" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">الاسم الكامل</label>
                                        <input type="text" class="form-control" placeholder="أدخل اسمك الكامل" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">البريد الإلكتروني</label>
                                        <input type="email" class="form-control" placeholder="أدخل بريدك الإلكتروني" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">رقم الهاتف</label>
                                        <input type="tel" class="form-control" placeholder="أدخل رقم هاتفك">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">اسم الشركة</label>
                                        <input type="text" class="form-control" placeholder="أدخل اسم شركتك">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">الموضوع</label>
                                    <select class="form-control">
                                        <option>استفسار عام</option>
                                        <option>طلب عرض سعر</option>
                                        <option>دعم فني</option>
                                        <option>شراكة تجارية</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">الرسالة</label>
                                    <textarea class="form-control" rows="5" placeholder="اكتب رسالتك هنا..." required></textarea>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary-custom btn-custom">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        إرسال الرسالة
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>
                        <i class="fas fa-file-invoice me-2"></i>
                        JO Invoicing
                    </h5>
                    <p class="text-muted">
                        نظام الفوترة الإلكترونية المتقدم والمتوافق مع جميع المعايير المحلية والدولية لإدارة فواتيرك بكفاءة ومرونة عالية.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-light fs-4"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light fs-4"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light fs-4"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-light fs-4"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>روابط سريعة</h5>
                    <a href="#home" class="footer-link">الرئيسية</a>
                    <a href="#features" class="footer-link">المميزات</a>
                    <a href="#about" class="footer-link">عن النظام</a>
                    <a href="#contact" class="footer-link">اتصل بنا</a>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>الخدمات</h5>
                    <a href="#" class="footer-link">الفوترة الإلكترونية</a>
                    <a href="#" class="footer-link">التقارير المتقدمة</a>
                    <a href="#" class="footer-link">التزامن التلقائي</a>
                    <a href="#" class="footer-link">الدعم الفني</a>
                </div>

                <div class="col-lg-3 mb-4">
                    <h5>معلومات الاتصال</h5>
                    <p class="footer-link">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        عمان، الأردن
                    </p>
                    <p class="footer-link">
                        <i class="fas fa-phone me-2"></i>
                        +962 6 123 4567
                    </p>
                    <p class="footer-link">
                        <i class="fas fa-envelope me-2"></i>
                        info@jo-invoicing.com
                    </p>
                </div>
            </div>

            <hr class="my-4 opacity-25">

            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        © 2024 JO Invoicing. جميع الحقوق محفوظة.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="footer-link d-inline me-3">سياسة الخصوصية</a>
                    <a href="#" class="footer-link d-inline">شروط الاستخدام</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-custom');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });

        // Counter animation
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = parseInt(counter.innerText.replace(/[^0-9]/g, ''));
                const increment = target / 100;
                let current = 0;

                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }

                    if (counter.innerText.includes('%')) {
                        counter.innerText = Math.floor(current) + '%';
                    } else if (counter.innerText.includes('+')) {
                        counter.innerText = Math.floor(current).toLocaleString() + '+';
                    } else if (counter.innerText.includes('/')) {
                        counter.innerText = '24/7';
                    } else {
                        counter.innerText = Math.floor(current).toLocaleString();
                    }
                }, 20);
            });
        }

        // Trigger counter animation when stats section is in view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        });

        observer.observe(document.querySelector('.stats-section'));
    </script>
</body>
</html>
