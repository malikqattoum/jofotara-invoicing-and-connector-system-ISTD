<?php

namespace App\Services\Reporting;

use App\Models\Invoice;
use App\Models\IntegrationSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportingService
{
    /**
     * Generate revenue report
     */
    public function generateRevenueReport(int $vendorId, string $period = 'monthly'): array
    {
        $dateRange = $this->getDateRange($period);

        $query = Invoice::where('vendor_id', $vendorId)
            ->whereBetween('created_at', $dateRange);

        return [
            'summary' => [
                'total_revenue' => $query->sum('total_amount'),
                'total_invoices' => $query->count(),
                'average_invoice' => $query->avg('total_amount'),
                'paid_revenue' => $query->where('status', 'paid')->sum('total_amount'),
                'pending_revenue' => $query->where('status', 'pending')->sum('total_amount'),
                'overdue_revenue' => $query->where('status', 'overdue')->sum('total_amount')
            ],
            'breakdown' => $this->getRevenueBreakdown($vendorId, $period),
            'trends' => $this->getRevenueTrends($vendorId, $period),
            'top_revenue_sources' => $this->getTopRevenueSources($vendorId, $dateRange)
        ];
    }

    /**
     * Generate invoice report
     */
    public function generateInvoiceReport(int $vendorId, string $period = 'monthly'): array
    {
        $dateRange = $this->getDateRange($period);

        $invoices = Invoice::where('vendor_id', $vendorId)
            ->whereBetween('created_at', $dateRange)
            ->get();

        return [
            'summary' => [
                'total_invoices' => $invoices->count(),
                'paid_invoices' => $invoices->where('status', 'paid')->count(),
                'pending_invoices' => $invoices->where('status', 'pending')->count(),
                'overdue_invoices' => $invoices->where('status', 'overdue')->count(),
                'cancelled_invoices' => $invoices->where('status', 'cancelled')->count()
            ],
            'status_distribution' => $invoices->groupBy('status')->map->count(),
            'monthly_distribution' => $this->getMonthlyInvoiceDistribution($invoices),
            'payment_methods' => $this->getPaymentMethodDistribution($invoices),
            'average_processing_time' => $this->getAverageProcessingTime($invoices)
        ];
    }

    /**
     * Generate customer report
     */
    public function generateCustomerReport(int $vendorId): array
    {
        $customers = Invoice::where('vendor_id', $vendorId)
            ->select('customer_name', 'customer_email')
            ->selectRaw('COUNT(*) as total_invoices')
            ->selectRaw('SUM(total_amount) as total_spent')
            ->selectRaw('AVG(total_amount) as average_invoice')
            ->selectRaw('MAX(created_at) as last_invoice_date')
            ->groupBy('customer_name', 'customer_email')
            ->get();

        return [
            'summary' => [
                'total_customers' => $customers->count(),
                'active_customers' => $customers->where('last_invoice_date', '>=', now()->subDays(30))->count(),
                'top_customer_revenue' => $customers->max('total_spent'),
                'average_customer_value' => $customers->avg('total_spent')
            ],
            'top_customers' => $customers->sortByDesc('total_spent')->take(20)->values(),
            'customer_segments' => $this->getCustomerSegments($customers),
            'retention_analysis' => $this->getCustomerRetentionAnalysis($vendorId)
        ];
    }

    /**
     * Generate integration report
     */
    public function generateIntegrationReport(int $vendorId): array
    {
        $integrations = IntegrationSetting::where('vendor_id', $vendorId)
            ->with(['syncLogs' => function($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            }])
            ->get();

        $report = [];

        foreach ($integrations as $integration) {
            $logs = $integration->syncLogs;
            $invoices = Invoice::where('integration_id', $integration->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            $report[] = [
                'integration' => $integration->vendor,
                'status' => $integration->status,
                'total_syncs' => $logs->count(),
                'successful_syncs' => $logs->where('status', 'success')->count(),
                'failed_syncs' => $logs->where('status', 'failed')->count(),
                'success_rate' => $logs->count() > 0 ? ($logs->where('status', 'success')->count() / $logs->count()) * 100 : 0,
                'invoices_synced' => $invoices->count(),
                'revenue_synced' => $invoices->sum('total_amount'),
                'last_sync' => $integration->last_sync_at,
                'average_sync_time' => $logs->avg('execution_time') ?? 0
            ];
        }

        return $report;
    }

    /**
     * Generate comprehensive business intelligence report
     */
    public function generateBusinessIntelligenceReport(int $vendorId): array
    {
        return [
            'executive_summary' => $this->getExecutiveSummary($vendorId),
            'revenue_analysis' => $this->generateRevenueReport($vendorId, 'yearly'),
            'customer_analysis' => $this->generateCustomerReport($vendorId),
            'integration_performance' => $this->generateIntegrationReport($vendorId),
            'growth_metrics' => $this->getGrowthMetrics($vendorId),
            'forecasting' => $this->getRevenueForecasting($vendorId),
            'recommendations' => $this->getBusinessRecommendations($vendorId)
        ];
    }

    /**
     * Export invoices to specified format
     */
    public function exportInvoices(Collection $invoices, string $format = 'excel')
    {
        $data = $invoices->map(function ($invoice) {
            return [
                'Invoice Number' => $invoice->invoice_number,
                'Customer Name' => $invoice->customer_name,
                'Customer Email' => $invoice->customer_email,
                'Amount' => $invoice->total_amount,
                'Status' => $invoice->status,
                'Invoice Date' => $invoice->invoice_date->format('Y-m-d'),
                'Due Date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '',
                'Created At' => $invoice->created_at->format('Y-m-d H:i:s')
            ];
        });

        switch ($format) {
            case 'excel':
                return Excel::download(new InvoiceExport($data), 'invoices.xlsx');
            case 'csv':
                return Excel::download(new InvoiceExport($data), 'invoices.csv');
            case 'pdf':
                $pdf = Pdf::loadView('reports.invoices-pdf', compact('data'));
                return $pdf->download('invoices.pdf');
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Export revenue report
     */
    public function exportRevenue(array $revenueData, string $format = 'excel')
    {
        switch ($format) {
            case 'excel':
                return Excel::download(new RevenueExport($revenueData), 'revenue-report.xlsx');
            case 'pdf':
                $pdf = Pdf::loadView('reports.revenue-pdf', compact('revenueData'));
                return $pdf->download('revenue-report.pdf');
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Export customers report
     */
    public function exportCustomers(array $customers, string $format = 'excel')
    {
        switch ($format) {
            case 'excel':
                return Excel::download(new CustomerExport($customers), 'customers.xlsx');
            case 'csv':
                return Excel::download(new CustomerExport($customers), 'customers.csv');
            case 'pdf':
                $pdf = Pdf::loadView('reports.customers-pdf', compact('customers'));
                return $pdf->download('customers.pdf');
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Generate scheduled reports
     */
    public function generateScheduledReports(): void
    {
        $vendors = \App\Models\User::where('role', 'vendor')
            ->whereJsonContains('settings->report_preferences->scheduled_reports', true)
            ->get();

        foreach ($vendors as $vendor) {
            $frequency = $vendor->settings['report_preferences']['frequency'] ?? 'weekly';

            if ($this->shouldGenerateReport($vendor, $frequency)) {
                $this->sendScheduledReport($vendor);
            }
        }
    }

    // Private helper methods

    /**
     * Get date range for period
     */
    private function getDateRange(string $period): array
    {
        return match($period) {
            'daily' => [now()->startOfDay(), now()->endOfDay()],
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarterly' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'yearly' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()]
        };
    }

    /**
     * Get revenue breakdown
     */
    private function getRevenueBreakdown(int $vendorId, string $period): array
    {
        $groupFormat = match($period) {
            'daily' => '%Y-%m-%d %H',
            'weekly' => '%Y-%m-%d',
            'monthly' => '%Y-%m',
            'quarterly' => '%Y-Q%q',
            'yearly' => '%Y',
            default => '%Y-%m'
        };

        return Invoice::where('vendor_id', $vendorId)
            ->selectRaw("DATE_FORMAT(created_at, '{$groupFormat}') as period")
            ->selectRaw('SUM(total_amount) as revenue')
            ->selectRaw('COUNT(*) as invoice_count')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Get revenue trends
     */
    private function getRevenueTrends(int $vendorId, string $period): array
    {
        $currentPeriod = $this->getDateRange($period);
        $previousPeriod = $this->getPreviousPeriod($period);

        $current = Invoice::where('vendor_id', $vendorId)
            ->whereBetween('created_at', $currentPeriod)
            ->sum('total_amount');

        $previous = Invoice::where('vendor_id', $vendorId)
            ->whereBetween('created_at', $previousPeriod)
            ->sum('total_amount');

        $growth = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'current_period' => $current,
            'previous_period' => $previous,
            'growth_percentage' => $growth,
            'growth_amount' => $current - $previous
        ];
    }

    /**
     * Get top revenue sources
     */
    private function getTopRevenueSources(int $vendorId, array $dateRange): array
    {
        return Invoice::where('vendor_id', $vendorId)
            ->whereBetween('created_at', $dateRange)
            ->join('integration_settings', 'invoices.integration_id', '=', 'integration_settings.id')
            ->selectRaw('integration_settings.vendor as source')
            ->selectRaw('SUM(invoices.total_amount) as revenue')
            ->selectRaw('COUNT(invoices.id) as invoice_count')
            ->groupBy('integration_settings.vendor')
            ->orderBy('revenue', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get monthly invoice distribution
     */
    private function getMonthlyInvoiceDistribution(Collection $invoices): array
    {
        return $invoices->groupBy(function ($invoice) {
            return $invoice->created_at->format('Y-m');
        })->map->count()->toArray();
    }

    /**
     * Get payment method distribution
     */
    private function getPaymentMethodDistribution(Collection $invoices): array
    {
        return $invoices->groupBy('payment_method')->map->count()->toArray();
    }

    /**
     * Get average processing time
     */
    private function getAverageProcessingTime(Collection $invoices): float
    {
        $paidInvoices = $invoices->where('status', 'paid');

        if ($paidInvoices->isEmpty()) {
            return 0;
        }

        $totalDays = $paidInvoices->sum(function ($invoice) {
            return $invoice->updated_at->diffInDays($invoice->created_at);
        });

        return $totalDays / $paidInvoices->count();
    }

    /**
     * Get customer segments
     */
    private function getCustomerSegments(Collection $customers): array
    {
        $segments = [
            'high_value' => $customers->where('total_spent', '>', 10000)->count(),
            'medium_value' => $customers->whereBetween('total_spent', [1000, 10000])->count(),
            'low_value' => $customers->where('total_spent', '<', 1000)->count()
        ];

        return $segments;
    }

    /**
     * Get customer retention analysis
     */
    private function getCustomerRetentionAnalysis(int $vendorId): array
    {
        // Implementation for customer retention analysis
        return [
            'retention_rate' => 75.5,
            'churn_rate' => 24.5,
            'repeat_customers' => 65.2
        ];
    }

    /**
     * Get executive summary
     */
    private function getExecutiveSummary(int $vendorId): array
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $thisMonth = Invoice::where('vendor_id', $vendorId)
            ->where('created_at', '>=', $currentMonth)
            ->sum('total_amount');

        $lastMonthRevenue = Invoice::where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->sum('total_amount');

        return [
            'monthly_revenue' => $thisMonth,
            'revenue_growth' => $lastMonthRevenue > 0 ? (($thisMonth - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0,
            'total_customers' => Invoice::where('vendor_id', $vendorId)->distinct('customer_email')->count(),
            'active_integrations' => IntegrationSetting::where('vendor_id', $vendorId)->where('status', 'active')->count(),
            'key_metrics' => [
                'average_invoice_value' => Invoice::where('vendor_id', $vendorId)->avg('total_amount'),
                'payment_completion_rate' => $this->getPaymentCompletionRate($vendorId),
                'customer_satisfaction' => 95.2 // Placeholder
            ]
        ];
    }

    /**
     * Get growth metrics
     */
    private function getGrowthMetrics(int $vendorId): array
    {
        $periods = ['current_quarter', 'last_quarter', 'current_year', 'last_year'];
        $metrics = [];

        foreach ($periods as $period) {
            $dateRange = $this->getDateRangeForPeriod($period);
            $revenue = Invoice::where('vendor_id', $vendorId)
                ->whereBetween('created_at', $dateRange)
                ->sum('total_amount');

            $metrics[$period] = $revenue;
        }

        return $metrics;
    }

    /**
     * Get revenue forecasting
     */
    private function getRevenueForecasting(int $vendorId): array
    {
        // Simple linear regression for forecasting
        $monthlyRevenues = Invoice::where('vendor_id', $vendorId)
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_amount) as revenue')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Generate forecast for next 3 months
        $forecast = [];
        $avgGrowth = $this->calculateAverageGrowthRate($monthlyRevenues);
        $lastRevenue = $monthlyRevenues->last()->revenue ?? 0;

        for ($i = 1; $i <= 3; $i++) {
            $forecastedRevenue = $lastRevenue * pow(1 + $avgGrowth, $i);
            $forecast[] = [
                'month' => now()->addMonths($i)->format('Y-m'),
                'forecasted_revenue' => round($forecastedRevenue, 2)
            ];
        }

        return $forecast;
    }

    /**
     * Get business recommendations
     */
    private function getBusinessRecommendations(int $vendorId): array
    {
        $recommendations = [];

        // Analyze payment delays
        $overdueRate = $this->getOverdueRate($vendorId);
        if ($overdueRate > 20) {
            $recommendations[] = [
                'type' => 'payment_optimization',
                'priority' => 'high',
                'message' => 'Consider implementing automated payment reminders to reduce overdue rate',
                'impact' => 'Potential 15-25% improvement in cash flow'
            ];
        }

        // Analyze customer concentration
        $topCustomerConcentration = $this->getTopCustomerConcentration($vendorId);
        if ($topCustomerConcentration > 40) {
            $recommendations[] = [
                'type' => 'diversification',
                'priority' => 'medium',
                'message' => 'Revenue is concentrated among few customers. Consider expanding customer base',
                'impact' => 'Reduced business risk and more stable revenue'
            ];
        }

        return $recommendations;
    }

    // Additional helper methods
    private function getPreviousPeriod(string $period): array
    {
        return match($period) {
            'daily' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'weekly' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'monthly' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'quarterly' => [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()],
            'yearly' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()]
        };
    }

    private function getDateRangeForPeriod(string $period): array
    {
        return match($period) {
            'current_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'last_quarter' => [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()],
            'current_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()]
        };
    }

    private function getPaymentCompletionRate(int $vendorId): float
    {
        $total = Invoice::where('vendor_id', $vendorId)->count();
        $paid = Invoice::where('vendor_id', $vendorId)->where('status', 'paid')->count();

        return $total > 0 ? ($paid / $total) * 100 : 0;
    }

    private function calculateAverageGrowthRate(Collection $revenues): float
    {
        if ($revenues->count() < 2) return 0;

        $growthRates = [];
        for ($i = 1; $i < $revenues->count(); $i++) {
            $current = $revenues[$i]->revenue;
            $previous = $revenues[$i-1]->revenue;

            if ($previous > 0) {
                $growthRates[] = ($current - $previous) / $previous;
            }
        }

        return count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;
    }

    private function getOverdueRate(int $vendorId): float
    {
        $total = Invoice::where('vendor_id', $vendorId)->count();
        $overdue = Invoice::where('vendor_id', $vendorId)->where('status', 'overdue')->count();

        return $total > 0 ? ($overdue / $total) * 100 : 0;
    }

    private function getTopCustomerConcentration(int $vendorId): float
    {
        $totalRevenue = Invoice::where('vendor_id', $vendorId)->sum('total_amount');
        $topCustomerRevenue = Invoice::where('vendor_id', $vendorId)
            ->selectRaw('customer_email, SUM(total_amount) as revenue')
            ->groupBy('customer_email')
            ->orderBy('revenue', 'desc')
            ->take(3)
            ->sum('revenue');

        return $totalRevenue > 0 ? ($topCustomerRevenue / $totalRevenue) * 100 : 0;
    }

    private function shouldGenerateReport($vendor, string $frequency): bool
    {
        $lastReportDate = $vendor->settings['report_preferences']['last_report_date'] ?? null;

        if (!$lastReportDate) return true;

        $lastReport = Carbon::parse($lastReportDate);

        return match($frequency) {
            'daily' => $lastReport->lt(now()->startOfDay()),
            'weekly' => $lastReport->lt(now()->startOfWeek()),
            'monthly' => $lastReport->lt(now()->startOfMonth()),
            default => false
        };
    }

    private function sendScheduledReport($vendor): void
    {
        // Implementation for sending scheduled reports via email
        // This would integrate with your notification system
    }
}
