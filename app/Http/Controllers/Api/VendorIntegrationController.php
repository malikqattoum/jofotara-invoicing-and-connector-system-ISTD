<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\VendorIntegrationService;
use App\Jobs\SyncVendorInvoicesJob;
use App\Jobs\SyncVendorCustomersJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class VendorIntegrationController extends Controller
{
    protected $vendorService;

    public function __construct(VendorIntegrationService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    /**
     * Get all supported vendors
     */
    public function getSupportedVendors(): JsonResponse
    {
        $vendors = $this->vendorService->getSupportedVendors();

        return response()->json([
            'success' => true,
            'data' => $vendors
        ]);
    }

    /**
     * Get configuration fields for a vendor
     */
    public function getVendorConfig(string $vendor): JsonResponse
    {
        try {
            $config = $this->vendorService->getVendorConfigFields($vendor);

            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Test vendor connection
     */
    public function testConnection(Request $request, IntegrationSetting $integration): JsonResponse
    {
        $success = $this->vendorService->testVendorConnection($integration);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Connection successful' : 'Connection failed'
        ]);
    }

    /**
     * Sync invoices from vendor
     */
    public function syncInvoices(Request $request, IntegrationSetting $integration): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
            'async' => 'nullable|boolean'
        ]);

        $filters = $request->only(['date_from', 'date_to', 'page', 'per_page']);
        $async = $request->boolean('async', true);

        try {
            if ($async) {
                // Queue the sync job
                SyncVendorInvoicesJob::dispatch($integration, $filters);

                return response()->json([
                    'success' => true,
                    'message' => 'Invoice sync job queued successfully'
                ]);
            } else {
                // Sync immediately
                $invoices = $this->vendorService->syncInvoices($integration, $filters);

                return response()->json([
                    'success' => true,
                    'data' => $invoices,
                    'count' => $invoices->count()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync customers from vendor
     */
    public function syncCustomers(Request $request, IntegrationSetting $integration): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
            'async' => 'nullable|boolean'
        ]);

        $filters = $request->only(['page', 'per_page']);
        $async = $request->boolean('async', true);

        try {
            if ($async) {
                // Queue the sync job
                SyncVendorCustomersJob::dispatch($integration, $filters);

                return response()->json([
                    'success' => true,
                    'message' => 'Customer sync job queued successfully'
                ]);
            } else {
                // Sync immediately
                $customers = $this->vendorService->syncCustomers($integration, $filters);

                return response()->json([
                    'success' => true,
                    'data' => $customers,
                    'count' => $customers->count()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific invoice from vendor
     */
    public function getInvoice(IntegrationSetting $integration, string $invoiceId): JsonResponse
    {
        try {
            $invoice = $this->vendorService->getInvoice($integration, $invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle vendor webhook
     */
    public function handleWebhook(Request $request, IntegrationSetting $integration): JsonResponse
    {
        try {
            $payload = $request->all();
            $success = $this->vendorService->handleWebhook($integration, $payload);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Webhook processed successfully' : 'Webhook processing failed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh vendor token
     */
    public function refreshToken(IntegrationSetting $integration): JsonResponse
    {
        try {
            $success = $this->vendorService->refreshVendorToken($integration);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Token refreshed successfully' : 'Token refresh failed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor statistics
     */
    public function getStats(IntegrationSetting $integration): JsonResponse
    {
        $stats = $this->vendorService->getVendorStats($integration);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Validate vendor configuration
     */
    public function validateConfig(Request $request): JsonResponse
    {
        $request->validate([
            'vendor' => ['required', 'string', Rule::in($this->vendorService->getSupportedVendors())],
            'config' => 'required|array'
        ]);

        try {
            $errors = $this->vendorService->validateVendorConfig(
                $request->input('vendor'),
                $request->input('config')
            );

            return response()->json([
                'success' => empty($errors),
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
