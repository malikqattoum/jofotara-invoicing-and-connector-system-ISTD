@extends('layouts.app')

@section('title', $posCustomer->customer_name . ' - Transactions')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">💳 {{ $posCustomer->customer_name }} - Transactions</h1>
        <div>
            <a href="{{ route('admin.pos-customers.show', $posCustomer) }}" class="btn btn-info mr-2">
                <i class="fas fa-user"></i> Customer Details
            </a>
            <a href="{{ route('admin.pos-customers.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Customers
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $transactions->total() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-gray-300"></i>
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
                                Processed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $posCustomer->transactions()->where('invoice_created', true)->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check fa-2x text-gray-300"></i>
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
                                Pending
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $posCustomer->transactions()->where('invoice_created', false)->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($posCustomer->transactions()->sum('total_amount'), 2) }}
                            </div>
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
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.pos-customers.transactions', $posCustomer) }}">
                <div class="row">
                    <div class="col-md-3">
                        <label for="date_from">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from"
                               value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to"
                               value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="payment_method">Payment Method</label>
                        <select class="form-control" id="payment_method" name="payment_method">
                            <option value="">All Methods</option>
                            <option value="Cash" {{ request('payment_method') === 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Credit Card" {{ request('payment_method') === 'Credit Card' ? 'selected' : '' }}>Credit Card</option>
                            <option value="Debit Card" {{ request('payment_method') === 'Debit Card' ? 'selected' : '' }}>Debit Card</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="processed">Status</label>
                        <select class="form-control" id="processed" name="processed">
                            <option value="">All</option>
                            <option value="yes" {{ request('processed') === 'yes' ? 'selected' : '' }}>Processed</option>
                            <option value="no" {{ request('processed') === 'no' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('admin.pos-customers.transactions', $posCustomer) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Actions -->
    @if($posCustomer->transactions()->unprocessed()->count() > 0)
    <div class="alert alert-warning">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="alert-heading mb-1">{{ $posCustomer->transactions()->unprocessed()->count() }} Unprocessed Transactions</h6>
                <p class="mb-0">These transactions haven't been converted to invoices yet.</p>
            </div>
            <form action="{{ route('admin.pos-customers.process-transactions', $posCustomer) }}"
                  method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-cogs"></i> Process All Unprocessed
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Transactions Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Transactions</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Date & Time</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>
                                <code>{{ $transaction->transaction_id }}</code>
                                @if($transaction->source_pos_system)
                                    <br><small class="text-muted">{{ $transaction->source_pos_system }}</small>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $transaction->transaction_date->format('M d, Y') }}</strong>
                                <br><small class="text-muted">{{ $transaction->transaction_date->format('H:i:s') }}</small>
                            </td>
                            <td>
                                {{ $transaction->customer_name ?? 'N/A' }}
                                @if($transaction->customer_email)
                                    <br><small class="text-muted">{{ $transaction->customer_email }}</small>
                                @endif
                                @if($transaction->location)
                                    <br><span class="badge badge-secondary">{{ $transaction->location }}</span>
                                @endif
                            </td>
                            <td>
                                @if($transaction->items && count($transaction->items) > 0)
                                    <button class="btn btn-sm btn-info" data-toggle="modal"
                                            data-target="#itemsModal{{ $transaction->id }}">
                                        <i class="fas fa-list"></i> {{ count($transaction->items) }} items
                                    </button>
                                @else
                                    <span class="text-muted">No details</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-primary">
                                    {{ $transaction->payment_method ?? 'N/A' }}
                                </span>
                                @if($transaction->payment_reference)
                                    <br><small class="text-muted">{{ $transaction->payment_reference }}</small>
                                @endif
                            </td>
                            <td>
                                <strong>${{ number_format($transaction->total_amount, 2) }}</strong>
                                @if($transaction->tip_amount)
                                    <br><small class="text-success">+${{ number_format($transaction->tip_amount, 2) }} tip</small>
                                @endif
                            </td>
                            <td>
                                @if($transaction->invoice_created)
                                    <span class="badge badge-success">
                                        <i class="fas fa-check"></i> Processed
                                    </span>
                                    @if($transaction->invoice_id)
                                        <br><small class="text-muted">Invoice #{{ $transaction->invoice_id }}</small>
                                    @endif
                                @else
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if(!$transaction->invoice_created)
                                    <button class="btn btn-sm btn-success"
                                            onclick="processTransaction({{ $transaction->id }})"
                                            title="Create Invoice">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                @elseif($transaction->invoice_id)
                                    <a href="#" class="btn btn-sm btn-info" title="View Invoice">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                @endif

                                <button class="btn btn-sm btn-secondary" data-toggle="modal"
                                        data-target="#detailsModal{{ $transaction->id }}" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Items Modal -->
                        @if($transaction->items && count($transaction->items) > 0)
                        <div class="modal fade" id="itemsModal{{ $transaction->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Transaction Items - {{ $transaction->transaction_id }}</h5>
                                        <button type="button" class="close" data-dismiss="modal">
                                            <span>&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Qty</th>
                                                    <th>Price</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($transaction->items as $item)
                                                <tr>
                                                    <td>{{ $item['description'] ?? $item['name'] ?? 'Item' }}</td>
                                                    <td>{{ $item['quantity'] ?? 1 }}</td>
                                                    <td>${{ number_format($item['unit_price'] ?? $item['price'] ?? 0, 2) }}</td>
                                                    <td><strong>${{ number_format($item['total'] ?? 0, 2) }}</strong></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-active">
                                                    <th colspan="3">Total</th>
                                                    <th>${{ number_format($transaction->total_amount, 2) }}</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Details Modal -->
                        <div class="modal fade" id="detailsModal{{ $transaction->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Transaction Details - {{ $transaction->transaction_id }}</h5>
                                        <button type="button" class="close" data-dismiss="modal">
                                            <span>&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Transaction Info</h6>
                                                <p><strong>ID:</strong> {{ $transaction->transaction_id }}</p>
                                                <p><strong>Date:</strong> {{ $transaction->transaction_date->format('M d, Y H:i:s') }}</p>
                                                <p><strong>Source:</strong> {{ $transaction->source_pos_system ?? 'Unknown' }}</p>
                                                <p><strong>File:</strong> {{ $transaction->source_file ?? 'N/A' }}</p>
                                                @if($transaction->employee)
                                                    <p><strong>Employee:</strong> {{ $transaction->employee }}</p>
                                                @endif
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Payment Info</h6>
                                                <p><strong>Method:</strong> {{ $transaction->payment_method ?? 'N/A' }}</p>
                                                <p><strong>Reference:</strong> {{ $transaction->payment_reference ?? 'N/A' }}</p>
                                                <p><strong>Status:</strong> {{ $transaction->payment_status }}</p>
                                                <p><strong>Subtotal:</strong> ${{ number_format($transaction->subtotal ?? 0, 2) }}</p>
                                                <p><strong>Tax:</strong> ${{ number_format($transaction->tax_amount ?? 0, 2) }}</p>
                                                <p><strong>Total:</strong> <strong>${{ number_format($transaction->total_amount, 2) }}</strong></p>
                                            </div>
                                        </div>
                                        @if($transaction->notes)
                                            <h6>Notes</h6>
                                            <p>{{ $transaction->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-exchange-alt fa-3x text-gray-300 mb-3"></i>
                                <p class="text-muted">No transactions found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($transactions->hasPages())
                <div class="d-flex justify-content-center">
                    {{ $transactions->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function processTransaction(id) {
    if (confirm('Create an invoice from this transaction?')) {
        // Here you would make an AJAX call to process individual transaction
        alert('Feature coming soon - use "Process All Unprocessed" for now');
    }
}
</script>
@endsection
