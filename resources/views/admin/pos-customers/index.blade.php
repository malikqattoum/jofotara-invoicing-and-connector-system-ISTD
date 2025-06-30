@extends('layouts.app')

@section('title', 'POS Customers Management')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">🏢 POS Customers Management</h1>
        <a href="{{ route('admin.pos-customers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Customer
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Customers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_customers'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Connectors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_connectors'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-plug fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Today's Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['today_transactions'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Today's Revenue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($stats['total_revenue_today'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters & Search</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.pos-customers.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <label for="search">Search</label>
                        <input type="text" class="form-control" id="search" name="search"
                               value="{{ request('search') }}" placeholder="Name, ID, or email...">
                    </div>
                    <div class="col-md-3">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Customers</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Connectors</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="never_connected" {{ request('status') === 'never_connected' ? 'selected' : '' }}>Never Connected</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="{{ route('admin.pos-customers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">POS Customers</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Business Type</th>
                            <th>Status</th>
                            <th>Last Seen</th>
                            <th>Today's Transactions</th>
                            <th>Total Transactions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td>
                                <div>
                                    <strong>{{ $customer->customer_name }}</strong>
                                    <br>
                                    <small class="text-muted">ID: {{ $customer->customer_id }}</small>
                                    <br>
                                    <small class="text-muted">{{ $customer->email }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    {{ ucfirst($customer->business_type ?? 'General') }}
                                </span>
                            </td>
                            <td>
                                @if($customer->is_connector_active)
                                    <span class="badge badge-success">
                                        <i class="fas fa-circle"></i> Active
                                    </span>
                                @elseif($customer->last_seen)
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock"></i> Inactive
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        <i class="fas fa-times-circle"></i> Never Connected
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($customer->last_seen)
                                    <span title="{{ $customer->last_seen->format('Y-m-d H:i:s') }}">
                                        {{ $customer->last_seen->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $customer->transactions->count() }}</strong>
                                @if($customer->transactions->count() > 0)
                                    <br>
                                    <small class="text-success">
                                        ${{ number_format($customer->transactions->sum('total_amount'), 2) }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $customer->total_transactions_synced }}</strong>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.pos-customers.show', $customer) }}"
                                       class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.pos-customers.edit', $customer) }}"
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.pos-customers.generate-package', $customer) }}"
                                          method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Download Package">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                                <p class="text-muted">No customers found</p>
                                <a href="{{ route('admin.pos-customers.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add First Customer
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($customers->hasPages())
                <div class="d-flex justify-content-center">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Auto-refresh every 30 seconds to show real-time status
setInterval(function() {
    if (!document.hidden) {
        window.location.reload();
    }
}, 30000);
</script>
@endsection
