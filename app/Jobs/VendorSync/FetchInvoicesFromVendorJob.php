<?php

namespace App\Jobs\VendorSync;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\VendorConnectorFactory;
use App\Jobs\VendorSync\TransformInvoiceDataJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class FetchInvoicesFromVendorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120]; // seconds

    protected $integration;
    protected $filters;

    public function __construct(IntegrationSetting $integration, array $filters = [])
    {
        $this->integration = $integration;
        $this->filters = $filters;
        $this->onQueue(config('vendor-connectors.queue', 'default'));
    }

    public function handle(): void
    {
        try {
            Log::info("Starting invoice fetch for integration {$this->integration->id}");

            $connector = VendorConnectorFactory::create($this->integration->vendor_type);

            // Fetch invoices from vendor
            $invoices = $connector->fetchInvoices($this->integration, $this->filters);

            Log::info("Fetched {$invoices->count()} invoices from {$this->integration->vendor_type}");

            // Queue transformation jobs for each invoice
            foreach ($invoices as $invoice) {
                TransformInvoiceDataJob::dispatch($this->integration, $invoice);
            }

            // Update last sync timestamp
            $this->integration->update([
                'last_sync_at' => now(),
                'sync_status' => 'completed'
            ]);
        } catch (Exception $e) {
            Log::error("Failed to fetch invoices for integration {$this->integration->id}: {$e->getMessage()}");

            $this->integration->update([
                'sync_status' => 'failed',
                'last_error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("FetchInvoicesFromVendorJob failed permanently for integration {$this->integration->id}: {$exception->getMessage()}");

        $this->integration->update([
            'sync_status' => 'failed',
            'last_error' => $exception->getMessage()
        ]);
    }
}
