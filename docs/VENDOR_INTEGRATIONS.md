# Vendor Integrations Documentation

## Overview

This implementation provides a comprehensive system for integrating with multiple accounting software vendors including:

-   **QuickBooks Online**
-   **Xero**
-   **SAP Business One / S/4HANA**
-   **Oracle NetSuite**
-   **Microsoft Dynamics 365**

## Architecture

### Core Components

1. **AbstractVendorConnector** - Base class for all vendor connectors
2. **VendorConnectorFactory** - Factory for creating vendor connector instances
3. **VendorIntegrationService** - Main service for managing integrations
4. **Individual Connector Classes** - Vendor-specific implementations

### File Structure

```
app/
├── Services/VendorConnectors/
│   ├── AbstractVendorConnector.php
│   ├── VendorConnectorFactory.php
│   ├── VendorIntegrationService.php
│   ├── Exceptions/
│   │   ├── VendorApiException.php
│   │   └── AuthenticationException.php
│   └── Vendors/
│       ├── QuickBooksConnector.php
│       ├── XeroConnector.php
│       ├── SAPConnector.php
│       ├── NetSuiteConnector.php
│       └── DynamicsConnector.php
├── Jobs/
│   ├── SyncVendorInvoicesJob.php
│   └── SyncVendorCustomersJob.php
├── Http/Controllers/Api/
│   └── VendorIntegrationController.php
├── Console/Commands/
│   ├── TestVendorConnection.php
│   └── SyncVendorData.php
└── Providers/
    └── VendorIntegrationServiceProvider.php
```

## Usage

### 1. Register the Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\VendorIntegrationServiceProvider::class,
],
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=vendor-integrations-config
```

### 3. Create Integration Settings

```php
$integration = IntegrationSetting::create([
    'vendor' => 'xero',
    'configuration' => [
        'tenant_id' => 'your-tenant-id',
        'access_token' => 'your-access-token',
        'refresh_token' => 'your-refresh-token',
        'access_token_expires_at' => '2024-12-31 23:59:59'
    ],
    'is_active' => true
]);
```

### 4. Test Connection

```php
$vendorService = app(VendorIntegrationService::class);
$success = $vendorService->testVendorConnection($integration);
```

### 5. Sync Data

```php
// Sync invoices
$invoices = $vendorService->syncInvoices($integration, [
    'date_from' => '2024-01-01',
    'page' => 1
]);

// Sync customers
$customers = $vendorService->syncCustomers($integration);
```

## API Endpoints

### General Endpoints

-   `GET /api/vendor-integrations/vendors` - Get supported vendors
-   `GET /api/vendor-integrations/vendors/{vendor}/config` - Get vendor config fields
-   `POST /api/vendor-integrations/validate-config` - Validate vendor configuration

### Integration-Specific Endpoints

-   `POST /api/vendor-integrations/{integration}/test-connection` - Test connection
-   `POST /api/vendor-integrations/{integration}/sync/invoices` - Sync invoices
-   `POST /api/vendor-integrations/{integration}/sync/customers` - Sync customers
-   `GET /api/vendor-integrations/{integration}/invoices/{invoiceId}` - Get specific invoice
-   `POST /api/vendor-integrations/{integration}/webhook` - Handle webhooks
-   `POST /api/vendor-integrations/{integration}/refresh-token` - Refresh token
-   `GET /api/vendor-integrations/{integration}/stats` - Get integration stats

## Console Commands

### Test Connection

```bash
php artisan vendor:test-connection {integration_id}
```

### Sync Data

```bash
# Sync all data
php artisan vendor:sync {integration_id}

# Sync only invoices
php artisan vendor:sync {integration_id} --type=invoices

