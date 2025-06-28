<?php

namespace App\Services\VendorConnectors\Vendors;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\AbstractVendorConnector;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class SAPConnector extends AbstractVendorConnector
{
    protected $client;
    protected $baseUrl;
    protected $sessionId;

    public function getVendorName(): string
    {
        return 'SAP Business One';
    }

    public function getRequiredConfigFields(): array
    {
        return [
            [
                'key' => 'server_url',
                'label' => 'SAP Server URL',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'database_name',
                'label' => 'Database Name',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'username',
                'label' => 'Username',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'password',
                'label' => 'Password',
                'type' => 'password',
                'required' => true
            ],
            [
                'key' => 'company_db',
                'label' => 'Company Database',
                'type' => 'string',
                'required' => true
            ]
        ];
    }

    public function authenticate(IntegrationSetting $integration): bool
    {
        try {
            $this->validateConfiguration($integration);

            $this->baseUrl = rtrim($integration->configuration['server_url'], '/') . '/b1s/v1';
            $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => 30,
                'verify' => false, // For development - should be true in production
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $loginData = [
                'CompanyDB' => $integration->configuration['company_db'],
                'UserName' => $integration->configuration['username'],
                'Password' => $integration->configuration['password']
            ];

            $response = $this->client->post('/Login', [
                'json' => $loginData
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Authentication failed');
            }

            $responseData = json_decode($response->getBody(), true);
            $this->sessionId = $responseData['SessionId'] ?? null;

            if (!$this->sessionId) {
                throw new Exception('No session ID received');
            }

            // Update client with session cookie
            $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => 30,
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Cookie' => "B1SESSION={$this->sessionId}"
                ]
            ]);

