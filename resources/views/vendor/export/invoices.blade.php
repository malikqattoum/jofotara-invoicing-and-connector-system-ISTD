@extends('layouts.vendor')

@section('title', 'Export Invoices')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Export Invoices</h1>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Export Options</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('vendor.export.invoices.download') }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="invoices">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="format" class="form-label">Export Format</label>
                                            <select class="form-select" id="format" name="format" required>
                                                <option value="excel">Excel (.xlsx)</option>
                                                <option value="csv">CSV</option>
                                                <option value="pdf">PDF</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_range" class="form-label">Date Range</label>
                                            <select class="form-select" id="date_range" name="date_range">
                                                <option value="all">All Time</option>
                                                <option value="this_month">This Month</option>
                                                <option value="last_month">Last Month</option>
                                                <option value="this_year">This Year</option>
                                                <option value="custom">Custom Range</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" id="custom_dates" style="display: none;">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_from" class="form-label">From Date</label>
                                            <input type="date" class="form-control" id="date_from" name="date_from">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_to" class="form-label">To Date</label>
                                            <input type="date" class="form-control" id="date_to" name="date_to">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Invoice Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Statuses</option>
                                        <option value="draft">Draft</option>
                                        <option value="pending">Pending</option>
                                        <option value="submitted">Submitted</option>
                                        <option value="paid">Paid</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Export Invoices
                                </button>
                                <a href="{{ route('vendor.export.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Export Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Total Invoices:</strong> {{ Auth::user()->invoices()->count() ?? 0 }}
                            </div>
                            <div class="mb-3">
                                <strong>Draft:</strong> {{ Auth::user()->invoices()->where('status', 'draft')->count() ?? 0 }}
                            </div>
                            <div class="mb-3">
                                <strong>Submitted:</strong> {{ Auth::user()->invoices()->where('status', 'submitted')->count() ?? 0 }}
                            </div>
                            <div class="mb-3">
                                <strong>Paid:</strong> {{ Auth::user()->invoices()->where('status', 'paid')->count() ?? 0 }}
                            </div>

                            <hr>

                            <div class="mb-3">
                                <strong>Total Revenue:</strong> ${{ number_format(Auth::user()->invoices()->where('status', 'submitted')->sum('total_amount') ?? 0, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('date_range').addEventListener('change', function() {
    const customDates = document.getElementById('custom_dates');
    if (this.value === 'custom') {
        customDates.style.display = 'block';
    } else {
        customDates.style.display = 'none';
    }
});
</script>
@endsection
