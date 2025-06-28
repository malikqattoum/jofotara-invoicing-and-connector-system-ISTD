@extends('layouts.app')

@section('title', 'Vendor Dashboard - InvoiceQ Style E-Invoicing System')

@push('styles')
<style>
    .dashboard-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        transition: all 0.3s ease;
    }
    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .metric-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .metric-card.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .metric-card.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .metric-card.info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
    .invoice-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .recent-invoice-card {
        border-left: 4px solid #007bff;
        background: #f8f9fa;
    }
    .recent-invoice-card.submitted {
        border-left-color: #28a745;
    }
    .recent-invoice-card.rejected {
        border-left-color: #dc3545;
    }
    .recent-invoice-card.draft {
        border-left-color: #ffc107;
    }
    .action-btn {
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .header-actions {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 1rem;
        color: white;
        margin-bottom: 2rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Enhanced Header with InvoiceQ-style Features -->
    <div class="header-actions">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-tachometer-alt"></i> E-Invoicing Dashboard
                </h2>
                <p class="mb-0 opacity-75">Complete control over invoices & payments anytime, anywhere.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light btn-sm action-btn" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <a href="#" class="btn btn-warning btn-sm action-btn">
                    <i class="fas fa-plus"></i> New Invoice
                </a>
                <button class="btn btn-info btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#quickActionsModal">
                    <i class="fas fa-bolt"></i> Quick Actions
                </button>
            </div>
        </div>
    </div>

    <!-- Key Metrics Cards - InvoiceQ Style -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card metric-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold">
                                {{ number_format($stats['total_revenue'] ?? 0, 2) }} JOD
                            </div>
                            <div class="mt-2 small">
                                <i class="fas fa-arrow-up"></i> 12.5% from last month
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card metric-card success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Invoices</div>
                            <div class="h5 mb-0 font-weight-bold">{{ $stats['total_invoices'] ?? 0 }}</div>
                            <div class="mt-2 small">
                                <span class="text-success">{{ $stats['submitted_invoices'] ?? 0 }}</span> submitted
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card metric-card info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Monthly Revenue</div>
                            <div class="h5 mb-0 font-weight-bold">
                                {{ number_format($stats['monthly_revenue'] ?? 0, 2) }} JOD
                            </div>
                            <div class="mt-2 small">This month</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card metric-card warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Compliance Rate</div>
                            <div class="h5 mb-0 font-weight-bold">
                                {{ number_format(100 - ($stats['rejection_rate'] ?? 0), 1) }}%
                            </div>
                            <div class="mt-2 small">
                                {{ $stats['rejected_invoices'] ?? 0 }} rejections
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shield-alt fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics Row -->
    <div class="row mb-4">
        <!-- Revenue Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card dashboard-card">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Revenue Analytics
                    </h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Last 6 Months
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Last 3 Months</a></li>
                            <li><a class="dropdown-item" href="#">Last 6 Months</a></li>
                            <li><a class="dropdown-item" href="#">Last Year</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Status Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card dashboard-card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Invoice Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success">■ Submitted</span>
                            <strong>{{ $stats['submitted_invoices'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-warning">■ Draft</span>
                            <strong>{{ $stats['draft_invoices'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-danger">■ Rejected</span>
                            <strong>{{ $stats['rejected_invoices'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Invoices & Quick Actions -->
    <div class="row">
        <!-- Recent Invoices -->
        <div class="col-xl-8">
            <div class="card dashboard-card">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock"></i> Recent Invoices
                    </h6>
                    <a href="#" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    @if(!empty($recentInvoices) && count($recentInvoices) > 0)
                        @foreach($recentInvoices as $invoice)
                        <div class="recent-invoice-card {{ $invoice->status }} p-3 mb-2 rounded">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong>{{ $invoice->invoice_number }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $invoice->invoice_date->format('M d, Y') }}</small>
                                </div>
                                <div class="col-md-4">
                                    <div>{{ $invoice->customer_name }}</div>
                                    <small class="text-muted">{{ $invoice->customer_tax_number }}</small>
                                </div>
                                <div class="col-md-2 text-end">
                                    <strong>{{ number_format($invoice->total_amount, 2) }} JOD</strong>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge invoice-badge
                                        @if($invoice->status === 'submitted') badge-success
                                        @elseif($invoice->status === 'rejected') badge-danger
                                        @else badge-warning @endif">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </div>
                                <div class="col-md-1">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-eye"></i> View</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-download"></i> Download</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-print"></i> Print</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No invoices yet</h5>
                            <p class="text-muted">Create your first invoice to get started</p>
                            <a href="#" class="btn btn-primary">Create Invoice</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions & Integrations -->
        <div class="col-xl-4">
            <div class="card dashboard-card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Create New Invoice
                        </button>
                        <button class="btn btn-info btn-sm">
                            <i class="fas fa-sync"></i> Sync All Integrations
                        </button>
                        <button class="btn btn-warning btn-sm">
                            <i class="fas fa-file-export"></i> Export Reports
                        </button>
                        <button class="btn btn-secondary btn-sm">
                            <i class="fas fa-cog"></i> Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Integration Status -->
            <div class="card dashboard-card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-plug"></i> Integration Status
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($integrations) && count($integrations) > 0)
                        @foreach($integrations as $integration)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>{{ $integration->name ?? 'ZATCA' }}</strong>
                                <br>
                                <small class="text-muted">{{ $integration->vendor_type ?? 'zatca' }}</small>
                            </div>
                            <span class="badge badge-{{ $integration->is_active ? 'success' : 'danger' }}">
                                {{ $integration->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-unlink fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No integrations configured</p>
                            <a href="#" class="btn btn-sm btn-primary mt-2">Setup Integration</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Modal -->
<div class="modal fade" id="quickActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <button class="btn btn-outline-primary w-100">
                            <i class="fas fa-plus fa-2x mb-2"></i>
                            <br>New Invoice
                        </button>
                    </div>
                    <div class="col-6 mb-3">
                        <button class="btn btn-outline-info w-100">
                            <i class="fas fa-sync fa-2x mb-2"></i>
                            <br>Sync Data
                        </button>
                    </div>
                    <div class="col-6 mb-3">
                        <button class="btn btn-outline-success w-100">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i>
                            <br>View Reports
                        </button>
                    </div>
                    <div class="col-6 mb-3">
                        <button class="btn btn-outline-warning w-100">
                            <i class="fas fa-download fa-2x mb-2"></i>
                            <br>Export Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Revenue (JOD)',
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
                beginAtZero: true,
                grid: {
                    display: false
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Submitted', 'Draft', 'Rejected'],
        datasets: [{
            data: [
                {{ $stats['submitted_invoices'] ?? 0 }},
                {{ $stats['draft_invoices'] ?? 0 }},
                {{ $stats['rejected_invoices'] ?? 0 }}
            ],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
            borderWidth: 0
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

// Auto-refresh dashboard every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>
@endpush
