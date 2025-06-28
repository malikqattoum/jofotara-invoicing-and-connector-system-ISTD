<?php

namespace App\Services\DataMapping;

use App\Models\IntegrationSetting;
use Illuminate\Support\Arr;
use Exception;

class DataMappingService
{
    /**
     * Transform vendor invoice data to Jo Fotara format
     */
    public function transformInvoiceToJoFotara(array $vendorData, IntegrationSetting $integration): array
    {
        $mappingConfig = $integration->field_mappings ?? [];

        if (empty($mappingConfig['invoice'])) {
            throw new Exception('Invoice field mapping configuration is missing');
        }

        $invoiceMapping = $mappingConfig['invoice'];
        $transformed = [];

        // Map basic invoice fields
        foreach ($invoiceMapping as $joFotaraField => $vendorField) {
            $value = $this->extractValue($vendorData, $vendorField);
            if ($value !== null) {
                $transformed[$joFotaraField] = $this->transformValue($value, $joFotaraField);
            }
        }

        // Map line items
        if (isset($mappingConfig['line_items']) && isset($vendorData['line_items'])) {
            $transformed['line_items'] = $this->transformLineItems(
                $vendorData['line_items'],
                $mappingConfig['line_items']
            );
        }

        // Map customer data
        if (isset($mappingConfig['customer']) && isset($vendorData['customer'])) {
            $transformed['customer'] = $this->transformCustomer(
                $vendorData['customer'],
                $mappingConfig['customer']
            );
        }

        // Apply Jo Fotara specific formatting
        return $this->applyJoFotaraFormatting($transformed);
    }

    /**
     * Transform vendor customer data to Jo Fotara format
     */
    public function transformCustomerToJoFotara(array $vendorData, IntegrationSetting $integration): array
    {
        $mappingConfig = $integration->field_mappings ?? [];

        if (empty($mappingConfig['customer'])) {
            throw new Exception('Customer field mapping configuration is missing');
        }

        $customerMapping = $mappingConfig['customer'];
        $transformed = [];

        foreach ($customerMapping as $joFotaraField => $vendorField) {
            $value = $this->extractValue($vendorData, $vendorField);
            if ($value !== null) {
                $transformed[$joFotaraField] = $this->transformValue($value, $joFotaraField);
            }
        }

        return $this->applyJoFotaraCustomerFormatting($transformed);
    }

    /**
     * Extract value from nested array using dot notation
     */
    private function extractValue(array $data, string $path)
    {
        return Arr::get($data, $path);
    }

