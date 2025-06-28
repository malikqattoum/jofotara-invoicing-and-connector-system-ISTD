# JoFotara SDK Integration

## Overview

This project has been updated to use the `jafar-albadarneh/jofotara` PHP SDK for Jordan E-Invoice Integration. The SDK provides a more robust and maintainable way to interact with the JoFotara e-invoicing system.

## Changes Made

1. Installed the JoFotara SDK via Composer:
   ```
   composer require jafar-albadarneh/jofotara
   ```

2. Created a new `JoFotaraService` class in `app/Services` to handle all interactions with the JoFotara API through the SDK.

3. Updated the `JoFotaraController` to use the new service instead of the direct implementation.

4. Added a migration to add the `income_source_sequence` field to the `integration_settings` table, which is required by the JoFotara SDK.

5. Updated the `IntegrationSetting` model to include the new field in its fillable array.

## How to Use

### Setting Up Integration Settings

Make sure to set the following fields in your integration settings:

- `client_id`: Your JoFotara client ID
- `secret_key`: Your JoFotara secret key
- `income_source_sequence`: Your business's income source sequence number (obtained from JoFotara portal)
- `environment_url`: The JoFotara API URL

### Submitting Invoices

The process for submitting invoices remains the same. Use the following API endpoint:

```
POST /api/invoices/{id}/submit
```

## Benefits of the SDK

- **Simple, Fluent API**: Intuitive builder pattern for creating invoices
- **Full UBL 2.1 Compliance**: Generates valid XML according to Jordan Tax Authority standards
- **Built-in Validation**: Ensures all required fields and business rules are satisfied
- **Multiple Invoice Types**: Support for sales, income, credit invoices, and more
- **Flexible Payment Methods**: Handle both cash and receivable transactions
- **Automatic Calculations**: Built-in tax and total calculations

## Important Notes

- JoFotara does not provide a sandbox environment. For testing, use past dates for test invoices and always issue credit invoices to reverse test transactions.
- Never commit your JoFotara credentials to version control. Use environment variables instead.

## References

- [JoFotara SDK Documentation](https://packagist.org/packages/jafar-albadarneh/jofotara)
- [Jordan E-Invoicing System (JoFotara)](https://edicomgroup.com/blog/jordan-prepares-to-launch-the-electronic-invoice)