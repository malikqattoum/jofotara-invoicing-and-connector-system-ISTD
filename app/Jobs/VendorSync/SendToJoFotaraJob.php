<?php

namespace App\Jobs\VendorSync;

use App\Models\IntegrationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class SendToJoFotaraJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [30, 60, 120, 300, 600]; // seconds

    protected $integration;
    protected $invoiceData;

    public function __construct(IntegrationSetting $integration, array $invoiceData)
    {
        $this->integration = $integration;
        $this->invoiceData = $invoiceData;
        $this->onQueue(config('vendor-connectors.queue', 'default'));
    }

    public function handle(): void
    {
        try {
            Log::info("Sending invoice to Jo Fotara for integration {$this->integration->id}");

            // Get Jo Fotara API configuration
            $joFotaraConfig = config('jofotara');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $joFotaraConfig['api_token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($joFotaraConfig['api_url'] . '/invoices', $this->invoiceData);

            if ($response->successful()) {
                Log::info("Successfully sent invoice {$this->invoiceData['invoice_number']} to Jo Fotara");

                // Log the successful transaction
                $this->logTransaction('success', $response->json());
            } else {
                throw new Exception("Jo Fotara API error: " . $response->body());
            }
        } catch (Exception $e) {
            Log::error("Failed to send invoice to Jo Fotara for integration {$this->integration->id}: {$e->getMessage()}");

            // Log the failed transaction
            $this->logTransaction('failed', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("SendToJoFotaraJob failed permanently for integration {$this->integration->id}: {$exception->getMessage()}");

        $this->logTransaction('failed_permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    private function logTransaction(string $status, array $data): void
    {
        // You can create a transaction log model to track all API calls
        // TransactionLog::create([
        //     'integration_id' => $this->integration->id,
        //     'invoice_number' => $this->invoiceData['invoice_number'],
        //     'status' => $status,
        //     'request_data' => $this->invoiceData,
        //     'response_data' => $data,
        //     'created_at' => now()
        // ]);
    }
}
