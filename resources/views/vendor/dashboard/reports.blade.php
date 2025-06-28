@extends('layouts.vendor')

@section('title', 'Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Reports & Analytics</h1>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('vendor.export.revenue') }}">Revenue Report</a></li>
                        <li><a class="dropdown-item" href="{{ route('vendor.export.invoices') }}">Invoice Report</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('vendor.export.index') }}">Custom Export</a></li>
                    </ul>
                </div>
            </div>

            <!-- Period Selector -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('vendor.reports.index') }}">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Report Period</label>
                                <select name="period" class="form-select">
                                    <option value="weekly" {{ $period == 'weekly' ? 'selected' : '' }}>Last 7 Days</option>
                                    <option value="monthly" {{ $period == 'monthly' ? 'selected' : '' }}>Last 30 Days</option>
                                    <option value="quarterly" {{ $period == 'quarterly' ? 'selected' : '' }}>Last 3 Months</option>
                                    <option value="yearly" {{ $period == 'yearly' ? 'selected' : '' }}>Last 12 Months</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Update Report</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <!-- Revenue Report -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Revenue Analysis</h5>
                        </div>
                        <div class="card-body">
                            @if(isset($reports['revenue']) && $reports['revenue']->count() > 0)
                            <canvas id="revenueChart" height="200"></canvas>
                            @else
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No revenue data available for the selected period.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Invoice Status Distribution -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Invoice Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            @if(isset($reports['invoices']) && $reports['invoices']->count() > 0)
                            <canvas id="statusChart" height="200"></canvas>
                            @else
                            <div class="text-center py-4">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No invoice data available for the selected period.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Top Customers -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Customers</h5>
                        </div>
                        <div class="card-body">
                            @if(isset($reports['customers']) && $reports['customers']->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th class="text-end">Invoices</th>
                                            <th class="text-end">Total Revenue</th>
                                            <th class="text-end">Avg. Invoice</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($reports['customers'] as $customer)
                                        <tr>
                                            <td>{{ $customer->customer_name ?? 'Unknown Customer' }}</td>
                                            <td class="text-end">{{ $customer->invoice_count ?? 0 }}</td>
                                            <td class="text-end">${{ number_format($customer->total_revenue ?? 0, 2) }}</td>
                                            <td class="text-end">${{ number_format(($customer->total_revenue ?? 0) / max($customer->invoice_count ?? 1, 1), 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No customer data available for the selected period.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Integration Performance -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Integration Performance</h5>
                        </div>
                        <div class="card-body">
                            @if(isset($reports['integrations']) && $reports['integrations']->count() > 0)
                            @foreach($reports['integrations'] as $integration)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <strong>{{ $integration->vendor_name ?? 'Unknown' }}</strong>
                                    <br><small class="text-muted">Last sync: {{ $integration->last_sync_at ? $integration->last_sync_at->diffForHumans() : 'Never' }}</small>
                                </div>
                                <div>
                                    <span class="badge
                                        @if($integration->status == 'active') bg-success
                                        @elseif($integration->status == 'error') bg-danger
                                        @else bg-warning
                                        @endif
                                    ">
                                        {{ ucfirst($integration->status) }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                            @else
                            <div class="text-center py-4">
                                <i class="fas fa-plug fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No integrations configured.</p>
                                <a href="{{ route('vendor.integrations.index') }}" class="btn btn-primary btn-sm">Setup Integration</a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Period Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="border-end">
                                        <h4 class="text-primary">{{ $reports['invoices']->count() ?? 0 }}</h4>
                                        <p class="text-muted mb-0">Total Invoices</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border-end">
                                        <h4 class="text-success">${{ number_format($reports['revenue']->sum('total_amount') ?? 0, 2) }}</h4>
                                        <p class="text-muted mb-0">Total Revenue</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border-end">
                                        <h4 class="text-info">${{ number_format(($reports['revenue']->sum('total_amount') ?? 0) / max($reports['invoices']->count() ?? 1, 1), 2) }}</h4>
                                        <p class="text-muted mb-0">Avg. Invoice Value</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <h4 class="text-warning">{{ $reports['customers']->count() ?? 0 }}</h4>
                                    <p class="text-muted mb-0">Unique Customers</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
@if(isset($reports['revenue']) && $reports['revenue']->count() > 0)
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: @json($reports['revenue']->pluck('period')),
        datasets: [{
            label: 'Revenue',
            data: @json($reports['revenue']->pluck('total_amount')),
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
@endif

// Status Chart
@if(isset($reports['invoices']) && $reports['invoices']->count() > 0)
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusData = @json($reports['invoices']->groupBy('status')->map->count());
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData),
        datasets: [{
            data: Object.values(statusData),
            backgroundColor: [
                '#28a745', // success/paid
                '#ffc107', // warning/pending
                '#dc3545', // danger/overdue
                '#6c757d'  // secondary/draft
            ]
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
@endif
</script>
@endsection
