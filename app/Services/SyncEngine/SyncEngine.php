<?php

namespace App\Services\SyncEngine;

use App\Models\IntegrationSetting;
use App\Models\SyncJob;
use App\Models\SyncSchedule;
use App\Services\VendorConnectors\VendorConnectorFactory;
use App\Jobs\ProcessSyncJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class SyncEngine
{
    private $maxConcurrentJobs = 10;
    private $retryAttempts = 3;
    private $retryDelay = 300; // 5 minutes

    /**
     * Start real-time sync for an integration
     */
    public function startRealtimeSync(IntegrationSetting $integration, array $options = []): void
    {
        $syncJob = SyncJob::create([
            'integration_id' => $integration->id,
            'type' => 'realtime',
            'status' => 'queued',
            'priority' => $options['priority'] ?? 'normal',
            'configuration' => $options,
            'scheduled_at' => now(),
            'created_by' => auth()->id()
        ]);

        // Dispatch to high-priority queue for realtime sync
        ProcessSyncJob::dispatch($syncJob)->onQueue('sync-realtime');

        Log::info("Realtime sync started for integration {$integration->id}");
    }

    /**
     * Schedule periodic sync
     */
    public function schedulePeriodicSync(IntegrationSetting $integration, string $frequency, array $options = []): SyncSchedule
    {
        $schedule = SyncSchedule::create([
            'integration_id' => $integration->id,
            'frequency' => $frequency, // hourly, daily, weekly, monthly
            'is_active' => true,
            'configuration' => array_merge([
                'sync_invoices' => true,
                'sync_customers' => true,
                'sync_items' => false,
                'conflict_resolution' => 'vendor_wins',
                'batch_size' => 100
            ], $options),
            'next_run_at' => $this->calculateNextRun($frequency),
            'created_by' => auth()->id()
        ]);

        Log::info("Periodic sync scheduled for integration {$integration->id} with frequency {$frequency}");
        return $schedule;
    }

    /**
     * Process sync job queue
     */
    public function processSyncQueue(): void
    {
        $runningJobs = SyncJob::where('status', 'running')->count();

        if ($runningJobs >= $this->maxConcurrentJobs) {
            Log::info("Max concurrent jobs reached ({$this->maxConcurrentJobs}). Waiting...");
            return;
        }

        $availableSlots = $this->maxConcurrentJobs - $runningJobs;

        $queuedJobs = SyncJob::where('status', 'queued')
            ->where('scheduled_at', '<=', now())
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_at', 'asc')
            ->limit($availableSlots)
            ->get();

        foreach ($queuedJobs as $job) {
            $this->processSyncJob($job);
        }
    }

    /**
     * Process individual sync job
     */
    public function processSyncJob(SyncJob $syncJob): void
    {
        try {
            $syncJob->update([
                'status' => 'running',
                'started_at' => now(),
                'worker_id' => gethostname() . '_' . getmypid()
            ]);

            $integration = $syncJob->integration;
            $connector = VendorConnectorFactory::create($integration->vendor);

            $startTime = microtime(true);

            // Execute sync based on configuration
            $this->executeSyncJob($connector, $integration, $syncJob);

            $executionTime = microtime(true) - $startTime;

            $syncJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'execution_time' => $executionTime,
                'records_processed' => $syncJob->records_processed ?? 0,
                'records_synced' => $syncJob->records_synced ?? 0,
                'records_failed' => $syncJob->records_failed ?? 0
            ]);

            Log::info("Sync job {$syncJob->id} completed in {$executionTime}s");

        } catch (Exception $e) {
            $this->handleSyncJobFailure($syncJob, $e);
        }
    }

    /**
     * Execute sync job based on configuration
     */
    private function executeSyncJob($connector, IntegrationSetting $integration, SyncJob $syncJob): void
    {
        $config = $syncJob->configuration ?? [];
        $stats = ['processed' => 0, 'synced' => 0, 'failed' => 0];

        if ($config['sync_invoices'] ?? true) {
            $result = $this->syncInvoices($connector, $integration, $config);
            $stats['processed'] += $result['processed'];
            $stats['synced'] += $result['synced'];
            $stats['failed'] += $result['failed'];
        }

        if ($config['sync_customers'] ?? true) {
            $result = $this->syncCustomers($connector, $integration, $config);
            $stats['processed'] += $result['processed'];
            $stats['synced'] += $result['synced'];
            $stats['failed'] += $result['failed'];
        }

        if ($config['sync_items'] ?? false) {
            $result = $this->syncItems($connector, $integration, $config);
            $stats['processed'] += $result['processed'];
            $stats['synced'] += $result['synced'];
            $stats['failed'] += $result['failed'];
        }

        $syncJob->update([
            'records_processed' => $stats['processed'],
            'records_synced' => $stats['synced'],
            'records_failed' => $stats['failed']
        ]);
    }

    /**
     * Sync invoices with advanced features
     */
    private function syncInvoices($connector, IntegrationSetting $integration, array $config): array
    {
        $stats = ['processed' => 0, 'synced' => 0, 'failed' => 0];
        $batchSize = $config['batch_size'] ?? 100;
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            try {
                $filters = [
                    'page' => $page,
                    'limit' => $batchSize
                ];

                // Add date filters if specified
                if (isset($config['date_from'])) {
                    $filters['date_from'] = $config['date_from'];
                }
                if (isset($config['date_to'])) {
                    $filters['date_to'] = $config['date_to'];
                }

                $invoices = $connector->fetchInvoices($integration, $filters);

                if ($invoices->isEmpty()) {
                    $hasMore = false;
                    break;
                }

                foreach ($invoices as $invoiceData) {
                    $stats['processed']++;

                    try {
                        $this->processInvoiceRecord($invoiceData, $integration, $config);
                        $stats['synced']++;
                    } catch (Exception $e) {
                        $stats['failed']++;
                        Log::error("Failed to sync invoice: " . $e->getMessage(), [
                            'invoice_number' => $invoiceData['invoice_number'] ?? 'unknown',
                            'integration_id' => $integration->id
                        ]);
                    }
                }

                $page++;

                // Respect rate limits
                $this->enforceRateLimit($connector, $integration);

                // Safety check
                if ($page > 1000) {
                    Log::warning("Sync page limit reached for integration {$integration->id}");
                    break;
                }

            } catch (Exception $e) {
                Log::error("Batch sync failed for integration {$integration->id}: " . $e->getMessage());
                $hasMore = false;
            }
        }

        return $stats;
    }

    /**
     * Sync customers with advanced features
     */
    private function syncCustomers($connector, IntegrationSetting $integration, array $config): array
    {
        $stats = ['processed' => 0, 'synced' => 0, 'failed' => 0];
        $batchSize = $config['batch_size'] ?? 100;
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            try {
                $customers = $connector->fetchCustomers($integration, [
                    'page' => $page,
                    'limit' => $batchSize
                ]);

                if ($customers->isEmpty()) {
                    $hasMore = false;
                    break;
                }

                foreach ($customers as $customerData) {
                    $stats['processed']++;

                    try {
                        $this->processCustomerRecord($customerData, $integration, $config);
                        $stats['synced']++;
                    } catch (Exception $e) {
                        $stats['failed']++;
                        Log::error("Failed to sync customer: " . $e->getMessage(), [
                            'customer_name' => $customerData['name'] ?? 'unknown',
                            'integration_id' => $integration->id
                        ]);
                    }
                }

                $page++;
                $this->enforceRateLimit($connector, $integration);

                if ($page > 1000) {
                    Log::warning("Customer sync page limit reached for integration {$integration->id}");
                    break;
                }

            } catch (Exception $e) {
                Log::error("Customer batch sync failed for integration {$integration->id}: " . $e->getMessage());
                $hasMore = false;
            }
        }

        return $stats;
    }

    /**
     * Sync items/products
     */
    private function syncItems($connector, IntegrationSetting $integration, array $config): array
    {
        // Implementation for syncing items/products
        return ['processed' => 0, 'synced' => 0, 'failed' => 0];
    }

    /**
     * Process individual invoice record
     */
    private function processInvoiceRecord(array $invoiceData, IntegrationSetting $integration, array $config): void
    {
        // Implementation would depend on your local Invoice model
        // This is a placeholder showing the structure

        $externalId = $invoiceData['external_id'] ?? $invoiceData['id'] ?? null;
        if (!$externalId) {
            throw new Exception("No external ID found for invoice");
        }

        // Check for existing record
        $existingInvoice = null; // Invoice::where('external_id', $externalId)->first();

        if ($existingInvoice) {
            // Handle conflict resolution
            $this->handleInvoiceConflict($existingInvoice, $invoiceData, $config);
        } else {
            // Create new invoice
            $this->createInvoiceRecord($invoiceData, $integration);
        }
    }

    /**
     * Process individual customer record
     */
    private function processCustomerRecord(array $customerData, IntegrationSetting $integration, array $config): void
    {
        // Similar to processInvoiceRecord but for customers
        $externalId = $customerData['external_id'] ?? $customerData['id'] ?? null;
        if (!$externalId) {
            throw new Exception("No external ID found for customer");
        }

        // Implementation would depend on your local Customer model
    }

    /**
     * Handle invoice conflicts
     */
    private function handleInvoiceConflict($existingInvoice, array $newData, array $config): void
    {
        $strategy = $config['conflict_resolution'] ?? 'vendor_wins';

        switch ($strategy) {
            case 'vendor_wins':
                // Update with vendor data
                break;
            case 'local_wins':
                // Keep local data
                break;
            case 'merge':
                // Merge data intelligently
                break;
            case 'manual':
                // Queue for manual review
                break;
        }
    }

    /**
     * Create invoice record
     */
    private function createInvoiceRecord(array $invoiceData, IntegrationSetting $integration): void
    {
        // Implementation would create Invoice model instance
        // This is a placeholder
    }

    /**
     * Enforce rate limits
     */
    private function enforceRateLimit($connector, IntegrationSetting $integration): void
    {
        $rateLimit = $connector->getRateLimit();
        $cacheKey = "rate_limit_{$integration->id}";

        $requests = Cache::get($cacheKey, 0);

        if ($requests >= $rateLimit['requests_per_minute']) {
            Log::info("Rate limit reached for integration {$integration->id}. Waiting...");
            sleep(60); // Wait for rate limit to reset
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, $requests + 1, 60); // 1 minute TTL
        }
    }

    /**
     * Handle sync job failure
     */
    private function handleSyncJobFailure(SyncJob $syncJob, Exception $e): void
    {
        $syncJob->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $e->getMessage(),
            'retry_count' => ($syncJob->retry_count ?? 0) + 1
        ]);

        Log::error("Sync job {$syncJob->id} failed: " . $e->getMessage());

        // Schedule retry if within retry limit
        if (($syncJob->retry_count ?? 0) < $this->retryAttempts) {
            $retryJob = SyncJob::create([
                'integration_id' => $syncJob->integration_id,
                'type' => $syncJob->type,
                'status' => 'queued',
                'priority' => $syncJob->priority,
                'configuration' => $syncJob->configuration,
                'scheduled_at' => now()->addSeconds($this->retryDelay),
                'parent_job_id' => $syncJob->id,
                'retry_count' => $syncJob->retry_count + 1
            ]);

            Log::info("Retry scheduled for sync job {$syncJob->id} as job {$retryJob->id}");
        }
    }

    /**
     * Calculate next run time for scheduled sync
     */
    private function calculateNextRun(string $frequency): Carbon
    {
        switch ($frequency) {
            case 'hourly':
                return now()->addHour();
            case 'daily':
                return now()->addDay();
            case 'weekly':
                return now()->addWeek();
            case 'monthly':
                return now()->addMonth();
            default:
                return now()->addHour();
        }
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats(IntegrationSetting $integration, int $days = 30): array
    {
        $stats = SyncJob::where('integration_id', $integration->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_jobs,
                SUM(records_processed) as total_records_processed,
                SUM(records_synced) as total_records_synced,
                SUM(records_failed) as total_records_failed,
                AVG(execution_time) as avg_execution_time
            ')
            ->first();

        return [
            'total_jobs' => $stats->total_jobs ?? 0,
            'completed_jobs' => $stats->completed_jobs ?? 0,
            'failed_jobs' => $stats->failed_jobs ?? 0,
            'success_rate' => $stats->total_jobs > 0 ? ($stats->completed_jobs / $stats->total_jobs * 100) : 0,
            'total_records_processed' => $stats->total_records_processed ?? 0,
            'total_records_synced' => $stats->total_records_synced ?? 0,
            'total_records_failed' => $stats->total_records_failed ?? 0,
            'avg_execution_time' => round($stats->avg_execution_time ?? 0, 2)
        ];
    }
}
