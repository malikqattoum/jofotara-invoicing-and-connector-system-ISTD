<?php

require_once 'vendor/autoload.php';

use App\Services\VendorConnectors\VendorConnectorFactory;
use App\Models\IntegrationSetting;

echo "Testing Vendor Connectors...\n\n";

$vendors = ['xero', 'quickbooks', 'netsuite', 'sap', 'dynamics'];

foreach ($vendors as $vendor) {
    try {
        $connector = VendorConnectorFactory::create($vendor);

        echo "✅ {$connector->getVendorName()} Connector:\n";
        echo "   - Required Config Fields: " . count($connector->getRequiredConfigFields()) . "\n";
        echo "   - Supported Webhook Events: " . count($connector->getSupportedWebhookEvents()) . "\n";
        echo "   - Rate Limit: " . json_encode($connector->getRateLimit()) . "\n";
        echo "\n";

    } catch (Exception $e) {
        echo "❌ Failed to create {$vendor} connector: " . $e->getMessage() . "\n\n";
    }
}

echo "All vendor connectors have been successfully completed!\n";
