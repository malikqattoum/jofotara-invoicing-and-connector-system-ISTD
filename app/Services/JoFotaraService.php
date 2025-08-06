<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\IntegrationSetting;
use JBadarneh\JoFotara\JoFotaraService as JoFotaraSDK;
use Illuminate\Support\Facades\Log;

class JoFotaraService
{
    protected $joFotaraSDK;
    protected $integration;

    /**
     * Create a new JoFotara service instance.
     *
     * @param IntegrationSetting $integration
     * @return void
     */
    public function __construct(IntegrationSetting $integration)
    {
        $this->integration = $integration;

        // Parse configuration JSON to get credentials
        $config = json_decode($integration->configuration, true) ?? [];
        $clientId = $config['client_id'] ?? $config['api_key'] ?? 'test_client_id';
        $secretKey = $config['secret_key'] ?? $config['api_key'] ?? 'test_secret_key';

        $this->joFotaraSDK = new JoFotaraSDK($clientId, $secretKey);
    }

    /**
     * Submit an invoice to the JoFotara tax system.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function submitInvoice(Invoice $invoice)
    {
        try {
            // Set basic invoice information
            $this->joFotaraSDK->basicInformation()
                ->setInvoiceId($invoice->invoice_number)
                ->setUuid($invoice->uuid)
                ->setIssueDate(date('d-m-Y', strtotime($invoice->invoice_date)))
                ->setInvoiceType('general_sales')
                ->cash(); // Assuming cash payment method, can be modified based on invoice data

            // Set seller information
            $this->joFotaraSDK->sellerInformation()
                ->setName('JoFotara Invoicing System') // Default organization name
                ->setTin('1234567890'); // Default tax number

            // Set income source sequence if available
            if ($this->integration->income_source_sequence) {
                $this->joFotaraSDK->sellerInformation()
                    ->setIncomeSourceSequence($this->integration->income_source_sequence);
            }

            // Set customer information
            $this->joFotaraSDK->customerInformation()
                ->setName($invoice->customer_name);

            // Add customer tax number if available
            if ($invoice->customer_tax_number) {
                $this->joFotaraSDK->customerInformation()
                    ->setId($invoice->customer_tax_number, 'tax_number');
            }

            // Add invoice items
            foreach ($invoice->items as $item) {
                $this->joFotaraSDK->items()
                    ->addItem($item->id)
                    ->setQuantity($item->quantity)
                    ->setUnitPrice($item->unit_price)
                    ->setDescription($item->item_name)
                    ->tax($item->tax);
            }

            // Send the invoice to JoFotara
            $response = $this->joFotaraSDK->send();

            if ($response->isSuccessful()) {
                $data = $response->getData();
                return [
                    'success' => true,
                    'status' => 'submitted',
                    'response' => json_encode($data)
                ];
            } else {
                $errors = $response->getErrors();
                return [
                    'success' => false,
                    'status' => 'rejected',
                    'response' => json_encode($errors)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error submitting invoice to JoFotara: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'response' => $e->getMessage()
            ];
        }
    }
}
