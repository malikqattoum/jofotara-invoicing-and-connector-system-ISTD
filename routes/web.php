<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VendorProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;

Route::get('/', function () {
    return view('landing');
})->name('landing');

// Arabic Authentication Routes
Route::get('/login-ar', function () {
    return view('auth.login-ar');
})->name('login.ar');

Route::get('/register-ar', function () {
    return view('auth.register-ar');
})->name('register.ar');

// Demo page
Route::get('/demo', function () {
    return view('demo');
})->name('demo');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/vendor/profile', [VendorProfileController::class, 'show'])->name('vendor.profile');
    Route::put('/vendor/profile', [VendorProfileController::class, 'update'])->name('vendor.profile.update');
});

// Enhanced Vendor Dashboard Routes - InvoiceQ inspired features
Route::middleware('auth')->prefix('vendor')->name('vendor.')->group(function () {
    // Main Dashboard
    Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('dashboard.index');

    // Test route for dashboard
    Route::get('/test-dashboard', function() {
        return view('vendor.dashboard.simple', [
            'stats' => [
                'total_revenue' => 15750.00,
                'total_invoices' => 3,
                'submitted_invoices' => 2,
                'draft_invoices' => 1,
                'rejected_invoices' => 0,
                'monthly_revenue' => 8500.00,
                'rejection_rate' => 0
            ],
            'recentInvoices' => \App\Models\Invoice::take(5)->get(),
            'integrations' => []
        ]);
    })->name('test-dashboard');

    // Invoice Management
    Route::get('/invoices', [VendorDashboardController::class, 'invoices'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [VendorDashboardController::class, 'showInvoice'])->name('invoices.show');
    Route::get('/invoices/{invoice}/download', [VendorDashboardController::class, 'downloadInvoice'])->name('invoices.download');
    Route::get('/invoices/{invoice}/print', [VendorDashboardController::class, 'printInvoice'])->name('invoices.print');

    // Reports & Analytics
    Route::get('/reports', [VendorDashboardController::class, 'reports'])->name('reports.index');
    Route::get('/reports/revenue', [VendorDashboardController::class, 'reports'])->name('reports.revenue');
    Route::get('/reports/customers', [VendorDashboardController::class, 'reports'])->name('reports.customers');
    Route::get('/analytics', [VendorDashboardController::class, 'analytics'])->name('analytics.index');

    // Integration Management
    Route::get('/integrations', [VendorDashboardController::class, 'integrations'])->name('integrations.index');
    Route::get('/integrations/create', function() { return view('vendor.integrations.create'); })->name('integrations.create');
    Route::get('/integrations/logs', function() { return view('vendor.integrations.logs'); })->name('integrations.logs');

    // Settings
    Route::get('/settings', [VendorDashboardController::class, 'settings'])->name('settings.index');
    Route::post('/settings', [VendorDashboardController::class, 'updateSettings'])->name('settings.update');

    // Data Export
    Route::get('/export', [VendorDashboardController::class, 'export'])->name('export');
    Route::get('/export/invoices', [VendorDashboardController::class, 'export'])->name('export.invoices');
    Route::get('/export/revenue', [VendorDashboardController::class, 'export'])->name('export.revenue');

    // Real-time Data & Quick Actions
    Route::get('/real-time-data', [VendorDashboardController::class, 'getRealTimeData'])->name('real-time-data');
    Route::post('/quick/sync-all', function() {
        return response()->json(['success' => true, 'message' => 'Sync initiated']);
    })->name('quick.sync-all');

    // Invoice Creation (placeholder route)
    Route::get('/invoices/create', function() { return view('vendor.invoices.create'); })->name('invoices.create');
});

Route::middleware(['auth', 'can:admin-panel'])->group(function () {
    Route::get('/admin', function() { return view('admin.panel'); })->name('admin.panel');
    Route::get('/admin/vendors', [AdminController::class, 'index'])->name('admin.vendors');
    Route::post('/admin/vendors/{id}/toggle', [AdminController::class, 'toggleVendor'])->name('admin.toggleVendor');
});
