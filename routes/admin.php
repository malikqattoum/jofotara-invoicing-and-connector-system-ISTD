<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SystemDashboardController;
use App\Http\Controllers\Admin\WorkflowController;
use App\Http\Controllers\Admin\DataPipelineController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes are for the admin panel and require admin authentication
|
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Main Dashboard
    Route::get('/', [SystemDashboardController::class, 'index'])->name('dashboard');
    Route::get('/real-time-metrics', [SystemDashboardController::class, 'realTimeMetrics'])->name('real-time-metrics');

    // System Monitoring Dashboards
    Route::get('/performance', [SystemDashboardController::class, 'performance'])->name('performance');
    Route::get('/analytics', [SystemDashboardController::class, 'analytics'])->name('analytics');
    Route::get('/security', [SystemDashboardController::class, 'security'])->name('security');
    Route::get('/cache', [SystemDashboardController::class, 'cache'])->name('cache');
    Route::get('/api-gateway', [SystemDashboardController::class, 'apiGateway'])->name('api-gateway');
    Route::get('/event-streaming', [SystemDashboardController::class, 'eventStreaming'])->name('event-streaming');

    // System Settings
    Route::get('/settings', [SystemDashboardController::class, 'settings'])->name('settings');
    Route::put('/settings', [SystemDashboardController::class, 'updateSettings'])->name('settings.update');

    // System Actions
    Route::post('/alerts/{alert}/resolve', [SystemDashboardController::class, 'resolveAlert'])->name('alerts.resolve');
    Route::post('/cache/clear', [SystemDashboardController::class, 'clearCache'])->name('cache.clear');
    Route::post('/cache/warm', [SystemDashboardController::class, 'warmCache'])->name('cache.warm');

    // Workflow Management
    Route::prefix('workflows')->name('workflows.')->group(function () {
        Route::get('/', [WorkflowController::class, 'index'])->name('index');
        Route::get('/create', [WorkflowController::class, 'create'])->name('create');
        Route::post('/', [WorkflowController::class, 'store'])->name('store');
        Route::get('/{workflow}', [WorkflowController::class, 'show'])->name('show');
        Route::put('/{workflow}', [WorkflowController::class, 'update'])->name('update');
        Route::delete('/{workflow}', [WorkflowController::class, 'destroy'])->name('destroy');
        Route::post('/{workflow}/execute', [WorkflowController::class, 'execute'])->name('execute');
        Route::post('/{workflow}/toggle', [WorkflowController::class, 'toggle'])->name('toggle');
        Route::get('/{workflow}/analytics', [WorkflowController::class, 'analytics'])->name('analytics');
        Route::get('/executions/{execution}', [WorkflowController::class, 'execution'])->name('execution');
    });

    // Data Pipeline Management
    Route::prefix('pipelines')->name('pipelines.')->group(function () {
        Route::get('/', [DataPipelineController::class, 'index'])->name('index');
        Route::get('/create', [DataPipelineController::class, 'create'])->name('create');
        Route::post('/', [DataPipelineController::class, 'store'])->name('store');
        Route::get('/{pipeline}', [DataPipelineController::class, 'show'])->name('show');
        Route::put('/{pipeline}', [DataPipelineController::class, 'update'])->name('update');
        Route::delete('/{pipeline}', [DataPipelineController::class, 'destroy'])->name('destroy');
        Route::post('/{pipeline}/execute', [DataPipelineController::class, 'execute'])->name('execute');
        Route::post('/{pipeline}/toggle', [DataPipelineController::class, 'toggle'])->name('toggle');
        Route::get('/{pipeline}/analytics', [DataPipelineController::class, 'analytics'])->name('analytics');
    });

    // Data Sources Management
    Route::resource('data-sources', 'DataSourceController');
    Route::post('/data-sources/{dataSource}/test', 'DataSourceController@testConnection')->name('data-sources.test');

    // Event Streams Management
    Route::prefix('event-streams')->name('event-streams.')->group(function () {
        Route::get('/', 'EventStreamController@index')->name('index');
        Route::post('/', 'EventStreamController@store')->name('store');
        Route::get('/{stream}', 'EventStreamController@show')->name('show');
        Route::delete('/{stream}', 'EventStreamController@destroy')->name('destroy');
        Route::get('/{stream}/analytics', 'EventStreamController@analytics')->name('analytics');
        Route::post('/{stream}/publish', 'EventStreamController@publishEvent')->name('publish');
    });

    // Integration Management
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', [IntegrationController::class, 'index'])->name('index');
        Route::get('/create', [IntegrationController::class, 'create'])->name('create');
        Route::post('/', [IntegrationController::class, 'store'])->name('store');
        Route::get('/{integration}', [IntegrationController::class, 'show'])->name('show');
        Route::put('/{integration}', [IntegrationController::class, 'update'])->name('update');
        Route::delete('/{integration}', [IntegrationController::class, 'destroy'])->name('destroy');
        Route::post('/{integration}/sync', [IntegrationController::class, 'sync'])->name('sync');
        Route::post('/{integration}/toggle', [IntegrationController::class, 'toggle'])->name('toggle');
        Route::get('/{integration}/logs', [IntegrationController::class, 'logs'])->name('logs');
        Route::get('/{integration}/test', [IntegrationController::class, 'test'])->name('test');
    });

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/toggle', [UserController::class, 'toggle'])->name('toggle');
        Route::get('/{user}/login-as', [UserController::class, 'loginAs'])->name('login-as');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', 'ReportController@index')->name('index');
        Route::get('/system', 'ReportController@systemReport')->name('system');
        Route::get('/performance', 'ReportController@performanceReport')->name('performance');
        Route::get('/security', 'ReportController@securityReport')->name('security');
        Route::get('/integrations', 'ReportController@integrationsReport')->name('integrations');
        Route::post('/export', 'ReportController@export')->name('export');
    });

    // API Management
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/keys', 'ApiKeyController@index')->name('keys.index');
        Route::post('/keys', 'ApiKeyController@store')->name('keys.store');
        Route::delete('/keys/{key}', 'ApiKeyController@destroy')->name('keys.destroy');
        Route::get('/documentation', 'ApiController@documentation')->name('documentation');
        Route::get('/analytics', 'ApiController@analytics')->name('analytics');
        Route::get('/rate-limits', 'ApiController@rateLimits')->name('rate-limits');
        Route::put('/rate-limits', 'ApiController@updateRateLimits')->name('rate-limits.update');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', 'NotificationController@index')->name('index');
        Route::get('/channels', 'NotificationController@channels')->name('channels');
        Route::put('/channels', 'NotificationController@updateChannels')->name('channels.update');
        Route::post('/test', 'NotificationController@test')->name('test');
        Route::get('/templates', 'NotificationController@templates')->name('templates');
        Route::put('/templates', 'NotificationController@updateTemplates')->name('templates.update');
    });

    // System Maintenance
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/', 'MaintenanceController@index')->name('index');
        Route::post('/backup', 'MaintenanceController@backup')->name('backup');
        Route::post('/optimize', 'MaintenanceController@optimize')->name('optimize');
        Route::post('/cleanup', 'MaintenanceController@cleanup')->name('cleanup');
        Route::get('/logs', 'MaintenanceController@logs')->name('logs');
        Route::post('/logs/clear', 'MaintenanceController@clearLogs')->name('logs.clear');
    });

    // Configuration
    Route::prefix('config')->name('config.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/system', [SettingsController::class, 'system'])->name('system');
        Route::get('/integrations', [SettingsController::class, 'integrations'])->name('integrations');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::put('/{category}', [SettingsController::class, 'update'])->name('update');
        Route::post('/import', [SettingsController::class, 'import'])->name('import');
        Route::get('/export', [SettingsController::class, 'export'])->name('export');
    });

    // Activity Logs
    Route::get('/activity', 'ActivityController@index')->name('activity.index');
    Route::get('/activity/{activity}', 'ActivityController@show')->name('activity.show');

    // System Information
    Route::get('/system-info', 'SystemInfoController@index')->name('system-info');
    Route::get('/phpinfo', 'SystemInfoController@phpInfo')->name('phpinfo');
    Route::get('/server-status', 'SystemInfoController@serverStatus')->name('server-status');
});
