<?php

namespace App\Services\VendorConnectors\Vendors;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\AbstractVendorConnector;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use League\OAuth2\Client\Provider\GenericProvider;
use Exception;

class DynamicsConnector extends AbstractVendorConnector
{
    protected $client;
    protected $baseUrl;
    protected $accessToken;

    public function getVendorName(): string
    {
        return 'Microsoft Dynamics 365';
    }

    public function getRequiredConfigFields(): array
    {
        return [
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'client_id',
                'label' => 'Client ID',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'client_secret',
                'label' => 'Client Secret',
                'type' => 'password',
                'required' => true
            ],
            [
                'key' => 'resource_url',
                'label' => 'Resource URL (e.g., https://yourorg.crm.dynamics.com)',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'access_token',
                'label' => 'Access Token',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'refresh_token',
                'label' => 'Refresh Token',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'access_token_expires_at',
                'label' => 'Access Token Expires At',
                'type' => 'datetime',
                'required' => true
            ]
        ];
    }

    public function authenticate(IntegrationSetting $integration): bool
    {
        try {
            $this->validateConfiguration($integration);

            $this->baseUrl = rtrim($integration->configuration['resource_url'], '/') . '/api/data/v9.2';
            $this->accessToken = $integration->configuration['access_token'];

            $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'OData-MaxVersion' => '4.0',
                    'OData-Version' => '4.0'
                ]
            ]);

            // Test connection by fetching organization info
            $response = $this->client->get('/organizations');

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Authentication failed');
            }

            return true;
        } catch (RequestException $e) {
            if ($this->isTokenExpired($integration)) {
                return $this->refreshToken($integration);
            }

            $this->handleApiException($e, 'authentication');
            return false;
        } catch (Exception $e) {
            $this->handleApiException($e, 'authentication');
            return false;
        }
    }

    public function testConnection(IntegrationSetting $integration): bool
    {
        return $this->authenticate($integration);
    }

    public function fetchInvoices(IntegrationSetting $integration, array $filters = []): Collection
    {
        try {
            $this->authenticate($integration);
            $this->handleRateLimit();

            $skip = ($filters['page'] ?? 1 - 1) * 50;
            $top = 50;

            $queryParams = [
                '$top' => $top,
                '$skip' => $skip,
                '$expand' => 'customerid_account,customerid_contact'
            ];

            if (isset($filters['date_from'])) {
                $queryParams['$filter'] = "createdon ge {$filters['date_from']}";
            }

            $response = $this->client->get('/invoices', [
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

            $skip = ($filters['page'] ?? 1 - 1) * 50;
            $top = 50;

            $response = $this->client->get('/accounts', [
                'query' => [
                    '$top' => $top,
                    '$skip' => $skip,
                    '$filter' => "customertypecode eq 3" // Customer accounts
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

            $response = $this->client->get("/invoices({$invoiceId})", [
                'query' => [
                    '$expand' => 'customerid_account,customerid_contact'
                ]
            ]);

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
        try {
            $provider = new GenericProvider([
                'clientId' => $integration->configuration['client_id'],
                'clientSecret' => $integration->configuration['client_secret'],
                'redirectUri' => config('services.dynamics.redirect_uri'),
                'urlAuthorize' => "https://login.microsoftonline.com/{$integration->configuration['tenant_id']}/oauth2/v2.0/authorize",
                'urlAccessToken' => "https://login.microsoftonline.com/{$integration->configuration['tenant_id']}/oauth2/v2.0/token",
                'urlResourceOwnerDetails' => '',
                'scopes' => [$integration->configuration['resource_url'] . '/.default']
            ]);

            $newAccessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $integration->configuration['refresh_token']
            ]);

            $integration->configuration = array_merge($integration->configuration, [
                'access_token' => $newAccessToken->getToken(),
                'refresh_token' => $newAccessToken->getRefreshToken() ?: $integration->configuration['refresh_token'],
                'access_token_expires_at' => $newAccessToken->getExpires()
            ]);

            $integration->save();

            $this->cacheToken($integration, [
                'access_token' => $newAccessToken->getToken(),
                'expires_at' => $newAccessToken->getExpires()
            ]);

            return true;
        } catch (Exception $e) {
            $this->handleApiException($e, 'refreshToken');
            return false;
        }
    }

    public function handleWebhook(IntegrationSetting $integration, array $payload): bool
    {
        try {
            // Dynamics 365 uses Service Bus or Event Grid for webhooks
            // Validate the webhook signature if configured
            if (isset($_SERVER['HTTP_X_MS_SIGNATURE'])) {
                $signature = $_SERVER['HTTP_X_MS_SIGNATURE'];
                if (!$this->validateWebhookSignature($signature, $payload)) {
                    throw new Exception('Invalid webhook signature');
                }
            }

            // Process webhook events
            foreach ($payload['value'] ?? [$payload] as $event) {
                $entityName = $event['entityName'] ?? '';
                $eventType = $event['eventType'] ?? '';

                if ($entityName === 'invoice' && in_array($eventType, ['Created', 'Updated'])) {
                    // Queue invoice sync job
                    // ProcessDynamicsInvoiceWebhook::dispatch($integration, $event);
                }
            }

            return true;
        } catch (Exception $e) {
            $this->handleApiException($e, 'handleWebhook');
            return false;
        }
    }

    protected function normalizeInvoiceData(array $invoice): array
    {
        return [
            'invoice_number' => $invoice['invoicenumber'] ?? '',
            'invoice_date' => isset($invoice['datedelivered']) ?
                date('Y-m-d', strtotime($invoice['datedelivered'])) : null,
            'due_date' => isset($invoice['duedate']) ?
                date('Y-m-d', strtotime($invoice['duedate'])) : null,
            'total_amount' => $invoice['totalamount'] ?? 0,
            'subtotal' => $invoice['totallineitemamount'] ?? 0,
            'tax_amount' => $invoice['totaltax'] ?? 0,
            'currency' => $invoice['transactioncurrencyid']['isocurrencycode'] ?? 'USD',
            'status' => $this->mapInvoiceStatus($invoice['statecode'] ?? 0),
            'customer' => [
                'name' => $this->getCustomerName($invoice),
                'email' => $this->getCustomerEmail($invoice)
            ],
            'line_items' => $this->extractLineItems($invoice['invoice_details'] ?? [])
        ];
    }

    protected function normalizeCustomerData(array $customer): array
    {
        return [
            'name' => $customer['name'] ?? '',
            'email' => $customer['emailaddress1'] ?? null,
            'phone' => $customer['telephone1'] ?? null,
            'address' => $this->formatAddress($customer),
            'tax_number' => $customer['accountnumber'] ?? null
        ];
    }

    private function getCustomerName(array $invoice): string
    {
        if (isset($invoice['customerid_account']['name'])) {
            return $invoice['customerid_account']['name'];
        }

        if (isset($invoice['customerid_contact']['fullname'])) {
            return $invoice['customerid_contact']['fullname'];
        }

        return '';
    }

    private function getCustomerEmail(array $invoice): ?string
    {
        if (isset($invoice['customerid_account']['emailaddress1'])) {
            return $invoice['customerid_account']['emailaddress1'];
        }

        if (isset($invoice['customerid_contact']['emailaddress1'])) {
            return $invoice['customerid_contact']['emailaddress1'];
        }

        return null;
    }

    private function extractLineItems(array $lineItems): array
    {
        $items = [];

        foreach ($lineItems as $lineItem) {
            $items[] = [
                'description' => $lineItem['productdescription'] ?? '',
                'quantity' => $lineItem['quantity'] ?? 1,
                'unit_price' => $lineItem['priceperunit'] ?? 0,
                'line_total' => $lineItem['baseamount'] ?? 0
            ];
        }

        return $items;
    }

    private function formatAddress(array $customer): ?string
    {
        $addressParts = array_filter([
            $customer['address1_line1'] ?? null,
            $customer['address1_city'] ?? null,
            $customer['address1_stateorprovince'] ?? null,
            $customer['address1_postalcode'] ?? null
        ]);

        return !empty($addressParts) ? implode(', ', $addressParts) : null;
    }

    private function mapInvoiceStatus(int $dynamicsStatus): string
    {
        $statusMap = [
            0 => 'draft',    // Active
            1 => 'paid',     // Inactive
            2 => 'cancelled' // Cancelled
        ];

        return $statusMap[$dynamicsStatus] ?? 'unknown';
    }

    // Make signature compatible with AbstractVendorConnector
    protected function isTokenExpired(\App\Models\IntegrationSetting $integration): bool
    {
        $expiresAt = $integration->configuration['access_token_expires_at'] ?? null;
        if (!$expiresAt) {
            return false;
        }
        return time() >= (strtotime($expiresAt) - 300); // 5 minute buffer
    }

    private function validateWebhookSignature(string $signature, array $payload): bool
    {
        // Implement Microsoft webhook signature validation
        // This would depend on your webhook configuration
        return true;
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'invoice.Created',
            'invoice.Updated',
            'invoice.Deleted',
            'account.Created',
            'account.Updated',
            'contact.Created',
            'contact.Updated'
        ];
    }

    public function getRateLimit(): array
    {
        return [
            'requests_per_minute' => 120,
            'requests_per_day' => 20000,
            'concurrent_requests' => 52
        ];
    }

    public function bidirectionalSync(IntegrationSetting $integration): void
    {
        $this->setCurrentIntegration($integration);
        $this->logSyncActivity('bidirectional_sync_started');

        try {
            // Sync invoices from Dynamics to local system
            $this->syncInvoicesFromVendor($integration);

            // Sync customers from Dynamics to local system
            $this->syncCustomersFromVendor($integration);

            // Sync local invoices to Dynamics (if needed)
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
        $top = 50;
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
            if ($skip > 10000) {
                $hasMore = false;
            }
        }
    }

    protected function syncCustomersFromVendor(IntegrationSetting $integration): void
    {
        $skip = 0;
        $top = 50;
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
            if ($skip > 10000) {
                $hasMore = false;
            }
        }
    }

    protected function syncInvoicesToVendor(IntegrationSetting $integration): void
    {
        // This method would sync local invoices to Dynamics
        // Implementation depends on your local data structure
        $this->logSyncActivity('sync_to_vendor_completed');
    }
}
