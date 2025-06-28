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

class SyncVendorCustomersJob implements ShouldQueue
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
            Log::info("Starting customer sync job", [
                'integration_id' => $this->integration->id,
                'vendor' => $this->integration->vendor
            ]);

            $customers = $vendorService->syncCustomers($this->integration, $this->filters);

            // Process and store customers
            foreach ($customers as $customerData) {
                $this->processCustomer($customerData);
            }

            Log::info("Completed customer sync job", [
                'integration_id' => $this->integration->id,
                'vendor' => $this->integration->vendor,
                'count' => $customers->count()
            ]);
        } catch (Exception $e) {
            Log::error("Customer sync job failed", [
                'integration_id' => $this->integration->id,
                'vendor' => $this->integration->vendor,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    protected function processCustomer(array $customerData)
    {
        // Here you would typically:
        // 1. Check if customer already exists
        // 2. Create or update customer record
        // 3. Handle any business logic

        // Example implementation:
        /*
        Customer::updateOrCreate(
            [
                'vendor_customer_id' => $customerData['id'],
                'integration_setting_id' => $this->integration->id
            ],
            [
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'address' => $customerData['address'],
                'tax_number' => $customerData['tax_number'],
                'raw_data' => $customerData
            ]
        );
        */
    }

    public function failed(Exception $exception)
    {
        Log::error("Customer sync job failed permanently", [
            'integration_id' => $this->integration->id,
            'vendor' => $this->integration->vendor,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
