@extends('layouts.vendor')

@section('title', 'Export Data')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Export Data</h1>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Export Invoices</h5>
                        </div>
                        <div class="card-body">
                            <p>Export your invoice data in various formats.</p>
                            <a href="{{ route('vendor.export.invoices') }}" class="btn btn-primary">
                                <i class="fas fa-file-invoice"></i> Export Invoices
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Export Reports</h5>
                        </div>
                        <div class="card-body">
                            <p>Export revenue and business reports.</p>
                            <form action="{{ route('vendor.export.revenue') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-chart-line"></i> Export Revenue Report
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Export Customers</h5>
                        </div>
                        <div class="card-body">
                            <p>Export customer data and analytics.</p>
                            <form action="{{ route('vendor.export.customers') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-users"></i> Export Customers
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Export All Reports</h5>
                        </div>
                        <div class="card-body">
                            <p>Export comprehensive business reports.</p>
                            <form action="{{ route('vendor.export.reports') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-download"></i> Export All Reports
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
