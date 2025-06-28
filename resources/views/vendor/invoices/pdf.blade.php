<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
            margin-bottom: 10px;
        }
        .invoice-details {
            text-align: right;
            margin-bottom: 30px;
        }
        .bill-to {
            margin-bottom: 30px;
        }
        .bill-to h3 {
            color: #4CAF50;
            margin-bottom: 10px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            border: 1px solid #ddd;
            padding: 10px 8px;
        }
        .items-table .text-right {
            text-align: right;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
            border: none;
        }
        .totals-table td {
            border: none;
            padding: 5px 10px;
        }
        .totals-table .total-row {
            font-weight: bold;
            border-top: 2px solid #4CAF50;
            font-size: 14px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-overdue { background-color: #f8d7da; color: #721c24; }
        .status-draft { background-color: #e2e3e5; color: #383d41; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; padding: 0;">
                    <div class="company-name">{{ config('app.name') }}</div>
                    <div>Invoice System</div>
                </td>
                <td style="border: none; padding: 0; text-align: right;">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-details">
                        <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                        <strong>Date:</strong> {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('M d, Y') : 'N/A' }}<br>
                        @if($invoice->due_date)
                        <strong>Due Date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}<br>
                        @endif
                        <strong>Status:</strong>
                        <span class="status-badge status-{{ $invoice->status }}">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="bill-to">
        <h3>Bill To:</h3>
        <strong>{{ $invoice->customer_name }}</strong><br>
        @if($invoice->customer_email)
        {{ $invoice->customer_email }}<br>
        @endif
        @if($invoice->customer_address)
        {{ $invoice->customer_address }}
        @endif
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th style="width: 15%; text-align: center;">Quantity</th>
                <th style="width: 17.5%; text-align: right;">Unit Price</th>
                <th style="width: 17.5%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->description }}</strong>
                    @if($item->additional_details)
                    <br><small style="color: #666;">{{ $item->additional_details }}</small>
                    @endif
                </td>
                <td class="text-right" style="text-align: center;">{{ $item->quantity }}</td>
                <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">${{ number_format($item->total_amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #666;">No items found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td><strong>Subtotal:</strong></td>
            <td class="text-right">${{ number_format($invoice->subtotal ?? $invoice->total_amount, 2) }}</td>
        </tr>
        @if($invoice->tax_amount)
        <tr>
            <td><strong>Tax:</strong></td>
            <td class="text-right">${{ number_format($invoice->tax_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td><strong>Total:</strong></td>
            <td class="text-right">${{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This invoice was generated on {{ now()->format('M d, Y \at g:i A') }}</p>
    </div>
</body>
</html>
