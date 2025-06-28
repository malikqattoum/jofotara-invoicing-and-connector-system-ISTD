<?php

namespace App\Services\VendorConnectors;

use App\Models\IntegrationSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

abstract class AbstractVendorConnector
{
    protected $rateLimitDelay = 1; // seconds
    protected $maxRetries = 3;
    protected $currentIntegration;

    /**
     * Get the vendor name
     */
    abstract public function getVendorName(): string;

    /**
     * Get required configuration fields for this vendor
     */
    abstract public function getRequiredConfigFields(): array;

    /**
     * Authenticate with the vendor API
     */
    abstract public function authenticate(IntegrationSetting $integration): bool;

    /**
     * Test the connection to the vendor API
     */
    abstract public function testConnection(IntegrationSetting $integration): bool;

    /**
     * Fetch invoices from the vendor
     */
    abstract public function fetchInvoices(IntegrationSetting $integration, array $filters = []): Collection;

    /**
     * Fetch customers from the vendor
     */
    abstract public function fetchCustomers(IntegrationSetting $integration, array $filters = []): Collection;

    /**
     * Fetch a specific invoice by ID
     */
    abstract public function fetchInvoiceById(IntegrationSetting $integration, string $invoiceId): ?array;

    /**
     * Refresh authentication token
     */
    abstract public function refreshToken(IntegrationSetting $integration): bool;

    /**
     * Handle webhook from vendor
     */
    abstract public function handleWebhook(IntegrationSetting $integration, array $payload): bool;

    /**
     * Validate configuration fields
     */
    protected function validateConfiguration(IntegrationSetting $integration): void
    {
        $requiredFields = $this->getRequiredConfigFields();
        $config = $integration->configuration ?? [];

        foreach ($requiredFields as $field) {
            if ($field['required'] && empty($config[$field['key']])) {
                throw new Exception("Missing required configuration field: {$field['label']}");
            }
        }
    }

    /**
     * Circuit breaker integration
     */
    protected function circuitBreaker(callable $callback, int $failureThreshold = 5, int $retryInterval = 60): mixed
    {
        static $failureCount = 0;
        static $lastFailureTime = 0;

        if ($failureCount >= $failureThreshold && (time() - $lastFailureTime) < $retryInterval) {
            throw new Exception("Circuit breaker is open. Try again later.");
        }

        try {
            $result = $callback();
            $failureCount = 0; // Reset on success
            return $result;
        } catch (Exception $e) {
            $failureCount++;
            $lastFailureTime = time();
            throw $e;
        }
    }

    /**
     * Bidirectional sync method
     */
    abstract public function bidirectionalSync(IntegrationSetting $integration): void;

    /**
     * Get supported webhook events
     */
    abstract public function getSupportedWebhookEvents(): array;

    /**
     * Get rate limit information
     */
    abstract public function getRateLimit(): array;

    /**
     * Sync logging
     */
    protected function logSyncActivity(string $action, array $data = []): void
    {
        Log::info("Sync Activity [{$this->getVendorName()}] - {$action}", array_merge([
            'vendor' => $this->getVendorName(),
            'integration_id' => $this->currentIntegration->id ?? null,
            'action' => $action
        ], $data));
    }

    /**
     * Handle API exceptions with logging
     */
    protected function handleApiException(Exception $e, string $operation): void
    {
        $message = "Vendor API Error [{$this->getVendorName()}] - {$operation}: {$e->getMessage()}";

        Log::error($message, [
            'vendor' => $this->getVendorName(),
            'operation' => $operation,
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Handle rate limiting
     */
    protected function handleRateLimit(): void
    {
        $cacheKey = "rate_limit_{$this->getVendorName()}_" . md5(serialize($this->currentIntegration->id ?? ''));

        if (Cache::has($cacheKey)) {
            sleep($this->rateLimitDelay);
        }

        Cache::put($cacheKey, true, now()->addSeconds($this->rateLimitDelay));
    }

    /**
     * Cache authentication token
     */
    protected function cacheToken(IntegrationSetting $integration, array $tokenData): void
    {
        $cacheKey = "auth_token_{$this->getVendorName()}_{$integration->id}";
        $expiresAt = $tokenData['expires_at'] ?? (time() + 3600);

        Cache::put($cacheKey, $tokenData, now()->addSeconds($expiresAt - time() - 300)); // 5 min buffer
    }

    /**
     * Get cached authentication token
     */
    protected function getCachedToken(IntegrationSetting $integration): ?array
    {
        $cacheKey = "auth_token_{$this->getVendorName()}_{$integration->id}";
        return Cache::get($cacheKey);
    }

    /**
     * Retry API calls with exponential backoff
     */
    protected function retryApiCall(callable $callback, int $maxRetries = null): mixed
    {
        $maxRetries = $maxRetries ?? $this->maxRetries;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return $callback();
            } catch (Exception $e) {
                $attempt++;

                if ($attempt >= $maxRetries) {
                    throw $e;
                }

                // Exponential backoff: 1s, 2s, 4s, etc.
                $delay = pow(2, $attempt - 1);
                sleep($delay);
            }
        }

        return null;
    }
    /**
     * Normalize date format
     */
    protected function normalizeDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            return date('Y-m-d', strtotime($date));
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Normalize currency amount
     */
    protected function normalizeAmount($amount): float
    {
        if (is_null($amount)) {
            return 0.0;
        }

        return (float) $amount;
    }

    /**
     * Set current integration for context
     */
    public function setCurrentIntegration(IntegrationSetting $integration): void
    {
        $this->currentIntegration = $integration;
    }

    /**
     * Get current integration
     */
    protected function getCurrentIntegration(): ?IntegrationSetting
    {
        return $this->currentIntegration;
    }

    /**
     * Get vendor-specific configuration
     */
    protected function getConfig(string $key, $default = null)
    {
        if (!$this->currentIntegration) {
            return $default;
        }

        return $this->currentIntegration->configuration[$key] ?? $default;
    }

    /**
     * Log vendor activity
     */
    protected function logActivity(string $action, array $data = []): void
    {
        Log::info("Vendor Activity [{$this->getVendorName()}] - {$action}", array_merge([
            'vendor' => $this->getVendorName(),
            'integration_id' => $this->currentIntegration->id ?? null,
            'action' => $action
        ], $data));
    }

    /**
     * Check if token is expired
     */
    protected function isTokenExpired(IntegrationSetting $integration): bool
    {
        $expiresAt = $integration->configuration['access_token_expires_at'] ?? null;

        if (!$expiresAt) {
            return false;
        }

        return time() >= (strtotime($expiresAt) - 300); // 5 minute buffer
    }

    /**
     * Get pagination parameters
     */
    protected function getPaginationParams(array $filters): array
    {
        return [
            'page' => max(1, (int)($filters['page'] ?? 1)),
            'per_page' => min(100, max(10, (int)($filters['per_page'] ?? 50)))
        ];
    }

    /**
     * Build query filters
     */
    protected function buildQueryFilters(array $filters): array
    {
        $queryFilters = [];

        if (isset($filters['date_from'])) {
            $queryFilters['date_from'] = $this->normalizeDate($filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $queryFilters['date_to'] = $this->normalizeDate($filters['date_to']);
        }

        if (isset($filters['status'])) {
            $queryFilters['status'] = $filters['status'];
        }

        return $queryFilters;
    }
}
