<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض توضيحي - نظام الفوترة الإلكترونية</title>

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }

        .demo-header {
            background: linear-gradient(135deg, #2C5282 0%, #3182CE 50%, #805AD5 100%);
            color: white;
            padding: 2rem 0;
        }

        .demo-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }

        .invoice-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-right: 4px solid #28a745;
        }

        .invoice-item.rejected {
            border-right-color: #dc3545;
        }

        .invoice-item.draft {
            border-right-color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="demo-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-file-invoice me-2"></i> عرض توضيحي - نظام الفوترة الإلكترونية</h1>
                    <p class="mb-0">تجربة مبسطة لواجهة النظام وإمكانياته المتقدمة</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('landing') }}" class="btn btn-light">
                        <i class="fas fa-arrow-right me-2"></i>
                        العودة للصفحة الرئيسية
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="metric-card">
                    <h3>15,750 د.أ</h3>
                    <p class="mb-0">إجمالي الإيرادات</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="metric-card" style="background: linear-gradient(135deg, #38A169 0%, #48BB78 100%);">
                    <h3>156</h3>
                    <p class="mb-0">إجمالي الفواتير</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="metric-card" style="background: linear-gradient(135deg, #D69E2E 0%, #ECC94B 100%);">
                    <h3>12</h3>
                    <p class="mb-0">فواتير مسودة</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="metric-card" style="background: linear-gradient(135deg, #E53E3E 0%, #FC8181 100%);">
                    <h3>3</h3>
                    <p class="mb-0">فواتير مرفوضة</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Invoices -->
            <div class="col-lg-8">
                <div class="demo-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i> الفواتير الأخيرة</h5>
                    </div>
                    <div class="card-body">
                        <div class="invoice-item">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong>INV-001</strong><br>
                                    <small class="text-muted">2024/01/15</small>
                                </div>
                                <div class="col-md-4">
                                    <div>شركة ABC للتجارة</div>
                                    <small class="text-muted">TAX123456789</small>
                                </div>
                                <div class="col-md-3 text-end">
                                    <strong>1,250.00 د.أ</strong>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-success">مقبولة</span>
                                </div>
                            </div>
                        </div>

                        <div class="invoice-item draft">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong>INV-002</strong><br>
                                    <small class="text-muted">2024/01/16</small>
                                </div>
                                <div class="col-md-4">
                                    <div>شركة XYZ للخدمات</div>
                                    <small class="text-muted">TAX987654321</small>
                                </div>
                                <div class="col-md-3 text-end">
                                    <strong>2,750.00 د.أ</strong>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-warning">مسودة</span>
                                </div>
                            </div>
                        </div>

                        <div class="invoice-item rejected">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong>INV-003</strong><br>
                                    <small class="text-muted">2024/01/14</small>
                                </div>
                                <div class="col-md-4">
                                    <div>المؤسسة الوطنية</div>
                                    <small class="text-muted">TAX555666777</small>
                                </div>
                                <div class="col-md-3 text-end">
                                    <strong>950.00 د.أ</strong>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-danger">مرفوضة</span>
                                </div>
                            </div>
                        </div>

                        <div class="invoice-item">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong>INV-004</strong><br>
                                    <small class="text-muted">2024/01/13</small>
                                </div>
                                <div class="col-md-4">
                                    <div>شركة التقنية المتقدمة</div>
                                    <small class="text-muted">TAX111222333</small>
                                </div>
                                <div class="col-md-3 text-end">
                                    <strong>3,200.00 د.أ</strong>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-success">مقبولة</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="demo-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i> إجراءات سريعة</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-success">
                                <i class="fas fa-plus me-2"></i> إنشاء فاتورة جديدة
                            </button>
                            <button class="btn btn-info">
                                <i class="fas fa-sync me-2"></i> مزامنة البيانات
                            </button>
                            <button class="btn btn-warning">
                                <i class="fas fa-file-export me-2"></i> تصدير التقارير
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Integration Status -->
                <div class="demo-card mt-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-plug me-2"></i> حالة التكامل</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>ZATCA</strong><br>
                                <small class="text-muted">هيئة الزكاة والضريبة</small>
                            </div>
                            <span class="badge bg-success">متصل</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>البنك المركزي</strong><br>
                                <small class="text-muted">نظام المدفوعات</small>
                            </div>
                            <span class="badge bg-success">متصل</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>SAP</strong><br>
                                <small class="text-muted">النظام المحاسبي</small>
                            </div>
                            <span class="badge bg-warning">في الانتظار</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="demo-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> تحليل الإيرادات</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="demo-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> توزيع الفواتير</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="row mt-5">
            <div class="col-12 text-center">
                <div class="demo-card" style="background: linear-gradient(135deg, #38A169 0%, #48BB78 100%); color: white;">
                    <div class="card-body py-5">
                        <h3>أعجبك ما رأيت؟</h3>
                        <p class="lead">ابدأ رحلتك مع نظام الفوترة الإلكترونية المتقدم اليوم</p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="{{ route('register.ar') }}" class="btn btn-light btn-lg">
                                <i class="fas fa-user-plus me-2"></i>
                                إنشاء حساب مجاني
                            </a>
                            <a href="{{ route('landing') }}" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-info-circle me-2"></i>
                                معرفة المزيد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
                datasets: [{
                    label: 'الإيرادات (د.أ)',
                    data: [5000, 7500, 6200, 8900, 11200, 9800],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['مقبولة', 'مسودة', 'مرفوضة'],
                datasets: [{
                    data: [141, 12, 3],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
