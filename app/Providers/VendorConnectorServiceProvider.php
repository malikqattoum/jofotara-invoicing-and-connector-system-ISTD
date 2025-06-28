<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\VendorConnectors\VendorConnectorFactory;
use App\Services\DataMapping\DataMappingService;

class VendorConnectorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DataMappingService::class, function ($app) {
            return new DataMappingService();
        });

        $this->app->bind('vendor.connector', function ($app, $parameters) {
            $vendorName = $parameters['vendor'] ?? null;

            if (!$vendorName) {
                throw new \Exception('Vendor name must be provided');
            }

            return VendorConnectorFactory::create($vendorName);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register config files
        $this->publishes([
            __DIR__ . '/../../config/vendor-connectors.php' => config_path('vendor-connectors.php'),
        ], 'config');

        // Register migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
