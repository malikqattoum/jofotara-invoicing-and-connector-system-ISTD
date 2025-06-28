<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class IntegrationController extends Controller
{
    /**
     * Display a listing of the integrations.
     */
    public function index()
    {
        $vendor = Auth::user();
        $integrations = IntegrationSetting::where('vendor_id', $vendor->id)
            ->with(['syncLogs' => function($query) {
                $query->latest()->take(5);
            }])
            ->get();

        return view('vendor.integrations.index', compact('integrations'));
    }

    /**
     * Show the form for creating a new integration.
     */
    public function create()
    {
        $availableVendors = [
            'xero' => 'Xero',
            'quickbooks' => 'QuickBooks',
            'sage' => 'Sage',
            'myob' => 'MYOB',
            'zoho' => 'Zoho Books',
            'freshbooks' => 'FreshBooks',
            'wave' => 'Wave Accounting',
            'kashflow' => 'KashFlow',
            'freeagent' => 'FreeAgent',
            'custom' => 'Custom API'
        ];

        return view('vendor.integrations.create', compact('availableVendors'));
    }

    /**
     * Store a newly created integration in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor' => 'required|string|max:255',
            'integration_type' => 'required|in:api,webhook,file_transfer,direct_db',
            'api_endpoint' => 'nullable|url',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'is_active' => 'boolean',
            'sync_frequency' => 'required|in:real_time,hourly,daily,weekly,manual',
            'data_mapping' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $integration = IntegrationSetting::create([
            'vendor_id' => Auth::id(),
            'vendor' => $request->vendor,
            'integration_type' => $request->integration_type,
            'api_endpoint' => $request->api_endpoint,
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
            'webhook_url' => $request->webhook_url,
            'is_active' => $request->boolean('is_active', true),
            'sync_frequency' => $request->sync_frequency,
            'data_mapping' => $request->data_mapping ? json_decode($request->data_mapping, true) : null,
            'status' => 'inactive',
            'sync_status' => 'idle'
        ]);

        return redirect()->route('vendor.integrations.index')
            ->with('success', 'Integration created successfully.');
    }

    /**
     * Display the specified integration.
     */
    public function show(IntegrationSetting $integration)
    {
        $this->authorize('view', $integration);

        $integration->load(['syncLogs' => function($query) {
            $query->latest()->take(20);
        }]);

        return view('vendor.integrations.show', compact('integration'));
    }

    /**
     * Update the specified integration in storage.
     */
    public function update(Request $request, IntegrationSetting $integration)
    {
        $this->authorize('update', $integration);

        $validator = Validator::make($request->all(), [
            'vendor' => 'required|string|max:255',
            'integration_type' => 'required|in:api,webhook,file_transfer,direct_db',
            'api_endpoint' => 'nullable|url',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'is_active' => 'boolean',
            'sync_frequency' => 'required|in:real_time,hourly,daily,weekly,manual',
            'data_mapping' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $integration->update([
            'vendor' => $request->vendor,
            'integration_type' => $request->integration_type,
            'api_endpoint' => $request->api_endpoint,
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
            'webhook_url' => $request->webhook_url,
            'is_active' => $request->boolean('is_active'),
            'sync_frequency' => $request->sync_frequency,
            'data_mapping' => $request->data_mapping ? json_decode($request->data_mapping, true) : null,
        ]);

        return redirect()->route('vendor.integrations.show', $integration)
            ->with('success', 'Integration updated successfully.');
    }

    /**
     * Remove the specified integration from storage.
     */
    public function destroy(IntegrationSetting $integration)
    {
        $this->authorize('delete', $integration);

        $integration->delete();

        return redirect()->route('vendor.integrations.index')
            ->with('success', 'Integration deleted successfully.');
    }

    /**
     * Test the integration connection.
     */
    public function test(IntegrationSetting $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        try {
            // Here you would implement the actual connection test
            // For now, we'll simulate a test
            $testResult = $this->performConnectionTest($integration);

            if ($testResult['success']) {
                $integration->update(['status' => 'active']);
                return response()->json([
                    'success' => true,
                    'message' => 'Connection test successful.',
                    'data' => $testResult['data']
                ]);
            } else {
                $integration->update(['status' => 'error']);
                return response()->json([
                    'success' => false,
                    'message' => 'Connection test failed: ' . $testResult['error']
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync data from the integration.
     */
    public function sync(IntegrationSetting $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        if (!$integration->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Integration is not active.'
            ], 422);
        }

        try {
            $integration->update(['sync_status' => 'syncing']);

            // Log the sync attempt
            SyncLog::create([
                'integration_setting_id' => $integration->id,
                'vendor_id' => $integration->vendor_id,
                'sync_type' => 'manual',
                'status' => 'running',
                'details' => ['message' => 'Manual sync started']
            ]);

            // Here you would implement the actual sync logic
            $syncResult = $this->performSync($integration);

            $integration->update([
                'sync_status' => 'idle',
                'last_sync_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sync completed successfully.',
                'data' => $syncResult
            ]);
        } catch (\Exception $e) {
            $integration->update(['sync_status' => 'error']);

            SyncLog::create([
                'integration_setting_id' => $integration->id,
                'vendor_id' => $integration->vendor_id,
                'sync_type' => 'manual',
                'status' => 'failed',
                'details' => ['error' => $e->getMessage()]
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle integration active status.
     */
    public function toggle(IntegrationSetting $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        $integration->update([
            'is_active' => !$integration->is_active,
            'status' => $integration->is_active ? 'inactive' : 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => $integration->is_active ? 'Integration activated.' : 'Integration deactivated.',
            'is_active' => $integration->is_active
        ]);
    }

    /**
     * Show integration logs.
     */
    public function logs(IntegrationSetting $integration)
    {
        $this->authorize('view', $integration);

        $logs = SyncLog::where('integration_setting_id', $integration->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('vendor.integrations.logs', compact('integration', 'logs'));
    }

    /**
     * Show integration setup guide.
     */
    public function setup(IntegrationSetting $integration)
    {
        $this->authorize('view', $integration);

        return view('vendor.integrations.setup', compact('integration'));
    }

    /**
     * Perform connection test (placeholder implementation).
     */
    private function performConnectionTest(IntegrationSetting $integration): array
    {
        // This is a placeholder - implement actual connection testing logic here
        return [
            'success' => true,
            'data' => [
                'vendor' => $integration->vendor,
                'endpoint' => $integration->api_endpoint,
                'response_time' => '150ms',
                'status' => 'Connected'
            ]
        ];
    }

    /**
     * Perform sync operation (placeholder implementation).
     */
    private function performSync(IntegrationSetting $integration): array
    {
        // This is a placeholder - implement actual sync logic here
        return [
            'invoices_synced' => rand(1, 50),
            'customers_synced' => rand(1, 20),
            'products_synced' => rand(1, 100)
        ];
    }
}
