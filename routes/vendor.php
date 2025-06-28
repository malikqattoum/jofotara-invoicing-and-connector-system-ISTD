<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\Vendor\InvoiceController;
use App\Http\Controllers\Vendor\IntegrationController;
use App\Http\Controllers\Vendor\ReportController;
use App\Http\Controllers\Vendor\AnalyticsController;
use App\Http\Controllers\Vendor\SettingsController;
use App\Http\Controllers\Vendor\ProfileController;

/*
|--------------------------------------------------------------------------
| Vendor Routes
|--------------------------------------------------------------------------
|
| These routes are for the vendor dashboard and require vendor authentication
|
*/

Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {

    // Main Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/real-time-data', [DashboardController::class, 'getRealTimeData'])->name('real-time-data');

    // Invoice Management
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [DashboardController::class, 'invoices'])->name('index');
        Route::get('/create', [InvoiceController::class, 'create'])->name('create');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}', [DashboardController::class, 'showInvoice'])->name('show');
        Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');

        // Invoice Actions
        Route::get('/{invoice}/download', [DashboardController::class, 'downloadInvoice'])->name('download');
        Route::get('/{invoice}/print', [DashboardController::class, 'printInvoice'])->name('print');
        Route::post('/{invoice}/send', [InvoiceController::class, 'sendInvoice'])->name('send');
        Route::post('/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('duplicate');
        Route::post('/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('mark-paid');
        Route::post('/{invoice}/mark-pending', [InvoiceController::class, 'markPending'])->name('mark-pending');

        // Bulk Actions
        Route::post('/bulk/send', [InvoiceController::class, 'bulkSend'])->name('bulk.send');
        Route::post('/bulk/delete', [InvoiceController::class, 'bulkDelete'])->name('bulk.delete');
        Route::post('/bulk/export', [InvoiceController::class, 'bulkExport'])->name('bulk.export');
    });

    // Integration Management
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', [DashboardController::class, 'integrations'])->name('index');
        Route::get('/create', [IntegrationController::class, 'create'])->name('create');
        Route::post('/', [IntegrationController::class, 'store'])->name('store');
        Route::get('/{integration}', [IntegrationController::class, 'show'])->name('show');
        Route::put('/{integration}', [IntegrationController::class, 'update'])->name('update');
        Route::delete('/{integration}', [IntegrationController::class, 'destroy'])->name('destroy');

        // Integration Actions
        Route::post('/{integration}/sync', [IntegrationController::class, 'sync'])->name('sync');
        Route::post('/{integration}/test', [IntegrationController::class, 'test'])->name('test');
        Route::post('/{integration}/toggle', [IntegrationController::class, 'toggle'])->name('toggle');
        Route::get('/{integration}/logs', [IntegrationController::class, 'logs'])->name('logs');
        Route::get('/{integration}/setup', [IntegrationController::class, 'setup'])->name('setup');
    });

    // Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [DashboardController::class, 'reports'])->name('index');
        Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('/invoices', [ReportController::class, 'invoices'])->name('invoices');
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('/integrations', [ReportController::class, 'integrations'])->name('integrations');
        Route::get('/business-intelligence', [ReportController::class, 'businessIntelligence'])->name('business-intelligence');

        // Custom Reports
        Route::get('/custom', [ReportController::class, 'custom'])->name('custom');
        Route::post('/custom', [ReportController::class, 'generateCustom'])->name('custom.generate');

        // Scheduled Reports
        Route::get('/scheduled', [ReportController::class, 'scheduled'])->name('scheduled');
        Route::post('/scheduled', [ReportController::class, 'createScheduled'])->name('scheduled.create');
        Route::delete('/scheduled/{report}', [ReportController::class, 'deleteScheduled'])->name('scheduled.delete');
    });

    // Analytics Dashboard
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [DashboardController::class, 'analytics'])->name('index');
        Route::get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');
        Route::get('/customers', [AnalyticsController::class, 'customers'])->name('customers');
        Route::get('/performance', [AnalyticsController::class, 'performance'])->name('performance');
        Route::get('/forecasting', [AnalyticsController::class, 'forecasting'])->name('forecasting');
        Route::get('/insights', [AnalyticsController::class, 'insights'])->name('insights');

        // Real-time Analytics
        Route::get('/real-time', [AnalyticsController::class, 'realTime'])->name('real-time');
        Route::get('/metrics/{metric}', [AnalyticsController::class, 'getMetric'])->name('metric');
    });

    // Data Export
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('/', function() {
            return view('vendor.export.index');
        })->name('index');
        Route::get('/invoices', function() {
            return view('vendor.export.invoices');
        })->name('invoices');
        Route::post('/invoices', [DashboardController::class, 'export'])->name('invoices.download');
        Route::post('/revenue', [DashboardController::class, 'export'])->name('revenue');
        Route::post('/customers', [DashboardController::class, 'export'])->name('customers');
        Route::post('/reports', [DashboardController::class, 'export'])->name('reports');
    });

    // Customer Management (commented out until CustomerController is created)
    /*
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
        Route::get('/{customer}/invoices', [CustomerController::class, 'invoices'])->name('invoices');
        Route::get('/{customer}/analytics', [CustomerController::class, 'analytics'])->name('analytics');
    });
    */

    // Notifications (commented out until NotificationController is created)
    /*
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
        Route::put('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
    });
    */

    // Settings & Profile
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/', [SettingsController::class, 'update'])->name('update');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/security', [ProfileController::class, 'security'])->name('security');
        Route::put('/security', [ProfileController::class, 'updateSecurity'])->name('security.update');
        Route::get('/preferences', [SettingsController::class, 'preferences'])->name('preferences');
        Route::put('/preferences', [SettingsController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/api', [SettingsController::class, 'api'])->name('api');
        Route::post('/api/generate-key', [SettingsController::class, 'generateApiKey'])->name('api.generate-key');
        Route::delete('/api/revoke-key/{key}', [SettingsController::class, 'revokeApiKey'])->name('api.revoke-key');
    });

    // Webhooks (commented out until WebhookController is created)
    /*
    Route::prefix('webhooks')->name('webhooks.')->group(function () {
        Route::get('/', [WebhookController::class, 'index'])->name('index');
        Route::post('/', [WebhookController::class, 'store'])->name('store');
        Route::get('/{webhook}', [WebhookController::class, 'show'])->name('show');
        Route::put('/{webhook}', [WebhookController::class, 'update'])->name('update');
        Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
        Route::post('/{webhook}/test', [WebhookController::class, 'test'])->name('test');
        Route::get('/{webhook}/logs', [WebhookController::class, 'logs'])->name('logs');
    });
    */

    // API Documentation (commented out until ApiDocController is created)
    /*
    Route::get('/api-docs', [ApiDocController::class, 'index'])->name('api-docs');
    Route::get('/api-docs/endpoints', [ApiDocController::class, 'endpoints'])->name('api-docs.endpoints');
    Route::get('/api-docs/examples', [ApiDocController::class, 'examples'])->name('api-docs.examples');
    */

    // Support & Help (commented out until SupportController is created)
    /*
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SupportController::class, 'index'])->name('index');
        Route::get('/tickets', [SupportController::class, 'tickets'])->name('tickets');
        Route::post('/tickets', [SupportController::class, 'createTicket'])->name('tickets.create');
        Route::get('/tickets/{ticket}', [SupportController::class, 'showTicket'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [SupportController::class, 'replyTicket'])->name('tickets.reply');
        Route::get('/faq', [SupportController::class, 'faq'])->name('faq');
        Route::get('/documentation', [SupportController::class, 'documentation'])->name('documentation');
    });
    */

    // Activity Log (commented out until ActivityController is created)
    /*
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity');
    Route::get('/activity/{activity}', [ActivityController::class, 'show'])->name('activity.show');
    */

    // Quick Actions (commented out until QuickActionController is created)
    /*
    Route::prefix('quick')->name('quick.')->group(function () {
        Route::post('/sync-all', [QuickActionController::class, 'syncAll'])->name('sync-all');
        Route::post('/refresh-dashboard', [QuickActionController::class, 'refreshDashboard'])->name('refresh-dashboard');
        Route::get('/search', [QuickActionController::class, 'search'])->name('search');
        Route::get('/stats', [QuickActionController::class, 'getStats'])->name('stats');
    });
    */
});
