@extends('layouts.vendor')

@section('title', 'Vendor Dashboard - Enhanced E-Invoicing System')

@section('content')
<div class="container-fluid">
    <!-- Enhanced Header with InvoiceQ-style Features -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt"></i> E-Invoicing Dashboard
                @if(isset($complianceStatus['status']))
                <span class="badge badge-{{ $complianceStatus['status'] === 'excellent' ? 'success' : ($complianceStatus['status'] === 'good' ? 'warning' : 'danger') }} ms-2">
                    {{ ucfirst(str_replace('_', ' ', $complianceStatus['status'])) }}
                </span>
                @endif
            </h1>
            <p class="text-muted">Welcome back, {{ auth()->user()->name }}! Complete control over invoices & payments anytime, anywhere.</p>
        </div>
        <div class="btn-group">
            <button class="btn btn-outline-secondary" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <a href="{{ route('vendor.invoices.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Invoice
            </a>
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#quickActionsModal">
                <i class="fas fa-bolt"></i> Quick Actions
            </button>
        </div>
    </div>

    <!-- System Alerts - InvoiceQ inspired -->
    @if(isset($alerts) && !empty($alerts))
    <div class="row mb-4">
        <div class="col-12">
            @foreach($alerts as $alert)
            <div class="alert alert-{{ $alert['type'] }} alert-dismissible fade show border-left-{{ $alert['type'] }}" role="alert">
                <i class="fas fa-{{ $alert['type'] === 'danger' ? 'exclamation-triangle' : ($alert['type'] === 'warning' ? 'exclamation-circle' : 'info-circle') }} mr-2"></i>
                <strong>{{ $alert['title'] }}:</strong> {{ $alert['message'] }}
                @if(isset($alert['action_url']))
                    <a href="{{ $alert['action_url'] }}" class="btn btn-sm btn-outline-{{ $alert['type'] }} ms-2">
                        <i class="fas fa-external-link-alt"></i> {{ $alert['action_text'] ?? 'Take Action' }}
                    </a>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Enhanced Stats Cards Row - InvoiceQ Style -->
    <div class="row">
        <!-- Total Revenue with Growth -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 hover-shadow">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Revenue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($stats['total_revenue'] ?? 0, 2) }}
                            </div>
                            <div class="text-xs {{ ($stats['monthly_growth'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="fas fa-arrow-{{ ($stats['monthly_growth'] ?? 0) >= 0 ? 'up' : 'down' }}"></i>
                                {{ abs($stats['monthly_growth'] ?? 0) }}% from last month
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collection Rate - InvoiceQ Feature -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 hover-shadow">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Collection Rate
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['collection_rate'] ?? 0 }}%
                            </div>
                            <div class="text-xs text-muted">
                                <i class="fas fa-clock"></i> Avg: {{ $stats['avg_payment_time'] ?? 0 }} days
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Amount with Alert -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 hover-shadow">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Overdue Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($stats['overdue_amount'] ?? 0, 2) }}
                            </div>
                            <div class="text-xs text-danger">
                                <i class="fas fa-exclamation-triangle"></i> {{ $stats['overdue_count'] ?? 0 }} invoices
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Score - InvoiceQ Feature -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 hover-shadow">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Compliance Score
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['compliance_score'] ?? 85 }}%
                            </div>
                            <div class="text-xs {{ ($stats['rejection_rate'] ?? 0) <= 5 ? 'text-success' : 'text-danger' }}">
                                <i class="fas fa-shield-alt"></i> {{ $stats['rejection_rate'] ?? 0 }}% rejection rate
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary KPI Row - InvoiceQ Advanced Features -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-3">
                    <h6 class="text-primary mb-1">{{ $stats['total_invoices'] ?? 0 }}</h6>
                    <small class="text-muted">Total Invoices</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-3">
                    <h6 class="text-success mb-1">{{ $stats['unique_customers'] ?? 0 }}</h6>
                    <small class="text-muted">Customers</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-3">
                    <h6 class="text-info mb-1">${{ number_format($stats['avg_invoice_value'] ?? 0, 0) }}</h6>
                    <small class="text-muted">Avg Invoice</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-3">
                    <h6 class="text-warning mb-1">${{ number_format($stats['total_tax_collected'] ?? 0, 0) }}</h6>
                    <small class="text-muted">Tax Collected</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-3">
                    <h6 class="text-secondary mb-1">{{ $stats['invoice_processing_time'] ?? 2.5 }}</h6>
                    <small class="text-muted">Avg Process Days</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-3">
                    <h6 class="text-primary mb-1">${{ number_format($stats['yearly_revenue'] ?? 0, 0) }}</h6>
                    <small class="text-muted">Year Revenue</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Charts and Analytics -->
        <div class="col-xl-8 col-lg-7">
            <!-- Revenue Chart -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Revenue Overview
                    </h6>
                    <div class="dropdown no-arrow">
                        <select class="form-control form-control-sm" id="revenueTimeRange">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                            <option value="365">Last Year</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>

            <!-- Account Receivables Aging - InvoiceQ Feature -->
            @if(isset($receivables))
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock"></i> Account Receivables Aging
                    </h6>
                    <a href="{{ route('vendor.reports.customers') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt"></i> Detailed Report
                    </a>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        @foreach($receivables['aging_buckets'] as $bucket => $amount)
                        <div class="col-md-2">
                            <div class="mb-2">
                                <div class="text-xs text-uppercase text-muted font-weight-bold">
                                    {{ str_replace('_', '-', $bucket) }}
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-{{ $bucket === 'current' ? 'success' : ($bucket === 'over_90_days' ? 'danger' : 'warning') }}">
                                    ${{ number_format($amount, 0) }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="progress mb-2" style="height: 20px;">
                        @php
                            $total = array_sum($receivables['aging_buckets']);
                            $currentPercent = $total > 0 ? ($receivables['aging_buckets']['current'] / $total) * 100 : 0;
                            $earlyPercent = $total > 0 ? (($receivables['aging_buckets']['1_30_days'] + $receivables['aging_buckets']['31_60_days']) / $total) * 100 : 0;
                            $overduePercent = 100 - $currentPercent - $earlyPercent;
                        @endphp
                        <div class="progress-bar bg-success" style="width: {{ $currentPercent }}%" title="Current: {{ round($currentPercent, 1) }}%"></div>
                        <div class="progress-bar bg-warning" style="width: {{ $earlyPercent }}%" title="Early Overdue: {{ round($earlyPercent, 1) }}%"></div>
                        <div class="progress-bar bg-danger" style="width: {{ $overduePercent }}%" title="Late Overdue: {{ round($overduePercent, 1) }}%"></div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-center">
                            <small class="text-success"><i class="fas fa-circle"></i> Current ({{ round($currentPercent, 1) }}%)</small>
                        </div>
                        <div class="col-4 text-center">
                            <small class="text-warning"><i class="fas fa-circle"></i> Early Overdue ({{ round($earlyPercent, 1) }}%)</small>
                        </div>
                        <div class="col-4 text-center">
                            <small class="text-danger"><i class="fas fa-circle"></i> Late Overdue ({{ round($overduePercent, 1) }}%)</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column - Real-time Data & Actions -->
        <div class="col-xl-4 col-lg-5">
            <!-- Payment Collection Efficiency - InvoiceQ Feature -->
            @if(isset($paymentData))
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Payment Collection
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="paymentEfficiencyChart" width="100" height="100"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-3">
                            <i class="fas fa-circle text-success"></i> On Time
                        </span>
                        <span class="mr-3">
                            <i class="fas fa-circle text-warning"></i> Late
                        </span>
                        <span class="mr-3">
                            <i class="fas fa-circle text-danger"></i> Overdue
                        </span>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-success h6">{{ $paymentData['collection_efficiency']['on_time'] ?? 0 }}</div>
                            <small class="text-muted">On Time</small>
                        </div>
                        <div class="col-4">
                            <div class="text-warning h6">{{ $paymentData['collection_efficiency']['late'] ?? 0 }}</div>
                            <small class="text-muted">Late</small>
                        </div>
                        <div class="col-4">
                            <div class="text-danger h6">{{ $paymentData['collection_efficiency']['overdue'] ?? 0 }}</div>
                            <small class="text-muted">Overdue</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Top Debtors - InvoiceQ Feature -->
            @if(isset($receivables) && !empty($receivables['top_debtors']))
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-exclamation-triangle"></i> Top Debtors
                    </h6>
                </div>
                <div class="card-body">
                    @foreach(array_slice($receivables['top_debtors'], 0, 5) as $debtor)
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ $debtor['customer_name'] }}</div>
                            <small class="text-muted">{{ $debtor['invoice_count'] }} invoices</small>
                        </div>
                        <div class="text-end">
                            <div class="font-weight-bold text-danger">${{ number_format($debtor['outstanding_amount'], 0) }}</div>
                        </div>
                    </div>
                    @endforeach
                    <a href="{{ route('vendor.reports.customers') }}" class="btn btn-outline-primary btn-sm w-100 mt-2">
                        <i class="fas fa-list"></i> View All Debtors
                    </a>
                </div>
            </div>
            @endif

            <!-- Integration Status -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-plug"></i> Integration Status
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle btn btn-sm btn-outline-secondary" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="{{ route('vendor.integrations.index') }}">Manage All</a>
                            <a class="dropdown-item" href="{{ route('vendor.integrations.create') }}">Add New</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('vendor.integration.logs') }}">View Logs</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($syncStatus))
                    <div class="row text-center mb-3">
                        <div class="col-3">
                            <div class="text-success h5">{{ $syncStatus['healthy'] ?? 0 }}</div>
                            <div class="text-xs text-uppercase text-muted">Healthy</div>
                        </div>
                        <div class="col-3">
                            <div class="text-info h5">{{ $syncStatus['syncing'] ?? 0 }}</div>
                            <div class="text-xs text-uppercase text-muted">Syncing</div>
                        </div>
                        <div class="col-3">
                            <div class="text-danger h5">{{ $syncStatus['errors'] ?? 0 }}</div>
                            <div class="text-xs text-uppercase text-muted">Errors</div>
                        </div>
                        <div class="col-3">
                            <div class="text-secondary h5">{{ $syncStatus['inactive'] ?? 0 }}</div>
                            <div class="text-xs text-uppercase text-muted">Inactive</div>
                        </div>
                    </div>
                    @endif

                    @foreach($integrations as $integration)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ ucfirst($integration->vendor) }}</h6>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> Last sync: {{ $integration->last_sync_at ? $integration->last_sync_at->diffForHumans() : 'Never' }}
                                </small>
                            </div>
                            <div>
                                @if($integration->status === 'active')
                                    @if($integration->sync_status === 'syncing')
                                        <span class="badge badge-warning">
                                            <i class="fas fa-spinner fa-spin"></i> Syncing
                                        </span>
                                    @else
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Active
                                        </span>
                                    @endif
                                @else
                                    <span class="badge badge-danger">
                                        <i class="fas fa-times"></i> Error
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="progress progress-sm mt-2">
                            <div class="progress-bar bg-{{ $integration->status === 'active' ? 'success' : 'danger' }}"
                                 style="width: {{ $integration->status === 'active' ? '100' : '0' }}%"></div>
                        </div>
                    </div>
                    @endforeach

                    @if($integrations->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-plug fa-3x mb-3"></i>
                        <h6>No Integrations</h6>
                        <p>Set up your first integration to start syncing invoices.</p>
                        <a href="{{ route('vendor.integrations.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Integration
                        </a>
                    </div>
                    @else
                    <div class="mt-3">
                        <a href="{{ route('vendor.integrations.index') }}" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-cogs"></i> Manage All Integrations
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Tax Authority Connection - InvoiceQ Feature -->
            @if(isset($complianceStatus) && isset($complianceStatus['tax_authority_connection']))
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shield-alt"></i> Tax Authority Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="mb-1">E-Invoicing Connection</h6>
                            <small class="text-muted">
                                Last sync: {{ $complianceStatus['tax_authority_connection']['last_sync'] ?? 'Never' }}
                            </small>
                        </div>
                        <div>
                            <span class="badge badge-{{ $complianceStatus['tax_authority_connection']['connected'] ? 'success' : 'danger' }}">
                                <i class="fas fa-{{ $complianceStatus['tax_authority_connection']['connected'] ? 'check' : 'times' }}"></i>
                                {{ $complianceStatus['tax_authority_connection']['connected'] ? 'Connected' : 'Disconnected' }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted mb-1">Compliance Rate</div>
                        <div class="progress">
                            <div class="progress-bar bg-{{ $complianceStatus['compliance_rate'] >= 95 ? 'success' : ($complianceStatus['compliance_rate'] >= 85 ? 'warning' : 'danger') }}"
                                 style="width: {{ $complianceStatus['compliance_rate'] }}%">
                                {{ $complianceStatus['compliance_rate'] }}%
                            </div>
                        </div>
                    </div>

                    @if(!empty($complianceStatus['recommendations']))
                    <div class="mt-3">
                        <h6 class="text-warning">Recommendations:</h6>
                        <ul class="list-unstyled">
                            @foreach($complianceStatus['recommendations'] as $recommendation)
                            <li class="small text-muted mb-1">
                                <i class="fas fa-lightbulb text-warning"></i> {{ $recommendation }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Recent Invoices -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Invoices</h6>
                    <a href="{{ route('vendor.invoices.index') }}" class="btn btn-primary btn-sm">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @if($recentInvoices->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentInvoices as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ route('vendor.invoices.show', $invoice) }}" class="text-decoration-none">
                                            #{{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $invoice->customer_name }}</strong><br>
                                            <small class="text-muted">{{ $invoice->customer_email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>${{ number_format($invoice->total_amount, 2) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $invoice->invoice_date->format('M j, Y') }}
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('vendor.invoices.show', $invoice) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('vendor.invoices.download', $invoice) }}" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="{{ route('vendor.invoices.print', $invoice) }}" target="_blank" class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-file-invoice fa-3x mb-3"></i>
                        <h5>No Invoices Yet</h5>
                        <p>Create your first invoice or set up integrations to start syncing.</p>
                        <div class="btn-group">
                            <a href="{{ route('vendor.invoices.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Invoice
                            </a>
                            <a href="{{ route('vendor.integrations.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-plug"></i> Setup Integration
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Stats</h6>
                </div>
                <div class="card-body">
                    <!-- Invoice Status Distribution -->
                    <div class="mb-4">
                        <h6 class="text-gray-700">Invoice Status</h6>
                        <canvas id="statusChart" height="200"></canvas>
                    </div>

                    <!-- Top Customers -->
                    <div class="mb-4">
                        <h6 class="text-gray-700">Top Customers</h6>
                        @if(isset($analytics['top_customers']) && count($analytics['top_customers']) > 0)
                            @foreach(array_slice($analytics['top_customers'], 0, 5) as $customer)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>{{ $customer['customer_name'] }}</strong><br>
                                    <small class="text-muted">{{ $customer['invoice_count'] }} invoices</small>
                                </div>
                                <div class="text-right">
                                    <strong>${{ number_format($customer['total_amount'], 0) }}</strong>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <p class="text-muted">No customer data available yet.</p>
                        @endif
                    </div>

                    <!-- Quick Actions -->
                    <div>
                        <h6 class="text-gray-700">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="{{ route('vendor.reports.index') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chart-bar"></i> View Reports
                            </a>
                            <a href="{{ route('vendor.analytics.index') }}" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-analytics"></i> Analytics
                            </a>
                            <button class="btn btn-outline-success btn-sm" onclick="exportData('invoices', 'excel')">
                                <i class="fas fa-file-excel"></i> Export Data
                            </button>
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
// Revenue Chart
@if(isset($analytics['revenue_trend']) && count($analytics['revenue_trend']) > 0)
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_column($analytics['revenue_trend'], 'month')) !!},
        datasets: [{
            label: 'Revenue',
            data: {!! json_encode(array_column($analytics['revenue_trend'], 'revenue')) !!},
            borderColor: 'rgb(78, 115, 223)',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Revenue: $' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});
@endif

// Status Chart
@if(isset($analytics['invoice_status_distribution']) && count($analytics['invoice_status_distribution']) > 0)
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusData = {!! json_encode($analytics['invoice_status_distribution']) !!};
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData),
        datasets: [{
            data: Object.values(statusData),
            backgroundColor: [
                '#28a745', // paid - green
                '#ffc107', // pending - yellow
                '#dc3545', // overdue - red
                '#6c757d'  // other - gray
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

// Real-time updates
function refreshDashboard() {
    fetch('{{ route("vendor.real-time-data") }}')
        .then(response => response.json())
        .then(data => {
            // Update stats
            updateStats(data.stats);

            // Update charts if needed
            // This would be implemented based on your needs
        })
        .catch(error => console.error('Error:', error));
}

function updateStats(stats) {
    // Update stat cards with new values
    // Implementation would update the DOM elements with new data
}

function exportData(type, format) {
    window.location.href = `{{ route('vendor.export.index') }}?type=${type}&format=${format}`;
}

// Payment Efficiency Chart - InvoiceQ Feature
@if(isset($paymentData))
const paymentCtx = document.getElementById('paymentEfficiencyChart').getContext('2d');
new Chart(paymentCtx, {
    type: 'doughnut',
    data: {
        labels: ['On Time', 'Late', 'Overdue'],
        datasets: [{
            data: [
                {{ $paymentData['collection_efficiency']['on_time'] ?? 0 }},
                {{ $paymentData['collection_efficiency']['late'] ?? 0 }},
                {{ $paymentData['collection_efficiency']['overdue'] ?? 0 }}
            ],
            backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
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
        },
        cutout: '60%'
    }
});
@endif

// Quick Actions
function syncAllIntegrations() {
    // TODO: Implement quick sync functionality
    alert('Quick sync functionality is not yet implemented. Please sync integrations individually.');
}

function generateQuickReport(type) {
    window.open(`{{ route('vendor.reports.index') }}?type=${type}&quick=1`, '_blank');
}

// Enhanced refresh with loading indicator
function refreshDashboard() {
    const btn = document.getElementById('refreshDashboard');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    btn.disabled = true;

    fetch('{{ route("vendor.real-time-data") }}')
        .then(response => response.json())
        .then(data => {
            updateStats(data.stats);
            // Show success message
            showNotification('Dashboard refreshed successfully!', 'success');
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error refreshing dashboard', 'error');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

function showNotification(message, type) {
    // Create and show Bootstrap toast notification
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'}`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    // Add to toast container or create one
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }

    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    // Remove after shown
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Auto-refresh every 5 minutes with better UX
setInterval(() => {
    fetch('{{ route("vendor.real-time-data") }}')
        .then(response => response.json())
        .then(data => {
            // Silent update - no loading indicator
            updateStats(data.stats);
            console.log('Dashboard auto-updated');
        })
        .catch(error => console.error('Auto-refresh error:', error));
}, 300000); // 5 minutes
</script>

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
                        <button type="button" class="btn btn-outline-primary w-100" onclick="syncAllIntegrations()">
                            <i class="fas fa-sync"></i><br>
                            <small>Sync All</small>
                        </button>
                    </div>
                    <div class="col-6 mb-3">
                        <button type="button" class="btn btn-outline-success w-100" onclick="generateQuickReport('revenue')">
                            <i class="fas fa-chart-line"></i><br>
                            <small>Revenue Report</small>
                        </button>
                    </div>
                    <div class="col-6 mb-3">
                        <button type="button" class="btn btn-outline-warning w-100" onclick="generateQuickReport('overdue')">
                            <i class="fas fa-exclamation-triangle"></i><br>
                            <small>Overdue Report</small>
                        </button>
                    </div>
                    <div class="col-6 mb-3">
                        <button type="button" class="btn btn-outline-info w-100" onclick="generateQuickReport('compliance')">
                            <i class="fas fa-shield-alt"></i><br>
                            <small>Compliance Report</small>
                        </button>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="{{ route('vendor.export.invoices') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-download"></i><br>
                            <small>Export Data</small>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="{{ route('vendor.settings.index') }}" class="btn btn-outline-dark w-100">
                            <i class="fas fa-cog"></i><br>
                            <small>Settings</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@section('styles')
<style>
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.chart-pie {
    position: relative;
    height: 200px;
    width: 100%;
}

.progress-sm {
    height: 0.5rem;
}

.card:hover .hover-shadow {
    transform: translateY(-2px);
}

/* Custom toast positioning */
.toast-container {
    z-index: 1100;
}

/* InvoiceQ-inspired color scheme */
.text-invoiceq-primary {
    color: #2c5aa0 !important;
}

.bg-invoiceq-primary {
    background-color: #2c5aa0 !important;
}

.btn-invoiceq {
    background-color: #2c5aa0;
    border-color: #2c5aa0;
    color: white;
}

.btn-invoiceq:hover {
    background-color: #1e3d70;
    border-color: #1e3d70;
    color: white;
}

/* Enhanced animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

.card:nth-child(2) { animation-delay: 0.1s; }
.card:nth-child(3) { animation-delay: 0.2s; }
.card:nth-child(4) { animation-delay: 0.3s; }
</style>
@endsection
@endpush
