@extends('layouts.vendor')

@section('title', 'Integration Logs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Integration Logs</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('vendor.dashboard.index') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('vendor.integrations.index') }}">Integrations</a></li>
                            <li class="breadcrumb-item active">Logs</li>
                        </ol>
                    </nav>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" onclick="refreshLogs()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="exportLogs('csv')">Export as CSV</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportLogs('json')">Export as JSON</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="clearOldLogs()">Clear Old Logs</a></li>
                    </ul>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('vendor.integration.logs') }}" id="filterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Integration</label>
                                <select name="integration" class="form-select">
                                    <option value="">All Integrations</option>
                                    <option value="quickbooks" {{ request('integration') == 'quickbooks' ? 'selected' : '' }}>QuickBooks</option>
                                    <option value="xero" {{ request('integration') == 'xero' ? 'selected' : '' }}>Xero</option>
                                    <option value="shopify" {{ request('integration') == 'shopify' ? 'selected' : '' }}>Shopify</option>
                                    <option value="square" {{ request('integration') == 'square' ? 'selected' : '' }}>Square</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                                    <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Error</option>
                                    <option value="warning" {{ request('status') == 'warning' ? 'selected' : '' }}>Warning</option>
                                    <option value="info" {{ request('status') == 'info' ? 'selected' : '' }}>Info</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date Range</label>
                                <select name="date_range" class="form-select">
                                    <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                                    <option value="week" {{ request('date_range', 'week') == 'week' ? 'selected' : '' }}>Last 7 Days</option>
                                    <option value="month" {{ request('date_range') == 'month' ? 'selected' : '' }}>Last 30 Days</option>
                                    <option value="all" {{ request('date_range') == 'all' ? 'selected' : '' }}>All Time</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Log Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['success'] ?? 0 }}</h4>
                                    <p class="card-text">Successful Operations</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['error'] ?? 0 }}</h4>
                                    <p class="card-text">Errors</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['warning'] ?? 0 }}</h4>
                                    <p class="card-text">Warnings</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['total'] ?? 0 }}</h4>
                                    <p class="card-text">Total Logs</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Integration</th>
                                    <th>Operation</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sample logs - in real implementation, this would come from the database -->
                                <tr>
                                    <td>
                                        <small class="text-muted">{{ now()->subMinutes(5)->format('M d, Y H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fab fa-quickbooks fa-lg text-primary me-2"></i>
                                            QuickBooks
                                        </div>
                                    </td>
                                    <td>Customer Sync</td>
                                    <td>
                                        <span class="badge bg-success">Success</span>
                                    </td>
                                    <td>Successfully synced 15 customers</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewLogDetails(1)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <small class="text-muted">{{ now()->subMinutes(15)->format('M d, Y H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fab fa-shopify fa-lg text-success me-2"></i>
                                            Shopify
                                        </div>
                                    </td>
                                    <td>Order Import</td>
                                    <td>
                                        <span class="badge bg-success">Success</span>
                                    </td>
                                    <td>Imported 8 new orders</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewLogDetails(2)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <small class="text-muted">{{ now()->subHour()->format('M d, Y H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calculator fa-lg text-info me-2"></i>
                                            Xero
                                        </div>
                                    </td>
                                    <td>Connection Test</td>
                                    <td>
                                        <span class="badge bg-danger">Error</span>
                                    </td>
                                    <td>Authentication failed: Invalid client credentials</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewLogDetails(3)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <small class="text-muted">{{ now()->subHours(2)->format('M d, Y H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-square fa-lg text-warning me-2"></i>
                                            Square
                                        </div>
                                    </td>
                                    <td>Transaction Sync</td>
                                    <td>
                                        <span class="badge bg-warning">Warning</span>
                                    </td>
                                    <td>Some transactions were skipped due to data quality issues</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewLogDetails(4)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <small class="text-muted">{{ now()->subHours(4)->format('M d, Y H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fab fa-quickbooks fa-lg text-primary me-2"></i>
                                            QuickBooks
                                        </div>
                                    </td>
                                    <td>Invoice Export</td>
                                    <td>
                                        <span class="badge bg-success">Success</span>
                                    </td>
                                    <td>Exported 23 invoices to QuickBooks</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewLogDetails(5)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Showing 1 to 5 of 50 results
                        </div>
                        <nav aria-label="Log pagination">
                            <ul class="pagination">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                </li>
                                <li class="page-item active">
                                    <a class="page-link" href="#">1</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">2</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">3</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="downloadLogDetails()">Download Details</button>
            </div>
        </div>
    </div>
</div>

<script>
function refreshLogs() {
    window.location.reload();
}

function exportLogs(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    window.open('/vendor/integrations/logs/export?' + params.toString());
}

function clearOldLogs() {
    if (confirm('Are you sure you want to clear logs older than 30 days? This action cannot be undone.')) {
        fetch('/vendor/integrations/logs/clear-old', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Old logs cleared successfully!');
                window.location.reload();
            } else {
                alert('Failed to clear logs: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while clearing logs.');
        });
    }
}

function viewLogDetails(logId) {
    // Sample log details - in real implementation, this would fetch from server
    const sampleDetails = {
        1: {
            id: 1,
            integration: 'QuickBooks',
            operation: 'Customer Sync',
            status: 'success',
            message: 'Successfully synced 15 customers',
            timestamp: '2024-01-15 10:30:25',
            duration: '2.3 seconds',
            details: {
                'Customers Added': 3,
                'Customers Updated': 12,
                'Customers Skipped': 0,
                'API Calls Made': 15,
                'Data Transferred': '45.2 KB'
            },
            stack_trace: null
        },
        3: {
            id: 3,
            integration: 'Xero',
            operation: 'Connection Test',
            status: 'error',
            message: 'Authentication failed: Invalid client credentials',
            timestamp: '2024-01-15 09:30:25',
            duration: '1.2 seconds',
            details: {
                'Error Code': 'AUTH_001',
                'HTTP Status': 401,
                'Response': 'Unauthorized access',
                'Endpoint': '/api/v1/auth/token'
            },
            stack_trace: 'XeroException: Invalid client credentials at line 45 in XeroConnector.php'
        }
    };

    const logDetails = sampleDetails[logId] || sampleDetails[1];

    const statusBadge = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'warning': 'bg-warning',
        'info': 'bg-info'
    };

    let detailsHtml = `
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Integration:</strong> ${logDetails.integration}
            </div>
            <div class="col-md-6">
                <strong>Operation:</strong> ${logDetails.operation}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Status:</strong> <span class="badge ${statusBadge[logDetails.status]}">${logDetails.status.toUpperCase()}</span>
            </div>
            <div class="col-md-6">
                <strong>Duration:</strong> ${logDetails.duration}
            </div>
        </div>
        <div class="mb-3">
            <strong>Timestamp:</strong> ${logDetails.timestamp}
        </div>
        <div class="mb-3">
            <strong>Message:</strong>
            <div class="border rounded p-2 bg-light">${logDetails.message}</div>
        </div>
    `;

    if (logDetails.details) {
        detailsHtml += `
            <div class="mb-3">
                <strong>Details:</strong>
                <div class="border rounded p-2 bg-light">
        `;
        for (const [key, value] of Object.entries(logDetails.details)) {
            detailsHtml += `<div><strong>${key}:</strong> ${value}</div>`;
        }
        detailsHtml += `</div></div>`;
    }

    if (logDetails.stack_trace) {
        detailsHtml += `
            <div class="mb-3">
                <strong>Stack Trace:</strong>
                <div class="border rounded p-2 bg-light">
                    <pre style="white-space: pre-wrap; font-size: 0.875rem;">${logDetails.stack_trace}</pre>
                </div>
            </div>
        `;
    }

    document.getElementById('logDetailsContent').innerHTML = detailsHtml;
    new bootstrap.Modal(document.getElementById('logDetailsModal')).show();
}

function downloadLogDetails() {
    // Implement log details download
    console.log('Downloading log details...');
}

// Auto-refresh logs every 30 seconds
setInterval(function() {
    if (document.querySelector('[name="date_range"]').value === 'today') {
        // Only auto-refresh if viewing today's logs
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('auto_refresh', '1');

        fetch(currentUrl.toString())
            .then(response => response.text())
            .then(html => {
                // Update only the table content
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTableBody = doc.querySelector('tbody');
                if (newTableBody) {
                    document.querySelector('tbody').innerHTML = newTableBody.innerHTML;
                }
            })
            .catch(error => console.error('Auto-refresh failed:', error));
    }
}, 30000);
</script>
@endsection
