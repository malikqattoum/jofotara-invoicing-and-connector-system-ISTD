<?php

namespace App\Services\VendorConnectors\Vendors;

use App\Models\IntegrationSetting;
use App\Services\VendorConnectors\AbstractVendorConnector;
use Illuminate\Support\Collection;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Api\AccountingApiException as ApiException;
use League\OAuth2\Client\Provider\GenericProvider;
use Exception;

class XeroConnector extends AbstractVendorConnector
{
    protected $accountingApi;
    protected $tenantId;

    public function getVendorName(): string
    {
        return 'Xero';
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

            $config = Configuration::getDefaultConfiguration()->setAccessToken(
                $integration->configuration['access_token']
            );

            $this->accountingApi = new AccountingApi(
                new \GuzzleHttp\Client(),
                $config
            );

            $this->tenantId = $integration->configuration['tenant_id'];

            // Test connection by fetching organisation info
            $organisations = $this->accountingApi->getOrganisations($this->tenantId);

            if (empty($organisations->getOrganisations())) {
                throw new Exception('Unable to fetch organisation information');
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

            $page = $filters['page'] ?? 1;
            $where = null;

            if (isset($filters['date_from'])) {
                $where = "Date >= DateTime({$filters['date_from']})";
            }

            $invoices = $this->accountingApi->getInvoices(
                $this->tenantId,
                null, // if-modified-since
                $where,
                null, // order
                null, // ids
                null, // invoice-numbers
                null, // contact-ids
                null, // statuses
                $page,
                true  // include-archived
            );

            return collect($invoices->getInvoices())->map(function ($invoice) {
                return $this->normalizeInvoiceData($invoice);
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

            $page = $filters['page'] ?? 1;

            $contacts = $this->accountingApi->getContacts(
                $this->tenantId,
                null, // if-modified-since
                null, // where
                null, // order
                null, // ids
                $page,
                true  // include-archived
            );

            return collect($contacts->getContacts())->map(function ($contact) {
                return $this->normalizeCustomerData($contact);
            });
        } catch (Exception $e) {
            $this->handleApiException($e, 'fetchCustomers');
            return collect();
        }
    }

    public function fetchInvoiceById(IntegrationSetting $integration, string $invoiceId): ?array
    {
        try {
            $this->authenticate($integration);
            $this->handleRateLimit();

            $invoice = $this->accountingApi->getInvoice($this->tenantId, $invoiceId);

            if (empty($invoice->getInvoices())) {
                return null;
            }

            return $this->normalizeInvoiceData($invoice->getInvoices()[0]);
        } catch (Exception $e) {
            $this->handleApiException($e, 'fetchInvoiceById');
            return null;
        }
    }

    public function refreshToken(IntegrationSetting $integration): bool
    {
        try {
            $provider = new GenericProvider([
                'clientId' => config('services.xero.client_id'),
                'clientSecret' => config('services.xero.client_secret'),
                'redirectUri' => config('services.xero.redirect_uri'),
                'urlAuthorize' => 'https://login.xero.com/identity/connect/authorize',
                'urlAccessToken' => 'https://identity.xero.com/connect/token',
                'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
            ]);

            $newAccessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $integration->configuration['refresh_token']
            ]);

            $integration->configuration = array_merge($integration->configuration, [
                'access_token' => $newAccessToken->getToken(),
                'refresh_token' => $newAccessToken->getRefreshToken(),
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
            $signature = $_SERVER['HTTP_X_XERO_SIGNATURE'] ?? '';
            if (!$this->validateWebhookSignature($signature, $payload)) {
                throw new Exception('Invalid webhook signature');
            }

            // Process webhook events
            foreach ($payload['events'] as $event) {
                if ($event['eventCategory'] === 'INVOICE') {
                    // Queue invoice sync job
                    // ProcessXeroInvoiceWebhook::dispatch($integration, $event);
                }
            }

            return true;
        } catch (Exception $e) {
            $this->handleApiException($e, 'handleWebhook');
            return false;
        }
    }

    protected function normalizeInvoiceData($invoice): array
    {
        return [
            'invoice_number' => $invoice->getInvoiceNumber() ?? '',
            'invoice_date' => $invoice->getDate() ? $invoice->getDate()->format('Y-m-d') : null,
            'due_date' => $invoice->getDueDate() ? $invoice->getDueDate()->format('Y-m-d') : null,
            'total_amount' => $invoice->getTotal() ?? 0,
            'subtotal' => $invoice->getSubTotal() ?? 0,
            'tax_amount' => $invoice->getTotalTax() ?? 0,
            'currency' => $invoice->getCurrencyCode() ?? 'USD',
            'status' => $this->mapInvoiceStatus($invoice->getStatus()),
            'customer' => [
                'name' => $invoice->getContact() ? $invoice->getContact()->getName() : '',
                'email' => $invoice->getContact() && $invoice->getContact()->getEmailAddress()
                    ? $invoice->getContact()->getEmailAddress() : null
            ],
            'line_items' => $this->extractLineItems($invoice->getLineItems() ?? [])
        ];
    }

    protected function normalizeCustomerData($contact): array
    {
        return [
            'name' => $contact->getName() ?? '',
            'email' => $contact->getEmailAddress() ?? null,
            'phone' => $contact->getPhones() && !empty($contact->getPhones())
                ? $contact->getPhones()[0]->getPhoneNumber() : null,
            'address' => $contact->getAddresses() && !empty($contact->getAddresses())
                ? $contact->getAddresses()[0]->getAddressLine1() : null,
            'tax_number' => $contact->getTaxNumber() ?? null
        ];
    }

    private function extractLineItems(array $lineItems): array
    {
        $items = [];

        foreach ($lineItems as $lineItem) {
            $items[] = [
                'description' => $lineItem->getDescription() ?? '',
                'quantity' => $lineItem->getQuantity() ?? 1,
                'unit_price' => $lineItem->getUnitAmount() ?? 0,
                'line_total' => $lineItem->getLineAmount() ?? 0
            ];
        }

        return $items;
    }

    private function mapInvoiceStatus(?string $xeroStatus): string
    {
        $statusMap = [
            'DRAFT' => 'draft',
            'SUBMITTED' => 'pending',
            'AUTHORISED' => 'sent',
            'PAID' => 'paid',
            'VOIDED' => 'cancelled'
        ];

        return $statusMap[$xeroStatus] ?? 'unknown';
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

    /**
     * Handles API exceptions and general exceptions.
     *
     * @param \Exception $e
     * @param string $operation
     */
    protected function handleApiException(\Exception $e, string $operation): void
    {
        // You can implement logging or error handling here
        // For example:
        // \Log::error("XeroConnector [$operation]: " . $e->getMessage(), ['exception' => $e]);
    }

    private function validateWebhookSignature(string $signature, array $payload): bool
    {
        $webhookKey = config('services.xero.webhook_key');
        $computedSignature = base64_encode(
            hash_hmac('sha256', json_encode($payload), $webhookKey, true)
        );

        return hash_equals($signature, $computedSignature);
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'INVOICE.CREATE',
            'INVOICE.UPDATE',
            'INVOICE.DELETE',
            'CONTACT.CREATE',
            'CONTACT.UPDATE',
            'ITEM.CREATE',
            'ITEM.UPDATE'
        ];
    }

    public function getRateLimit(): array
    {
        return [
            'requests_per_minute' => 60,
            'requests_per_day' => 5000,
            'concurrent_requests' => 10
        ];
    }

    public function bidirectionalSync(IntegrationSetting $integration): void
    {
        $this->setCurrentIntegration($integration);
        $this->logSyncActivity('bidirectional_sync_started');

        try {
            // Sync invoices from Xero to local system
            $this->syncInvoicesFromVendor($integration);

            // Sync customers from Xero to local system
            $this->syncCustomersFromVendor($integration);

            // Sync local invoices to Xero (if needed)
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
        // This method would sync local invoices to Xero
        // Implementation depends on your local data structure
        $this->logSyncActivity('sync_to_vendor_completed');
    }
}
