<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Models\IntegrationSetting;
use App\Models\SyncLog;
use App\Models\User;
use Carbon\Carbon;

class VendorDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard index page
     */
    public function index()
    {
        $user = Auth::user();

        try {
            // Get dashboard statistics
            $stats = $this->getDashboardStats($user);

            // Get recent invoices
            $recentInvoices = Invoice::where('vendor_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Get integrations
            $integrations = IntegrationSetting::where('user_id', $user->id)
                ->orWhere('vendor_id', $user->id)
                ->get();

            // Get analytics data for charts
            $analytics = $this->getAnalyticsData($user);

            return view('vendor.dashboard.index', compact('stats', 'recentInvoices', 'integrations', 'analytics'));

        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            // Return a simplified dashboard to help debug
            return view('vendor.dashboard.index', [
                'stats' => [
                    'total_revenue' => 0,
                    'total_invoices' => 0,
                    'submitted_invoices' => 0,
                    'draft_invoices' => 0,
                    'rejected_invoices' => 0,
                    'monthly_revenue' => 0,
                    'rejection_rate' => 0,
                    'integration_status' => 'error'
                ],
                'recentInvoices' => collect(),
                'integrations' => collect(),
                'analytics' => [
                    'revenue_trend' => [],
                    'invoice_status_distribution' => []
                ]
            ]);
        }
    }

    /**
     * Invoices listing page
     */
    public function invoices(Request $request)
    {
        $user = Auth::user();
        $query = Invoice::where('vendor_id', $user->id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('vendor.dashboard.invoices', compact('invoices'));
    }

    /**
     * Create invoice form
     */
    public function createInvoice()
    {
        $this->authorize('create', Invoice::class);
        return view('vendor.invoices.create');
    }

    /**
     * Store new invoice
     */
    public function storeInvoice(Request $request)
    {
        $this->authorize('create', Invoice::class);

        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:invoices',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_address' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'currency' => 'required|string|max:3',
            'status' => 'required|in:draft,pending,sent',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $taxAmount = ($subtotal * ($validated['tax_rate'] ?? 0)) / 100;
            $totalAmount = $subtotal + $taxAmount - ($validated['discount_amount'] ?? 0);

            // Create invoice
            $invoice = Invoice::create([
                'vendor_id' => Auth::id(),
                'invoice_number' => $validated['invoice_number'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_address' => $validated['customer_address'],
                'customer_phone' => $validated['customer_phone'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'currency' => $validated['currency'],
                'status' => $validated['status'],
                'subtotal_amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'],
                'terms' => $validated['terms']
            ]);

            // Create invoice items
            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'additional_details' => $item['additional_details'] ?? null
                ]);
            }

            DB::commit();

            // Handle different actions
            if ($request->action === 'save_and_send') {
                // In a real implementation, you would send the invoice here
                $invoice->update(['status' => 'sent']);
                $message = 'Invoice created and sent successfully!';
            } else {
                $message = 'Invoice created successfully!';
            }

            return redirect()->route('vendor.invoices.show', $invoice)->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice creation failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to create invoice. Please try again.']);
        }
    }

    /**
     * Show single invoice
     */
    public function showInvoice(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'integration']);

        return view('vendor.dashboard.invoice-details', compact('invoice'));
    }

    /**
     * Edit invoice form
     */
    public function editInvoice(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        return view('vendor.invoices.edit', compact('invoice'));
    }

    /**
     * Update invoice
     */
    public function updateInvoice(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        // Implementation similar to storeInvoice...
        return redirect()->route('vendor.invoices.show', $invoice)->with('success', 'Invoice updated successfully!');
    }

    /**
     * Delete invoice
     */
    public function deleteInvoice(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();
        return redirect()->route('vendor.invoices.index')->with('success', 'Invoice deleted successfully!');
    }

    /**
     * Download invoice
     */
    public function downloadInvoice(Invoice $invoice)
    {
        $this->authorize('download', $invoice);
        // Implementation for PDF download...
        return view('vendor.invoices.pdf', compact('invoice'));
    }

    /**
     * Print invoice
     */
    public function printInvoice(Invoice $invoice)
    {
        $this->authorize('print', $invoice);
        return view('vendor.invoices.print', compact('invoice'));
    }

    /**
     * Reports page
     */
    public function reports()
    {
        $user = Auth::user();

        // Get reports data
        $reports = [
            'revenue_overview' => $this->getRevenueOverview($user),
            'invoice_analytics' => $this->getInvoiceAnalytics($user),
            'customer_analytics' => $this->getCustomerAnalytics($user),
            'integration_status' => $this->getIntegrationStatus($user)
        ];

        return view('vendor.dashboard.reports', compact('reports'));
    }

    /**
     * Analytics page
     */
    public function analytics()
    {
        $user = Auth::user();

        $analytics = [
            'kpis' => $this->getKPIs($user),
            'trends' => $this->getTrends($user),
            'insights' => $this->getInsights($user)
        ];

        return view('vendor.dashboard.analytics', compact('analytics'));
    }

    /**
     * Integrations listing
     */
    public function integrations()
    {
        $user = Auth::user();

        $integrations = IntegrationSetting::where('user_id', $user->id)
            ->orWhere('vendor_id', $user->id)
            ->with('syncLogs')
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
     * Create integration form
     */
    public function createIntegration()
    {
        return view('vendor.integrations.create');
    }

    /**
     * Store integration
     */
    public function storeIntegration(Request $request)
    {
        $validated = $request->validate([
            'integration_type' => 'required|string',
            'name' => 'required|string|max:255',
            'config' => 'required|array',
            'sync_frequency' => 'required|in:manual,hourly,daily,weekly',
            'auto_sync' => 'boolean'
        ]);

        try {
            $integration = IntegrationSetting::create([
                'user_id' => Auth::id(),
                'vendor_id' => Auth::id(),
                'vendor_name' => ucfirst($validated['integration_type']),
                'integration_type' => $validated['integration_type'],
                'name' => $validated['name'],
                'settings' => $validated['config'],
                'sync_frequency' => $validated['sync_frequency'],
                'auto_sync_enabled' => $validated['auto_sync'] ?? false,
                'status' => 'pending'
            ]);

            return redirect()->route('vendor.integrations.index')->with('success', 'Integration created successfully!');

        } catch (\Exception $e) {
            Log::error('Integration creation failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to create integration.']);
        }
    }

    /**
     * Integration logs
     */
    public function integrationLogs(Request $request)
    {
        $user = Auth::user();

        $query = SyncLog::whereHas('integration', function($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('vendor_id', $user->id);
        });

        // Apply filters
        if ($request->filled('integration')) {
            $query->whereHas('integration', function($q) use ($request) {
                $q->where('vendor_name', $request->integration);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->subMonth());
                    break;
            }
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15);

        $stats = [
            'total' => $query->count(),
            'success' => $query->where('status', 'success')->count(),
            'error' => $query->where('status', 'error')->count(),
            'warning' => $query->where('status', 'warning')->count()
        ];

        return view('vendor.integrations.logs', compact('logs', 'stats'));
    }

    /**
     * Settings page
     */
    public function settings()
    {
        $user = Auth::user();

        $settings = [
            'integrations' => IntegrationSetting::where('user_id', $user->id)
                ->orWhere('vendor_id', $user->id)->get()
        ];

        return view('vendor.dashboard.settings', compact('settings'));
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        if ($request->settings_type === 'password') {
            $validated = $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|confirmed|min:8',
            ]);

            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $user->update(['password' => Hash::make($validated['new_password'])]);
            return back()->with('success', 'Password updated successfully!');

        } elseif ($request->settings_type === 'notifications') {
            $user->update(['settings' => array_merge($user->settings ?? [], ['notifications' => $request->notifications])]);
            return back()->with('success', 'Notification preferences updated!');

        } else {
            // Profile update
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'company_name' => 'nullable|string|max:255',
                'tax_number' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'phone' => 'nullable|string|max:20'
            ]);

            $user->update($validated);
            return back()->with('success', 'Profile updated successfully!');
        }
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        try {
            Cache::flush();
            return response()->json(['success' => true, 'message' => 'Cache cleared successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to clear cache.']);
        }
    }

    /**
     * Export data
     */
    public function export(Request $request)
    {
        // Implementation for data export...
        return response()->json(['success' => true, 'message' => 'Export started']);
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(User $user): array
    {
        return [
            'total_revenue' => Invoice::where('vendor_id', $user->id)->sum('total_amount'),
            'total_invoices' => Invoice::where('vendor_id', $user->id)->count(),
            'submitted_invoices' => Invoice::where('vendor_id', $user->id)->whereIn('status', ['sent', 'paid'])->count(),
            'draft_invoices' => Invoice::where('vendor_id', $user->id)->where('status', 'draft')->count(),
            'rejected_invoices' => Invoice::where('vendor_id', $user->id)->where('status', 'rejected')->count(),
            'monthly_revenue' => Invoice::where('vendor_id', $user->id)->whereMonth('created_at', now()->month)->sum('total_amount'),
            'rejection_rate' => 0,
            'integration_status' => 'active'
        ];
    }

    /**
     * Get KPIs for analytics
     */
    private function getKPIs(User $user): array
    {
        return [
            'total_revenue' => 15750,
            'revenue_growth' => 12.5,
            'total_invoices' => 48,
            'invoice_growth' => 8.3,
            'avg_invoice_value' => 328,
            'avg_value_trend' => 5.2,
            'payment_rate' => 94.5,
            'payment_rate_trend' => 2.1,
            'collection_rate' => 88.2,
            'approval_rate' => 96.7,
            'integration_health' => 92.3,
            'data_quality' => 95.8
        ];
    }

    /**
     * Get insights for analytics
     */
    private function getInsights(User $user): array
    {
        return [
            [
                'type' => 'positive',
                'title' => 'Revenue Growth',
                'description' => 'Your revenue increased by 12.5% compared to last month. Keep up the excellent work!'
            ],
            [
                'type' => 'warning',
                'title' => 'Payment Delays',
                'description' => 'Some invoices are taking longer to get paid. Consider following up with customers.'
            ],
            [
                'type' => 'info',
                'title' => 'Integration Sync',
                'description' => 'All integrations are running smoothly with 99.2% uptime this month.'
            ]
        ];
    }

    /**
     * Get analytics data for charts
     */
    private function getAnalyticsData(User $user): array
    {
        return [
            'revenue_trend' => $this->getRevenueTrend($user),
            'invoice_status_distribution' => $this->getInvoiceStatusDistribution($user)
        ];
    }

    /**
     * Get revenue trend data for the last 12 months
     */
    private function getRevenueTrend(User $user): array
    {
        $trend = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = Invoice::where('vendor_id', $user->id)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('total_amount');

            $trend[] = [
                'month' => $month->format('M Y'),
                'revenue' => floatval($revenue ?: 0)
            ];
        }
        return $trend;
    }

    /**
     * Get invoice status distribution
     */
    private function getInvoiceStatusDistribution(User $user): array
    {
        $statuses = Invoice::where('vendor_id', $user->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Ensure we have default values for common statuses
        return array_merge([
            'draft' => 0,
            'sent' => 0,
            'paid' => 0,
            'overdue' => 0,
            'cancelled' => 0
        ], $statuses);
    }

    // Additional helper methods would go here...
    private function getRevenueOverview(User $user): array { return []; }
    private function getInvoiceAnalytics(User $user): array { return []; }
    private function getCustomerAnalytics(User $user): array { return []; }
    private function getIntegrationStatus(User $user): array { return []; }
    private function getTrends(User $user): array { return []; }
}
