<?php

namespace App\Services\VendorConnectors;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\Exceptions\VendorApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

class VendorIntegrationService
{
    /**
     * Get all supported vendors
     */
    public function getSupportedVendors(): array
    {
        return VendorConnectorFactory::getSupportedVendors();
    }

    /**
     * Get configuration fields for a vendor
     */
    public function getVendorConfigFields(string $vendor): array
    {
        try {
            $connector = VendorConnectorFactory::create($vendor);
            return $connector->getRequiredConfigFields();
        } catch (Exception $e) {
            throw new VendorApiException("Failed to get config fields for vendor: {$vendor}", 0, $e);
        }
    }

    /**
     * Test connection to a vendor
     */
    public function testVendorConnection(IntegrationSetting $integration): bool
    {
        try {
            $connector = VendorConnectorFactory::create($integration->vendor);
            $connector->setCurrentIntegration($integration);

            return $connector->testConnection($integration);
        } catch (Exception $e) {
            Log::error("Failed to test vendor connection", [
                'integration_id' => $integration->id,
                'vendor' => $integration->vendor,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Sync invoices from vendor
     */
    public function syncInvoices(IntegrationSetting $integration, array $filters = []): Collection
    {
        try {
            $connector = VendorConnectorFactory::create($integration->vendor);
            $connector->setCurrentIntegration($integration);

            $invoices = $connector->fetchInvoices($integration, $filters);

            Log::info("Synced invoices from vendor", [
                'integration_id' => $integration->id,
                'vendor' => $integration->vendor,
                'count' => $invoices->count()
            ]);

            return $invoices;
        } catch (Exception $e) {
            Log::error("Failed to sync invoices from vendor", [
                'integration_id' => $integration->id,
                'vendor' => $integration->vendor,
                'error' => $e->getMessage()
            ]);

            throw new VendorApiException(
                "Failed to sync invoices from {$integration->vendor}",
                0,
                $e,
                $integration->vendor,
                'syncInvoices'
            );
        }
    }

    /**
     * Sync customers from vendor
     */
    public function syncCustomers(IntegrationSetting $integration, array $filters = []): Collection
    {
        try {
            $connector = VendorConnectorFactory::create($integration->vendor);
            $connector->setCurrentIntegration($integration);

            $customers = $connector->fetchCustomers($integration, $filters);

            Log::info("Synced customers from vendor", [
                'integration_id' => $integration->id,
                'vendor' => $integration->vendor,
                'count' => $customers->count()
            ]);

            return $customers;
        } catch (Exception $e) {
            Log::error("Failed to sync customers from vendor", [
                'integration_id' => $integration->id,
                'vendor' => $integration->vendor,
                'error' => $e->getMessage()
            ]);

            throw new VendorApiException(
                "Failed to sync customers from {$integration->vendor}",
                0,
                $e,
                $integration->vendor,
                'syncCustomers'
            );
        }
    }

    /**
     * Get a specific invoice from vendor
     */
    public function getInvoice(IntegrationSetting $integration, string $invoiceId): ?array
    {
        try {
            $connector = VendorConnectorFactory::create($integration->vendor);
            $connector->setCurrentIntegration($integration);

            return $connector->fetchInvoiceById($integration, $invoiceId);
        } catch (Exception $e) {
            Log::error("Failed to fetch invoice from vendor", [
                'integration_id' => $integration->id,
                'vendor' => $integration->vendor,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Handle webhook from vendor
     */
    public function handleWebhook(IntegrationSetting $integration, array $payload): bool
    {
        try {
            $connector = VendorConnectorFactory::create($integration->vendor);
            $connector->setCurrentIntegration($integration);

            $result = $connector->handleWebhook($integration, $payload);

            Log::info("Processed vendor webhook", [
                'integration_id' => $integration->id,
                'vendor' => $integration->vendor,
                'success' => $result
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error("Failed to process vendor webhook", [
                'integration_id' => $integration->id,
                'vendor' => $integration->vendor,
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return false;
        }
    }

    /**
     * Refresh vendor authentication token
     */
    public function refreshVendorToken(IntegrationSetting $integration): bool
    {
        try {
            $connector = VendorConnectorFactory::create($integration->vendor);
            $connector->setCurrentIntegration($integration);

            $result = $connector->refreshToken($integration);

            if ($result) {
                Log::info("Refreshed vendor token", [
                    'integration_id' => $integration->id,
                    'vendor' => $integration->vendor
                ]);
            }

            return $result;
        } catch (Exception $e) {
            Log::error("Failed to refresh vendor token", [
                'integration_id' => $integration->id,
                'vendor' => $integration->vendor,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get vendor connector instance
     */
    public function getConnector(string $vendor): AbstractVendorConnector
    {
        return VendorConnectorFactory::create($vendor);
    }

    /**
     * Validate vendor configuration
     */
    public function validateVendorConfig(string $vendor, array $config): array
    {
        $connector = VendorConnectorFactory::create($vendor);
        $requiredFields = $connector->getRequiredConfigFields();
        $errors = [];

        foreach ($requiredFields as $field) {
            if ($field['required'] && empty($config[$field['key']])) {
                $errors[] = "Missing required field: {$field['label']}";
            }
        }

        return $errors;
    }

    /**
     * Get vendor statistics
     */
    public function getVendorStats(IntegrationSetting $integration): array
    {
        return [
            'vendor' => $integration->vendor,
            'status' => $integration->is_active ? 'active' : 'inactive',
            'last_sync' => $integration->last_sync_at,
            'created_at' => $integration->created_at,
            'updated_at' => $integration->updated_at
        ];
    }
}
