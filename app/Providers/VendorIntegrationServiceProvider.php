<?php

namespace App\Providers;

use App\Services\VendorConnectors\VendorIntegrationService;
use App\Services\VendorConnectors\VendorConnectorFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class VendorIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the main service
        $this->app->singleton(VendorIntegrationService::class, function ($app) {
            return new VendorIntegrationService();
        });

        // Register the factory
        $this->app->singleton(VendorConnectorFactory::class, function ($app) {
            return new VendorConnectorFactory();
        });

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/vendor-integrations.php',
            'vendor-integrations'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/vendor-integrations.php' => config_path('vendor-integrations.php'),
            ], 'vendor-integrations-config');
        }

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api/vendor-integrations.php');

        // Configure logging channel
        if (config('vendor-integrations.logging.enabled')) {
            $this->configureLogging();
        }
    }

    /**
     * Configure logging channel for vendor integrations
     */
    protected function configureLogging(): void
    {
        $config = config('logging.channels');

        $config['vendor-integrations'] = [
            'driver' => 'daily',
            'path' => storage_path('logs/vendor-integrations.log'),
            'level' => config('vendor-integrations.logging.level', 'info'),
            'days' => 14,
        ];

        config(['logging.channels' => $config]);
    }
}
