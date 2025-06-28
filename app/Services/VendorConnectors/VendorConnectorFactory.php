<?php

namespace App\Services\VendorConnectors;

use App\Services\VendorConnectors\Vendors\QuickBooksConnector;
use App\Services\VendorConnectors\Vendors\XeroConnector;
use App\Services\VendorConnectors\Vendors\SAPConnector;
use App\Services\VendorConnectors\Vendors\NetSuiteConnector;
use App\Services\VendorConnectors\Vendors\DynamicsConnector;
use Exception;

class VendorConnectorFactory
{
    private static $connectors = [
        'quickbooks' => QuickBooksConnector::class,
        'xero' => XeroConnector::class,
        'sap' => SAPConnector::class,
        'netsuite' => NetSuiteConnector::class,
        'dynamics' => DynamicsConnector::class
    ];

    public static function create(string $vendor): AbstractVendorConnector
    {
        $connectorClass = self::$connectors[strtolower($vendor)] ?? null;

        if (!$connectorClass) {
            throw new Exception("Unsupported vendor: {$vendor}");
        }

        return new $connectorClass();
    }

    public static function getSupportedVendors(): array
    {
        return array_keys(self::$connectors);
    }

    public static function registerConnector(string $vendor, string $connectorClass): void
    {
        if (!is_subclass_of($connectorClass, AbstractVendorConnector::class)) {
            throw new Exception("Connector class must extend AbstractVendorConnector");
        }

        self::$connectors[strtolower($vendor)] = $connectorClass;
    }
}
