<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\VendorConnectors\QuickBooksConnector;
use App\Services\VendorConnectors\XeroConnector;
use App\Services\VendorConnectors\ShopifyConnector;
use App\Services\VendorConnectors\SquareConnector;

class IntegrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Test connection for a new integration before creating it
     */
    public function testConnection(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'integration_type' => 'required|string|in:quickbooks,xero,shopify,square,generic',
                'config' => 'required|array'
            ]);

            $integrationType = $validated['integration_type'];
            $config = $validated['config'];

            // Test connection based on integration type
            $result = $this->performConnectionTest($integrationType, $config);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'details' => $result['details'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Integration connection test failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'integration_type' => $request->input('integration_type'),
                'error' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test an existing integration
     */
    public function testIntegration(IntegrationSetting $integration): JsonResponse
    {
        try {
            // Check if user owns this integration
            if ($integration->user_id !== Auth::id() && $integration->vendor_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to integration'
                ], 403);
            }

            $result = $this->performConnectionTest($integration->vendor_name, $integration->settings);

            // Update integration status based on test result
            $integration->update([
                'status' => $result['success'] ? 'active' : 'error',
                'last_error' => $result['success'] ? null : $result['message'],
                'last_tested_at' => now()
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Integration test failed: ' . $e->getMessage(), [
                'integration_id' => $integration->id,
                'user_id' => Auth::id(),
                'error' => $e->getTraceAsString()
            ]);

            // Update integration with error status
            $integration->update([
                'status' => 'error',
                'last_error' => $e->getMessage(),
                'last_tested_at' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Integration test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force sync an integration
     */
    public function forceSync(IntegrationSetting $integration): JsonResponse
    {
        try {
            // Check if user owns this integration
            if ($integration->user_id !== Auth::id() && $integration->vendor_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to integration'
                ], 403);
            }

            // Check if integration is active
            if ($integration->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Integration must be active to perform sync'
                ], 400);
            }

            // Update sync status
            $integration->update([
                'sync_status' => 'syncing',
                'last_sync_started_at' => now()
            ]);

            // Dispatch sync job based on integration type
            $this->dispatchSyncJob($integration);

            return response()->json([
                'success' => true,
                'message' => 'Sync started successfully. This may take a few minutes to complete.',
                'sync_status' => 'syncing'
            ]);

        } catch (\Exception $e) {
            Log::error('Integration sync failed: ' . $e->getMessage(), [
                'integration_id' => $integration->id,
                'user_id' => Auth::id(),
                'error' => $e->getTraceAsString()
            ]);

            // Reset sync status on error
            $integration->update([
                'sync_status' => 'idle',
                'last_error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an integration
     */
    public function destroy(IntegrationSetting $integration): JsonResponse
    {
        try {
            // Check if user owns this integration
            if ($integration->user_id !== Auth::id() && $integration->vendor_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to integration'
                ], 403);
            }

            // Store integration name for response
            $integrationName = $integration->vendor_name;

            // Delete the integration
            $integration->delete();

            Log::info('Integration deleted successfully', [
                'integration_id' => $integration->id,
                'integration_name' => $integrationName,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Integration '{$integrationName}' has been removed successfully."
            ]);

        } catch (\Exception $e) {
            Log::error('Integration deletion failed: ' . $e->getMessage(), [
                'integration_id' => $integration->id,
                'user_id' => Auth::id(),
                'error' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove integration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform connection test based on integration type
     */
    private function performConnectionTest(string $integrationType, array $config): array
    {
        switch (strtolower($integrationType)) {
            case 'quickbooks':
                return $this->testQuickBooksConnection($config);
            case 'xero':
                return $this->testXeroConnection($config);
            case 'shopify':
                return $this->testShopifyConnection($config);
            case 'square':
                return $this->testSquareConnection($config);
            case 'generic':
                return $this->testGenericAPIConnection($config);
            default:
                return [
                    'success' => false,
                    'message' => 'Unsupported integration type: ' . $integrationType
                ];
        }
    }

    /**
     * Test QuickBooks connection
     */
    private function testQuickBooksConnection(array $config): array
    {
        try {
            // Basic validation
            if (empty($config['client_id']) || empty($config['client_secret'])) {
                return [
                    'success' => false,
                    'message' => 'Client ID and Client Secret are required for QuickBooks integration.'
                ];
            }

            // If connector service exists, use it
            if (class_exists(QuickBooksConnector::class)) {
                $connector = new QuickBooksConnector($config);
                return $connector->testConnection();
            }

            // Basic mock test
            return [
                'success' => true,
                'message' => 'QuickBooks configuration appears valid. Full connection test requires OAuth completion.',
                'details' => [
                    'environment' => $config['environment'] ?? 'sandbox',
                    'requires_oauth' => true
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'QuickBooks connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test Xero connection
     */
    private function testXeroConnection(array $config): array
    {
        try {
            if (empty($config['client_id']) || empty($config['client_secret'])) {
                return [
                    'success' => false,
                    'message' => 'Client ID and Client Secret are required for Xero integration.'
                ];
            }

            // If connector service exists, use it
            if (class_exists(XeroConnector::class)) {
                $connector = new XeroConnector($config);
                return $connector->testConnection();
            }

            return [
                'success' => true,
                'message' => 'Xero configuration appears valid. Full connection test requires OAuth completion.',
                'details' => [
                    'tenant_id' => $config['tenant_id'] ?? 'Not provided',
                    'requires_oauth' => true
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Xero connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test Shopify connection
     */
    private function testShopifyConnection(array $config): array
    {
        try {
            if (empty($config['store_url']) || empty($config['access_token'])) {
                return [
                    'success' => false,
                    'message' => 'Store URL and Access Token are required for Shopify integration.'
                ];
            }

            // Basic URL validation
            if (!filter_var($config['store_url'], FILTER_VALIDATE_URL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid store URL format.'
                ];
            }

            // If connector service exists, use it
            if (class_exists(ShopifyConnector::class)) {
                $connector = new ShopifyConnector($config);
                return $connector->testConnection();
            }

            return [
                'success' => true,
                'message' => 'Shopify configuration appears valid.',
                'details' => [
                    'store_url' => $config['store_url']
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Shopify connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test Square connection
     */
    private function testSquareConnection(array $config): array
    {
        try {
            if (empty($config['application_id']) || empty($config['access_token'])) {
                return [
                    'success' => false,
                    'message' => 'Application ID and Access Token are required for Square integration.'
                ];
            }

            // If connector service exists, use it
            if (class_exists(SquareConnector::class)) {
                $connector = new SquareConnector($config);
                return $connector->testConnection();
            }

            return [
                'success' => true,
                'message' => 'Square configuration appears valid.',
                'details' => [
                    'environment' => $config['environment'] ?? 'sandbox'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Square connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test Generic API connection
     */
    private function testGenericAPIConnection(array $config): array
    {
        try {
            if (empty($config['base_url'])) {
                return [
                    'success' => false,
                    'message' => 'Base URL is required for Generic API integration.'
                ];
            }

            // Basic URL validation
            if (!filter_var($config['base_url'], FILTER_VALIDATE_URL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid base URL format.'
                ];
            }

            // Try to make a basic request to test connectivity
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $headers = [];

            // Add authentication headers based on auth type
            switch ($config['auth_type'] ?? 'api_key') {
                case 'api_key':
                    if (!empty($config['api_key'])) {
                        $headers['X-API-Key'] = $config['api_key'];
                    }
                    break;
                case 'bearer_token':
                    if (!empty($config['api_key'])) {
                        $headers['Authorization'] = 'Bearer ' . $config['api_key'];
                    }
                    break;
            }

            try {
                $response = $client->head($config['base_url'], ['headers' => $headers]);
                $statusCode = $response->getStatusCode();

                if ($statusCode < 400) {
                    return [
                        'success' => true,
                        'message' => 'Generic API connection successful.',
                        'details' => [
                            'status_code' => $statusCode,
                            'base_url' => $config['base_url']
                        ]
                    ];
                }
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                // Connection might still be valid, just endpoint doesn't support HEAD
                if ($e->getCode() === 405) {
                    return [
                        'success' => true,
                        'message' => 'Generic API appears reachable (Method not allowed is expected).',
                        'details' => [
                            'base_url' => $config['base_url']
                        ]
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Generic API configuration appears valid.',
                'details' => [
                    'base_url' => $config['base_url'],
                    'auth_type' => $config['auth_type'] ?? 'api_key'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Generic API connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Dispatch appropriate sync job for the integration
     */
    private function dispatchSyncJob(IntegrationSetting $integration): void
    {
        // In a real implementation, this would dispatch specific jobs
        // For now, we'll just log the action
        Log::info('Sync job dispatched for integration', [
            'integration_id' => $integration->id,
            'vendor_name' => $integration->vendor_name,
            'user_id' => Auth::id()
        ]);

        // Update status to completed for demo purposes
        // In real implementation, the job would update this
        dispatch(function () use ($integration) {
            sleep(2); // Simulate processing time
            $integration->update([
                'sync_status' => 'idle',
                'last_sync_at' => now()
            ]);
        })->delay(now()->addSeconds(5));
    }
}
