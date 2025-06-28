@extends('layouts.admin')

@section('title', 'System Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt"></i> System Dashboard
        </h1>
        <div class="btn-group">
            <button class="btn btn-primary btn-sm" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <a href="{{ route('admin.settings') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </div>

    <!-- Alert Cards Row -->
    <div class="row">
        <!-- System Health -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                System Health
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['performance']['system_load'] < 0.8 ? 'Healthy' : 'High Load' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-heart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Integrations -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Active Integrations
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['integrations']['active'] }}/{{ $stats['integrations']['total'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-plug fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Alerts -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Active Alerts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['security']['active_alerts'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cache Hit Rate -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Cache Hit Rate
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        {{ number_format($stats['cache']['hit_rate'], 1) }}%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-primary" role="progressbar"
                                             style="width: {{ $stats['cache']['hit_rate'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-memory fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- System Overview -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Performance Metrics</h6>
                    <div class="dropdown no-arrow">
                        <select class="form-control form-control-sm" id="metricsTimeRange">
                            <option value="1">Last Hour</option>
                            <option value="24" selected>Last 24 Hours</option>
                            <option value="168">Last Week</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- System Components Status -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">System Components</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-sm">Database</span>
                            <span class="badge badge-success">Healthy</span>
                        </div>
                        <div class="progress progress-sm mt-1">
                            <div class="progress-bar bg-success" style="width: 95%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-sm">Cache Layer</span>
                            <span class="badge badge-success">Optimal</span>
                        </div>
                        <div class="progress progress-sm mt-1">
                            <div class="progress-bar bg-success" style="width: {{ $stats['cache']['memory_usage'] * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-sm">API Gateway</span>
                            <span class="badge badge-success">Active</span>
                        </div>
                        <div class="progress progress-sm mt-1">
                            <div class="progress-bar bg-success" style="width: 92%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-sm">Event Streaming</span>
                            <span class="badge badge-info">Running</span>
                        </div>
                        <div class="progress progress-sm mt-1">
                            <div class="progress-bar bg-info" style="width: 88%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-sm">Workflows</span>
                            <span class="badge badge-success">Active</span>
                        </div>
                        <div class="progress progress-sm mt-1">
                            <div class="progress-bar bg-success" style="width: 96%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Alerts -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Alerts</h6>
                </div>
                <div class="card-body">
                    @if($alerts->count() > 0)
                        @foreach($alerts as $alert)
                        <div class="alert alert-{{ $alert->severity === 'critical' ? 'danger' : ($alert->severity === 'warning' ? 'warning' : 'info') }} alert-dismissible fade show mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ ucfirst($alert->type) }}</strong><br>
                                    <small>{{ $alert->message }}</small>
                                </div>
                                <div class="text-right">
                                    <small class="text-muted">{{ $alert->created_at->diffForHumans() }}</small><br>
                                    <button class="btn btn-sm btn-outline-primary" onclick="resolveAlert({{ $alert->id }})">
                                        Resolve
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <h5>No Active Alerts</h5>
                            <p>System is running smoothly</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Performance Metrics -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Metrics</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Value</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentMetrics->take(10) as $metric)
                                <tr>
                                    <td>
                                        <span class="badge badge-secondary">{{ $metric->type }}</span>
                                    </td>
                                    <td>
                                        {{ number_format($metric->value, 2) }}
                                        @if(str_contains($metric->type, 'time'))
                                            ms
                                        @endif
                                    </td>
                                    <td class="text-muted">
                                        {{ $metric->recorded_at->format('H:i:s') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <a href="{{ route('admin.workflows.index') }}" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-sitemap"></i><br>
                                Workflows
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.pipelines.index') }}" class="btn btn-outline-info btn-block">
                                <i class="fas fa-stream"></i><br>
                                Data Pipelines
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.performance') }}" class="btn btn-outline-success btn-block">
                                <i class="fas fa-chart-line"></i><br>
                                Performance
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.security') }}" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-shield-alt"></i><br>
                                Security
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.cache') }}" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-memory"></i><br>
                                Cache
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.api-gateway') }}" class="btn btn-outline-dark btn-block">
                                <i class="fas fa-gateway"></i><br>
                                API Gateway
                            </a>
                        </div>
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
// Performance Chart
const ctx = document.getElementById('performanceChart').getContext('2d');
const performanceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [], // Will be populated via AJAX
        datasets: [{
            label: 'Response Time (ms)',
            data: [],
            borderColor: 'rgb(78, 115, 223)',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Real-time updates
function refreshDashboard() {
    fetch('{{ route("admin.real-time-metrics") }}')
        .then(response => response.json())
        .then(data => {
            // Update performance chart
            performanceChart.data.labels = data.performance.labels;
            performanceChart.data.datasets[0].data = data.performance.data;
            performanceChart.update();

            // Update stats cards
            updateStatsCards(data);
        })
        .catch(error => console.error('Error fetching real-time data:', error));
}

function resolveAlert(alertId) {
    fetch(`/admin/alerts/${alertId}/resolve`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function updateStatsCards(data) {
    // Implementation for updating stats cards
}

// Auto-refresh every 30 seconds
setInterval(refreshDashboard, 30000);

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    refreshDashboard();
});
</script>
@endpush
