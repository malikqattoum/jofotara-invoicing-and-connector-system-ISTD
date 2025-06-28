<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsDashboardService;
use App\Services\AI\DataIntelligenceService;
use App\Services\SyncEngine\SyncEngine;
use App\Models\IntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AnalyticsDashboardController extends Controller
{
    private $analyticsService;
    private $aiService;
    private $syncEngine;

    public function __construct(
        AnalyticsDashboardService $analyticsService,
        DataIntelligenceService $aiService,
        SyncEngine $syncEngine
    ) {
        $this->analyticsService = $analyticsService;
        $this->aiService = $aiService;
        $this->syncEngine = $syncEngine;
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $integrationIds = $request->input('integration_ids', []);
            $dateRange = $this->parseDateRange($request);

            $dashboardData = $this->analyticsService->getDashboardData($integrationIds, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
                'generated_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load dashboard data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overview metrics
     */
    public function getOverviewMetrics(Request $request): JsonResponse
    {
        try {
            $integrationIds = $request->input('integration_ids', []);
            $dateRange = $this->parseDateRange($request);

            $metrics = $this->analyticsService->getOverviewMetrics($integrationIds, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load overview metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        try {
            $integrationIds = $request->input('integration_ids', []);
            $dateRange = $this->parseDateRange($request);

            $analytics = $this->analyticsService->getRevenueAnalytics($integrationIds, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load revenue analytics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync performance metrics
     */
    public function getSyncPerformance(Request $request): JsonResponse
    {
        try {
            $integrationIds = $request->input('integration_ids', []);
            $dateRange = $this->parseDateRange($request);

            $performance = $this->analyticsService->getSyncPerformanceMetrics($integrationIds, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $performance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load sync performance metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(Request $request): JsonResponse
    {
        try {
            $integrationIds = $request->input('integration_ids', []);
            $dateRange = $this->parseDateRange($request);

            $analytics = $this->analyticsService->getCustomerAnalytics($integrationIds, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load customer analytics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get integration health metrics
     */
    public function getIntegrationHealth(Request $request): JsonResponse
    {
        try {
            $integrationIds = $request->input('integration_ids', []);

            $health = $this->analyticsService->getIntegrationHealthMetrics($integrationIds);

            return response()->json([
                'success' => true,
                'data' => $health
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load integration health metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI insights
     */
    public function getAIInsights(Request $request): JsonResponse
    {
        try {
            $integrationIds = $request->input('integration_ids', []);

            $insights = $this->analyticsService->getAIInsights($integrationIds);

            return response()->json([
                'success' => true,
                'data' => $insights
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load AI insights',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time metrics
     */
    public function getRealTimeMetrics(Request $request): JsonResponse
    {
        try {
            $integrationIds = $request->input('integration_ids', []);

            $metrics = $this->analyticsService->getRealTimeMetrics($integrationIds);

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load real-time metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trend analysis
     */
    public function getTrendAnalysis(Request $request): JsonResponse
    {
        try {
            $integrationIds = $request->input('integration_ids', []);
            $dateRange = $this->parseDateRange($request);

            $trends = $this->analyticsService->getTrendAnalysis($integrationIds, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $trends
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load trend analysis',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate AI insights for specific integration
     */
    public function generateAIInsights(Request $request, int $integrationId): JsonResponse
    {
        try {
            $integration = IntegrationSetting::findOrFail($integrationId);

            $insights = $this->aiService->analyzeDataPatterns($integration);

            return response()->json([
                'success' => true,
                'data' => $insights,
                'integration_id' => $integrationId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate AI insights',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync statistics for integration
     */
    public function getSyncStats(Request $request, int $integrationId): JsonResponse
    {
        try {
            $integration = IntegrationSetting::findOrFail($integrationId);
            $days = $request->input('days', 30);

            $stats = $this->syncEngine->getSyncStats($integration, $days);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'integration_id' => $integrationId,
                'period_days' => $days
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load sync statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics data
     */
    public function exportData(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'format' => 'required|in:json,csv,xlsx',
                'data_type' => 'required|in:overview,revenue,sync,customer,all',
                'integration_ids' => 'array',
                'date_from' => 'date',
                'date_to' => 'date'
            ]);

            $format = $request->input('format');
            $dataType = $request->input('data_type');
            $integrationIds = $request->input('integration_ids', []);
            $dateRange = $this->parseDateRange($request);

            // Get the requested data
            $data = match($dataType) {
                'overview' => $this->analyticsService->getOverviewMetrics($integrationIds, $dateRange),
                'revenue' => $this->analyticsService->getRevenueAnalytics($integrationIds, $dateRange),
                'sync' => $this->analyticsService->getSyncPerformanceMetrics($integrationIds, $dateRange),
                'customer' => $this->analyticsService->getCustomerAnalytics($integrationIds, $dateRange),
                'all' => $this->analyticsService->getDashboardData($integrationIds, $dateRange)
            };

            // For now, return JSON (file export would be implemented separately)
            return response()->json([
                'success' => true,
                'data' => $data,
                'format' => $format,
                'exported_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to export data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available integrations for filtering
     */
    public function getAvailableIntegrations(): JsonResponse
    {
        try {
            $integrations = IntegrationSetting::select('id', 'vendor', 'name', 'is_active')
                ->orderBy('vendor')
                ->get()
                ->map(function ($integration) {
                    return [
                        'id' => $integration->id,
                        'vendor' => $integration->vendor,
                        'name' => $integration->name ?? $integration->vendor,
                        'is_active' => $integration->is_active
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $integrations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load integrations',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard configuration
     */
    public function getDashboardConfig(): JsonResponse
    {
        try {
            $config = [
                'refresh_intervals' => [
                    'real_time' => 30, // seconds
                    'overview' => 300, // 5 minutes
                    'analytics' => 900, // 15 minutes
                ],
                'date_ranges' => [
                    'today' => ['label' => 'Today', 'days' => 1],
                    'yesterday' => ['label' => 'Yesterday', 'days' => 1],
                    'last_7_days' => ['label' => 'Last 7 Days', 'days' => 7],
                    'last_30_days' => ['label' => 'Last 30 Days', 'days' => 30],
                    'last_90_days' => ['label' => 'Last 90 Days', 'days' => 90],
                    'custom' => ['label' => 'Custom Range', 'days' => null]
                ],
                'metrics' => [
                    'overview' => ['invoices', 'revenue', 'customers', 'payment_rate'],
                    'charts' => ['revenue_timeline', 'integration_performance', 'customer_trends'],
                    'ai_features' => ['anomaly_detection', 'forecasting', 'insights']
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load dashboard configuration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse date range from request
     */
    private function parseDateRange(Request $request): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $preset = $request->input('date_preset');

        if ($preset) {
            switch ($preset) {
                case 'today':
                    return [Carbon::today(), Carbon::today()->endOfDay()];
                case 'yesterday':
                    return [Carbon::yesterday(), Carbon::yesterday()->endOfDay()];
                case 'last_7_days':
                    return [Carbon::now()->subDays(7), Carbon::now()];
                case 'last_30_days':
                    return [Carbon::now()->subDays(30), Carbon::now()];
                case 'last_90_days':
                    return [Carbon::now()->subDays(90), Carbon::now()];
                default:
                    return [Carbon::now()->subDays(30), Carbon::now()];
            }
        }

        if ($dateFrom && $dateTo) {
            return [Carbon::parse($dateFrom), Carbon::parse($dateTo)];
        }

        // Default to last 30 days
        return [Carbon::now()->subDays(30), Carbon::now()];
    }
}
