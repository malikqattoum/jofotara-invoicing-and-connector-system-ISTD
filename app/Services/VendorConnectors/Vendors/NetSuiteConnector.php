<?php

namespace App\Services\VendorConnectors\Vendors;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\AbstractVendorConnector;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class NetSuiteConnector extends AbstractVendorConnector
{
    protected $client;
    protected $baseUrl;
    protected $accountId;

    public function getVendorName(): string
    {
        return 'Oracle NetSuite';
    }

    public function getRequiredConfigFields(): array
    {
        return [
            [
                'key' => 'account_id',
                'label' => 'Account ID',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'consumer_key',
                'label' => 'Consumer Key',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'consumer_secret',
                'label' => 'Consumer Secret',
                'type' => 'password',
                'required' => true
            ],
            [
                'key' => 'token_id',
                'label' => 'Token ID',
                'type' => 'string',
                'required' => true
            ],
            [
                'key' => 'token_secret',
                'label' => 'Token Secret',
                'type' => 'password',
                'required' => true
            ],
            [
                'key' => 'realm',
                'label' => 'Realm (Environment)',
                'type' => 'select',
                'options' => [
                    'production' => 'Production',
                    'sandbox' => 'Sandbox'
                ],
                'required' => true
            ]
        ];
    }

    public function authenticate(IntegrationSetting $integration): bool
    {
        try {
            $this->validateConfiguration($integration);

            $this->accountId = $integration->configuration['account_id'];
            $realm = $integration->configuration['realm'] ?? 'production';

            $this->baseUrl = $realm === 'sandbox'
                ? "https://{$this->accountId}-sb1.suitetalk.api.netsuite.com/services/rest/record/v1"
                : "https://{$this->accountId}.suitetalk.api.netsuite.com/services/rest/record/v1";

            $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            // Test connection by making a simple request
            $response = $this->makeAuthenticatedRequest('GET', '/account');

            return $response->getStatusCode() === 200;
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

            $offset = ($filters['page'] ?? 1 - 1) * 100;
            $limit = 100;

            $queryParams = [
                'limit' => $limit,
                'offset' => $offset
            ];

            if (isset($filters['date_from'])) {
                $queryParams['q'] = "trandate AFTER '{$filters['date_from']}'";
            }

            $response = $this->makeAuthenticatedRequest('GET', '/invoice', [
                'query' => $queryParams
            ]);

            $data = json_decode($response->getBody(), true);
            $invoices = $data['items'] ?? [];

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

            $offset = ($filters['page'] ?? 1 - 1) * 100;
            $limit = 100;

            $response = $this->makeAuthenticatedRequest('GET', '/customer', [
                'query' => [
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $customers = $data['items'] ?? [];

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

            $response = $this->makeAuthenticatedRequest('GET', "/invoice/{$invoiceId}");

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
        // NetSuite uses OAuth 1.0 which doesn't have refresh tokens
        // Token-based authentication doesn't expire like OAuth 2.0
        return true;
    }

    public function handleWebhook(IntegrationSetting $integration, array $payload): bool
    {
        try {
            // NetSuite webhooks are called "SuiteScript RESTlets"
            // Validate the webhook signature if configured
            if (isset($_SERVER['HTTP_X_NETSUITE_SIGNATURE'])) {
                $signature = $_SERVER['HTTP_X_NETSUITE_SIGNATURE'];
                if (!$this->validateWebhookSignature($signature, $payload)) {
                    throw new Exception('Invalid webhook signature');
                }
            }

            // Process webhook events based on record type
            $recordType = $payload['recordType'] ?? null;
            $eventType = $payload['eventType'] ?? null;

            if ($recordType === 'invoice' && in_array($eventType, ['create', 'edit'])) {
                // Queue invoice sync job
                // ProcessNetSuiteInvoiceWebhook::dispatch($integration, $payload);
            }

            return true;
        } catch (Exception $e) {
            $this->handleApiException($e, 'handleWebhook');
            return false;
        }
    }

    protected function makeAuthenticatedRequest(string $method, string $endpoint, array $options = [])
    {
        $integration = $this->getCurrentIntegration();

        $oauthParams = [
            'oauth_consumer_key' => $integration->configuration['consumer_key'],
            'oauth_token' => $integration->configuration['token_id'],
            'oauth_signature_method' => 'HMAC-SHA256',
            'oauth_timestamp' => time(),
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_version' => '1.0'
        ];

        $signature = $this->generateOAuthSignature(
            $method,
            $this->baseUrl . $endpoint,
            $oauthParams,
            $integration->configuration['consumer_secret'],
            $integration->configuration['token_secret']
        );

        $oauthParams['oauth_signature'] = $signature;

        $authHeader = 'OAuth ' . http_build_query($oauthParams, '', ', ', PHP_QUERY_RFC3986);

        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Authorization' => $authHeader
        ]);

        return $this->client->request($method, $endpoint, $options);
    }

    protected function generateOAuthSignature(string $method, string $url, array $params, string $consumerSecret, string $tokenSecret): string
    {
        $baseString = strtoupper($method) . '&' .
            rawurlencode($url) . '&' .
            rawurlencode(http_build_query($params, '', '&', PHP_QUERY_RFC3986));

        $signingKey = rawurlencode($consumerSecret) . '&' . rawurlencode($tokenSecret);

        return base64_encode(hash_hmac('sha256', $baseString, $signingKey, true));
    }

    protected function normalizeInvoiceData(array $invoice): array
    {
        return [
            'invoice_number' => $invoice['tranid'] ?? '',
            'invoice_date' => isset($invoice['trandate']) ?
                date('Y-m-d', strtotime($invoice['trandate'])) : null,
            'due_date' => isset($invoice['duedate']) ?
                date('Y-m-d', strtotime($invoice['duedate'])) : null,
            'total_amount' => $invoice['total'] ?? 0,
            'subtotal' => $invoice['subtotal'] ?? 0,
            'tax_amount' => $invoice['taxtotal'] ?? 0,
            'currency' => $invoice['currency']['name'] ?? 'USD',
            'status' => $this->mapInvoiceStatus($invoice['status']['id'] ?? ''),
            'customer' => [
                'name' => $invoice['entity']['name'] ?? '',
                'email' => $invoice['entity']['email'] ?? null
            ],
            'line_items' => $this->extractLineItems($invoice['item'] ?? [])
        ];
    }

    protected function normalizeCustomerData(array $customer): array
    {
        return [
            'name' => $customer['companyname'] ?? $customer['entityid'] ?? '',
            'email' => $customer['email'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'address' => $this->formatAddress($customer),
            'tax_number' => $customer['taxidnum'] ?? null
        ];
    }

    private function extractLineItems(array $lineItems): array
    {
        $items = [];

        foreach ($lineItems as $lineItem) {
            $items[] = [
                'description' => $lineItem['description'] ?? '',
                'quantity' => $lineItem['quantity'] ?? 1,
                'unit_price' => $lineItem['rate'] ?? 0,
                'line_total' => $lineItem['amount'] ?? 0
            ];
        }

        return $items;
    }

    private function formatAddress(array $customer): ?string
    {
        $addressParts = array_filter([
            $customer['billaddr1'] ?? null,
            $customer['billcity'] ?? null,
            $customer['billstate'] ?? null,
            $customer['billzip'] ?? null
        ]);

        return !empty($addressParts) ? implode(', ', $addressParts) : null;
    }

    private function mapInvoiceStatus(string $netsuiteStatus): string
    {
        $statusMap = [
            'A' => 'sent', // Open
            'B' => 'paid', // Paid In Full
            'C' => 'pending', // Pending Approval
            'D' => 'cancelled' // Voided
        ];

        return $statusMap[$netsuiteStatus] ?? 'unknown';
    }

    private function validateWebhookSignature(string $signature, array $payload): bool
    {
        // Implement NetSuite webhook signature validation
        // This would depend on your NetSuite webhook configuration
        return true;
    }

    protected function getCurrentIntegration(): IntegrationSetting
    {
        // This should be set during authentication
        // For now, we'll need to pass it through the class
        return $this->currentIntegration;
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'invoice.create',
            'invoice.edit',
            'invoice.delete',
            'customer.create',
            'customer.edit',
            'item.create',
            'item.edit'
        ];
    }

    public function getRateLimit(): array
    {
        return [
            'requests_per_minute' => 60,
            'requests_per_day' => 5000,
            'concurrent_requests' => 5
        ];
    }

    public function bidirectionalSync(IntegrationSetting $integration): void
    {
        $this->setCurrentIntegration($integration);
        $this->logSyncActivity('bidirectional_sync_started');

        try {
            // Sync invoices from NetSuite to local system
            $this->syncInvoicesFromVendor($integration);

            // Sync customers from NetSuite to local system
            $this->syncCustomersFromVendor($integration);

            // Sync local invoices to NetSuite (if needed)
            $this->syncInvoicesToVendor($integration);

            $this->logSyncActivity('bidirectional_sync_completed');
        } catch (Exception $e) {
            $this->logSyncActivity('bidirectional_sync_failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function syncInvoicesFromVendor(IntegrationSetting $integration): void
    {
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $invoices = $this->fetchInvoices($integration, ['page' => $page]);

            if ($invoices->isEmpty()) {
                $hasMore = false;
                break;
            }

            foreach ($invoices as $invoiceData) {
                // Here you would typically save to your local database
                // Example: Invoice::updateOrCreate(['external_id' => $invoiceData['id']], $invoiceData);
                $this->logSyncActivity('invoice_synced', ['invoice_number' => $invoiceData['invoice_number']]);
            }

            $page++;

            // Safety check to prevent infinite loops
            if ($page > 100) {
                $hasMore = false;
            }
        }
    }

    protected function syncCustomersFromVendor(IntegrationSetting $integration): void
    {
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $customers = $this->fetchCustomers($integration, ['page' => $page]);

            if ($customers->isEmpty()) {
                $hasMore = false;
                break;
            }

            foreach ($customers as $customerData) {
                // Here you would typically save to your local database
                // Example: Customer::updateOrCreate(['external_id' => $customerData['id']], $customerData);
                $this->logSyncActivity('customer_synced', ['customer_name' => $customerData['name']]);
            }

            $page++;

            // Safety check to prevent infinite loops
            if ($page > 100) {
                $hasMore = false;
            }
        }
    }

    protected function syncInvoicesToVendor(IntegrationSetting $integration): void
    {
        // This method would sync local invoices to NetSuite
        // Implementation depends on your local data structure
        $this->logSyncActivity('sync_to_vendor_completed');
    }
}
