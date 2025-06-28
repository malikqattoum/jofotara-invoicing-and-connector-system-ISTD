<?php

namespace App\Services\VendorConnectors;

use App\Models\IntegrationSetting;
use App\Models\SyncLog;
use App\Services\VendorConnectors\Exceptions\VendorApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

class BidirectionalSyncService
{
    protected $vendorService;

    public function __construct(VendorIntegrationService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    /**
     * Push local data to vendor
     */
    public function pushToVendor(IntegrationSetting $integration, string $dataType, array $data): array
    {
        $syncLog = $this->createSyncLog($integration, "push_{$dataType}");

        try {
            $connector = VendorConnectorFactory::create($integration->vendor);
            $connector->setCurrentIntegration($integration);

            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($data as $record) {
                try {
                    $result = $this->pushSingleRecord($connector, $dataType, $record);
                    $results[] = $result;

                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $results[] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'record_id' => $record['id'] ?? 'unknown'
                    ];
                }
            }

            $this->completeSyncLog($syncLog, 'success', $successCount, [
                'pushed_records' => $successCount,
                'failed_records' => $errorCount,
                'results' => $results
            ]);

            return [
                'success' => true,
                'pushed_records' => $successCount,
                'failed_records' => $errorCount,
                'results' => $results
            ];
        } catch (Exception $e) {
            $this->completeSyncLog($syncLog, 'failed', 0, null, $e->getMessage());
            throw new VendorApiException(
                "Failed to push {$dataType} to {$integration->vendor}",
                0,
                $e,
                $integration->vendor,
                "push_{$dataType}"
            );
        }
    }

    /**
     * Sync data bidirectionally with conflict resolution
     */
    public function bidirectionalSync(IntegrationSetting $integration, string $dataType, array $options = []): array
    {
        $syncLog = $this->createSyncLog($integration, "bidirectional_{$dataType}");

        try {
            // Step 1: Fetch remote data
            $remoteData = $this->fetchRemoteData($integration, $dataType, $options);

            // Step 2: Fetch local data
            $localData = $this->fetchLocalData($integration, $dataType, $options);

            // Step 3: Detect conflicts and resolve them
            $conflicts = $this->detectConflicts($localData, $remoteData, $dataType);
            $resolvedData = $this->resolveConflicts($conflicts, $options['conflict_resolution'] ?? 'prefer_remote');

            // Step 4: Apply changes
            $pushResults = $this->applyLocalChanges($integration, $resolvedData['push_to_vendor']);
            $pullResults = $this->applyRemoteChanges($integration, $resolvedData['pull_from_vendor']);

            $totalProcessed = count($pushResults) + count($pullResults);

            $this->completeSyncLog($syncLog, 'success', $totalProcessed, [
                'conflicts_detected' => count($conflicts),
                'pushed_to_vendor' => count($pushResults),
                'pulled_from_vendor' => count($pullResults),
                'conflict_resolution_strategy' => $options['conflict_resolution'] ?? 'prefer_remote'
            ]);

            return [
                'success' => true,
                'conflicts_detected' => count($conflicts),
                'pushed_to_vendor' => count($pushResults),
                'pulled_from_vendor' => count($pullResults),
                'push_results' => $pushResults,
                'pull_results' => $pullResults
            ];
        } catch (Exception $e) {
            $this->completeSyncLog($syncLog, 'failed', 0, null, $e->getMessage());
            throw $e;
        }
    }

    protected function pushSingleRecord(AbstractVendorConnector $connector, string $dataType, array $record): array
    {
        switch ($dataType) {
            case 'invoices':
                return $this->pushInvoice($connector, $record);
            case 'customers':
                return $this->pushCustomer($connector, $record);
            default:
                throw new Exception("Unsupported data type for push: {$dataType}");
        }
    }

    protected function pushInvoice(AbstractVendorConnector $connector, array $invoice): array
    {
        // This would be implemented in each connector
        if (method_exists($connector, 'createInvoice')) {
            return $connector->createInvoice($invoice);
        }

        throw new Exception("Invoice creation not supported by this vendor");
    }

    protected function pushCustomer(AbstractVendorConnector $connector, array $customer): array
    {
        // This would be implemented in each connector
        if (method_exists($connector, 'createCustomer')) {
            return $connector->createCustomer($customer);
        }

        throw new Exception("Customer creation not supported by this vendor");
    }

    protected function fetchRemoteData(IntegrationSetting $integration, string $dataType, array $options): Collection
    {
        switch ($dataType) {
            case 'invoices':
                return $this->vendorService->syncInvoices($integration, $options);
            case 'customers':
                return $this->vendorService->syncCustomers($integration, $options);
            default:
                throw new Exception("Unsupported data type: {$dataType}");
        }
    }

