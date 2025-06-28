@extends('layouts.vendor')

@section('title', 'Invoice Details - ' . $invoice->invoice_number)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Invoice {{ $invoice->invoice_number }}</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('vendor.dashboard.index') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('vendor.invoices.index') }}">Invoices</a></li>
                            <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="btn-group">
                    <a href="{{ route('vendor.invoices.download', $invoice) }}" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                    <a href="{{ route('vendor.invoices.print', $invoice) }}" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-print"></i> Print
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Invoice Details Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Invoice Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Bill To:</h6>
                                    <p class="mb-1"><strong>{{ $invoice->customer_name }}</strong></p>
                                    @if($invoice->customer_email)
                                    <p class="mb-1">{{ $invoice->customer_email }}</p>
                                    @endif
                                    @if($invoice->customer_address)
                                    <p class="mb-0 text-muted">{{ $invoice->customer_address }}</p>
                                    @endif
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <h6 class="text-muted">Invoice Details:</h6>
                                    <p class="mb-1"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                                    <p class="mb-1"><strong>Date:</strong> {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('M d, Y') : 'N/A' }}</p>
                                    @if($invoice->due_date)
                                    <p class="mb-1"><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</p>
                                    @endif
                                    <p class="mb-0">
                                        <strong>Status:</strong>
                                        <span class="badge
                                            @if($invoice->status == 'paid') bg-success
                                            @elseif($invoice->status == 'pending') bg-warning
                                            @elseif($invoice->status == 'overdue') bg-danger
                                            @else bg-secondary
                                            @endif
                                        ">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Invoice Items</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Description</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($invoice->items as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->description }}</strong>
                                                @if($item->additional_details)
                                                <br><small class="text-muted">{{ $item->additional_details }}</small>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ $item->quantity }}</td>
                                            <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-end">${{ number_format($item->total_amount, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No items found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="3" class="text-end">Subtotal:</th>
                                            <th class="text-end">${{ number_format($invoice->subtotal ?? $invoice->total_amount, 2) }}</th>
                                        </tr>
                                        @if($invoice->tax_amount)
                                        <tr>
                                            <th colspan="3" class="text-end">Tax:</th>
                                            <th class="text-end">${{ number_format($invoice->tax_amount, 2) }}</th>
                                        </tr>
                                        @endif
                                        <tr class="table-primary">
                                            <th colspan="3" class="text-end">Total:</th>
                                            <th class="text-end">${{ number_format($invoice->total_amount, 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Status & Actions Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('vendor.invoices.download', $invoice) }}" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Download PDF
                                </a>
                                <a href="{{ route('vendor.invoices.print', $invoice) }}" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-print"></i> Print Invoice
                                </a>
                                @if($invoice->status == 'draft')
                                <button class="btn btn-success" onclick="submitInvoice({{ $invoice->id }})">
                                    <i class="fas fa-paper-plane"></i> Submit Invoice
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Integration Status Card -->
                    @if($invoice->integration)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Integration Status</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Source:</strong> {{ $invoice->integration->vendor_name }}</p>
                            <p><strong>Last Sync:</strong> {{ $invoice->updated_at->diffForHumans() }}</p>
                            <div class="mt-3">
                                <span class="badge
                                    @if($invoice->integration->status == 'active') bg-success
                                    @else bg-warning
                                    @endif
                                ">
                                    {{ ucfirst($invoice->integration->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Sync Logs Card -->
                    @if($invoice->syncLogs && $invoice->syncLogs->count() > 0)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                @foreach($invoice->syncLogs->take(5) as $log)
                                <div class="timeline-item">
                                    <div class="timeline-marker
                                        @if($log->status == 'success') bg-success
                                        @elseif($log->status == 'error') bg-danger
                                        @else bg-warning
                                        @endif
                                    "></div>
                                    <div class="timeline-content">
                                        <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                        <p class="mb-0">{{ $log->message }}</p>
                                        @if($log->status == 'error' && $log->error_details)
                                        <small class="text-danger">{{ $log->error_details }}</small>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-item {
    position: relative;
    margin-bottom: 1rem;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0.5rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -1.75rem;
    top: 1rem;
    width: 2px;
    height: calc(100% + 0.5rem);
    background-color: #dee2e6;
}
</style>

<script>
function submitInvoice(invoiceId) {
    if (confirm('Are you sure you want to submit this invoice?')) {
        fetch(`/api/invoices/${invoiceId}/submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error submitting invoice: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting the invoice.');
        });
    }
}
</script>
@endsection
