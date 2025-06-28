<?php

namespace App\Services\VendorConnectors\Vendors;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\AbstractVendorConnector;
use Illuminate\Support\Collection;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\String as QBString;
use Exception;

class QuickBooksConnector extends AbstractVendorConnector
{
    protected $dataService;

    public function getVendorName(): string
    {
        return 'QuickBooks';
    }

    public function getRequiredConfigFields(): array
    {
        return [
            [
                'key' => 'company_id',
                'label' => 'Company ID',
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

            $this->dataService = DataService::Configure([
                'auth_mode' => 'oauth2',
                'ClientID' => config('services.quickbooks.client_id'),
                'ClientSecret' => config('services.quickbooks.client_secret'),
                'RedirectURI' => config('services.quickbooks.redirect_uri'),
                'scope' => 'com.intuit.quickbooks.accounting',
                'baseUrl' => config('services.quickbooks.base_url', 'Production'),
                'accessTokenKey' => $integration->configuration['access_token'],
                'refreshTokenKey' => $integration->configuration['refresh_token'],
                'QBORealmID' => $integration->configuration['company_id']
            ]);

            // Test connection by fetching company info
            $companyInfo = $this->dataService->getCompanyInfo();

            if ($error = $this->dataService->getLastError()) {
                throw new Exception((string) $error->getIntuitErrorDetail());
            }

            return true;
        } catch (Exception $e) {
            if ($this->isTokenExpired($integration)) {
                return $this->refreshToken($integration);
            }

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

            $startPosition = $filters['start_position'] ?? 1;
            $maxResults = $filters['max_results'] ?? 100;

            $query = "SELECT * FROM Invoice";

            if (isset($filters['date_from'])) {
                $query .= " WHERE TxnDate >= '{$filters['date_from']}'";
            }

            $query .= " STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";

            $invoices = $this->dataService->Query($query);

            if ($this->dataService->getLastError()) {
                throw new Exception((string) $this->dataService->getLastError()->getIntuitErrorDetail());
            }

            return collect($invoices)->map(function ($invoice) {
                return $this->normalizeInvoiceData($this->invoiceToArray($invoice));
            });
        } catch (Exception $e) {
            $this->handleApiException($e, 'fetchInvoices');
            return collect();
        }
    }

    public function fetchCustomers(IntegrationSetting $integration, array $filters = []): Collection
    {
        try {
            $this->authenticate($integration);
            $this->handleRateLimit();

            $startPosition = $filters['start_position'] ?? 1;
            $maxResults = $filters['max_results'] ?? 100;

            $query = "SELECT * FROM Customer STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";

            $customers = $this->dataService->Query($query);

            if ($this->dataService->getLastError()) {
                throw new Exception((string) $this->dataService->getLastError()->getIntuitErrorDetail());
            }

            return collect($customers)->map(function ($customer) {
                return $this->normalizeCustomerData($this->customerToArray($customer));
            });
        } catch (Exception $e) {
            $this->handleApiException($e, 'fetchCustomers');
            return collect();
        }
    }

    public function refreshToken(IntegrationSetting $integration): bool
    {
        try {
            $dataService = DataService::Configure([
                'auth_mode' => 'oauth2',
                'ClientID' => config('services.quickbooks.client_id'),
                'ClientSecret' => config('services.quickbooks.client_secret'),
                'RedirectURI' => config('services.quickbooks.redirect_uri'),
                'scope' => 'com.intuit.quickbooks.accounting',
                'baseUrl' => config('services.quickbooks.base_url', 'Production'),
                'QBORealmID' => $integration->configuration['company_id']
            ]);
            $serviceContext = $dataService->getServiceContext();
            $oauthHelper = new OAuth2LoginHelper(
                config('services.quickbooks.client_id'),
                config('services.quickbooks.client_secret'),
                config('services.quickbooks.redirect_uri'),
                null, // scope
                null, // state
                $serviceContext
            );
            $newToken = $oauthHelper->refreshAccessTokenWithRefreshToken($integration->configuration['refresh_token']);
            $integration->configuration = array_merge($integration->configuration, [
                'access_token' => $newToken->getAccessToken(),
                'refresh_token' => $newToken->getRefreshToken(),
                'access_token_expires_at' => $newToken->getAccessTokenExpiresAt()
            ]);
            $integration->save();
            $this->cacheToken($integration, [
                'access_token' => $newToken->getAccessToken(),
                'expires_at' => $newToken->getAccessTokenExpiresAt()
            ]);
            return true;
        } catch (Exception $e) {
            $this->handleApiException($e, 'refreshToken');
            return false;
        }
    }

    public function fetchInvoiceById(IntegrationSetting $integration, string $invoiceId): ?array
    {
        try {
            $this->authenticate($integration);
            $this->handleRateLimit();
            $query = "SELECT * FROM Invoice WHERE Id = '{$invoiceId}'";
            $result = $this->dataService->Query($query);
            if ($error = $this->dataService->getLastError()) {
                throw new Exception((string) $error->getIntuitErrorDetail());
            }
            $invoice = $result && count($result) > 0 ? $result[0] : null;
            if (!$invoice) {
                return null;
            }
            return $this->normalizeInvoiceData($this->invoiceToArray($invoice));
        } catch (Exception $e) {
            $this->handleApiException($e, 'fetchInvoiceById');
            return null;
        }
    }

    public function handleWebhook(IntegrationSetting $integration, array $payload): bool
    {
        // QuickBooks webhooks implementation
        try {
            $signature = $_SERVER['HTTP_INTUIT_SIGNATURE'] ?? '';
            if (!$this->validateWebhookSignature($signature, $payload)) {
                throw new Exception('Invalid webhook signature');
            }

            // Process webhook events
            foreach ($payload['eventNotifications'] as $notification) {
                foreach ($notification['dataChangeEvent']['entities'] as $entity) {
                    if ($entity['name'] === 'Invoice') {
                        // Queue invoice sync job
                        // ProcessQuickBooksInvoiceWebhook::dispatch($integration, $entity);
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            $this->handleApiException($e, 'handleWebhook');
            return false;
        }
    }

    protected function normalizeInvoiceData(array $rawData): array
    {
        return [
            'invoice_number' => $rawData['DocNumber'] ?? '',
            'invoice_date' => $rawData['TxnDate'] ?? null,
            'due_date' => $rawData['DueDate'] ?? null,
            'total_amount' => $rawData['TotalAmt'] ?? 0,
            'subtotal' => $rawData['TxnTaxDetail']['TotalTax'] ?? 0,
            'tax_amount' => $rawData['TxnTaxDetail']['TotalTax'] ?? 0,
            'currency' => $rawData['CurrencyRef']['value'] ?? 'USD',
            'status' => $this->mapInvoiceStatus($rawData['EmailStatus'] ?? 'NotSet'),
            'customer' => [
                'name' => $rawData['CustomerRef']['name'] ?? '',
                'email' => $rawData['BillEmail']['Address'] ?? null
            ],
            'line_items' => $this->extractLineItems($rawData['Line'] ?? [])
        ];
    }

    protected function normalizeCustomerData(array $rawData): array
    {
        return [
            'name' => $rawData['Name'] ?? '',
            'email' => $rawData['PrimaryEmailAddr']['Address'] ?? null,
            'phone' => $rawData['PrimaryPhone']['FreeFormNumber'] ?? null,
            'address' => $rawData['BillAddr']['Line1'] ?? null,
            'tax_number' => $rawData['ResaleNum'] ?? null
        ];
    }

    private function extractLineItems(array $lines): array
    {
        $lineItems = [];

        foreach ($lines as $line) {
            if ($line['DetailType'] === 'SalesItemLineDetail') {
                $lineItems[] = [
                    'description' => $line['Description'] ?? '',
                    'quantity' => $line['SalesItemLineDetail']['Qty'] ?? 1,
                    'unit_price' => $line['SalesItemLineDetail']['UnitPrice'] ?? 0,
                    'line_total' => $line['Amount'] ?? 0
                ];
            }
        }

        return $lineItems;
    }

    private function mapInvoiceStatus(string $qbStatus): string
    {
        $statusMap = [
            'NotSet' => 'draft',
            'NeedToSend' => 'pending',
            'EmailSent' => 'sent',
            'Viewed' => 'viewed'
        ];

        return $statusMap[$qbStatus] ?? 'unknown';
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
        $webhookToken = config('services.quickbooks.webhook_token');
        $computedSignature = base64_encode(
            hash_hmac('sha256', json_encode($payload), $webhookToken, true)
        );

        return hash_equals($signature, $computedSignature);
    }

    private function invoiceToArray($invoice): array
    {
        // Convert QuickBooks invoice object to array
        return json_decode(json_encode($invoice), true);
    }

    private function customerToArray($customer): array
    {
        // Convert QuickBooks customer object to array
        return json_decode(json_encode($customer), true);
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'Invoice',
            'Customer',
            'Item',
            'Payment',
            'Estimate'
        ];
    }

    public function getRateLimit(): array
    {
        return [
            'requests_per_minute' => 100,
            'requests_per_day' => 10000,
            'concurrent_requests' => 10
        ];
    }

    public function bidirectionalSync(IntegrationSetting $integration): void
    {
        $this->setCurrentIntegration($integration);
        $this->logSyncActivity('bidirectional_sync_started');

        try {
            // Sync invoices from QuickBooks to local system
            $this->syncInvoicesFromVendor($integration);

            // Sync customers from QuickBooks to local system
            $this->syncCustomersFromVendor($integration);

            // Sync local invoices to QuickBooks (if needed)
            $this->syncInvoicesToVendor($integration);

            $this->logSyncActivity('bidirectional_sync_completed');
        } catch (Exception $e) {
            $this->logSyncActivity('bidirectional_sync_failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function syncInvoicesFromVendor(IntegrationSetting $integration): void
    {
        $startPosition = 1;
        $maxResults = 100;
        $hasMore = true;

        while ($hasMore) {
            $invoices = $this->fetchInvoices($integration, [
                'start_position' => $startPosition,
                'max_results' => $maxResults
            ]);

            if ($invoices->isEmpty()) {
                $hasMore = false;
                break;
            }

            foreach ($invoices as $invoiceData) {
                // Here you would typically save to your local database
                // Example: Invoice::updateOrCreate(['external_id' => $invoiceData['id']], $invoiceData);
                $this->logSyncActivity('invoice_synced', ['invoice_number' => $invoiceData['invoice_number']]);
            }

            $startPosition += $maxResults;

            // Safety check to prevent infinite loops
            if ($startPosition > 10000) {
                $hasMore = false;
            }
        }
    }

    protected function syncCustomersFromVendor(IntegrationSetting $integration): void
    {
        $startPosition = 1;
        $maxResults = 100;
        $hasMore = true;

        while ($hasMore) {
            $customers = $this->fetchCustomers($integration, [
                'start_position' => $startPosition,
                'max_results' => $maxResults
            ]);

            if ($customers->isEmpty()) {
                $hasMore = false;
                break;
            }

            foreach ($customers as $customerData) {
                // Here you would typically save to your local database
                // Example: Customer::updateOrCreate(['external_id' => $customerData['id']], $customerData);
                $this->logSyncActivity('customer_synced', ['customer_name' => $customerData['name']]);
            }

            $startPosition += $maxResults;

            // Safety check to prevent infinite loops
            if ($startPosition > 10000) {
                $hasMore = false;
            }
        }
    }

    protected function syncInvoicesToVendor(IntegrationSetting $integration): void
    {
        // This method would sync local invoices to QuickBooks
        // Implementation depends on your local data structure
        $this->logSyncActivity('sync_to_vendor_completed');
    }
}
