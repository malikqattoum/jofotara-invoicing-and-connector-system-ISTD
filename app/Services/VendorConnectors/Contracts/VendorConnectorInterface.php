<?php

namespace App\Services\VendorConnectors\Contracts;

use App\Models\IntegrationSetting;
use Illuminate\Support\Collection;

interface VendorConnectorInterface
{
    /**
     * Authenticate with the vendor system
     */
    public function authenticate(IntegrationSetting $integration): bool;

    /**
     * Test the connection to the vendor system
     */
    public function testConnection(IntegrationSetting $integration): bool;

    /**
     * Fetch invoices from the vendor system
     */
    public function fetchInvoices(IntegrationSetting $integration, array $filters = []): Collection;

    /**
     * Fetch customers from the vendor system
     */
    public function fetchCustomers(IntegrationSetting $integration, array $filters = []): Collection;

    /**
     * Fetch a specific invoice by ID
     */
    public function fetchInvoiceById(IntegrationSetting $integration, string $invoiceId): ?array;

    /**
     * Get the vendor system name
     */
    public function getVendorName(): string;

    /**
     * Get required configuration fields for this vendor
     */
    public function getRequiredConfigFields(): array;

    /**
     * Get supported webhook events
     */
    public function getSupportedWebhookEvents(): array;

    /**
     * Handle webhook payload
     */
    public function handleWebhook(IntegrationSetting $integration, array $payload): bool;

    /**
     * Get rate limit information
     */
    public function getRateLimit(): array;

    /**
     * Refresh authentication token if needed
     */
    public function refreshToken(IntegrationSetting $integration): bool;
}
