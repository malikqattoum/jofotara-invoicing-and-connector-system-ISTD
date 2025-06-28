@extends('welcome')
@section('content')
<div class="container mt-4">
    <h2>Welcome, {{ $user->name }}</h2>
    <h4>Your Dashboard</h4>
    <div class="mb-4">
        <strong>Organization:</strong> {{ $integration->organization_name ?? 'N/A' }}<br>
        <strong>Role:</strong> {{ $user->role ?? 'N/A' }}
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Invoices</h5>
                    <p class="card-text display-6">{{ $stats['total'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Submitted</h5>
                    <p class="card-text display-6">{{ $stats['submitted'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Rejected</h5>
                    <p class="card-text display-6">{{ $stats['rejected'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-secondary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Draft</h5>
                    <p class="card-text display-6">{{ $stats['draft'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <h5>Recent Invoices</h5>
    <div class="mb-3">
        <a href="{{ route('vendor.invoices.create') }}" class="btn btn-primary">Create New Invoice</a>
        <form method="GET" action="{{ route('vendor.dashboard.index') }}" class="d-inline-block float-end" style="max-width:300px;">
            <input type="text" name="search" class="form-control d-inline-block w-auto" placeholder="Search invoice # or customer" value="{{ request('search') }}">
            <select name="status" class="form-select d-inline-block w-auto ms-2">
                <option value="">All Statuses</option>
                <option value="submitted" @if(request('status')=='submitted') selected @endif>Submitted</option>
                <option value="rejected" @if(request('status')=='rejected') selected @endif>Rejected</option>
                <option value="draft" @if(request('status')=='draft') selected @endif>Draft</option>
            </select>
            <button type="submit" class="btn btn-secondary ms-2">Filter</button>
        </form>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
                <td>{{ ucfirst($invoice->status) }}</td>
                <td>{{ number_format($invoice->total, 2) }}</td>
                <td>
                    <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-sm btn-warning">Edit</a>
                    <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-sm btn-success">Download</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
