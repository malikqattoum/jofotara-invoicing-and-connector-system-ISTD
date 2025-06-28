<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\IntegrationSetting;
use App\Models\SyncLog;
use App\Services\Analytics\AnalyticsDashboardService;
use App\Services\Reporting\ReportingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    private $analyticsService;
    private $reportingService;

    public function __construct(
        AnalyticsDashboardService $analyticsService = null,
        ReportingService $reportingService = null
    ) {
        $this->analyticsService = $analyticsService;
        $this->reportingService = $reportingService;
    }

    /**
     * Main vendor dashboard - Enhanced with InvoiceQ-inspired features
     */
    public function index()
    {
        $vendor = Auth::user();
        $organizationId = $vendor->organization_id ?? $vendor->id;

        // Get integrations using organization_id as fallback
        $integrations = IntegrationSetting::where(function($query) use ($vendor, $organizationId) {
            $query->where('vendor_id', $vendor->id)
                  ->orWhere('organization_id', $organizationId);
        })->get();

        // Enhanced statistics with InvoiceQ-inspired metrics
        $stats = $this->getBasicDashboardStats($organizationId);

        // Recent invoices with better filtering
        $recentInvoices = Invoice::where(function($query) use ($vendor, $organizationId) {
            $query->where('vendor_id', $vendor->id)
                  ->orWhere('organization_id', $organizationId);
        })
        ->with('items')
        ->latest()
        ->take(10)
        ->get();

        // Skip enhanced features for now - comment out until services are fully implemented
        // $syncStatus = $this->getSyncStatus($integrations);
        // $analytics = $this->getEnhancedVendorAnalytics($organizationId);
        // $paymentData = $this->getPaymentCollectionData($organizationId);
        // $receivables = $this->getAccountReceivables($organizationId);
        // $alerts = $this->getSystemAlerts($organizationId);
        // $complianceStatus = $this->getComplianceStatus($organizationId);

        // For now, use the simple dashboard view with basic data
        return view('vendor.dashboard.simple', compact(
            'stats', 'recentInvoices', 'integrations'
        ));
    }

    /**
     * Invoice management dashboard
     */
    public function invoices(Request $request)
    {
        $vendor = Auth::user();
        $query = Invoice::where('vendor_id', $vendor->id);

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $request->search . '%');
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(25);

        $stats = [
            'total' => Invoice::where('vendor_id', $vendor->id)->count(),
            'pending' => Invoice::where('vendor_id', $vendor->id)->where('status', 'pending')->count(),
            'paid' => Invoice::where('vendor_id', $vendor->id)->where('status', 'paid')->count(),
            'overdue' => Invoice::where('vendor_id', $vendor->id)->where('status', 'overdue')->count(),
        ];

        return view('vendor.dashboard.invoices', compact('invoices', 'stats'));
    }

    /**
     * Show single invoice
     */
    public function showInvoice(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'integration', 'syncLogs']);

        return view('vendor.dashboard.invoice-details', compact('invoice'));
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load('items');

        $pdf = Pdf::loadView('vendor.invoices.pdf', compact('invoice'));

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    /**
     * Print invoice
     */
    public function printInvoice(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load('items');

        return view('vendor.invoices.print', compact('invoice'));
    }

    /**
     * Reports dashboard
     */
    public function reports(Request $request)
    {
        $vendor = Auth::user();
        $period = $request->period ?? 'monthly';

        $reports = [
            'revenue' => $this->reportingService->generateRevenueReport($vendor->id, $period),
            'invoices' => $this->reportingService->generateInvoiceReport($vendor->id, $period),
            'customers' => $this->reportingService->generateCustomerReport($vendor->id),
            'integrations' => $this->reportingService->generateIntegrationReport($vendor->id)
        ];

        return view('vendor.dashboard.reports', compact('reports', 'period'));
    }

    /**
     * Analytics dashboard
     */
    public function analytics()
    {
        $vendor = Auth::user();

        $analytics = [
            'dashboard_data' => $this->analyticsService->getVendorDashboardData($vendor->id),
            'kpis' => $this->analyticsService->getVendorKPIs($vendor->id),
            'trends' => $this->analyticsService->getVendorTrends($vendor->id),
            'insights' => $this->analyticsService->getVendorInsights($vendor->id)
        ];

        return view('vendor.dashboard.analytics', compact('analytics'));
    }

    /**
     * Integration status
     */
    public function integrations()
    {
        $vendor = Auth::user();
        $integrations = IntegrationSetting::where('vendor_id', $vendor->id)
            ->with(['syncLogs' => function($query) {
                $query->latest()->take(5);
            }])
            ->get();

        $stats = [
            'total' => $integrations->count(),
            'active' => $integrations->where('status', 'active')->count(),
            'syncing' => $integrations->where('sync_status', 'syncing')->count(),
            'errors' => $integrations->where('status', 'error')->count()
        ];

        return view('vendor.dashboard.integrations', compact('integrations', 'stats'));
    }

    /**
     * Get real-time dashboard data
     */
    public function getRealTimeData(): JsonResponse
    {
        $vendor = Auth::user();

        return response()->json([
            'stats' => $this->getDashboardStats($vendor->id),
            'recent_invoices' => Invoice::where('vendor_id', $vendor->id)
                ->latest()
                ->take(5)
                ->get(),
            'sync_status' => $this->getSyncStatus(
                IntegrationSetting::where('vendor_id', $vendor->id)->get()
            ),
            'notifications' => $this->getRecentNotifications($vendor->id)
        ]);
    }

    /**
     * Export data
     */
    public function export(Request $request)
    {
        $vendor = Auth::user();
        $type = $request->type; // 'invoices', 'revenue', 'customers'
        $format = $request->format ?? 'excel'; // 'excel', 'csv', 'pdf'

        switch ($type) {
            case 'invoices':
                return $this->exportInvoices($vendor->id, $format, $request->all());
            case 'revenue':
                return $this->exportRevenue($vendor->id, $format, $request->all());
            case 'customers':
                return $this->exportCustomers($vendor->id, $format);
            default:
                return response()->json(['error' => 'Invalid export type'], 400);
        }
    }

    /**
     * Settings page
     */
    public function settings()
    {
        $vendor = Auth::user();
        $integrations = IntegrationSetting::where('vendor_id', $vendor->id)->get();

        return view('vendor.dashboard.settings', compact('integrations'));
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $vendor = Auth::user();

        $request->validate([
            'notification_preferences' => 'array',
            'report_preferences' => 'array',
            'dashboard_layout' => 'array'
        ]);

        $vendor->update([
            'settings' => array_merge($vendor->settings ?? [], [
                'notification_preferences' => $request->notification_preferences,
                'report_preferences' => $request->report_preferences,
                'dashboard_layout' => $request->dashboard_layout
            ])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(int $vendorId): array
    {
        $invoices = Invoice::where('vendor_id', $vendorId);

        return [
            'total_invoices' => $invoices->count(),
            'total_revenue' => $invoices->sum('total_amount'),
            'pending_amount' => $invoices->where('status', 'pending')->sum('total_amount'),
            'paid_amount' => $invoices->where('status', 'paid')->sum('total_amount'),
            'monthly_revenue' => $invoices->whereMonth('created_at', now()->month)->sum('total_amount'),
            'monthly_invoices' => $invoices->whereMonth('created_at', now()->month)->count(),
            'average_invoice' => $invoices->avg('total_amount') ?? 0,
            'customers' => $invoices->distinct('customer_email')->count(),
            'this_week' => [
                'invoices' => $invoices->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'revenue' => $invoices->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount')
            ],
            'last_sync' => IntegrationSetting::where('vendor_id', $vendorId)->max('last_sync_at')
        ];
    }

    /**
     * Get sync status for integrations
     */
    private function getSyncStatus($integrations): array
    {
        $status = [
            'healthy' => 0,
            'syncing' => 0,
            'errors' => 0,
            'inactive' => 0
        ];

        foreach ($integrations as $integration) {
            switch ($integration->status) {
                case 'active':
                    if ($integration->sync_status === 'syncing') {
                        $status['syncing']++;
                    } else {
                        $status['healthy']++;
                    }
                    break;
                case 'error':
                    $status['errors']++;
                    break;
                default:
                    $status['inactive']++;
            }
        }

        return $status;
    }

    /**
     * Get vendor analytics
     */
    private function getVendorAnalytics(int $vendorId): array
    {
        return [
            'revenue_trend' => $this->getRevenueTrend($vendorId),
            'invoice_status_distribution' => $this->getInvoiceStatusDistribution($vendorId),
            'top_customers' => $this->getTopCustomers($vendorId),
            'integration_performance' => $this->getIntegrationPerformance($vendorId)
        ];
    }

    /**
     * Get revenue trend data
     */
    private function getRevenueTrend(int $vendorId): array
    {
        $trend = [];
        $months = collect(range(0, 11))->map(function ($i) {
            return now()->subMonths($i);
        })->reverse();

        foreach ($months as $month) {
            $revenue = Invoice::where('vendor_id', $vendorId)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('total_amount');

            $trend[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue
            ];
        }

        return $trend;
    }

    /**
     * Get invoice status distribution
     */
    private function getInvoiceStatusDistribution(int $vendorId): array
    {
        return Invoice::where('vendor_id', $vendorId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get top customers
     */
    private function getTopCustomers(int $vendorId): array
    {
        return Invoice::where('vendor_id', $vendorId)
            ->selectRaw('customer_name, customer_email, COUNT(*) as invoice_count, SUM(total_amount) as total_amount')
            ->groupBy('customer_name', 'customer_email')
            ->orderBy('total_amount', 'desc')
            ->take(10)
            ->get()
            ->toArray();
    }

    /**
     * Get integration performance
     */
    private function getIntegrationPerformance(int $vendorId): array
    {
        return IntegrationSetting::where('vendor_id', $vendorId)
            ->with(['syncLogs' => function($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            }])
            ->get()
            ->map(function ($integration) {
                $logs = $integration->syncLogs;
                $successful = $logs->where('status', 'success')->count();
                $total = $logs->count();

                return [
                    'name' => $integration->vendor,
                    'success_rate' => $total > 0 ? ($successful / $total) * 100 : 100,
                    'last_sync' => $integration->last_sync_at,
                    'status' => $integration->status
                ];
            })
            ->toArray();
    }

    /**
     * Get enhanced dashboard statistics with InvoiceQ-inspired metrics
     */
    private function getEnhancedDashboardStats(int $organizationId): array
    {
        $baseStats = $this->getDashboardStats($organizationId);

        // Additional InvoiceQ-inspired metrics
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $currentYear = now()->startOfYear();

        // Query optimization - get invoice data once
        $invoiceQuery = Invoice::where(function($query) use ($organizationId) {
            $query->where('vendor_id', $organizationId)
                  ->orWhere('organization_id', $organizationId);
        });

        // Collection metrics
        $totalInvoiced = $invoiceQuery->clone()->where('status', 'submitted')->sum('total_amount');
        $totalCollected = $invoiceQuery->clone()->where('status', 'submitted')
            ->where('payment_status', 'paid')->sum('total_amount');
        $collectionRate = $totalInvoiced > 0 ? ($totalCollected / $totalInvoiced) * 100 : 0;

        // Growth calculations
        $currentRevenue = $invoiceQuery->clone()->where('created_at', '>=', $currentMonth)
            ->where('status', 'submitted')->sum('total_amount');
        $previousRevenue = $invoiceQuery->clone()
            ->where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $currentMonth)
            ->where('status', 'submitted')->sum('total_amount');
        $monthlyGrowth = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        return array_merge($baseStats, [
            // Payment Collection Metrics
            'collection_rate' => round($collectionRate, 2),
            'pending_payments' => $invoiceQuery->clone()->where('status', 'submitted')
                ->where('payment_status', 'pending')->sum('total_amount'),
            'overdue_amount' => $invoiceQuery->clone()->where('status', 'submitted')
                ->where('due_date', '<', now())
                ->where('payment_status', '!=', 'paid')->sum('total_amount'),
            'overdue_count' => $invoiceQuery->clone()->where('status', 'submitted')
                ->where('due_date', '<', now())
                ->where('payment_status', '!=', 'paid')->count(),

            // Growth Metrics
            'monthly_growth' => round($monthlyGrowth, 2),
            'yearly_revenue' => $invoiceQuery->clone()->where('created_at', '>=', $currentYear)
                ->where('status', 'submitted')->sum('total_amount'),

            // Tax & Compliance
            'total_tax_collected' => $invoiceQuery->clone()->where('status', 'submitted')
                ->sum('tax_amount'),
            'compliance_score' => $this->calculateComplianceScore($organizationId),

            // Customer Insights
            'unique_customers' => $invoiceQuery->clone()->distinct('customer_tax_number')
                ->count('customer_tax_number'),
            'avg_invoice_value' => $invoiceQuery->clone()->where('status', 'submitted')
                ->avg('total_amount') ?? 0,
            'avg_payment_time' => $this->calculateAveragePaymentTime($organizationId),

            // Performance Indicators
            'invoice_processing_time' => $this->calculateInvoiceProcessingTime($organizationId),
            'rejection_rate' => $this->calculateRejectionRate($organizationId),
        ]);
    }

    /**
     * Get enhanced vendor analytics with advanced features
     */
    private function getEnhancedVendorAnalytics(int $organizationId): array
    {
        $baseAnalytics = $this->getVendorAnalytics($organizationId);

        return array_merge($baseAnalytics, [
            'payment_trends' => $this->getPaymentTrends($organizationId),
            'customer_segmentation' => $this->getCustomerSegmentation($organizationId),
            'seasonal_patterns' => $this->getSeasonalPatterns($organizationId),
            'compliance_metrics' => $this->getComplianceMetrics($organizationId),
            'forecasting' => $this->getRevenueForecast($organizationId),
        ]);
    }

    /**
     * Get payment collection data - InvoiceQ inspired feature
     */
    private function getPaymentCollectionData(int $organizationId): array
    {
        $invoiceQuery = Invoice::where(function($query) use ($organizationId) {
            $query->where('vendor_id', $organizationId)
                  ->orWhere('organization_id', $organizationId);
        });

        return [
            'collection_efficiency' => [
                'on_time' => $invoiceQuery->clone()->where('status', 'submitted')
                    ->where('payment_status', 'paid')
                    ->where('paid_at', '<=', 'due_date')->count(),
                'late' => $invoiceQuery->clone()->where('status', 'submitted')
                    ->where('payment_status', 'paid')
                    ->where('paid_at', '>', 'due_date')->count(),
                'overdue' => $invoiceQuery->clone()->where('status', 'submitted')
                    ->where('due_date', '<', now())
                    ->where('payment_status', '!=', 'paid')->count(),
            ],
            'payment_methods' => $this->getPaymentMethodsDistribution($organizationId),
            'dunning_process' => $this->getDunningProcessData($organizationId),
            'collection_forecast' => $this->getCollectionForecast($organizationId),
        ];
    }

    /**
     * Get account receivables data - InvoiceQ feature
     */
    private function getAccountReceivables(int $organizationId): array
    {
        $invoiceQuery = Invoice::where(function($query) use ($organizationId) {
            $query->where('vendor_id', $organizationId)
                  ->orWhere('organization_id', $organizationId);
        })->where('status', 'submitted')->where('payment_status', '!=', 'paid');

        return [
            'aging_buckets' => [
                'current' => $invoiceQuery->clone()->where('due_date', '>=', now())->sum('total_amount'),
                '1_30_days' => $invoiceQuery->clone()
                    ->where('due_date', '<', now())
                    ->where('due_date', '>=', now()->subDays(30))->sum('total_amount'),
                '31_60_days' => $invoiceQuery->clone()
                    ->where('due_date', '<', now()->subDays(30))
                    ->where('due_date', '>=', now()->subDays(60))->sum('total_amount'),
                '61_90_days' => $invoiceQuery->clone()
                    ->where('due_date', '<', now()->subDays(60))
                    ->where('due_date', '>=', now()->subDays(90))->sum('total_amount'),
                'over_90_days' => $invoiceQuery->clone()
                    ->where('due_date', '<', now()->subDays(90))->sum('total_amount'),
            ],
            'top_debtors' => $this->getTopDebtors($organizationId),
            'collection_actions' => $this->getCollectionActions($organizationId),
        ];
    }

    /**
     * Get system alerts - InvoiceQ feature
     */
    private function getSystemAlerts(int $organizationId): array
    {
        $alerts = [];

        $invoiceQuery = Invoice::where(function($query) use ($organizationId) {
            $query->where('vendor_id', $organizationId)
                  ->orWhere('organization_id', $organizationId);
        });

        // Overdue invoices alert
        $overdueCount = $invoiceQuery->clone()->where('status', 'submitted')
            ->where('due_date', '<', now())
            ->where('payment_status', '!=', 'paid')->count();

        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'priority' => 'high',
                'title' => 'Overdue Invoices',
                'message' => "You have {$overdueCount} overdue invoices requiring attention.",
                'action_url' => route('vendor.invoices.index', ['status' => 'overdue']),
                'action_text' => 'View Overdue Invoices'
            ];
        }

        // Integration health check
        $integration = IntegrationSetting::where('organization_id', $organizationId)->first();
        if (!$integration || !$integration->is_active) {
            $alerts[] = [
                'type' => 'danger',
                'priority' => 'critical',
                'title' => 'Integration Issue',
                'message' => 'Your e-invoicing integration is not active or configured.',
                'action_url' => route('vendor.integrations.index'),
                'action_text' => 'Fix Integration'
            ];
        }

        // Compliance alerts
        $rejectedCount = $invoiceQuery->clone()->where('status', 'rejected')
            ->where('created_at', '>=', now()->subDays(7))->count();

        if ($rejectedCount > 5) {
            $alerts[] = [
                'type' => 'warning',
                'priority' => 'medium',
                'title' => 'High Rejection Rate',
                'message' => "You have {$rejectedCount} rejected invoices this week. Review compliance requirements.",
                'action_url' => route('vendor.reports.index', ['type' => 'compliance']),
                'action_text' => 'View Compliance Report'
            ];
        }

        return $alerts;
    }

    /**
     * Get compliance status - InvoiceQ feature
     */
    private function getComplianceStatus(int $organizationId): array
    {
        $invoiceQuery = Invoice::where(function($query) use ($organizationId) {
            $query->where('vendor_id', $organizationId)
                  ->orWhere('organization_id', $organizationId);
        });

        $totalInvoices = $invoiceQuery->clone()->count();
        $submittedInvoices = $invoiceQuery->clone()->where('status', 'submitted')->count();
        $rejectedInvoices = $invoiceQuery->clone()->where('status', 'rejected')->count();

        $complianceRate = $totalInvoices > 0 ? (($submittedInvoices / $totalInvoices) * 100) : 100;
        $rejectionRate = $totalInvoices > 0 ? (($rejectedInvoices / $totalInvoices) * 100) : 0;

        return [
            'compliance_rate' => round($complianceRate, 2),
            'rejection_rate' => round($rejectionRate, 2),
            'status' => $complianceRate >= 95 ? 'excellent' : ($complianceRate >= 85 ? 'good' : 'needs_improvement'),
            'recommendations' => $this->getComplianceRecommendations($complianceRate, $rejectionRate),
            'tax_authority_connection' => $this->checkTaxAuthorityConnection($organizationId),
        ];
    }

    /**
     * Helper methods for enhanced features
     */
    private function calculateComplianceScore(int $organizationId): float
    {
        // Implementation for compliance score calculation
        return 85.5; // Placeholder
    }

    private function calculateAveragePaymentTime(int $organizationId): float
    {
        $paidInvoices = Invoice::where(function($query) use ($organizationId) {
            $query->where('vendor_id', $organizationId)
                  ->orWhere('organization_id', $organizationId);
        })
        ->where('status', 'submitted')
        ->where('payment_status', 'paid')
        ->whereNotNull('paid_at')
        ->get();

        if ($paidInvoices->isEmpty()) return 0;

        $totalDays = 0;
        foreach ($paidInvoices as $invoice) {
            $totalDays += now()->parse($invoice->invoice_date)
                ->diffInDays(now()->parse($invoice->paid_at));
        }

        return round($totalDays / $paidInvoices->count(), 1);
    }

    private function calculateInvoiceProcessingTime(int $organizationId): float
    {
        // Implementation for processing time calculation
        return 2.5; // Placeholder - average days
    }

    private function calculateRejectionRate(int $organizationId): float
    {
        $invoiceQuery = Invoice::where(function($query) use ($organizationId) {
            $query->where('vendor_id', $organizationId)
                  ->orWhere('organization_id', $organizationId);
        });

        $totalInvoices = $invoiceQuery->clone()->count();
        $rejectedInvoices = $invoiceQuery->clone()->where('status', 'rejected')->count();

        return $totalInvoices > 0 ? round(($rejectedInvoices / $totalInvoices) * 100, 2) : 0;
    }

    private function getPaymentTrends(int $organizationId): array
    {
        // Implementation for payment trends
        return []; // Placeholder
    }

    private function getCustomerSegmentation(int $organizationId): array
    {
        // Implementation for customer segmentation
        return []; // Placeholder
    }

    private function getSeasonalPatterns(int $organizationId): array
    {
        // Implementation for seasonal patterns
        return []; // Placeholder
    }

    private function getComplianceMetrics(int $organizationId): array
    {
        // Implementation for compliance metrics
        return []; // Placeholder
    }

    private function getRevenueForecast(int $organizationId): array
    {
        // Implementation for revenue forecasting
        return []; // Placeholder
    }

    private function getPaymentMethodsDistribution(int $organizationId): array
    {
        // This would require a payment_method field in invoices table
        return [
            'bank_transfer' => 60,
            'credit_card' => 30,
            'cash' => 10
        ];
    }

    private function getDunningProcessData(int $organizationId): array
    {
        // Implementation for dunning process data
        return []; // Placeholder
    }

    private function getCollectionForecast(int $organizationId): array
    {
        // Implementation for collection forecast
        return []; // Placeholder
    }

    private function getTopDebtors(int $organizationId): array
    {
        return Invoice::where(function($query) use ($organizationId) {
            $query->where('vendor_id', $organizationId)
                  ->orWhere('organization_id', $organizationId);
        })
        ->where('status', 'submitted')
        ->where('payment_status', '!=', 'paid')
        ->select('customer_name', 'customer_tax_number')
        ->selectRaw('SUM(total_amount) as outstanding_amount')
        ->selectRaw('COUNT(*) as invoice_count')
        ->groupBy('customer_name', 'customer_tax_number')
        ->orderBy('outstanding_amount', 'desc')
        ->take(10)
        ->get()
        ->toArray();
    }

    private function getCollectionActions(int $organizationId): array
    {
        // Implementation for collection actions tracking
        return []; // Placeholder
    }

    private function getComplianceRecommendations(float $complianceRate, float $rejectionRate): array
    {
        $recommendations = [];

        if ($complianceRate < 85) {
            $recommendations[] = 'Review invoice format and required fields';
            $recommendations[] = 'Ensure tax calculations are accurate';
        }

        if ($rejectionRate > 10) {
            $recommendations[] = 'Check customer tax numbers for validity';
            $recommendations[] = 'Verify invoice sequence numbers';
        }

        return $recommendations;
    }

    private function checkTaxAuthorityConnection(int $organizationId): array
    {
        $integration = IntegrationSetting::where('organization_id', $organizationId)->first();

        return [
            'connected' => $integration && $integration->is_active,
            'last_sync' => $integration?->last_sync_at,
            'status' => $integration?->status ?? 'not_configured'
        ];
    }

    /**
     * Get recent notifications
     */
    private function getRecentNotifications(int $vendorId): array
    {
        // Implementation would depend on your notification system
        return [];
    }

    /**
     * Export invoices
     */
    private function exportInvoices(int $vendorId, string $format, array $filters)
    {
        $query = Invoice::where('vendor_id', $vendorId);

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('invoice_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('invoice_date', '<=', $filters['date_to']);
        }

        $invoices = $query->get();

        return $this->reportingService->exportInvoices($invoices, $format);
    }

    /**
     * Export revenue report
     */
    private function exportRevenue(int $vendorId, string $format, array $filters)
    {
        $revenueData = $this->reportingService->generateRevenueReport($vendorId, $filters['period'] ?? 'monthly');

        return $this->reportingService->exportRevenue($revenueData, $format);
    }

    /**
     * Export customers
     */
    private function exportCustomers(int $vendorId, string $format)
    {
        $customers = $this->getTopCustomers($vendorId);

        return $this->reportingService->exportCustomers($customers, $format);
    }

    /**
     * Get basic dashboard statistics (fallback method for current DB structure)
     */
    private function getBasicDashboardStats(int $organizationId): array
    {
        $invoiceQuery = Invoice::where('organization_id', $organizationId);

        return [
            'total_invoices' => $invoiceQuery->clone()->count(),
            'submitted_invoices' => $invoiceQuery->clone()->where('status', 'submitted')->count(),
            'draft_invoices' => $invoiceQuery->clone()->where('status', 'draft')->count(),
            'rejected_invoices' => $invoiceQuery->clone()->where('status', 'rejected')->count(),
            'total_revenue' => $invoiceQuery->clone()->where('status', 'submitted')->sum('total_amount'),
            'total_tax_collected' => $invoiceQuery->clone()->where('status', 'submitted')->sum('tax_amount'),
            'monthly_revenue' => $invoiceQuery->clone()
                ->where('status', 'submitted')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount'),
            'avg_invoice_value' => $invoiceQuery->clone()->where('status', 'submitted')->avg('total_amount') ?? 0,
            'unique_customers' => $invoiceQuery->clone()->distinct('customer_tax_number')->count(),
            'rejection_rate' => $this->calculateBasicRejectionRate($organizationId),
        ];
    }

    private function calculateBasicRejectionRate(int $organizationId): float
    {
        $invoiceQuery = Invoice::where('organization_id', $organizationId);
        $totalInvoices = $invoiceQuery->clone()->count();
        $rejectedInvoices = $invoiceQuery->clone()->where('status', 'rejected')->count();

        return $totalInvoices > 0 ? round(($rejectedInvoices / $totalInvoices) * 100, 2) : 0;
    }
}
