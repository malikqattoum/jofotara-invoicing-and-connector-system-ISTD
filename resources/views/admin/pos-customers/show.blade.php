@extends('layouts.app')

@section('title', 'POS Customer Details')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">👤 {{ $posCustomer->customer_name }}</h1>
        <div>
            <a href="{{ route('admin.pos-customers.index') }}" class="btn btn-secondary mr-2">
                <i class="fas fa-arrow-left"></i> Back to Customers
            </a>
            <a href="{{ route('admin.pos-customers.edit', $posCustomer) }}" class="btn btn-warning mr-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <form action="{{ route('admin.pos-customers.generate-package', $posCustomer) }}"
                  method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-download"></i> Download Package
                </button>
            </form>
        </div>
    </div>

    <!-- Status Alert -->
    <div class="row mb-4">
        <div class="col-12">
            @if($posCustomer->is_connector_active)
                <div class="alert alert-success">
                    <h5 class="alert-heading"><i class="fas fa-check-circle"></i> Connector Active</h5>
                    <p class="mb-0">
                        The POS connector is currently online and syncing data.
                        Last seen: {{ $posCustomer->last_seen->diffForHumans() }}
                    </p>
                </div>
            @elseif($posCustomer->last_seen)
                <div class="alert alert-warning">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Connector Inactive</h5>
                    <p class="mb-0">
                        The POS connector was last seen {{ $posCustomer->last_seen->diffForHumans() }}.
                        It may be offline or experiencing connectivity issues.
                    </p>
                </div>
            @else
                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Never Connected</h5>
                    <p class="mb-0">
                        This customer has not yet installed or connected their POS connector.
                        Generate and send them the installation package.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_transactions'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Today's Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['today_transactions'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Revenue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($stats['total_revenue'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Average Transaction
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($stats['avg_transaction'] ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Customer Information -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Customer ID:</strong></div>
                        <div class="col-sm-8">
                            <span class="badge badge-primary">{{ $posCustomer->customer_id }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Business Type:</strong></div>
                        <div class="col-sm-8">
                            <span class="badge badge-secondary">
                                {{ ucfirst($posCustomer->business_type ?? 'General') }}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Email:</strong></div>
                        <div class="col-sm-8">{{ $posCustomer->email }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Phone:</strong></div>
                        <div class="col-sm-8">{{ $posCustomer->phone ?? 'Not provided' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Address:</strong></div>
                        <div class="col-sm-8">{{ $posCustomer->address ?? 'Not provided' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Created:</strong></div>
                        <div class="col-sm-8">{{ $posCustomer->created_at->format('M d, Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connector Configuration -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Connector Configuration</h6>
                    <form action="{{ route('admin.pos-customers.regenerate-api-key', $posCustomer) }}"
                          method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning"
                                onclick="return confirm('This will invalidate the current connector. Customer will need to reinstall. Continue?')">
                            <i class="fas fa-key"></i> Regenerate API Key
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>API Key:</strong></div>
                        <div class="col-sm-8">
                            <code style="font-size: 0.8em;">{{ substr($posCustomer->api_key, 0, 20) }}...</code>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Sync Interval:</strong></div>
                        <div class="col-sm-8">{{ $posCustomer->sync_interval }} seconds</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Debug Mode:</strong></div>
                        <div class="col-sm-8">
                            <span class="badge badge-{{ $posCustomer->debug_mode ? 'warning' : 'success' }}">
                                {{ $posCustomer->debug_mode ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Auto Start:</strong></div>
                        <div class="col-sm-8">
                            <span class="badge badge-{{ $posCustomer->auto_start ? 'success' : 'secondary' }}">
                                {{ $posCustomer->auto_start ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>
                    @if($posCustomer->pos_systems_detected)
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Detected POS:</strong></div>
                            <div class="col-sm-8">
                                @foreach($posCustomer->pos_systems_detected as $system)
                                    <span class="badge badge-info mr-1">{{ $system }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Recent Transactions</h6>
            <div>
                <a href="{{ route('admin.pos-customers.transactions', $posCustomer) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-list"></i> View All Transactions
                </a>
                @if($posCustomer->transactions()->unprocessed()->count() > 0)
                    <form action="{{ route('admin.pos-customers.process-transactions', $posCustomer) }}"
                          method="POST" style="display: inline;" class="ml-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fas fa-cogs"></i> Process Unprocessed ({{ $posCustomer->transactions()->unprocessed()->count() }})
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if($posCustomer->transactions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($posCustomer->transactions->take(10) as $transaction)
                            <tr>
                                <td><code>{{ $transaction->transaction_id }}</code></td>
                                <td>{{ $transaction->transaction_date->format('M d, H:i') }}</td>
                                <td>{{ $transaction->customer_name ?? 'N/A' }}</td>
                                <td><strong>${{ number_format($transaction->total_amount, 2) }}</strong></td>
                                <td>
                                    <span class="badge badge-secondary">
                                        {{ $transaction->payment_method ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    @if($transaction->invoice_created)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Processed
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-exchange-alt fa-3x text-gray-300 mb-3"></i>
                    <p class="text-muted">No transactions received yet</p>
                    <small class="text-muted">
                        Transactions will appear here once the customer's POS connector starts syncing data.
                    </small>
                </div>
            @endif
        </div>
    </div>

    <!-- Notes Section -->
    @if($posCustomer->notes)
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Internal Notes</h6>
        </div>
        <div class="card-body">
            <p class="mb-0">{{ $posCustomer->notes }}</p>
        </div>
    </div>
    @endif
</div>
@endsection
