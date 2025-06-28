<?php

namespace App\Jobs\VendorSync;

use App\Models\IntegrationSetting;
use App\Services\DataMapping\DataMappingService;
use App\Jobs\VendorSync\SendToJoFotaraJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class TransformInvoiceDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // seconds

    protected $integration;
    protected $invoiceData;

    public function __construct(IntegrationSetting $integration, array $invoiceData)
    {
        $this->integration = $integration;
        $this->invoiceData = $invoiceData;
        $this->onQueue(config('vendor-connectors.queue', 'default'));
    }

    public function handle(DataMappingService $mappingService): void
    {
        try {
            Log::info("Transforming invoice data for integration {$this->integration->id}");

            // Transform vendor invoice data to Jo Fotara format
            $transformedData = $mappingService->transformInvoiceToJoFotara(
                $this->invoiceData,
                $this->integration
            );

            Log::info("Successfully transformed invoice {$transformedData['invoice_number']}");

            // Queue job to send to Jo Fotara
            SendToJoFotaraJob::dispatch($this->integration, $transformedData);
        } catch (Exception $e) {
            Log::error("Failed to transform invoice data for integration {$this->integration->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("TransformInvoiceDataJob failed permanently for integration {$this->integration->id}: {$exception->getMessage()}");
    }
}
