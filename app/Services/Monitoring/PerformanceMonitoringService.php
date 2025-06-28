<?php

namespace App\Services\Monitoring;

use App\Models\IntegrationSetting;
use App\Models\SyncJob;
use App\Models\PerformanceMetric;
use App\Models\SystemAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Exception;

class PerformanceMonitoringService
{
    private $metricRetentionDays = 90;
    private $alertThresholds = [
        'response_time' => 5000, // ms
        'error_rate' => 5, // %
        'queue_size' => 1000,
        'memory_usage' => 80, // %
        'cpu_usage' => 80 // %
    ];

    /**
     * Record performance metric
     */
    public function recordMetric(string $type, $value, array $metadata = []): void
    {
        try {
            PerformanceMetric::create([
                'type' => $type,
                'value' => $value,
                'metadata' => $metadata,
                'recorded_at' => now(),
                'integration_id' => $metadata['integration_id'] ?? null
            ]);

            // Store in Redis for real-time monitoring
            $this->storeRealTimeMetric($type, $value, $metadata);

            // Check thresholds and create alerts if needed
            $this->checkThresholds($type, $value, $metadata);

        } catch (Exception $e) {
            Log::error("Failed to record performance metric: " . $e->getMessage());
        }
    }

    /**
     * Get real-time system metrics
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'system' => $this->getSystemMetrics(),
            'api' => $this->getAPIMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'queue' => $this->getQueueMetrics(),
            'integrations' => $this->getIntegrationMetrics(),
            'alerts' => $this->getActiveAlerts()
        ];
    }

    /**
     * Get system performance metrics
     */
    private function getSystemMetrics(): array
    {
        $cpuUsage = $this->getCPUUsage();
        $memoryUsage = $this->getMemoryUsage();
        $diskUsage = $this->getDiskUsage();

        return [
            'cpu_usage' => $cpuUsage,
            'memory_usage' => $memoryUsage,
            'disk_usage' => $diskUsage,
            'load_average' => $this->getLoadAverage(),
            'uptime' => $this->getSystemUptime()
        ];
    }

    /**
     * Get API performance metrics
     */
    private function getAPIMetrics(): array
    {
        $timeWindow = 300; // 5 minutes
        $cacheKey = "api_metrics_{$timeWindow}";

        return Cache::remember($cacheKey, 60, function () use ($timeWindow) {
            $since = now()->subSeconds($timeWindow);

            $metrics = PerformanceMetric::where('type', 'api_request')
                ->where('recorded_at', '>=', $since)
                ->get();

            $totalRequests = $metrics->count();
            $averageResponseTime = $metrics->avg('value');
            $errorCount = $metrics->where('metadata.status_code', '>=', 400)->count();
            $errorRate = $totalRequests > 0 ? ($errorCount / $totalRequests) * 100 : 0;

            return [
                'total_requests' => $totalRequests,
                'requests_per_minute' => $totalRequests / 5,
                'average_response_time' => round($averageResponseTime, 2),
                'error_rate' => round($errorRate, 2),
                'slowest_endpoints' => $this->getSlowestEndpoints($since)
            ];
        });
    }

    /**
     * Get database performance metrics
     */
    private function getDatabaseMetrics(): array
    {
        try {
            $connections = DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0;
            $maxConnections = DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value ?? 1;
            $connectionUsage = ($connections / $maxConnections) * 100;

            $slowQueries = DB::select('SHOW STATUS LIKE "Slow_queries"')[0]->Value ?? 0;

            return [
                'connection_usage' => round($connectionUsage, 2),
                'active_connections' => (int) $connections,
                'max_connections' => (int) $maxConnections,
                'slow_queries' => (int) $slowQueries,
                'query_cache_hit_rate' => $this->getQueryCacheHitRate()
            ];
        } catch (Exception $e) {
            Log::error("Failed to get database metrics: " . $e->getMessage());
            return ['error' => 'Unable to fetch database metrics'];
        }
    }

    /**
     * Get queue performance metrics
     */
    private function getQueueMetrics(): array
    {
        try {
            $queueSizes = [];
            $queues = ['default', 'sync-high', 'sync-normal', 'sync-low', 'notifications'];

            foreach ($queues as $queue) {
                $queueSizes[$queue] = Redis::llen("queue:$queue");
            }

            $failedJobs = Redis::llen('failed_jobs');
            $totalQueued = array_sum($queueSizes);

            return [
                'queue_sizes' => $queueSizes,
                'total_queued' => $totalQueued,
                'failed_jobs' => $failedJobs,
                'processing_rate' => $this->getJobProcessingRate()
            ];
        } catch (Exception $e) {
            Log::error("Failed to get queue metrics: " . $e->getMessage());
            return ['error' => 'Unable to fetch queue metrics'];
        }
    }

