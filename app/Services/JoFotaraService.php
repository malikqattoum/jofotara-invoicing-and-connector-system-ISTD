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
        $this->joFotaraSDK = new JoFotaraSDK(
            $integration->client_id,
            $integration->secret_key
        );
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
                ->setName($invoice->organization_name)
                ->setTin($invoice->tax_number);
                
            // Set income source sequence if available
            if ($this->integration->income_source_sequence) {
                $this->joFotaraSDK->sellerInformation()
                    ->setIncomeSourceSequence($this->integration->income_source_sequence);
            }

            // Set customer information
            $this->joFotaraSDK->customerInformation()
                ->setName($invoice->customer_name);

            // Add customer ID if available
            if ($invoice->customer_id && $invoice->customer_id_type) {
                $this->joFotaraSDK->customerInformation()
                    ->setId($invoice->customer_id, $invoice->customer_id_type);
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