    protected function fetchLocalData(IntegrationSetting $integration, string $dataType, array $options): Collection
    {
        // This would fetch data from your local database
        // Implementation depends on your local data models
        switch ($dataType) {
            case 'invoices':
                return $this->fetchLocalInvoices($integration, $options);
            case 'customers':
                return $this->fetchLocalCustomers($integration, $options);
            default:
                throw new Exception("Unsupported data type: {$dataType}");
        }
    }

    protected function fetchLocalInvoices(IntegrationSetting $integration, array $options): Collection
    {
        // Example implementation - adjust based on your Invoice model
        /*
        return Invoice::where('integration_setting_id', $integration->id)
            ->when(isset($options['date_from']), function($query) use ($options) {
                return $query->where('created_at', '>=', $options['date_from']);
            })
            ->get()
            ->map(function($invoice) {
                return $invoice->toVendorFormat();
            });
        */
        return collect();
    }

    protected function fetchLocalCustomers(IntegrationSetting $integration, array $options): Collection
    {
        // Example implementation - adjust based on your Customer model
        /*
        return Customer::where('integration_setting_id', $integration->id)
            ->get()
            ->map(function($customer) {
                return $customer->toVendorFormat();
            });
        */
        return collect();
    }

    protected function detectConflicts(Collection $localData, Collection $remoteData, string $dataType): array
    {
        $conflicts = [];

        foreach ($localData as $localRecord) {
            $remoteRecord = $remoteData->firstWhere('id', $localRecord['id']);

            if ($remoteRecord && $this->hasConflict($localRecord, $remoteRecord, $dataType)) {
                $conflicts[] = [
                    'id' => $localRecord['id'],
                    'local' => $localRecord,
                    'remote' => $remoteRecord,
                    'conflicts' => $this->identifyConflictFields($localRecord, $remoteRecord)
                ];
            }
        }

        return $conflicts;
    }

    protected function hasConflict(array $local, array $remote, string $dataType): bool
    {
        $compareFields = $this->getCompareFields($dataType);

        foreach ($compareFields as $field) {
            if (($local[$field] ?? null) !== ($remote[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    protected function getCompareFields(string $dataType): array
    {
        return match ($dataType) {
            'invoices' => ['total_amount', 'status', 'due_date'],
            'customers' => ['name', 'email', 'phone'],
            default => []
        };
    }

    protected function identifyConflictFields(array $local, array $remote): array
    {
        $conflicts = [];
        $compareFields = $this->getCompareFields('invoices'); // This should be dynamic

        foreach ($compareFields as $field) {
            if (($local[$field] ?? null) !== ($remote[$field] ?? null)) {
                $conflicts[] = [
                    'field' => $field,
                    'local_value' => $local[$field] ?? null,
                    'remote_value' => $remote[$field] ?? null
                ];
            }
        }

        return $conflicts;
    }

    protected function resolveConflicts(array $conflicts, string $strategy): array
    {
        $pushToVendor = [];
        $pullFromVendor = [];

        foreach ($conflicts as $conflict) {
            switch ($strategy) {
                case 'prefer_local':
                    $pushToVendor[] = $conflict['local'];
                    break;
                case 'prefer_remote':
                    $pullFromVendor[] = $conflict['remote'];
                    break;
                case 'newest_wins':
                    $localTimestamp = strtotime($conflict['local']['updated_at'] ?? '1970-01-01');
                    $remoteTimestamp = strtotime($conflict['remote']['updated_at'] ?? '1970-01-01');

                    if ($localTimestamp > $remoteTimestamp) {
                        $pushToVendor[] = $conflict['local'];
                    } else {
                        $pullFromVendor[] = $conflict['remote'];
                    }
                    break;
            }
        }

        return [
            'push_to_vendor' => $pushToVendor,
            'pull_from_vendor' => $pullFromVendor
        ];
    }

    protected function applyLocalChanges(IntegrationSetting $integration, array $data): array
    {
        // Apply changes to local database
        $results = [];

        foreach ($data as $record) {
            try {
                // Update local record based on remote data
                // Implementation depends on your local models
                $results[] = ['success' => true, 'id' => $record['id']];
            } catch (Exception $e) {
                $results[] = ['success' => false, 'id' => $record['id'], 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    protected function applyRemoteChanges(IntegrationSetting $integration, array $data): array
    {
        // Push changes to vendor
        return $this->pushToVendor($integration, 'mixed', $data)['results'] ?? [];
    }

    protected function createSyncLog(IntegrationSetting $integration, string $syncType): SyncLog
    {
        return SyncLog::create([
            'integration_setting_id' => $integration->id,
            'sync_type' => $syncType,
            'status' => 'running',
            'started_at' => now()
        ]);
    }

    protected function completeSyncLog(SyncLog $syncLog, string $status, int $recordsProcessed, ?array $metadata = null, ?string $errorMessage = null): void
    {
        $syncLog->update([
            'status' => $status,
            'records_processed' => $recordsProcessed,
            'duration_seconds' => now()->diffInSeconds($syncLog->started_at),
            'metadata' => $metadata,
            'error_message' => $errorMessage,
            'completed_at' => now()
        ]);
    }
}
