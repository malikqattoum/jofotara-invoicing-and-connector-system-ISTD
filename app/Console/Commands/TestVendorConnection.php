<?php

namespace App\Console\Commands;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\VendorIntegrationService;
use Illuminate\Console\Command;

class TestVendorConnection extends Command
{
    protected $signature = 'vendor:test-connection {integration_id}';
    protected $description = 'Test connection to a vendor integration';

    public function handle(VendorIntegrationService $vendorService)
    {
        $integrationId = $this->argument('integration_id');

        $integration = IntegrationSetting::find($integrationId);

        if (!$integration) {
            $this->error("Integration with ID {$integrationId} not found.");
            return 1;
        }

        $this->info("Testing connection to {$integration->vendor}...");

        try {
            $success = $vendorService->testVendorConnection($integration);

            if ($success) {
                $this->info("✅ Connection successful!");
                return 0;
            } else {
                $this->error("❌ Connection failed!");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Connection failed: " . $e->getMessage());
            return 1;
        }
    }
}
