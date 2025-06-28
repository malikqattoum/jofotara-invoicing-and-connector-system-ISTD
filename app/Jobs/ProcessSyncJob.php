<?php

namespace App\Jobs;

use App\Models\SyncJob;
use App\Services\SyncEngine\SyncEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour
    public $tries = 3;
    public $backoff = [60, 300, 600]; // 1min, 5min, 10min

    private $syncJob;

    public function __construct(SyncJob $syncJob)
    {
        $this->syncJob = $syncJob;
        $this->onQueue('sync-' . $syncJob->priority);
    }

    public function handle(SyncEngine $syncEngine): void
    {
        try {
            Log::info("Processing sync job {$this->syncJob->id}");
            $syncEngine->processSyncJob($this->syncJob);
        } catch (Exception $e) {
            Log::error("Sync job {$this->syncJob->id} failed: " . $e->getMessage());

            $this->syncJob->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("Sync job {$this->syncJob->id} permanently failed: " . $exception->getMessage());

        $this->syncJob->update([
            'status' => 'permanently_failed',
            'failed_at' => now(),
            'error_message' => $exception->getMessage()
        ]);
    }
}
