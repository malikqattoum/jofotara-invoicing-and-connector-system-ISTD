<?php

use App\Http\Controllers\Api\VendorIntegrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendor-integrations')->group(function () {
    // General vendor endpoints
    Route::get('/vendors', [VendorIntegrationController::class, 'getSupportedVendors']);
    Route::get('/vendors/{vendor}/config', [VendorIntegrationController::class, 'getVendorConfig']);
    Route::post('/validate-config', [VendorIntegrationController::class, 'validateConfig']);

    // Integration-specific endpoints
    Route::prefix('/{integration}')->group(function () {
        Route::post('/test-connection', [VendorIntegrationController::class, 'testConnection']);
        Route::post('/sync/invoices', [VendorIntegrationController::class, 'syncInvoices']);
        Route::post('/sync/customers', [VendorIntegrationController::class, 'syncCustomers']);
        Route::get('/invoices/{invoiceId}', [VendorIntegrationController::class, 'getInvoice']);
        Route::post('/webhook', [VendorIntegrationController::class, 'handleWebhook']);
        Route::post('/refresh-token', [VendorIntegrationController::class, 'refreshToken']);
        Route::get('/stats', [VendorIntegrationController::class, 'getStats']);
    });
});
