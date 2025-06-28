<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\VendorIntegrationService;
use App\Services\IntegrationHealthService;
use App\Services\IntegrationMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IntegrationDashboardController extends Controller
{
    protected $vendorService;
    protected $healthService;
    protected $metricsService;

    public function __construct(
        VendorIntegrationService $vendorService,
        IntegrationHealthService $healthService,
        IntegrationMetricsService $metricsService
    ) {
        $this->vendorService = $vendorService;
        $this->healthService = $healthService;
        $this->metricsService = $metricsService;
    }

    public function index()
    {
        $user = Auth::user();
        $integrations = IntegrationSetting::where('organization_id', $user->id)
            ->with(['syncLogs' => function ($query) {
                $query->latest()->limit(5);
            }])
            ->get();

        $dashboardData = [
            'integrations' => $integrations->map(function ($integration) {
                return [
                    'id' => $integration->id,
                    'vendor' => $integration->vendor,
                    'vendor_name' => $this->vendorService->getConnector($integration->vendor)->getVendorName(),
                    'status' => $integration->is_active ? 'active' : 'inactive',
                    'health' => $this->healthService->checkIntegrationHealth($integration),
                    'last_sync' => $integration->last_sync_at,
                    'metrics' => $this->metricsService->getIntegrationMetrics($integration),
                    'recent_logs' => $integration->syncLogs
                ];
            }),
            'summary' => [
                'total_integrations' => $integrations->count(),
                'active_integrations' => $integrations->where('is_active', true)->count(),
                'healthy_integrations' => $integrations->filter(function ($integration) {
                    return $this->healthService->checkIntegrationHealth($integration)['status'] === 'healthy';
                })->count(),
                'failed_syncs_today' => $this->metricsService->getFailedSyncsToday($user->id)
            ]
        ];

        return view('integration-dashboard', compact('dashboardData'));
    }

    public function getIntegrationDetails(IntegrationSetting $integration)
    {
        $this->authorize('view', $integration);

        $details = [
            'integration' => $integration,
            'health' => $this->healthService->checkIntegrationHealth($integration),
            'metrics' => $this->metricsService->getDetailedMetrics($integration),
            'recent_activity' => $this->metricsService->getRecentActivity($integration),
            'sync_history' => $integration->syncLogs()->latest()->paginate(20)
        ];

        return response()->json($details);
    }

    public function triggerManualSync(Request $request, IntegrationSetting $integration)
    {
        $this->authorize('update', $integration);

        $request->validate([
            'type' => 'required|in:invoices,customers,all'
        ]);

        $type = $request->input('type');

        try {
            if (in_array($type, ['invoices', 'all'])) {
                \App\Jobs\SyncVendorInvoicesJob::dispatch($integration);
            }

            if (in_array($type, ['customers', 'all'])) {
                \App\Jobs\SyncVendorCustomersJob::dispatch($integration);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sync jobs queued successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