    /**
     * Transform value based on field type
     */
    private function transformValue($value, string $fieldName)
    {
        // Date transformations
        if (in_array($fieldName, ['invoice_date', 'due_date', 'created_at', 'updated_at'])) {
            return $this->transformDate($value);
        }

        // Amount transformations
        if (in_array($fieldName, ['total_amount', 'tax_amount', 'subtotal', 'unit_price', 'line_total'])) {
            return $this->transformAmount($value);
        }

        // Tax rate transformations
        if (in_array($fieldName, ['tax_rate'])) {
            return $this->transformTaxRate($value);
        }

        // String transformations
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Transform date to Jo Fotara format
     */
    private function transformDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            if (is_string($date)) {
                $dateTime = new \DateTime($date);
            } elseif ($date instanceof \DateTime) {
                $dateTime = $date;
            } else {
                return null;
            }

            return $dateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Transform amount to proper format
     */
    private function transformAmount($amount): float
    {
        if (is_numeric($amount)) {
            return round((float) $amount, 2);
        }

        // Handle string amounts with currency symbols
        $cleanAmount = preg_replace('/[^\d.-]/', '', $amount);
        return round((float) $cleanAmount, 2);
    }

    /**
     * Transform tax rate to percentage
     */
    private function transformTaxRate($rate): float
    {
        $numericRate = (float) $rate;

        // If rate is already in percentage (0-100), return as is
        if ($numericRate <= 1) {
            return $numericRate * 100; // Convert decimal to percentage
        }

        return $numericRate;
    }

    /**
     * Transform line items array
     */
    private function transformLineItems(array $lineItems, array $mapping): array
    {
        $transformed = [];

        foreach ($lineItems as $item) {
            $transformedItem = [];

            foreach ($mapping as $joFotaraField => $vendorField) {
                $value = $this->extractValue($item, $vendorField);
                if ($value !== null) {
                    $transformedItem[$joFotaraField] = $this->transformValue($value, $joFotaraField);
                }
            }

            $transformed[] = $transformedItem;
        }

        return $transformed;
    }

    /**
     * Transform customer data
     */
    private function transformCustomer(array $customerData, array $mapping): array
    {
        $transformed = [];

        foreach ($mapping as $joFotaraField => $vendorField) {
            $value = $this->extractValue($customerData, $vendorField);
            if ($value !== null) {
                $transformed[$joFotaraField] = $this->transformValue($value, $joFotaraField);
            }
        }

        return $transformed;
    }

    /**
     * Apply Jo Fotara specific formatting rules
     */
    private function applyJoFotaraFormatting(array $data): array
    {
        // Ensure required fields are present
        $required = [
            'invoice_number' => $data['invoice_number'] ?? 'INV-' . time(),
            'invoice_date' => $data['invoice_date'] ?? date('Y-m-d H:i:s'),
            'total_amount' => $data['total_amount'] ?? 0,
            'currency' => $data['currency'] ?? 'JOD',
            'status' => $data['status'] ?? 'draft'
        ];

        $data = array_merge($data, $required);

        // Calculate totals if not provided
        if (isset($data['line_items']) && !isset($data['subtotal'])) {
            $data['subtotal'] = array_sum(array_column($data['line_items'], 'line_total'));
        }

        if (!isset($data['tax_amount']) && isset($data['subtotal']) && isset($data['tax_rate'])) {
            $data['tax_amount'] = $data['subtotal'] * ($data['tax_rate'] / 100);
        }

        if (!isset($data['total_amount']) && isset($data['subtotal']) && isset($data['tax_amount'])) {
            $data['total_amount'] = $data['subtotal'] + $data['tax_amount'];
        }

        return $data;
    }

    /**
     * Apply Jo Fotara customer formatting rules
     */
    private function applyJoFotaraCustomerFormatting(array $data): array
    {
        // Ensure required fields
        $required = [
            'name' => $data['name'] ?? 'Unknown Customer',
            'type' => $data['type'] ?? 'individual'
        ];

        return array_merge($data, $required);
    }

    /**
     * Get default field mappings for a vendor
     */
    public function getDefaultMappings(string $vendorName): array
    {
        $mappings = [
            'xero' => [
                'invoice' => [
                    'invoice_number' => 'InvoiceNumber',
                    'invoice_date' => 'Date',
                    'due_date' => 'DueDate',
                    'total_amount' => 'Total',
                    'subtotal' => 'SubTotal',
                    'tax_amount' => 'TotalTax',
                    'currency' => 'CurrencyCode',
                    'status' => 'Status',
                    'reference' => 'Reference'
                ],
                'customer' => [
                    'name' => 'Contact.Name',
                    'email' => 'Contact.EmailAddress',
                    'phone' => 'Contact.Phones.0.PhoneNumber',
                    'address' => 'Contact.Addresses.0.AddressLine1',
                    'tax_number' => 'Contact.TaxNumber'
                ],
                'line_items' => [
                    'description' => 'Description',
                    'quantity' => 'Quantity',
                    'unit_price' => 'UnitAmount',
                    'line_total' => 'LineAmount',
                    'tax_rate' => 'TaxAmount'
                ]
            ],
            'quickbooks' => [
                'invoice' => [
                    'invoice_number' => 'DocNumber',
                    'invoice_date' => 'TxnDate',
                    'due_date' => 'DueDate',
                    'total_amount' => 'TotalAmt',
                    'subtotal' => 'TxnTaxDetail.TotalTax',
                    'currency' => 'CurrencyRef.value',
                    'status' => 'EmailStatus',
                    'reference' => 'PrivateNote'
                ],
                'customer' => [
                    'name' => 'CustomerRef.name',
                    'email' => 'BillEmail.Address',
                    'phone' => 'PrimaryPhone.FreeFormNumber',
                    'address' => 'BillAddr.Line1'
                ],
                'line_items' => [
                    'description' => 'Description',
                    'quantity' => 'Qty',
                    'unit_price' => 'UnitPrice',
                    'line_total' => 'Amount'
                ]
            ]
        ];

        return $mappings[strtolower($vendorName)] ?? [];
    }
}
