@extends('layouts.vendor')

@section('title', 'Settings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Settings</h1>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Profile Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('vendor.settings.update') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                value="{{ old('name', Auth::user()->name) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="{{ old('email', Auth::user()->email) }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="company_name" class="form-label">Company Name</label>
                                            <input type="text" class="form-control" id="company_name" name="company_name"
                                                value="{{ old('company_name', Auth::user()->company_name) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tax_number" class="form-label">Tax Number</label>
                                            <input type="text" class="form-control" id="tax_number" name="tax_number"
                                                value="{{ old('tax_number', Auth::user()->tax_number) }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Business Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', Auth::user()->address) }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="{{ old('phone', Auth::user()->phone) }}">
                                </div>
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Notification Preferences</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('vendor.settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="settings_type" value="notifications">

                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Email Notifications</h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_invoice_status"
                                                name="notifications[email][invoice_status]" value="1"
                                                {{ (Auth::user()->settings['notifications']['email']['invoice_status'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="email_invoice_status">
                                                Invoice status changes
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_integration_alerts"
                                                name="notifications[email][integration_alerts]" value="1"
                                                {{ (Auth::user()->settings['notifications']['email']['integration_alerts'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="email_integration_alerts">
                                                Integration alerts
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_weekly_reports"
                                                name="notifications[email][weekly_reports]" value="1"
                                                {{ (Auth::user()->settings['notifications']['email']['weekly_reports'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="email_weekly_reports">
                                                Weekly reports
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Dashboard Notifications</h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="dashboard_real_time"
                                                name="notifications[dashboard][real_time]" value="1"
                                                {{ (Auth::user()->settings['notifications']['dashboard']['real_time'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="dashboard_real_time">
                                                Real-time updates
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="dashboard_system_alerts"
                                                name="notifications[dashboard][system_alerts]" value="1"
                                                {{ (Auth::user()->settings['notifications']['dashboard']['system_alerts'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="dashboard_system_alerts">
                                                System alerts
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Preferences</button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('vendor.settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="settings_type" value="password">

                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password"
                                        name="current_password" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password"
                                                name="new_password" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="new_password_confirmation"
                                                name="new_password_confirmation" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('vendor.integrations.create') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-plug"></i> Add Integration
                                </a>
                                <a href="{{ route('vendor.export.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-download"></i> Export Data
                                </a>
                                <a href="{{ route('vendor.reports.index') }}" class="btn btn-outline-info">
                                    <i class="fas fa-chart-bar"></i> View Reports
                                </a>
                                <button class="btn btn-outline-warning" onclick="clearCache()">
                                    <i class="fas fa-trash"></i> Clear Cache
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Integration Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Integration Status</h5>
                        </div>
                        <div class="card-body">
                            @if(isset($settings['integrations']) && $settings['integrations']->count() > 0)
                            @foreach($settings['integrations'] as $integration)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>{{ $integration->vendor_name }}</strong>
                                    <br><small class="text-muted">{{ ucfirst($integration->integration_type) }}</small>
                                </div>
                                <span class="badge
                                    @if($integration->status == 'active') bg-success
                                    @elseif($integration->status == 'error') bg-danger
                                    @else bg-warning
                                    @endif
                                ">
                                    {{ ucfirst($integration->status) }}
                                </span>
                            </div>
                            @if(!$loop->last)<hr>@endif
                            @endforeach
                            @else
                            <div class="text-center py-3">
                                <i class="fas fa-plug fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No integrations configured</p>
                                <a href="{{ route('vendor.integrations.create') }}" class="btn btn-sm btn-primary mt-2">
                                    Add Integration
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Account Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Account Type:</strong> Standard
                            </div>
                            <div class="mb-2">
                                <strong>Member Since:</strong> {{ Auth::user()->created_at->format('M Y') }}
                            </div>
                            <div class="mb-2">
                                <strong>Last Login:</strong> {{ Auth::user()->updated_at->diffForHumans() }}
                            </div>
                            <div class="mb-2">
                                <strong>Total Invoices:</strong> {{ Auth::user()->invoices()->count() ?? 0 }}
                            </div>
                            <hr>
                            <div class="d-grid">
                                <button class="btn btn-outline-danger btn-sm" onclick="confirmAccountDeletion()">
                                    <i class="fas fa-user-times"></i> Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function clearCache() {
    if (confirm('Clear application cache? This will remove stored data and may temporarily slow down the application.')) {
        fetch('/vendor/settings/clear-cache', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cache cleared successfully!');
            } else {
                alert('Failed to clear cache: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while clearing cache.');
        });
    }
}

function confirmAccountDeletion() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone and will permanently remove all your data.')) {
        if (confirm('This is your final warning. Are you absolutely sure you want to delete your account?')) {
            // Redirect to account deletion flow
            window.location.href = '/vendor/settings/delete-account';
        }
    }
}
</script>
@endsection
