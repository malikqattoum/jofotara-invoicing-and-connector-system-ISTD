<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Invoice {{ $invoice->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }

        .invoice-header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
        }

        .status-badge {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }

        .items-table th {
            background-color: #f8f9fa;
            border-top: 2px solid #007bff;
        }

        .totals-section {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
        }

        .total-amount {
            font-size: 1.25rem;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Print Button -->
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Close
            </button>
        </div>

        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <div class="company-name">{{ config('app.name') }}</div>
                    <div class="text-muted">Invoice System</div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="invoice-title">INVOICE</div>
                    <div class="mt-2">
                        <p class="mb-1"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                        <p class="mb-1"><strong>Date:</strong> {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('M d, Y') : 'N/A' }}</p>
                        @if($invoice->due_date)
                        <p class="mb-1"><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</p>
                        @endif
                        <p class="mb-0">
                            <strong>Status:</strong>
                            <span class="badge status-badge
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

        <!-- Bill To Section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="text-primary">Bill To:</h5>
                <div class="border-start border-primary border-3 ps-3">
                    <p class="mb-1"><strong>{{ $invoice->customer_name }}</strong></p>
                    @if($invoice->customer_email)
                    <p class="mb-1">{{ $invoice->customer_email }}</p>
                    @endif
                    @if($invoice->customer_address)
                    <p class="mb-0 text-muted">{{ $invoice->customer_address }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-striped items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 15%;" class="text-center">Quantity</th>
                        <th style="width: 17.5%;" class="text-end">Unit Price</th>
                        <th style="width: 17.5%;" class="text-end">Total</th>
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
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end">${{ number_format($item->total_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No items found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Totals Section -->
        <div class="row">
            <div class="col-md-6"></div>
            <div class="col-md-6">
                <div class="totals-section">
                    <div class="d-flex justify-content-between mb-2">
                        <span><strong>Subtotal:</strong></span>
                        <span>${{ number_format($invoice->subtotal ?? $invoice->total_amount, 2) }}</span>
                    </div>
                    @if($invoice->tax_amount)
                    <div class="d-flex justify-content-between mb-2">
                        <span><strong>Tax:</strong></span>
                        <span>${{ number_format($invoice->tax_amount, 2) }}</span>
                    </div>
                    @endif
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="total-amount">Total:</span>
                        <span class="total-amount">${{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 pt-4 border-top">
            <p class="text-muted mb-1">Thank you for your business!</p>
            <p class="text-muted small">This invoice was printed on {{ now()->format('M d, Y \at g:i A') }}</p>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
