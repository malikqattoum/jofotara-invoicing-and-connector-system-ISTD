<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\PerformanceMonitoringService;
use App\Services\Analytics\AnalyticsDashboardService;
use App\Services\Security\SecurityAuditService;
use App\Services\Caching\AdvancedCacheService;
use App\Services\API\APIGatewayService;
use App\Services\EventStreaming\EventStreamingService;
use App\Models\SystemAlert;
use App\Models\PerformanceMetric;
use App\Models\IntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SystemDashboardController extends Controller
{
    private $performanceService;
    private $analyticsService;
    private $securityService;
    private $cacheService;
    private $apiGatewayService;
    private $eventStreamingService;

    public function __construct(
        PerformanceMonitoringService $performanceService,
        AnalyticsDashboardService $analyticsService,
        SecurityAuditService $securityService,
        AdvancedCacheService $cacheService,
        APIGatewayService $apiGatewayService,
        EventStreamingService $eventStreamingService
    ) {
        $this->performanceService = $performanceService;
        $this->analyticsService = $analyticsService;
        $this->securityService = $securityService;
        $this->cacheService = $cacheService;
        $this->apiGatewayService = $apiGatewayService;
        $this->eventStreamingService = $eventStreamingService;
    }

    /**
     * Main admin dashboard
     */
    public function index()
    {
        $stats = $this->getSystemOverview();
        $alerts = SystemAlert::active()->orderBy('severity', 'desc')->take(10)->get();
        $recentMetrics = PerformanceMetric::recent(24)->orderBy('recorded_at', 'desc')->take(20)->get();

        return view('admin.dashboard.index', compact('stats', 'alerts', 'recentMetrics'));
    }

    /**
     * Performance monitoring dashboard
     */
    public function performance()
    {
        $metrics = $this->performanceService->getSystemMetrics();
        $alerts = $this->performanceService->getActiveAlerts();
        $healthStatus = $this->performanceService->getSystemHealth();

        return view('admin.dashboard.performance', compact('metrics', 'alerts', 'healthStatus'));
    }

    /**
     * Analytics dashboard
     */
    public function analytics()
    {
        $dashboardData = $this->analyticsService->getDashboardData();
        $kpis = $this->analyticsService->getKPIs();
        $trends = $this->analyticsService->getTrendAnalysis();

        return view('admin.dashboard.analytics', compact('dashboardData', 'kpis', 'trends'));
    }

    /**
     * Security audit dashboard
     */
    public function security()
    {
        $auditLogs = $this->securityService->getRecentAuditLogs(100);
        $securityEvents = $this->securityService->getSecurityEvents();
        $threatAnalysis = $this->securityService->getThreatAnalysis();

        return view('admin.dashboard.security', compact('auditLogs', 'securityEvents', 'threatAnalysis'));
    }

    /**
     * Cache management dashboard
     */
    public function cache()
    {
        $cacheAnalytics = $this->cacheService->getCacheAnalytics();
        $healthCheck = $this->cacheService->healthCheck();

        return view('admin.dashboard.cache', compact('cacheAnalytics', 'healthCheck'));
    }

    /**
     * API Gateway dashboard
     */
    public function apiGateway()
    {
        $apiAnalytics = $this->apiGatewayService->getAPIAnalytics();
        $documentation = $this->apiGatewayService->generateAPIDocumentation();

        return view('admin.dashboard.api-gateway', compact('apiAnalytics', 'documentation'));
    }

    /**
     * Event streaming dashboard
     */
    public function eventStreaming()
    {
        $streams = \App\Models\EventStream::with('subscriptions')->get();
        $streamAnalytics = [];

        foreach ($streams as $stream) {
            $streamAnalytics[$stream->name] = $this->eventStreamingService->getStreamAnalytics($stream->name);
        }

        return view('admin.dashboard.event-streaming', compact('streams', 'streamAnalytics'));
    }

    /**
     * System settings
     */
    public function settings()
    {
        $settings = [
            'performance' => config('monitoring.performance'),
            'security' => config('security.audit'),
            'cache' => config('cache.advanced'),
            'api' => config('api.gateway'),
            'notifications' => config('notifications.channels'),
            'workflows' => config('workflow.settings'),
            'etl' => config('etl.settings')
        ];

        return view('admin.dashboard.settings', compact('settings'));
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $settings = $request->validate([
                'performance.*' => 'sometimes|array',
                'security.*' => 'sometimes|array',
                'cache.*' => 'sometimes|array',
                'api.*' => 'sometimes|array',
                'notifications.*' => 'sometimes|array',
                'workflows.*' => 'sometimes|array',
                'etl.*' => 'sometimes|array'
            ]);

            // Update configuration files
            foreach ($settings as $category => $config) {
                $this->updateConfigFile($category, $config);
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time metrics
     */
    public function realTimeMetrics(): JsonResponse
    {
        return response()->json([
            'performance' => $this->performanceService->getRealTimeMetrics(),
            'cache' => $this->cacheService->getCacheAnalytics(1), // Last hour
            'api' => $this->apiGatewayService->getAPIAnalytics(1),
            'security' => $this->securityService->getSecurityMetrics(),
            'system_health' => $this->getSystemHealth()
        ]);
    }

    /**
     * Resolve system alert
     */
    public function resolveAlert(SystemAlert $alert): JsonResponse
    {
        try {
            $alert->resolve(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Alert resolved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve alert: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $layers = $request->layers ?? ['memory', 'redis'];

            foreach ($layers as $layer) {
                if ($layer === 'memory') {
                    \Illuminate\Support\Facades\Cache::flush();
                } elseif ($layer === 'redis') {
                    \Illuminate\Support\Facades\Redis::flushall();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Warm cache
     */
    public function warmCache(): JsonResponse
    {
        try {
            $this->cacheService->warmCache();

            return response()->json([
                'success' => true,
                'message' => 'Cache warmed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to warm cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system overview
     */
    private function getSystemOverview(): array
    {
        return [
            'integrations' => [
                'total' => IntegrationSetting::count(),
                'active' => IntegrationSetting::where('status', 'active')->count(),
                'syncing' => IntegrationSetting::where('sync_status', 'syncing')->count()
            ],
            'performance' => [
                'avg_response_time' => PerformanceMetric::recent(24)->avg('value') ?? 0,
                'system_load' => $this->performanceService->getSystemLoad(),
                'memory_usage' => $this->performanceService->getMemoryUsage()
            ],
            'security' => [
                'active_alerts' => SystemAlert::active()->count(),
                'critical_alerts' => SystemAlert::active()->where('severity', 'critical')->count(),
                'recent_events' => $this->securityService->getRecentEventsCount()
            ],
            'cache' => [
                'hit_rate' => $this->cacheService->getCacheAnalytics(1)['hit_rate'] ?? 0,
                'memory_usage' => $this->cacheService->getCacheAnalytics(1)['memory_usage'] ?? 0
            ]
        ];
    }

    /**
     * Get system health status
     */
    private function getSystemHealth(): array
    {
        return [
            'overall_status' => 'healthy',
            'components' => [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->cacheService->healthCheck(),
                'integrations' => $this->checkIntegrationsHealth(),
                'performance' => $this->performanceService->getSystemHealth()
            ]
        ];
    }

    /**
     * Update configuration file
     */
    private function updateConfigFile(string $category, array $config): void
    {
        $configPath = config_path("{$category}.php");

        if (file_exists($configPath)) {
            $currentConfig = include $configPath;
            $mergedConfig = array_merge($currentConfig, $config);

            $configContent = "<?php\n\nreturn " . var_export($mergedConfig, true) . ";\n";
            file_put_contents($configPath, $configContent);
        }
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    /**
     * Check integrations health
     */
    private function checkIntegrationsHealth(): array
    {
        $total = IntegrationSetting::count();
        $healthy = IntegrationSetting::where('status', 'active')->count();

        $healthPercentage = $total > 0 ? ($healthy / $total) * 100 : 100;

        return [
            'status' => $healthPercentage >= 80 ? 'healthy' : 'degraded',
            'message' => "{$healthy}/{$total} integrations healthy",
            'percentage' => $healthPercentage
        ];
    }
}
