@extends('layouts.vendor')

@section('title', 'Integrations')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Integration Management</h1>
                <a href="{{ route('vendor.integrations.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Integration
                </a>
            </div>

            <!-- Status Overview -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['total'] }}</h4>
                                    <p class="card-text">Total Integrations</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-plug fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['active'] }}</h4>
                                    <p class="card-text">Active</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['syncing'] }}</h4>
                                    <p class="card-text">Syncing</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-sync-alt fa-2x fa-spin"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['errors'] }}</h4>
                                    <p class="card-text">Errors</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integrations List -->
            <div class="row">
                @forelse($integrations as $integration)
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="integration-icon me-3">
                                    @switch($integration->vendor_name)
                                        @case('QuickBooks')
                                            <i class="fab fa-quickbooks fa-2x text-primary"></i>
                                            @break
                                        @case('Xero')
                                            <i class="fas fa-calculator fa-2x text-info"></i>
                                            @break
                                        @case('Shopify')
                                            <i class="fab fa-shopify fa-2x text-success"></i>
                                            @break
                                        @case('Square')
                                            <i class="fas fa-square fa-2x text-warning"></i>
                                            @break
                                        @default
                                            <i class="fas fa-store fa-2x text-secondary"></i>
                                    @endswitch
                                </div>
                                <div>
                                    <h5 class="card-title mb-1">{{ $integration->vendor_name }}</h5>
                                    <small class="text-muted">{{ ucfirst($integration->integration_type) }}</small>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="testConnection({{ $integration->id }})">
                                        <i class="fas fa-wifi"></i> Test Connection
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="forcSync({{ $integration->id }})">
                                        <i class="fas fa-sync"></i> Force Sync
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('vendor.integrations.logs', $integration) }}">
                                        <i class="fas fa-list"></i> View Logs
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="removeIntegration({{ $integration->id }})">
                                        <i class="fas fa-trash"></i> Remove
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Status:</strong>
                                    <span class="badge
                                        @if($integration->status == 'active') bg-success
                                        @elseif($integration->status == 'error') bg-danger
                                        @elseif($integration->sync_status == 'syncing') bg-warning
                                        @else bg-secondary
                                        @endif
                                    ">
                                        @if($integration->sync_status == 'syncing')
                                            Syncing...
                                        @else
                                            {{ ucfirst($integration->status) }}
                                        @endif
                                    </span>
                                </div>
                                <div class="col-6">
                                    <strong>Last Sync:</strong>
                                    <small class="text-muted">
                                        {{ $integration->last_sync_at ? $integration->last_sync_at->diffForHumans() : 'Never' }}
                                    </small>
                                </div>
                            </div>

                            @if($integration->sync_frequency)
                            <div class="mb-3">
                                <strong>Sync Frequency:</strong> {{ ucfirst($integration->sync_frequency) }}
                            </div>
                            @endif

                            @if($integration->last_error)
                            <div class="alert alert-danger alert-sm">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Last Error:</strong> {{ $integration->last_error }}
                            </div>
                            @endif

                            <!-- Recent Sync Logs -->
                            @if($integration->syncLogs && $integration->syncLogs->count() > 0)
                            <div class="mt-3">
                                <h6 class="text-muted">Recent Activity</h6>
                                <div class="timeline-sm">
                                    @foreach($integration->syncLogs->take(3) as $log)
                                    <div class="timeline-item-sm">
                                        <span class="timeline-marker-sm
                                            @if($log->status == 'success') bg-success
                                            @elseif($log->status == 'error') bg-danger
                                            @else bg-warning
                                            @endif
                                        "></span>
                                        <div class="timeline-content-sm">
                                            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                            <p class="mb-0 small">{{ $log->message }}</p>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Created {{ $integration->created_at->diffForHumans() }}
                                </small>
                                @if($integration->status == 'active')
                                <button class="btn btn-sm btn-outline-primary" onclick="forcSync({{ $integration->id }})">
                                    <i class="fas fa-sync"></i> Sync Now
                                </button>
                                @else
                                <button class="btn btn-sm btn-outline-warning" onclick="testConnection({{ $integration->id }})">
                                    <i class="fas fa-wifi"></i> Test
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-plug fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">No Integrations Configured</h4>
                        <p class="text-muted mb-4">Connect your business systems to automatically sync invoices and customer data.</p>
                        <a href="{{ route('vendor.integrations.create') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus"></i> Add Your First Integration
                        </a>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
.timeline-sm {
    position: relative;
    padding-left: 1.5rem;
}

.timeline-item-sm {
    position: relative;
    margin-bottom: 0.75rem;
}

.timeline-marker-sm {
    position: absolute;
    left: -1.5rem;
    top: 0.25rem;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-item-sm:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -1.25rem;
    top: 0.75rem;
    width: 1px;
    height: calc(100% + 0.25rem);
    background-color: #dee2e6;
}

.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}
</style>

<script>
function testConnection(integrationId) {
    if (confirm('Test connection to this integration?')) {
        fetch(`/api/integrations/${integrationId}/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Connection test successful!');
                location.reload();
            } else {
                alert('Connection test failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while testing the connection.');
        });
    }
}

function forcSync(integrationId) {
    if (confirm('Force sync this integration? This may take a few minutes.')) {
        fetch(`/api/integrations/${integrationId}/sync`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sync started successfully!');
                location.reload();
            } else {
                alert('Sync failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while starting the sync.');
        });
    }
}

function removeIntegration(integrationId) {
    if (confirm('Are you sure you want to remove this integration? This action cannot be undone.')) {
        fetch(`/api/integrations/${integrationId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Integration removed successfully!');
                location.reload();
            } else {
                alert('Failed to remove integration: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the integration.');
        });
    }
}
</script>
@endsection
