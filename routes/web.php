<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VendorProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VendorDashboardController;
use Illuminate\Support\Facades\Auth;

Auth::routes();

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

    // Integration logs (general view for all integrations)
    Route::get('/integration-logs', [VendorDashboardController::class, 'integrationLogs'])->name('integration.logs');

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

    // Note: Vendor routes have been moved to routes/vendor.php for better organization

    // These routes should also be moved to vendor.php if needed
});

Route::middleware(['auth', 'can:admin-panel'])->group(function () {
    Route::get('/admin', function() { return view('admin.panel'); })->name('admin.panel');
    Route::get('/admin/vendors', [AdminController::class, 'index'])->name('admin.vendors');
    Route::post('/admin/vendors/{id}/toggle', [AdminController::class, 'toggleVendor'])->name('admin.toggleVendor');
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