    /**
     * Get integration performance metrics
     */
    private function getIntegrationMetrics(): array
    {
        $timeWindow = 3600; // 1 hour
        $since = now()->subSeconds($timeWindow);

        $syncJobs = SyncJob::where('created_at', '>=', $since)->get();

        $byStatus = $syncJobs->groupBy('status')->map->count();
        $byIntegration = $syncJobs->groupBy('integration_id')->map->count();

        $averageExecutionTime = $syncJobs->where('status', 'completed')->avg('execution_time');
        $successRate = $syncJobs->count() > 0 ?
            ($syncJobs->where('status', 'completed')->count() / $syncJobs->count()) * 100 : 100;

        return [
            'jobs_by_status' => $byStatus->toArray(),
            'jobs_by_integration' => $byIntegration->toArray(),
            'average_execution_time' => round($averageExecutionTime ?? 0, 2),
            'success_rate' => round($successRate, 2),
            'total_records_synced' => $syncJobs->sum('records_synced')
        ];
    }

    /**
     * Get active alerts
     */
    private function getActiveAlerts(): array
    {
        return SystemAlert::where('status', 'active')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Store real-time metric in Redis
     */
    private function storeRealTimeMetric(string $type, $value, array $metadata): void
    {
        try {
            $key = "metrics:realtime:{$type}";
            $data = [
                'value' => $value,
                'timestamp' => now()->timestamp,
                'metadata' => $metadata
            ];

            Redis::lpush($key, json_encode($data));
            Redis::ltrim($key, 0, 999); // Keep last 1000 entries
            Redis::expire($key, 3600); // 1 hour TTL
        } catch (Exception $e) {
            Log::error("Failed to store real-time metric: " . $e->getMessage());
        }
    }

    /**
     * Check performance thresholds and create alerts
     */
    private function checkThresholds(string $type, $value, array $metadata): void
    {
        $threshold = $this->alertThresholds[$type] ?? null;

        if (!$threshold) {
            return;
        }

        $isViolation = match($type) {
            'response_time' => $value > $threshold,
            'error_rate' => $value > $threshold,
            'queue_size' => $value > $threshold,
            'memory_usage', 'cpu_usage' => $value > $threshold,
            default => false
        };

        if ($isViolation) {
            $this->createAlert($type, $value, $threshold, $metadata);
        }
    }

    /**
     * Create performance alert
     */
    private function createAlert(string $type, $value, $threshold, array $metadata): void
    {
        try {
            // Check if similar alert already exists (prevent spam)
            $recentAlert = SystemAlert::where('type', $type)
                ->where('status', 'active')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->first();

            if ($recentAlert) {
                return; // Don't create duplicate alerts
            }

            $severity = $this->determineSeverity($type, $value, $threshold);
            $message = $this->buildAlertMessage($type, $value, $threshold);

            SystemAlert::create([
                'type' => $type,
                'severity' => $severity,
                'message' => $message,
                'value' => $value,
                'threshold' => $threshold,
                'metadata' => $metadata,
                'status' => 'active',
                'created_at' => now()
            ]);

            Log::warning("Performance alert created: {$message}");

        } catch (Exception $e) {
            Log::error("Failed to create performance alert: " . $e->getMessage());
        }
    }

    /**
     * Determine alert severity
     */
    private function determineSeverity(string $type, $value, $threshold): string
    {
        $ratio = $value / $threshold;

        if ($ratio >= 2) return 'critical';
        if ($ratio >= 1.5) return 'high';
        if ($ratio >= 1.2) return 'medium';
        return 'low';
    }

    /**
     * Build alert message
     */
    private function buildAlertMessage(string $type, $value, $threshold): string
    {
        return match($type) {
            'response_time' => "API response time ({$value}ms) exceeded threshold ({$threshold}ms)",
            'error_rate' => "Error rate ({$value}%) exceeded threshold ({$threshold}%)",
            'queue_size' => "Queue size ({$value}) exceeded threshold ({$threshold})",
            'memory_usage' => "Memory usage ({$value}%) exceeded threshold ({$threshold}%)",
            'cpu_usage' => "CPU usage ({$value}%) exceeded threshold ({$threshold}%)",
            default => "Performance metric {$type} ({$value}) exceeded threshold ({$threshold})"
        };
    }

    /**
     * Get system health score
     */
    public function getSystemHealthScore(): array
    {
        $metrics = $this->getRealTimeMetrics();
        $scores = [];

        // System metrics (25%)
        $systemScore = 100;
        if ($metrics['system']['cpu_usage'] > 80) $systemScore -= 30;
        if ($metrics['system']['memory_usage'] > 80) $systemScore -= 30;
        if ($metrics['system']['disk_usage'] > 90) $systemScore -= 40;
        $scores['system'] = max(0, $systemScore) * 0.25;

        // API metrics (25%)
        $apiScore = 100;
        if ($metrics['api']['error_rate'] > 5) $apiScore -= 40;
        if ($metrics['api']['average_response_time'] > 3000) $apiScore -= 30;
        $scores['api'] = max(0, $apiScore) * 0.25;

        // Database metrics (25%)
        $dbScore = 100;
        if ($metrics['database']['connection_usage'] > 80) $dbScore -= 30;
        $scores['database'] = max(0, $dbScore) * 0.25;

        // Integration metrics (25%)
        $integrationScore = 100;
        if ($metrics['integrations']['success_rate'] < 95) {
            $integrationScore -= (95 - $metrics['integrations']['success_rate']) * 2;
        }
        $scores['integration'] = max(0, $integrationScore) * 0.25;

        $overallScore = array_sum($scores);

        return [
            'overall_score' => round($overallScore, 1),
            'component_scores' => [
                'system' => round($scores['system'] / 0.25, 1),
                'api' => round($scores['api'] / 0.25, 1),
                'database' => round($scores['database'] / 0.25, 1),
                'integration' => round($scores['integration'] / 0.25, 1)
            ],
            'status' => $this->getHealthStatus($overallScore)
        ];
    }

    /**
     * Get health status based on score
     */
    private function getHealthStatus(float $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 75) return 'good';
        if ($score >= 60) return 'fair';
        if ($score >= 40) return 'poor';
        return 'critical';
    }

