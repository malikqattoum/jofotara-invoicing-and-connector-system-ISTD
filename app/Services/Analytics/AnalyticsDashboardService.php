<?php

namespace App\Services\Analytics;

use App\Models\IntegrationSetting;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\SyncJob;
use App\Services\AI\DataIntelligenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AnalyticsDashboardService
{
    private $dataIntelligence;
    private $cacheTimeout = 3600; // 1 hour

    public function __construct(DataIntelligenceService $dataIntelligence)
    {
        $this->dataIntelligence = $dataIntelligence;
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData(array $integrationIds = [], array $dateRange = []): array
    {
        $cacheKey = $this->generateCacheKey('dashboard', $integrationIds, $dateRange);

        return Cache::remember($cacheKey, $this->cacheTimeout, function () use ($integrationIds, $dateRange) {
            return [
                'overview' => $this->getOverviewMetrics($integrationIds, $dateRange),
                'revenue_analytics' => $this->getRevenueAnalytics($integrationIds, $dateRange),
                'sync_performance' => $this->getSyncPerformanceMetrics($integrationIds, $dateRange),
                'customer_analytics' => $this->getCustomerAnalytics($integrationIds, $dateRange),
                'integration_health' => $this->getIntegrationHealthMetrics($integrationIds),
                'ai_insights' => $this->getAIInsights($integrationIds),
                'real_time_metrics' => $this->getRealTimeMetrics($integrationIds),
                'trend_analysis' => $this->getTrendAnalysis($integrationIds, $dateRange)
            ];
        });
    }

    /**
     * Get overview metrics
     */
    public function getOverviewMetrics(array $integrationIds = [], array $dateRange = []): array
    {
        $query = $this->buildBaseQuery('invoices', $integrationIds, $dateRange);

        $metrics = $query->selectRaw('
            COUNT(*) as total_invoices,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_invoice_value,
            COUNT(DISTINCT customer_id) as unique_customers,
            SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_invoices,
            SUM(CASE WHEN due_date < NOW() AND status != "paid" THEN 1 ELSE 0 END) as overdue_invoices
        ')->first();

        // Calculate period comparison
        $previousPeriod = $this->getPreviousPeriodData($integrationIds, $dateRange);

        return [
            'total_invoices' => [
                'value' => $metrics->total_invoices ?? 0,
                'change' => $this->calculatePercentageChange($metrics->total_invoices, $previousPeriod['total_invoices'])
            ],
            'total_revenue' => [
                'value' => $metrics->total_revenue ?? 0,
                'change' => $this->calculatePercentageChange($metrics->total_revenue, $previousPeriod['total_revenue'])
            ],
            'avg_invoice_value' => [
                'value' => $metrics->avg_invoice_value ?? 0,
                'change' => $this->calculatePercentageChange($metrics->avg_invoice_value, $previousPeriod['avg_invoice_value'])
            ],
            'unique_customers' => [
                'value' => $metrics->unique_customers ?? 0,
                'change' => $this->calculatePercentageChange($metrics->unique_customers, $previousPeriod['unique_customers'])
            ],
            'payment_rate' => [
                'value' => $metrics->total_invoices > 0 ? ($metrics->paid_invoices / $metrics->total_invoices * 100) : 0,
                'change' => $this->calculatePaymentRateChange($metrics, $previousPeriod)
            ],
            'overdue_rate' => [
                'value' => $metrics->total_invoices > 0 ? ($metrics->overdue_invoices / $metrics->total_invoices * 100) : 0,
                'change' => $this->calculateOverdueRateChange($metrics, $previousPeriod)
            ]
        ];
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(array $integrationIds = [], array $dateRange = []): array
    {
        return [
            'time_series' => $this->getRevenueTimeSeries($integrationIds, $dateRange),
            'by_integration' => $this->getRevenueByIntegration($integrationIds, $dateRange),
            'by_customer_segment' => $this->getRevenueByCustomerSegment($integrationIds, $dateRange),
            'by_currency' => $this->getRevenueByCurrency($integrationIds, $dateRange),
            'forecasting' => $this->getRevenueForecast($integrationIds),
            'goals_tracking' => $this->getRevenueGoalsTracking($integrationIds, $dateRange)
        ];
    }

    /**
     * Get sync performance metrics
     */
    public function getSyncPerformanceMetrics(array $integrationIds = [], array $dateRange = []): array
    {
        $syncJobs = SyncJob::query()
            ->when($integrationIds, fn($q) => $q->whereIn('integration_id', $integrationIds))
            ->when($dateRange, fn($q) => $q->whereBetween('created_at', $dateRange))
            ->get();

        $totalJobs = $syncJobs->count();
        $completedJobs = $syncJobs->where('status', 'completed')->count();
        $failedJobs = $syncJobs->where('status', 'failed')->count();

        return [
            'success_rate' => $totalJobs > 0 ? ($completedJobs / $totalJobs * 100) : 0,
            'failure_rate' => $totalJobs > 0 ? ($failedJobs / $totalJobs * 100) : 0,
            'avg_execution_time' => $syncJobs->where('status', 'completed')->avg('execution_time') ?? 0,
            'total_records_synced' => $syncJobs->sum('records_synced'),
            'sync_frequency' => $this->calculateSyncFrequency($syncJobs),
            'performance_trends' => $this->getSyncPerformanceTrends($syncJobs),
            'error_analysis' => $this->analyzeSyncErrors($syncJobs->where('status', 'failed'))
        ];
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(array $integrationIds = [], array $dateRange = []): array
    {
        return [
            'acquisition_metrics' => $this->getCustomerAcquisitionMetrics($integrationIds, $dateRange),
            'retention_metrics' => $this->getCustomerRetentionMetrics($integrationIds, $dateRange),
            'lifetime_value' => $this->getCustomerLifetimeValue($integrationIds),
            'segmentation' => $this->getCustomerSegmentation($integrationIds),
            'geographic_distribution' => $this->getCustomerGeographicDistribution($integrationIds),
            'payment_behavior' => $this->getCustomerPaymentBehavior($integrationIds, $dateRange)
        ];
    }

    /**
     * Get integration health metrics
     */
    public function getIntegrationHealthMetrics(array $integrationIds = []): array
    {
        $integrations = IntegrationSetting::query()
            ->when($integrationIds, fn($q) => $q->whereIn('id', $integrationIds))
            ->with(['latestSync', 'syncJobs' => fn($q) => $q->latest()->limit(10)])
            ->get();

        $healthMetrics = [];

        foreach ($integrations as $integration) {
            $recentJobs = $integration->syncJobs;
            $successRate = $recentJobs->count() > 0 ?
                ($recentJobs->where('status', 'completed')->count() / $recentJobs->count() * 100) : 0;

            $healthScore = $this->calculateIntegrationHealthScore($integration, $recentJobs);

            $healthMetrics[] = [
                'integration_id' => $integration->id,
                'vendor' => $integration->vendor,
                'status' => $integration->is_active ? 'active' : 'inactive',
                'health_score' => $healthScore,
                'success_rate' => $successRate,
                'last_sync' => $integration->latestSync?->completed_at,
                'issues' => $this->identifyIntegrationIssues($integration, $recentJobs),
                'recommendations' => $this->getIntegrationRecommendations($integration, $healthScore)
            ];
        }

        return $healthMetrics;
    }

    /**
     * Get AI insights
     */
    public function getAIInsights(array $integrationIds = []): array
    {
        $insights = [];

        foreach ($integrationIds as $integrationId) {
            $integration = IntegrationSetting::find($integrationId);
            if ($integration) {
                $aiInsights = $this->dataIntelligence->analyzeDataPatterns($integration);
                $insights[$integrationId] = $aiInsights;
            }
        }

        return [
            'integration_insights' => $insights,
            'global_patterns' => $this->getGlobalPatterns($integrationIds),
            'anomaly_alerts' => $this->getActiveAnomalies($integrationIds),
            'predictions' => $this->getConsolidatedPredictions($integrationIds),
            'recommendations' => $this->getAIRecommendations($integrationIds)
        ];
    }

    /**
     * Get real-time metrics
     */
    public function getRealTimeMetrics(array $integrationIds = []): array
    {
        return [
            'active_syncs' => $this->getActiveSyncs($integrationIds),
            'recent_activities' => $this->getRecentActivities($integrationIds),
            'system_load' => $this->getSystemLoadMetrics(),
            'api_performance' => $this->getAPIPerformanceMetrics($integrationIds),
            'error_rates' => $this->getCurrentErrorRates($integrationIds)
        ];
    }

    /**
     * Get trend analysis
     */
    public function getTrendAnalysis(array $integrationIds = [], array $dateRange = []): array
    {
        return [
            'revenue_trends' => $this->analyzeRevenueTrends($integrationIds, $dateRange),
            'volume_trends' => $this->analyzeVolumeTrends($integrationIds, $dateRange),
            'customer_trends' => $this->analyzeCustomerTrends($integrationIds, $dateRange),
            'seasonal_patterns' => $this->analyzeSeasonalPatterns($integrationIds),
            'performance_trends' => $this->analyzePerformanceTrends($integrationIds, $dateRange)
        ];
    }

    /**
     * Get revenue time series data
     */
    private function getRevenueTimeSeries(array $integrationIds, array $dateRange): array
    {
        $startDate = $dateRange[0] ?? Carbon::now()->subDays(30);
        $endDate = $dateRange[1] ?? Carbon::now();

        $query = $this->buildBaseQuery('invoices', $integrationIds, $dateRange);

        $data = $query->selectRaw('
            DATE(invoice_date) as date,
            SUM(total_amount) as revenue,
            COUNT(*) as invoice_count
        ')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Fill missing dates with zero values
        $period = CarbonPeriod::create($startDate, $endDate);
        $timeSeries = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayData = $data->firstWhere('date', $dateStr);

            $timeSeries[] = [
                'date' => $dateStr,
                'revenue' => $dayData->revenue ?? 0,
                'invoice_count' => $dayData->invoice_count ?? 0
            ];
        }

        return $timeSeries;
    }

    /**
     * Get revenue by integration
     */
    private function getRevenueByIntegration(array $integrationIds, array $dateRange): array
    {
        $query = $this->buildBaseQuery('invoices', $integrationIds, $dateRange);

        return $query->join('integration_settings', 'invoices.integration_id', '=', 'integration_settings.id')
            ->selectRaw('
                integration_settings.vendor,
                integration_settings.id as integration_id,
                SUM(invoices.total_amount) as revenue,
                COUNT(*) as invoice_count,
                AVG(invoices.total_amount) as avg_invoice_value
            ')
            ->groupBy('integration_settings.id', 'integration_settings.vendor')
            ->orderBy('revenue', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Calculate integration health score
     */
    private function calculateIntegrationHealthScore(IntegrationSetting $integration, $recentJobs): float
    {
        $score = 100;

        // Deduct for failed syncs
        $failureRate = $recentJobs->count() > 0 ?
            ($recentJobs->where('status', 'failed')->count() / $recentJobs->count()) : 0;
        $score -= $failureRate * 30;

        // Deduct for stale data
        $lastSync = $integration->latestSync?->completed_at;
        if ($lastSync && $lastSync->diffInHours(now()) > 24) {
            $score -= 20;
        }

        // Deduct for configuration issues
        if (!$integration->is_active) {
            $score -= 40;
        }

        // Deduct for performance issues
        $avgExecutionTime = $recentJobs->where('status', 'completed')->avg('execution_time');
        if ($avgExecutionTime > 300) { // 5 minutes
            $score -= 10;
        }

        return max(0, $score);
    }

    /**
     * Build base query for analytics
     */
    private function buildBaseQuery(string $table, array $integrationIds = [], array $dateRange = [])
    {
        $query = DB::table($table);

        if (!empty($integrationIds)) {
            $query->whereIn('integration_id', $integrationIds);
        }

        if (!empty($dateRange)) {
            $query->whereBetween('created_at', $dateRange);
        }

        return $query;
    }

    /**
     * Generate cache key
     */
    private function generateCacheKey(string $type, array $integrationIds, array $dateRange): string
    {
        return sprintf(
            'analytics_%s_%s_%s',
            $type,
            md5(serialize($integrationIds)),
            md5(serialize($dateRange))
        );
    }

    /**
     * Calculate percentage change
     */
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    // Additional helper methods (implementations would be based on actual data models)
    private function getPreviousPeriodData(array $integrationIds, array $dateRange): array
    {
        // Implementation for getting previous period data for comparison
        return ['total_invoices' => 0, 'total_revenue' => 0, 'avg_invoice_value' => 0, 'unique_customers' => 0];
    }

    private function calculatePaymentRateChange($current, $previous): float
    {
        $currentRate = $current->total_invoices > 0 ? ($current->paid_invoices / $current->total_invoices * 100) : 0;
        $previousRate = $previous['total_invoices'] > 0 ? ($previous['paid_invoices'] / $previous['total_invoices'] * 100) : 0;
        return $currentRate - $previousRate;
    }

    private function calculateOverdueRateChange($current, $previous): float
    {
        $currentRate = $current->total_invoices > 0 ? ($current->overdue_invoices / $current->total_invoices * 100) : 0;
        $previousRate = $previous['total_invoices'] > 0 ? ($previous['overdue_invoices'] / $previous['total_invoices'] * 100) : 0;
        return $currentRate - $previousRate;
    }

    private function getRevenueByCustomerSegment(array $integrationIds, array $dateRange): array { return []; }
    private function getRevenueByCurrency(array $integrationIds, array $dateRange): array { return []; }
    private function getRevenueForecast(array $integrationIds): array { return []; }
    private function getRevenueGoalsTracking(array $integrationIds, array $dateRange): array { return []; }
    private function calculateSyncFrequency($syncJobs): array { return []; }
    private function getSyncPerformanceTrends($syncJobs): array { return []; }
    private function analyzeSyncErrors($failedJobs): array { return []; }
    private function getCustomerAcquisitionMetrics(array $integrationIds, array $dateRange): array { return []; }
    private function getCustomerRetentionMetrics(array $integrationIds, array $dateRange): array { return []; }
    private function getCustomerLifetimeValue(array $integrationIds): array { return []; }
    private function getCustomerSegmentation(array $integrationIds): array { return []; }
    private function getCustomerGeographicDistribution(array $integrationIds): array { return []; }
    private function getCustomerPaymentBehavior(array $integrationIds, array $dateRange): array { return []; }
    private function identifyIntegrationIssues($integration, $recentJobs): array { return []; }
    private function getIntegrationRecommendations($integration, float $healthScore): array { return []; }
    private function getGlobalPatterns(array $integrationIds): array { return []; }
    private function getActiveAnomalies(array $integrationIds): array { return []; }
    private function getConsolidatedPredictions(array $integrationIds): array { return []; }
    private function getAIRecommendations(array $integrationIds): array { return []; }
    private function getActiveSyncs(array $integrationIds): array { return []; }
    private function getRecentActivities(array $integrationIds): array { return []; }
    private function getSystemLoadMetrics(): array { return []; }
    private function getAPIPerformanceMetrics(array $integrationIds): array { return []; }
    private function getCurrentErrorRates(array $integrationIds): array { return []; }
    private function analyzeRevenueTrends(array $integrationIds, array $dateRange): array { return []; }
    private function analyzeVolumeTrends(array $integrationIds, array $dateRange): array { return []; }
    private function analyzeCustomerTrends(array $integrationIds, array $dateRange): array { return []; }
    private function analyzeSeasonalPatterns(array $integrationIds): array { return []; }
    private function analyzePerformanceTrends(array $integrationIds, array $dateRange): array { return []; }
}
