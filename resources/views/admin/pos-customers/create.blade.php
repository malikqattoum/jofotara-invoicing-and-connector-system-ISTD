@extends('layouts.app')

@section('title', 'Add POS Customer')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">➕ Add New POS Customer</h1>
                <a href="{{ route('admin.pos-customers.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Customers
                </a>
            </div>

            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.pos-customers.store') }}">
                        @csrf

                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_name">Customer/Business Name *</label>
                                    <input type="text" class="form-control @error('customer_name') is-invalid @enderror"
                                           id="customer_name" name="customer_name" value="{{ old('customer_name') }}"
                                           required placeholder="e.g., Mario's Pizza Restaurant">
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
                                        <option value="restaurant" {{ old('business_type') === 'restaurant' ? 'selected' : '' }}>Restaurant</option>
                                        <option value="retail" {{ old('business_type') === 'retail' ? 'selected' : '' }}>Retail Store</option>
                                        <option value="medical" {{ old('business_type') === 'medical' ? 'selected' : '' }}>Medical/Healthcare</option>
                                        <option value="automotive" {{ old('business_type') === 'automotive' ? 'selected' : '' }}>Automotive</option>
                                        <option value="beauty" {{ old('business_type') === 'beauty' ? 'selected' : '' }}>Beauty/Salon</option>
                                        <option value="professional" {{ old('business_type') === 'professional' ? 'selected' : '' }}>Professional Services</option>
                                        <option value="other" {{ old('business_type') === 'other' ? 'selected' : '' }}>Other</option>
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
                                           id="email" name="email" value="{{ old('email') }}"
                                           required placeholder="customer@business.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                           id="phone" name="phone" value="{{ old('phone') }}"
                                           placeholder="+1-555-123-4567">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Business Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      id="address" name="address" rows="3"
                                      placeholder="123 Business St, City, State 12345">{{ old('address') }}</textarea>
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
                                        <option value="60" {{ old('sync_interval') == 60 ? 'selected' : '' }}>1 minute (Fast)</option>
                                        <option value="300" {{ old('sync_interval', 300) == 300 ? 'selected' : '' }}>5 minutes (Default)</option>
                                        <option value="600" {{ old('sync_interval') == 600 ? 'selected' : '' }}>10 minutes</option>
                                        <option value="1800" {{ old('sync_interval') == 1800 ? 'selected' : '' }}>30 minutes</option>
                                        <option value="3600" {{ old('sync_interval') == 3600 ? 'selected' : '' }}>1 hour (Slow)</option>
                                    </select>
                                    @error('sync_interval')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">How often the connector checks for new transactions</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="support_contact">Support Contact</label>
                                    <input type="text" class="form-control @error('support_contact') is-invalid @enderror"
                                           id="support_contact" name="support_contact" value="{{ old('support_contact') }}"
                                           placeholder="+1-800-SUPPORT">
                                    @error('support_contact')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Phone number for customer support</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="debug_mode" name="debug_mode" value="1"
                                           {{ old('debug_mode') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="debug_mode">
                                        Enable Debug Mode
                                    </label>
                                    <small class="form-text text-muted">Creates detailed logs for troubleshooting</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="auto_start" name="auto_start" value="1"
                                           {{ old('auto_start', true) ? 'checked' : '' }}>
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
                                      id="notes" name="notes" rows="3"
                                      placeholder="Internal notes about this customer...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Info Box -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle"></i> What happens next?</h6>
                            <p class="mb-0">
                                After creating this customer:
                            </p>
                            <ul class="mb-0 mt-2">
                                <li>Unique <strong>Customer ID</strong> and <strong>API Key</strong> will be generated</li>
                                <li>You can download a <strong>customized installer package</strong></li>
                                <li>Customer installs the package and the connector <strong>automatically detects their POS</strong></li>
                                <li>Transaction data flows directly to your invoicing system</li>
                            </ul>
                        </div>

                        <div class="form-group text-right">
                            <button type="button" class="btn btn-secondary mr-2" onclick="history.back()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus"></i> Create Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