    /**
     * Clean up old metrics
     */
    public function cleanupOldMetrics(): void
    {
        $cutoffDate = now()->subDays($this->metricRetentionDays);

        PerformanceMetric::where('recorded_at', '<', $cutoffDate)->delete();
        SystemAlert::where('status', 'resolved')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        Log::info("Cleaned up performance metrics older than {$this->metricRetentionDays} days");
    }

    // Helper methods for system metrics
    private function getCPUUsage(): float
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $load = sys_getloadavg();
            return $load ? round($load[0] * 100, 2) : 0;
        }
        return 0; // Windows/other OS support would be added here
    }

    private function getMemoryUsage(): float
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);

        if ($memoryLimit === '-1') return 0; // No limit

        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        return round(($memoryUsage / $memoryLimitBytes) * 100, 2);
    }

    private function getDiskUsage(): float
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');

        if (!$totalSpace || !$freeSpace) return 0;

        $usedSpace = $totalSpace - $freeSpace;
        return round(($usedSpace / $totalSpace) * 100, 2);
    }

    private function getLoadAverage(): array
    {
        if (PHP_OS_FAMILY === 'Linux') {
            return sys_getloadavg() ?: [0, 0, 0];
        }
        return [0, 0, 0];
    }

    private function getSystemUptime(): int
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = shell_exec('cat /proc/uptime');
            return $uptime ? (int) explode(' ', $uptime)[0] : 0;
        }
        return 0;
    }

    private function convertToBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;

        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }

        return $val;
    }

    private function getSlowestEndpoints(Carbon $since): array
    {
        return PerformanceMetric::where('type', 'api_request')
            ->where('recorded_at', '>=', $since)
            ->selectRaw('JSON_EXTRACT(metadata, "$.endpoint") as endpoint, AVG(value) as avg_time')
            ->groupBy('endpoint')
            ->orderBy('avg_time', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getQueryCacheHitRate(): float
    {
        try {
            $hits = DB::select('SHOW STATUS LIKE "Qcache_hits"')[0]->Value ?? 0;
            $inserts = DB::select('SHOW STATUS LIKE "Qcache_inserts"')[0]->Value ?? 0;

            $total = $hits + $inserts;
            return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function getJobProcessingRate(): float
    {
        $timeWindow = 300; // 5 minutes
        $since = now()->subSeconds($timeWindow);

        $processedJobs = SyncJob::where('completed_at', '>=', $since)->count();
        return round($processedJobs / 5, 2); // jobs per minute
    }
}