            return true;
        } catch (RequestException $e) {
            $this->handleApiException($e, 'authentication');
            return false;
        } catch (Exception $e) {
            $this->handleApiException($e, 'authentication');
            return false;
        }
    }

    public function testConnection(IntegrationSetting $integration): bool
    {
        if (!$this->authenticate($integration)) {
            return false;
        }

        try {
            // Test by fetching company info
            $response = $this->client->get('/CompanyService_GetCompanyInfo');
            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            $this->handleApiException($e, 'testConnection');
            return false;
        }
    }

    public function fetchInvoices(IntegrationSetting $integration, array $filters = []): Collection
    {
        try {
            $this->authenticate($integration);
            $this->handleRateLimit();

            $skip = ($filters['page'] ?? 1 - 1) * 20;
            $top = 20;

            $queryParams = [
                '$skip' => $skip,
                '$top' => $top
            ];

            if (isset($filters['date_from'])) {
                $queryParams['$filter'] = "DocDate ge '{$filters['date_from']}'";
            }

            $response = $this->client->get('/Invoices', [
                'query' => $queryParams
            ]);

            $data = json_decode($response->getBody(), true);
            $invoices = $data['value'] ?? [];

            return collect($invoices)->map(function ($invoice) {
                return $this->normalizeInvoiceData($invoice);
            });
        } catch (RequestException $e) {
            $this->handleApiException($e, 'fetchInvoices');
            return collect();
        }
    }

    public function fetchCustomers(IntegrationSetting $integration, array $filters = []): Collection
    {
        try {
            $this->authenticate($integration);
            $this->handleRateLimit();

            $skip = ($filters['page'] ?? 1 - 1) * 20;
            $top = 20;

            $response = $this->client->get('/BusinessPartners', [
                'query' => [
                    '$skip' => $skip,
                    '$top' => $top,
                    '$filter' => "CardType eq 'cCustomer'"
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $customers = $data['value'] ?? [];

            return collect($customers)->map(function ($customer) {
                return $this->normalizeCustomerData($customer);
            });
        } catch (RequestException $e) {
            $this->handleApiException($e, 'fetchCustomers');
            return collect();
        }
    }

    public function fetchInvoiceById(IntegrationSetting $integration, string $invoiceId): ?array
    {
        try {
            $this->authenticate($integration);
            $this->handleRateLimit();

            $response = $this->client->get("/Invoices({$invoiceId})");

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $invoice = json_decode($response->getBody(), true);
            return $this->normalizeInvoiceData($invoice);
        } catch (RequestException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            $this->handleApiException($e, 'fetchInvoiceById');
            return null;
        }
    }

    public function refreshToken(IntegrationSetting $integration): bool
    {
        // SAP Business One uses session-based authentication
        // Re-authenticate to get a new session
        return $this->authenticate($integration);
    }

    public function handleWebhook(IntegrationSetting $integration, array $payload): bool
    {
        // SAP Business One doesn't have native webhooks
        // This would need to be implemented through SAP Event Notifications
        // or custom triggers in the SAP system
        return true;
    }

    protected function normalizeInvoiceData(array $invoice): array
    {
        return [
            'invoice_number' => $invoice['DocNum'] ?? '',
            'invoice_date' => isset($invoice['DocDate']) ?
                date('Y-m-d', strtotime($invoice['DocDate'])) : null,
            'due_date' => isset($invoice['DocDueDate']) ?
                date('Y-m-d', strtotime($invoice['DocDueDate'])) : null,
            'total_amount' => $invoice['DocTotal'] ?? 0,
            'subtotal' => $invoice['VatSum'] ?
                ($invoice['DocTotal'] - $invoice['VatSum']) : $invoice['DocTotal'],
            'tax_amount' => $invoice['VatSum'] ?? 0,
            'currency' => $invoice['DocCurrency'] ?? 'USD',
            'status' => $this->mapInvoiceStatus($invoice['DocumentStatus'] ?? ''),
            'customer' => [
                'name' => $invoice['CardName'] ?? '',
                'email' => $invoice['EmailAddress'] ?? null
            ],
            'line_items' => $this->extractLineItems($invoice['DocumentLines'] ?? [])
        ];
    }

    protected function normalizeCustomerData(array $customer): array
    {
        return [
            'name' => $customer['CardName'] ?? '',
            'email' => $customer['EmailAddress'] ?? null,
            'phone' => $customer['Phone1'] ?? null,
            'address' => $this->formatAddress($customer),
            'tax_number' => $customer['FederalTaxID'] ?? null
        ];
    }

    private function extractLineItems(array $lineItems): array
    {
        $items = [];

        foreach ($lineItems as $lineItem) {
            $items[] = [
                'description' => $lineItem['ItemDescription'] ?? '',
                'quantity' => $lineItem['Quantity'] ?? 1,
                'unit_price' => $lineItem['UnitPrice'] ?? 0,
                'line_total' => $lineItem['LineTotal'] ?? 0
            ];
        }

        return $items;
    }

    private function formatAddress(array $customer): ?string
    {
        $addressParts = array_filter([
            $customer['BillToStreet'] ?? null,
            $customer['BillToCity'] ?? null,
            $customer['BillToState'] ?? null,
            $customer['BillToZipCode'] ?? null
        ]);

        return !empty($addressParts) ? implode(', ', $addressParts) : null;
    }

    private function mapInvoiceStatus(string $sapStatus): string
    {
        $statusMap = [
            'bost_Open' => 'sent',
            'bost_Close' => 'paid',
            'bost_Paid' => 'paid',
            'bost_Delivered' => 'sent'
        ];

        return $statusMap[$sapStatus] ?? 'unknown';
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'invoice.create',
            'invoice.update',
            'invoice.delete',
            'business_partner.create',
            'business_partner.update',
            'item.create',
            'item.update'
        ];
    }

    public function getRateLimit(): array
    {
        return [
            'requests_per_minute' => 30,
            'requests_per_day' => 2000,
            'concurrent_requests' => 3
        ];
    }

    public function bidirectionalSync(IntegrationSetting $integration): void
    {
        $this->setCurrentIntegration($integration);
        $this->logSyncActivity('bidirectional_sync_started');

        try {
            // Sync invoices from SAP to local system
            $this->syncInvoicesFromVendor($integration);

            // Sync customers from SAP to local system
            $this->syncCustomersFromVendor($integration);

            // Sync local invoices to SAP (if needed)
            $this->syncInvoicesToVendor($integration);

            $this->logSyncActivity('bidirectional_sync_completed');
        } catch (Exception $e) {
            $this->logSyncActivity('bidirectional_sync_failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function syncInvoicesFromVendor(IntegrationSetting $integration): void
    {
        $skip = 0;
        $top = 20;
        $hasMore = true;

        while ($hasMore) {
            $invoices = $this->fetchInvoices($integration, ['page' => ($skip / $top) + 1]);

            if ($invoices->isEmpty()) {
                $hasMore = false;
                break;
            }

            foreach ($invoices as $invoiceData) {
                // Here you would typically save to your local database
                // Example: Invoice::updateOrCreate(['external_id' => $invoiceData['id']], $invoiceData);
                $this->logSyncActivity('invoice_synced', ['invoice_number' => $invoiceData['invoice_number']]);
            }

            $skip += $top;

            // Safety check to prevent infinite loops
            if ($skip > 2000) {
                $hasMore = false;
            }
        }
    }

    protected function syncCustomersFromVendor(IntegrationSetting $integration): void
    {
        $skip = 0;
        $top = 20;
        $hasMore = true;

        while ($hasMore) {
            $customers = $this->fetchCustomers($integration, ['page' => ($skip / $top) + 1]);

            if ($customers->isEmpty()) {
                $hasMore = false;
                break;
            }

            foreach ($customers as $customerData) {
                // Here you would typically save to your local database
                // Example: Customer::updateOrCreate(['external_id' => $customerData['id']], $customerData);
                $this->logSyncActivity('customer_synced', ['customer_name' => $customerData['name']]);
            }

            $skip += $top;

            // Safety check to prevent infinite loops
            if ($skip > 2000) {
                $hasMore = false;
            }
        }
    }

    protected function syncInvoicesToVendor(IntegrationSetting $integration): void
    {
        // This method would sync local invoices to SAP
        // Implementation depends on your local data structure
        $this->logSyncActivity('sync_to_vendor_completed');
    }

    public function __destruct()
    {
        // Logout from SAP session
        if ($this->client && $this->sessionId) {
            try {
                $this->client->post('/Logout');
            } catch (Exception $e) {
                // Ignore logout errors
            }
        }
    }
}