# Sync asynchronously
php artisan vendor:sync {integration_id} --async
```

## Configuration

### Vendor-Specific Settings

Each vendor has specific configuration requirements:

#### QuickBooks Online

-   `company_id` - QuickBooks Company ID
-   `access_token` - OAuth 2.0 access token
-   `refresh_token` - OAuth 2.0 refresh token
-   `access_token_expires_at` - Token expiration timestamp

#### Xero

-   `tenant_id` - Xero Tenant ID
-   `access_token` - OAuth 2.0 access token
-   `refresh_token` - OAuth 2.0 refresh token
-   `access_token_expires_at` - Token expiration timestamp

#### SAP Business One

-   `server_url` - SAP Server URL
-   `database_name` - Database name
-   `username` - Username
-   `password` - Password
-   `company_db` - Company database

#### Oracle NetSuite

-   `account_id` - NetSuite Account ID
-   `consumer_key` - OAuth 1.0 consumer key
-   `consumer_secret` - OAuth 1.0 consumer secret
-   `token_id` - OAuth 1.0 token ID
-   `token_secret` - OAuth 1.0 token secret
-   `realm` - Environment (production/sandbox)

#### Microsoft Dynamics 365

-   `tenant_id` - Azure AD Tenant ID
-   `client_id` - Application Client ID
-   `client_secret` - Application Client Secret
-   `resource_url` - Dynamics 365 Resource URL
-   `access_token` - OAuth 2.0 access token
-   `refresh_token` - OAuth 2.0 refresh token
-   `access_token_expires_at` - Token expiration timestamp

## Features

### Authentication

-   OAuth 2.0 support (QuickBooks, Xero, Dynamics)
-   OAuth 1.0 support (NetSuite)
-   Session-based authentication (SAP)
-   Automatic token refresh

### Data Synchronization

-   Invoice synchronization with pagination
-   Customer synchronization
-   Incremental sync with date filters
-   Background job processing

### Error Handling

-   Comprehensive exception handling
-   Retry mechanisms with exponential backoff
-   Rate limiting protection
-   Detailed logging

### Webhooks

-   Webhook signature verification
-   Event processing
-   Background job queuing

### Caching

-   Token caching
-   Rate limit caching
-   Configurable cache durations

## Extending the System

### Adding New Vendors

1. Create a new connector class extending `AbstractVendorConnector`
2. Implement all required methods
3. Register the connector in `VendorConnectorFactory`
4. Add vendor configuration to `config/vendor-integrations.php`

Example:

```php
class NewVendorConnector extends AbstractVendorConnector
{
    public function getVendorName(): string
    {
        return 'New Vendor';
    }

    // Implement other required methods...
}

// Register in factory
VendorConnectorFactory::registerConnector('newvendor', NewVendorConnector::class);
```

## Security Considerations

1. **Token Storage** - Store tokens securely, preferably encrypted
2. **Webhook Verification** - Always verify webhook signatures
3. **Rate Limiting** - Respect vendor API rate limits
4. **Error Logging** - Don't log sensitive information
5. **HTTPS** - Use HTTPS for all API communications

## Monitoring and Logging

The system provides comprehensive logging for:

-   Authentication attempts
-   API calls and responses
-   Sync operations
-   Error conditions
-   Webhook processing

Logs are stored in `storage/logs/vendor-integrations.log` by default.

## Troubleshooting

### Common Issues

1. **Authentication Failures**

    - Check token expiration
    - Verify credentials
    - Check API permissions

2. **Rate Limiting**

    - Increase delay between requests
    - Implement exponential backoff
    - Use background jobs

3. **Webhook Issues**
    - Verify webhook signatures
    - Check endpoint accessibility
    - Review webhook configuration

### Debug Mode

Enable debug logging by setting:

```php
'logging' => [
    'level' => 'debug'
]
```

## Performance Optimization

1. **Use Background Jobs** - For large data syncs
2. **Implement Caching** - For frequently accessed data
3. **Batch Processing** - Process data in batches
4. **Database Indexing** - Index frequently queried fields
5. **Queue Management** - Use dedicated queues for vendor operations

## Testing

### Unit Tests

Create tests for each connector:

```php
class XeroConnectorTest extends TestCase
{
    public function test_authentication()
    {
        $connector = new XeroConnector();
        $integration = IntegrationSetting::factory()->create([
            'vendor' => 'xero'
        ]);

        $result = $connector->authenticate($integration);
        $this->assertTrue($result);
    }
}
```

### Integration Tests

Test with actual vendor APIs using sandbox environments.

## Support

For issues and questions:

1. Check the logs for error details
2. Verify vendor API documentation
3. Test with vendor sandbox environments
4. Review configuration settings
