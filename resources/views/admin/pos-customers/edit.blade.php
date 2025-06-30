@extends('layouts.app')

@section('title', 'Edit POS Customer')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">✏️ Edit POS Customer</h1>
                <div>
                    <a href="{{ route('admin.pos-customers.show', $posCustomer) }}" class="btn btn-info mr-2">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                    <a href="{{ route('admin.pos-customers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Customers
                    </a>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        {{ $posCustomer->customer_name }}
                        <small class="text-muted">({{ $posCustomer->customer_id }})</small>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.pos-customers.update', $posCustomer) }}">
                        @csrf
                        @method('PUT')

                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_name">Customer/Business Name *</label>
                                    <input type="text" class="form-control @error('customer_name') is-invalid @enderror"
                                           id="customer_name" name="customer_name"
                                           value="{{ old('customer_name', $posCustomer->customer_name) }}"
                                           required>
                                    @error('customer_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="business_type">Business Type</label>
                                    <select class="form-control @error('business_type') is-invalid @enderror"
                                            id="business_type" name="business_type">
                                        <option value="">Select Business Type</option>
                                        @foreach(config('pos.business_types') as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ old('business_type', $posCustomer->business_type) === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('business_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email"
                                           value="{{ old('email', $posCustomer->email) }}"
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                           id="phone" name="phone"
                                           value="{{ old('phone', $posCustomer->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Business Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      id="address" name="address" rows="3">{{ old('address', $posCustomer->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- POS Connector Configuration -->
                        <hr>
                        <h6 class="font-weight-bold text-primary mb-3">POS Connector Settings</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sync_interval">Sync Interval (seconds)</label>
                                    <select class="form-control @error('sync_interval') is-invalid @enderror"
                                            id="sync_interval" name="sync_interval">
                                        @foreach(config('pos.sync_intervals') as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ old('sync_interval', $posCustomer->sync_interval) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sync_interval')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="support_contact">Support Contact</label>
                                    <input type="text" class="form-control @error('support_contact') is-invalid @enderror"
                                           id="support_contact" name="support_contact"
                                           value="{{ old('support_contact', $posCustomer->support_contact) }}">
                                    @error('support_contact')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="debug_mode" name="debug_mode" value="1"
                                           {{ old('debug_mode', $posCustomer->debug_mode) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="debug_mode">
                                        Enable Debug Mode
                                    </label>
                                    <small class="form-text text-muted">Creates detailed logs for troubleshooting</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="auto_start" name="auto_start" value="1"
                                           {{ old('auto_start', $posCustomer->auto_start) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_start">
                                        Auto-start with Windows
                                    </label>
                                    <small class="form-text text-muted">Connector starts automatically when computer boots</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Internal Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                      id="notes" name="notes" rows="3">{{ old('notes', $posCustomer->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Current Status Info -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Current Status</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Customer ID:</strong> {{ $posCustomer->customer_id }}</p>
                                    <p class="mb-1"><strong>API Key:</strong> {{ substr($posCustomer->api_key, 0, 20) }}...</p>
                                    <p class="mb-1"><strong>Total Transactions:</strong> {{ $posCustomer->total_transactions_synced }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Last Seen:</strong> {{ $posCustomer->last_seen?->diffForHumans() ?? 'Never' }}</p>
                                    <p class="mb-1"><strong>Connector Status:</strong>
                                        <span class="badge badge-{{ $posCustomer->is_connector_active ? 'success' : 'warning' }}">
                                            {{ $posCustomer->is_connector_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                    <p class="mb-0"><strong>Created:</strong> {{ $posCustomer->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-right">
                            <button type="button" class="btn btn-secondary mr-2" onclick="history.back()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Update Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card border-danger shadow mt-4">
                <div class="card-header bg-danger text-white">
                    <h6 class="m-0 font-weight-bold">⚠️ Danger Zone</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        These actions are permanent and cannot be undone. Use with caution.
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('admin.pos-customers.regenerate-api-key', $posCustomer) }}"
                                  method="POST" onsubmit="return confirm('This will invalidate the current connector. Customer will need to reinstall. Continue?')">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-block">
                                    <i class="fas fa-key"></i> Regenerate API Key
                                </button>
                            </form>
                            <small class="text-muted">Customer will need to reinstall connector</small>
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('admin.pos-customers.destroy', $posCustomer) }}"
                                  method="POST" onsubmit="return confirm('This will permanently delete this customer and ALL their transaction data. This cannot be undone. Are you absolutely sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-block">
                                    <i class="fas fa-trash"></i> Delete Customer
                                </button>
                            </form>
                            <small class="text-muted">Deletes customer and all transaction data</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
