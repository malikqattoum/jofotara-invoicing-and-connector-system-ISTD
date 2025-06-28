<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IntegrationMetricsService
{
    public function getIntegrationMetrics(IntegrationSetting $integration): array
    {
        return [
            'total_syncs' => $this->getTotalSyncs($integration),
            'successful_syncs' => $this->getSuccessfulSyncs($integration),
            'failed_syncs' => $this->getFailedSyncs($integration),
            'success_rate' => $this->getSuccessRate($integration),
            'avg_sync_duration' => $this->getAverageSyncDuration($integration),
            'last_24h_syncs' => $this->getLast24HourSyncs($integration),
            'data_volume' => $this->getDataVolume($integration)
        ];
    }

    public function getDetailedMetrics(IntegrationSetting $integration): array
    {
        return [
            'basic' => $this->getIntegrationMetrics($integration),
            'trends' => $this->getSyncTrends($integration),
            'performance' => $this->getPerformanceMetrics($integration),
            'errors' => $this->getErrorAnalysis($integration)
        ];
    }

    public function getRecentActivity(IntegrationSetting $integration, int $limit = 10): array
    {
        return SyncLog::where('integration_setting_id', $integration->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->sync_type,
                    'status' => $log->status,
                    'records_processed' => $log->records_processed,
                    'duration' => $log->duration_seconds,
                    'error_message' => $log->error_message,
                    'created_at' => $log->created_at
                ];
            })
            ->toArray();
    }

    public function getFailedSyncsToday(int $organizationId): int
    {
        return SyncLog::whereHas('integrationSetting', function ($query) use ($organizationId) {
            $query->where('organization_id', $organizationId);
        })
            ->where('status', 'failed')
            ->whereDate('created_at', today())
            ->count();
    }

    protected function getTotalSyncs(IntegrationSetting $integration): int
    {
        return SyncLog::where('integration_setting_id', $integration->id)->count();
    }

    protected function getSuccessfulSyncs(IntegrationSetting $integration): int
    {
        return SyncLog::where('integration_setting_id', $integration->id)
            ->where('status', 'success')
            ->count();
    }

    protected function getFailedSyncs(IntegrationSetting $integration): int
    {
        return SyncLog::where('integration_setting_id', $integration->id)
            ->where('status', 'failed')
            ->count();
    }

    protected function getSuccessRate(IntegrationSetting $integration): float
    {
        $total = $this->getTotalSyncs($integration);
        if ($total === 0) return 0;

        $successful = $this->getSuccessfulSyncs($integration);
        return round(($successful / $total) * 100, 2);
    }

    protected function getAverageSyncDuration(IntegrationSetting $integration): float
    {
        return SyncLog::where('integration_setting_id', $integration->id)
            ->whereNotNull('duration_seconds')
            ->avg('duration_seconds') ?? 0;
    }

    protected function getLast24HourSyncs(IntegrationSetting $integration): array
    {
        $syncs = SyncLog::where('integration_setting_id', $integration->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        return [
            'total' => $syncs->count(),
            'successful' => $syncs->where('status', 'success')->count(),
            'failed' => $syncs->where('status', 'failed')->count()
        ];
    }

    protected function getDataVolume(IntegrationSetting $integration): array
    {
        $logs = SyncLog::where('integration_setting_id', $integration->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        return [
            'total_records' => $logs->sum('records_processed'),
            'invoices_synced' => $logs->where('sync_type', 'invoices')->sum('records_processed'),
            'customers_synced' => $logs->where('sync_type', 'customers')->sum('records_processed'),
            'last_30_days' => $logs->sum('records_processed')
        ];
    }

    protected function getSyncTrends(IntegrationSetting $integration): array
    {
        $trends = SyncLog::where('integration_setting_id', $integration->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_syncs'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_syncs'),
                DB::raw('SUM(records_processed) as records_processed'),
                DB::raw('AVG(duration_seconds) as avg_duration')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $trends->map(function ($trend) {
            return [
                'date' => $trend->date,
                'total_syncs' => $trend->total_syncs,
                'successful_syncs' => $trend->successful_syncs,
                'success_rate' => $trend->total_syncs > 0 ?
                    round(($trend->successful_syncs / $trend->total_syncs) * 100, 2) : 0,
                'records_processed' => $trend->records_processed,
                'avg_duration' => round($trend->avg_duration, 2)
            ];
        })->toArray();
    }

    protected function getPerformanceMetrics(IntegrationSetting $integration): array
    {
        $recentLogs = SyncLog::where('integration_setting_id', $integration->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('duration_seconds')
            ->get();

        if ($recentLogs->isEmpty()) {
            return [
                'avg_duration' => 0,
                'min_duration' => 0,
                'max_duration' => 0,
                'records_per_second' => 0
            ];
        }

        $durations = $recentLogs->pluck('duration_seconds');
        $totalRecords = $recentLogs->sum('records_processed');
        $totalDuration = $recentLogs->sum('duration_seconds');

        return [
            'avg_duration' => round($durations->avg(), 2),
            'min_duration' => $durations->min(),
            'max_duration' => $durations->max(),
            'records_per_second' => $totalDuration > 0 ?
                round($totalRecords / $totalDuration, 2) : 0
        ];
    }

    protected function getErrorAnalysis(IntegrationSetting $integration): array
    {
        $errors = SyncLog::where('integration_setting_id', $integration->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('error_message')
            ->get();

        $errorGroups = $errors->groupBy(function ($log) {
            // Group similar errors together
            $message = $log->error_message;
            if (str_contains($message, 'timeout')) return 'Timeout Errors';
            if (str_contains($message, 'authentication')) return 'Authentication Errors';
            if (str_contains($message, 'rate limit')) return 'Rate Limit Errors';
            if (str_contains($message, 'network')) return 'Network Errors';
            return 'Other Errors';
        });

        return $errorGroups->map(function ($group, $type) {
            return [
                'type' => $type,
                'count' => $group->count(),
                'recent_example' => $group->first()->error_message,
                'last_occurrence' => $group->max('created_at')
            ];
        })->values()->toArray();
    }
}
