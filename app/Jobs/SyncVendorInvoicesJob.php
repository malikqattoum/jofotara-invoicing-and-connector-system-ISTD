<?php

namespace App\Jobs;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\VendorIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncVendorInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $integration;
    protected $filters;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function __construct(IntegrationSetting $integration, array $filters = [])
    {
        $this->integration = $integration;
        $this->filters = $filters;
    }

    public function handle(VendorIntegrationService $vendorService)
    {
        try {
            Log::info("Starting invoice sync job", [
                'integration_id' => $this->integration->id,
                'vendor' => $this->integration->vendor
            ]);

            $invoices = $vendorService->syncInvoices($this->integration, $this->filters);

            // Process and store invoices
            foreach ($invoices as $invoiceData) {
                $this->processInvoice($invoiceData);
            }

            // Update last sync timestamp
            $this->integration->update([
                'last_sync_at' => now()
            ]);

            Log::info("Completed invoice sync job", [
                'integration_id' => $this->integration->id,
                'vendor' => $this->integration->vendor,
                'count' => $invoices->count()
            ]);
        } catch (Exception $e) {
            Log::error("Invoice sync job failed", [
                'integration_id' => $this->integration->id,
                'vendor' => $this->integration->vendor,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    protected function processInvoice(array $invoiceData)
    {
        // Here you would typically:
        // 1. Check if invoice already exists
        // 2. Create or update invoice record
        // 3. Process line items
        // 4. Handle any business logic

        // Example implementation:
        /*
        Invoice::updateOrCreate(
            [
                'vendor_invoice_id' => $invoiceData['id'],
                'integration_setting_id' => $this->integration->id
            ],
            [
                'invoice_number' => $invoiceData['invoice_number'],
                'invoice_date' => $invoiceData['invoice_date'],
                'due_date' => $invoiceData['due_date'],
                'total_amount' => $invoiceData['total_amount'],
                'subtotal' => $invoiceData['subtotal'],
                'tax_amount' => $invoiceData['tax_amount'],
                'currency' => $invoiceData['currency'],
                'status' => $invoiceData['status'],
                'customer_data' => $invoiceData['customer'],
                'line_items' => $invoiceData['line_items'],
                'raw_data' => $invoiceData
            ]
        );
        */
    }

    public function failed(Exception $exception)
    {
        Log::error("Invoice sync job failed permanently", [
            'integration_id' => $this->integration->id,
            'vendor' => $this->integration->vendor,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // You might want to notify administrators or update integration status
    }
}
