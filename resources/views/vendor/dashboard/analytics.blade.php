@extends('layouts.vendor')

@section('title', 'Analytics')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Advanced Analytics</h1>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="exportAnalytics('pdf')">Export as PDF</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportAnalytics('excel')">Export as Excel</a></li>
                    </ul>
                </div>
            </div>

            <!-- KPI Cards -->
            @if(isset($analytics['kpis']))
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-gradient-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">${{ number_format($analytics['kpis']['total_revenue'] ?? 0, 0) }}</h4>
                                    <p class="card-text">Total Revenue</p>
                                    <small class="text-white-75">
                                        <i class="fas fa-arrow-up"></i>
                                        {{ $analytics['kpis']['revenue_growth'] ?? 0 }}% vs last period
                                    </small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-gradient-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $analytics['kpis']['total_invoices'] ?? 0 }}</h4>
                                    <p class="card-text">Total Invoices</p>
                                    <small class="text-white-75">
                                        <i class="fas fa-arrow-up"></i>
                                        {{ $analytics['kpis']['invoice_growth'] ?? 0 }}% vs last period
                                    </small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file-invoice fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-gradient-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">${{ number_format($analytics['kpis']['avg_invoice_value'] ?? 0, 0) }}</h4>
                                    <p class="card-text">Avg Invoice Value</p>
                                    <small class="text-white-75">
                                        <i class="fas {{ ($analytics['kpis']['avg_value_trend'] ?? 0) >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                        {{ abs($analytics['kpis']['avg_value_trend'] ?? 0) }}% vs last period
                                    </small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chart-line fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-gradient-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ number_format($analytics['kpis']['payment_rate'] ?? 0, 1) }}%</h4>
                                    <p class="card-text">Payment Rate</p>
                                    <small class="text-white-75">
                                        <i class="fas {{ ($analytics['kpis']['payment_rate_trend'] ?? 0) >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                        {{ abs($analytics['kpis']['payment_rate_trend'] ?? 0) }}% vs last period
                                    </small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-percentage fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="row">
                <!-- Revenue Trends -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Revenue Trends</h5>
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="trendPeriod" id="trend7" autocomplete="off" checked>
                                <label class="btn btn-outline-primary" for="trend7">7D</label>

                                <input type="radio" class="btn-check" name="trendPeriod" id="trend30" autocomplete="off">
                                <label class="btn btn-outline-primary" for="trend30">30D</label>

                                <input type="radio" class="btn-check" name="trendPeriod" id="trend90" autocomplete="off">
                                <label class="btn btn-outline-primary" for="trend90">90D</label>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueTrendChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Performance Metrics</h5>
                        </div>
                        <div class="card-body">
                            @if(isset($analytics['kpis']))
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Collection Rate</span>
                                    <span class="fw-bold">{{ number_format($analytics['kpis']['collection_rate'] ?? 0, 1) }}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $analytics['kpis']['collection_rate'] ?? 0 }}%"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Invoice Approval Rate</span>
                                    <span class="fw-bold">{{ number_format($analytics['kpis']['approval_rate'] ?? 0, 1) }}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $analytics['kpis']['approval_rate'] ?? 0 }}%"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Integration Health</span>
                                    <span class="fw-bold">{{ number_format($analytics['kpis']['integration_health'] ?? 0, 1) }}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" style="width: {{ $analytics['kpis']['integration_health'] ?? 0 }}%"></div>
                                </div>
                            </div>

                            <div class="mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Data Quality Score</span>
                                    <span class="fw-bold">{{ number_format($analytics['kpis']['data_quality'] ?? 0, 1) }}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" style="width: {{ $analytics['kpis']['data_quality'] ?? 0 }}%"></div>
                                </div>
                            </div>
                            @else
                            <div class="text-center py-4">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Performance metrics will appear here once you have more data.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Customer Segmentation -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Customer Segmentation</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="customerSegmentChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Invoice Lifecycle -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Invoice Lifecycle</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="invoiceLifecycleChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insights Section -->
            @if(isset($analytics['insights']) && count($analytics['insights']) > 0)
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">AI-Powered Insights</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($analytics['insights'] as $insight)
                                <div class="col-lg-4 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="rounded-circle p-2
                                                    @if($insight['type'] == 'positive') bg-success-subtle text-success
                                                    @elseif($insight['type'] == 'warning') bg-warning-subtle text-warning
                                                    @else bg-info-subtle text-info
                                                    @endif
                                                ">
                                                    <i class="fas
                                                        @if($insight['type'] == 'positive') fa-thumbs-up
                                                        @elseif($insight['type'] == 'warning') fa-exclamation-triangle
                                                        @else fa-lightbulb
                                                        @endif
                                                    "></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="fw-bold">{{ $insight['title'] }}</h6>
                                                <p class="text-muted mb-2">{{ $insight['description'] }}</p>
                                                @if(isset($insight['action']))
                                                <a href="{{ $insight['action']['url'] }}" class="btn btn-sm btn-outline-primary">
                                                    {{ $insight['action']['text'] }}
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sample data - in real implementation, this would come from the analytics service
const defaultTrendsData = {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    revenue: [1200, 1900, 3000, 5000, 2300, 4200, 3200],
    invoices: [5, 8, 12, 15, 10, 18, 14]
};

// Revenue Trend Chart
const trendCtx = document.getElementById('revenueTrendChart').getContext('2d');
const trendChart = new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: defaultTrendsData.labels,
        datasets: [{
            label: 'Revenue',
            data: defaultTrendsData.revenue,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Invoices',
            data: defaultTrendsData.invoices,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false,
                }
            }
        },
        plugins: {
            legend: {
                position: 'top'
            }
        }
    }
});

// Customer Segmentation Chart
const segmentCtx = document.getElementById('customerSegmentChart').getContext('2d');
new Chart(segmentCtx, {
    type: 'doughnut',
    data: {
        labels: ['High Value', 'Regular', 'New Customers', 'At Risk'],
        datasets: [{
            data: [35, 45, 15, 5],
            backgroundColor: ['#28a745', '#007bff', '#ffc107', '#dc3545']
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

// Invoice Lifecycle Chart
const lifecycleCtx = document.getElementById('invoiceLifecycleChart').getContext('2d');
new Chart(lifecycleCtx, {
    type: 'funnel',
    data: {
        labels: ['Created', 'Submitted', 'Approved', 'Paid'],
        datasets: [{
            data: [100, 85, 75, 65],
            backgroundColor: ['#6c757d', '#ffc107', '#007bff', '#28a745']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Functions
function refreshData() {
    // Implement real-time data refresh
    console.log('Refreshing analytics data...');
    // window.location.reload();
}

function exportAnalytics(format) {
    // Implement export functionality
    console.log('Exporting analytics as:', format);
    // window.open(`/vendor/export/analytics?format=${format}`);
}

// Period change handlers
document.querySelectorAll('input[name="trendPeriod"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Update chart data based on selected period
        console.log('Period changed to:', this.id);
        // updateTrendChart(this.id);
    });
});
</script>

<style>
.bg-gradient-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
}
.bg-gradient-success {
    background: linear-gradient(45deg, #28a745, #1e7e34);
}
.bg-gradient-info {
    background: linear-gradient(45deg, #17a2b8, #117a8b);
}
.bg-gradient-warning {
    background: linear-gradient(45deg, #ffc107, #e0a800);
}
.opacity-75 {
    opacity: 0.75;
}
.text-white-75 {
    color: rgba(255, 255, 255, 0.75) !important;
}
</style>
@endsection
