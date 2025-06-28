<?php

namespace App\Console\Commands;

use App\Models\IntegrationSetting;
use App\Jobs\SyncVendorInvoicesJob;
use App\Jobs\SyncVendorCustomersJob;
use Illuminate\Console\Command;

class SyncVendorData extends Command
{
    protected $signature = 'vendor:sync {integration_id} {--type=all : Type of data to sync (invoices, customers, all)} {--async : Run sync jobs asynchronously}';
    protected $description = 'Sync data from vendor integration';

    public function handle()
    {
        $integrationId = $this->argument('integration_id');
        $type = $this->option('type');
        $async = $this->option('async');

        $integration = IntegrationSetting::find($integrationId);

        if (!$integration) {
            $this->error("Integration with ID {$integrationId} not found.");
            return 1;
        }

        $this->info("Starting sync for {$integration->vendor} integration...");

        try {
            if (in_array($type, ['invoices', 'all'])) {
                $this->info("Syncing invoices...");

                if ($async) {
                    SyncVendorInvoicesJob::dispatch($integration);
                    $this->info("Invoice sync job queued.");
                } else {
                    SyncVendorInvoicesJob::dispatchSync($integration);
                    $this->info("Invoice sync completed.");
                }
            }

            if (in_array($type, ['customers', 'all'])) {
                $this->info("Syncing customers...");

                if ($async) {
                    SyncVendorCustomersJob::dispatch($integration);
                    $this->info("Customer sync job queued.");
                } else {
                    SyncVendorCustomersJob::dispatchSync($integration);
                    $this->info("Customer sync completed.");
                }
            }

            $this->info("✅ Sync process initiated successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Sync failed: " . $e->getMessage());
            return 1;
        }
    }
}
