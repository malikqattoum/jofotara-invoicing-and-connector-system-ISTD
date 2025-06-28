@extends('layouts.vendor')

@section('title', 'Add Integration')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Add New Integration</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('vendor.dashboard.index') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('vendor.integrations.index') }}">Integrations</a></li>
                            <li class="breadcrumb-item active">Add Integration</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('vendor.integrations.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Integrations
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Integration Selection -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Select Integration Type</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- QuickBooks -->
                                <div class="col-md-6 mb-3">
                                    <div class="integration-option" data-integration="quickbooks">
                                        <div class="card h-100 border-2 integration-card">
                                            <div class="card-body text-center">
                                                <i class="fab fa-quickbooks fa-3x text-primary mb-3"></i>
                                                <h5 class="card-title">QuickBooks Online</h5>
                                                <p class="card-text text-muted">Connect your QuickBooks account to sync customers, invoices, and financial data.</p>
                                                <div class="mt-3">
                                                    <span class="badge bg-success">Popular</span>
                                                    <span class="badge bg-info">Real-time Sync</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Xero -->
                                <div class="col-md-6 mb-3">
                                    <div class="integration-option" data-integration="xero">
                                        <div class="card h-100 border-2 integration-card">
                                            <div class="card-body text-center">
                                                <i class="fas fa-calculator fa-3x text-info mb-3"></i>
                                                <h5 class="card-title">Xero</h5>
                                                <p class="card-text text-muted">Integrate with Xero accounting software for seamless financial management.</p>
                                                <div class="mt-3">
                                                    <span class="badge bg-info">Cloud-based</span>
                                                    <span class="badge bg-warning">API Access</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Shopify -->
                                <div class="col-md-6 mb-3">
                                    <div class="integration-option" data-integration="shopify">
                                        <div class="card h-100 border-2 integration-card">
                                            <div class="card-body text-center">
                                                <i class="fab fa-shopify fa-3x text-success mb-3"></i>
                                                <h5 class="card-title">Shopify</h5>
                                                <p class="card-text text-muted">Connect your Shopify store to automatically sync orders and customer data.</p>
                                                <div class="mt-3">
                                                    <span class="badge bg-success">E-commerce</span>
                                                    <span class="badge bg-primary">Order Sync</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Square -->
                                <div class="col-md-6 mb-3">
                                    <div class="integration-option" data-integration="square">
                                        <div class="card h-100 border-2 integration-card">
                                            <div class="card-body text-center">
                                                <i class="fas fa-square fa-3x text-warning mb-3"></i>
                                                <h5 class="card-title">Square</h5>
                                                <p class="card-text text-muted">Integrate with Square POS system for transaction and inventory management.</p>
                                                <div class="mt-3">
                                                    <span class="badge bg-warning">POS System</span>
                                                    <span class="badge bg-info">Payment Processing</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Custom Integration -->
                                <div class="col-md-6 mb-3">
                                    <div class="integration-option" data-integration="custom">
                                        <div class="card h-100 border-2 integration-card">
                                            <div class="card-body text-center">
                                                <i class="fas fa-code fa-3x text-secondary mb-3"></i>
                                                <h5 class="card-title">Custom Integration</h5>
                                                <p class="card-text text-muted">Build a custom integration using our API for your specific business needs.</p>
                                                <div class="mt-3">
                                                    <span class="badge bg-secondary">API</span>
                                                    <span class="badge bg-dark">Advanced</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Generic API -->
                                <div class="col-md-6 mb-3">
                                    <div class="integration-option" data-integration="generic">
                                        <div class="card h-100 border-2 integration-card">
                                            <div class="card-body text-center">
                                                <i class="fas fa-plug fa-3x text-dark mb-3"></i>
                                                <h5 class="card-title">Generic API</h5>
                                                <p class="card-text text-muted">Connect any system with API support using our generic integration framework.</p>
                                                <div class="mt-3">
                                                    <span class="badge bg-dark">Generic</span>
                                                    <span class="badge bg-secondary">Flexible</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuration Form (Initially Hidden) -->
                    <div class="card" id="configurationForm" style="display: none;">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Configure Integration</h5>
                        </div>
                        <div class="card-body">
                            <form id="integrationForm" action="{{ route('vendor.integrations.store') }}" method="POST">
                                @csrf
                                <input type="hidden" id="integration_type" name="integration_type" value="">

                                <!-- Common Fields -->
                                <div class="mb-3">
                                    <label for="name" class="form-label">Integration Name</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        placeholder="e.g., My QuickBooks Integration" required>
                                    <div class="form-text">Choose a descriptive name for this integration.</div>
                                </div>

                                <!-- QuickBooks Configuration -->
                                <div class="integration-config" id="quickbooks-config" style="display: none;">
                                    <h6 class="text-primary mb-3">QuickBooks Online Configuration</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="qb_client_id" class="form-label">Client ID</label>
                                                <input type="text" class="form-control" id="qb_client_id" name="config[client_id]">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="qb_client_secret" class="form-label">Client Secret</label>
                                                <input type="password" class="form-control" id="qb_client_secret" name="config[client_secret]">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="qb_environment" class="form-label">Environment</label>
                                        <select class="form-select" id="qb_environment" name="config[environment]">
                                            <option value="sandbox">Sandbox (Testing)</option>
                                            <option value="production">Production</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Xero Configuration -->
                                <div class="integration-config" id="xero-config" style="display: none;">
                                    <h6 class="text-info mb-3">Xero Configuration</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="xero_client_id" class="form-label">Client ID</label>
                                                <input type="text" class="form-control" id="xero_client_id" name="config[client_id]">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="xero_client_secret" class="form-label">Client Secret</label>
                                                <input type="password" class="form-control" id="xero_client_secret" name="config[client_secret]">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="xero_tenant_id" class="form-label">Tenant ID</label>
                                        <input type="text" class="form-control" id="xero_tenant_id" name="config[tenant_id]">
                                        <div class="form-text">Your Xero organization's tenant ID.</div>
                                    </div>
                                </div>

                                <!-- Shopify Configuration -->
                                <div class="integration-config" id="shopify-config" style="display: none;">
                                    <h6 class="text-success mb-3">Shopify Configuration</h6>
                                    <div class="mb-3">
                                        <label for="shopify_store_url" class="form-label">Store URL</label>
                                        <input type="url" class="form-control" id="shopify_store_url" name="config[store_url]"
                                            placeholder="https://your-store.myshopify.com">
                                    </div>
                                    <div class="mb-3">
                                        <label for="shopify_access_token" class="form-label">Private App Access Token</label>
                                        <input type="password" class="form-control" id="shopify_access_token" name="config[access_token]">
                                        <div class="form-text">Create a private app in your Shopify admin to get this token.</div>
                                    </div>
                                </div>

                                <!-- Square Configuration -->
                                <div class="integration-config" id="square-config" style="display: none;">
                                    <h6 class="text-warning mb-3">Square Configuration</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="square_application_id" class="form-label">Application ID</label>
                                                <input type="text" class="form-control" id="square_application_id" name="config[application_id]">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="square_access_token" class="form-label">Access Token</label>
                                                <input type="password" class="form-control" id="square_access_token" name="config[access_token]">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="square_environment" class="form-label">Environment</label>
                                        <select class="form-select" id="square_environment" name="config[environment]">
                                            <option value="sandbox">Sandbox (Testing)</option>
                                            <option value="production">Production</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Generic API Configuration -->
                                <div class="integration-config" id="generic-config" style="display: none;">
                                    <h6 class="text-dark mb-3">Generic API Configuration</h6>
                                    <div class="mb-3">
                                        <label for="api_base_url" class="form-label">API Base URL</label>
                                        <input type="url" class="form-control" id="api_base_url" name="config[base_url]"
                                            placeholder="https://api.example.com">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="api_key" class="form-label">API Key</label>
                                                <input type="password" class="form-control" id="api_key" name="config[api_key]">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="auth_type" class="form-label">Authentication Type</label>
                                                <select class="form-select" id="auth_type" name="config[auth_type]">
                                                    <option value="api_key">API Key</option>
                                                    <option value="bearer_token">Bearer Token</option>
                                                    <option value="basic_auth">Basic Auth</option>
                                                    <option value="oauth2">OAuth 2.0</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sync Settings -->
                                <div class="mb-3">
                                    <label for="sync_frequency" class="form-label">Sync Frequency</label>
                                    <select class="form-select" id="sync_frequency" name="sync_frequency">
                                        <option value="manual">Manual Only</option>
                                        <option value="hourly">Every Hour</option>
                                        <option value="daily" selected>Daily</option>
                                        <option value="weekly">Weekly</option>
                                    </select>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="auto_sync" name="auto_sync" value="1" checked>
                                    <label class="form-check-label" for="auto_sync">
                                        Enable automatic synchronization
                                    </label>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary me-2" onclick="testConnection()">
                                            <i class="fas fa-wifi"></i> Test Connection
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Create Integration
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Help & Documentation -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Need Help?</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-primary">Setup Guides</h6>
                                <ul class="list-unstyled">
                                    <li><a href="#" class="text-decoration-none">QuickBooks Setup Guide</a></li>
                                    <li><a href="#" class="text-decoration-none">Xero Configuration</a></li>
                                    <li><a href="#" class="text-decoration-none">Shopify Integration</a></li>
                                    <li><a href="#" class="text-decoration-none">API Documentation</a></li>
                                </ul>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-info">Support</h6>
                                <p class="small text-muted">
                                    Having trouble setting up your integration? Our support team is here to help.
                                </p>
                                <a href="#" class="btn btn-outline-info btn-sm">Contact Support</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.integration-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.integration-card:hover {
    border-color: #007bff !important;
    box-shadow: 0 4px 8px rgba(0,123,255,0.15);
}

.integration-option.selected .integration-card {
    border-color: #007bff !important;
    background-color: #f8f9fa;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Integration selection handling
    document.querySelectorAll('.integration-option').forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            document.querySelectorAll('.integration-option').forEach(opt =>
                opt.classList.remove('selected'));

            // Add selected class to clicked option
            this.classList.add('selected');

            // Get integration type
            const integrationType = this.dataset.integration;

            // Update form
            document.getElementById('integration_type').value = integrationType;

            // Show configuration form
            document.getElementById('configurationForm').style.display = 'block';

            // Hide all config sections
            document.querySelectorAll('.integration-config').forEach(config =>
                config.style.display = 'none');

            // Show relevant config section
            const configSection = document.getElementById(integrationType + '-config');
            if (configSection) {
                configSection.style.display = 'block';
            }

            // Update form name placeholder
            const nameField = document.getElementById('name');
            const integrationNames = {
                'quickbooks': 'QuickBooks Integration',
                'xero': 'Xero Integration',
                'shopify': 'Shopify Store',
                'square': 'Square POS',
                'custom': 'Custom Integration',
                'generic': 'Generic API Integration'
            };
            nameField.placeholder = integrationNames[integrationType] || 'Integration Name';

            // Scroll to form
            document.getElementById('configurationForm').scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});

function resetForm() {
    // Reset integration selection
    document.querySelectorAll('.integration-option').forEach(opt =>
        opt.classList.remove('selected'));

    // Hide configuration form
    document.getElementById('configurationForm').style.display = 'none';

    // Reset form fields
    document.getElementById('integrationForm').reset();
}

function testConnection() {
    const formData = new FormData(document.getElementById('integrationForm'));

    fetch('/api/integrations/test-connection', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Connection test successful! You can now create the integration.');
        } else {
            alert('Connection test failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while testing the connection.');
    });
}
</script>
@endsection
