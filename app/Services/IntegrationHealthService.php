<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\VendorIntegrationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IntegrationHealthService
{
    protected $vendorService;

    public function __construct(VendorIntegrationService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    public function checkIntegrationHealth(IntegrationSetting $integration): array
    {
        $cacheKey = "integration_health_{$integration->id}";

        return Cache::remember($cacheKey, 300, function () use ($integration) {
            return $this->performHealthCheck($integration);
        });
    }

    protected function performHealthCheck(IntegrationSetting $integration): array
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'last_checked' => now(),
            'checks' => []
        ];

        // Check if integration is active
        if (!$integration->is_active) {
            $health['status'] = 'inactive';
            $health['issues'][] = 'Integration is disabled';
        }

        // Check token expiration
        $tokenCheck = $this->checkTokenExpiration($integration);
        $health['checks']['token'] = $tokenCheck;
        if (!$tokenCheck['passed']) {
            $health['status'] = 'warning';
            $health['issues'][] = $tokenCheck['message'];
        }

        // Check last sync time
        $syncCheck = $this->checkLastSync($integration);
        $health['checks']['last_sync'] = $syncCheck;
        if (!$syncCheck['passed']) {
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
            $health['issues'][] = $syncCheck['message'];
        }

        // Check recent failures
        $failureCheck = $this->checkRecentFailures($integration);
        $health['checks']['recent_failures'] = $failureCheck;
        if (!$failureCheck['passed']) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = $failureCheck['message'];
        }

        // Test API connectivity (if enabled)
        if (config('vendor-integrations.health_checks.test_connectivity', false)) {
            $connectivityCheck = $this->checkConnectivity($integration);
            $health['checks']['connectivity'] = $connectivityCheck;
            if (!$connectivityCheck['passed']) {
                $health['status'] = 'unhealthy';
                $health['issues'][] = $connectivityCheck['message'];
            }
        }

        return $health;
    }

    protected function checkTokenExpiration(IntegrationSetting $integration): array
    {
        $expiresAt = $integration->configuration['access_token_expires_at'] ?? null;

        if (!$expiresAt) {
            return [
                'passed' => true,
                'message' => 'No token expiration configured'
            ];
        }

        $expirationTime = Carbon::parse($expiresAt);
        $now = now();

        if ($expirationTime->isPast()) {
            return [
                'passed' => false,
                'message' => 'Access token has expired'
            ];
        }

        if ($expirationTime->diffInHours($now) < 24) {
            return [
                'passed' => false,
                'message' => 'Access token expires within 24 hours'
            ];
        }

        return [
            'passed' => true,
            'message' => 'Token is valid'
        ];
    }

    protected function checkLastSync(IntegrationSetting $integration): array
    {
        $lastSync = $integration->last_sync_at;

        if (!$lastSync) {
            return [
                'passed' => false,
                'message' => 'No sync has been performed yet'
            ];
        }

        $hoursSinceLastSync = Carbon::parse($lastSync)->diffInHours(now());

        if ($hoursSinceLastSync > 48) {
            return [
                'passed' => false,
                'message' => "Last sync was {$hoursSinceLastSync} hours ago"
            ];
        }

        return [
            'passed' => true,
            'message' => "Last sync was {$hoursSinceLastSync} hours ago"
        ];
    }

    protected function checkRecentFailures(IntegrationSetting $integration): array
    {
        $recentFailures = $integration->syncLogs()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($recentFailures >= 5) {
            return [
                'passed' => false,
                'message' => "{$recentFailures} sync failures in the last 24 hours"
            ];
        }

        return [
            'passed' => true,
            'message' => "{$recentFailures} failures in the last 24 hours"
        ];
    }

    protected function checkConnectivity(IntegrationSetting $integration): array
    {
        try {
            $success = $this->vendorService->testVendorConnection($integration);

            return [
                'passed' => $success,
                'message' => $success ? 'API connectivity is working' : 'API connectivity failed'
            ];
        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => 'API connectivity test failed: ' . $e->getMessage()
            ];
        }
    }

    public function getHealthSummary(int $organizationId): array
    {
        $integrations = IntegrationSetting::where('organization_id', $organizationId)->get();

        $summary = [
            'total' => $integrations->count(),
            'healthy' => 0,
            'warning' => 0,
            'unhealthy' => 0,
            'inactive' => 0
        ];

        foreach ($integrations as $integration) {
            $health = $this->checkIntegrationHealth($integration);
            $summary[$health['status']]++;
        }

        return $summary;
    }
}
