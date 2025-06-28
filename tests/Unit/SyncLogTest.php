<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\SyncLog;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sync_log_can_be_created()
    {
        $invoice = Invoice::factory()->create();

        $syncData = [
            'invoice_id' => $invoice->id,
            'sync_type' => 'export',
            'status' => 'pending',
            'started_at' => now(),
            'metadata' => ['batch_id' => 'B001'],
        ];

        $syncLog = SyncLog::create($syncData);

        $this->assertInstanceOf(SyncLog::class, $syncLog);
        $this->assertEquals($invoice->id, $syncLog->invoice_id);
        $this->assertEquals('export', $syncLog->sync_type);
        $this->assertEquals('pending', $syncLog->status);
    }

    /** @test */
    public function sync_log_belongs_to_invoice()
    {
        $invoice = Invoice::factory()->create();
        $syncLog = SyncLog::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertInstanceOf(Invoice::class, $syncLog->invoice);
        $this->assertEquals($invoice->id, $syncLog->invoice->id);
    }

    /** @test */
    public function sync_log_can_be_marked_as_completed()
    {
        $syncLog = SyncLog::factory()->create(['status' => 'pending']);

        $syncLog->markAsCompleted('Sync completed successfully');

        $this->assertEquals('completed', $syncLog->status);
        $this->assertEquals('Sync completed successfully', $syncLog->result_message);
        $this->assertNotNull($syncLog->completed_at);
    }

    /** @test */
    public function sync_log_can_be_marked_as_failed()
    {
        $syncLog = SyncLog::factory()->create(['status' => 'pending']);

        $syncLog->markAsFailed('Connection timeout', ['error_code' => 500]);

        $this->assertEquals('failed', $syncLog->status);
        $this->assertEquals('Connection timeout', $syncLog->error_message);
        $this->assertEquals(['error_code' => 500], $syncLog->error_details);
        $this->assertNotNull($syncLog->completed_at);
    }

    /** @test */
    public function sync_log_calculates_duration_correctly()
    {
        $syncLog = SyncLog::factory()->create([
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()
        ]);

        $this->assertEquals(5, $syncLog->duration_minutes);
    }

    /** @test */
    public function sync_log_scopes_work_correctly()
    {
        SyncLog::factory()->create(['status' => 'completed']);
        SyncLog::factory()->create(['status' => 'failed']);
        SyncLog::factory()->create(['status' => 'pending']);

        $completedLogs = SyncLog::completed()->get();
        $failedLogs = SyncLog::failed()->get();
        $pendingLogs = SyncLog::pending()->get();

        $this->assertCount(1, $completedLogs);
        $this->assertCount(1, $failedLogs);
        $this->assertCount(1, $pendingLogs);
    }

    /** @test */
    public function sync_log_can_filter_by_sync_type()
    {
        SyncLog::factory()->create(['sync_type' => 'import']);
        SyncLog::factory()->create(['sync_type' => 'export']);
        SyncLog::factory()->create(['sync_type' => 'import']);

        $importLogs = SyncLog::ofType('import')->get();
        $exportLogs = SyncLog::ofType('export')->get();

        $this->assertCount(2, $importLogs);
        $this->assertCount(1, $exportLogs);
    }

    /** @test */
    public function sync_log_can_get_recent_logs()
    {
        SyncLog::factory()->create(['created_at' => now()->subDays(2)]);
        SyncLog::factory()->create(['created_at' => now()->subHours(1)]);
        SyncLog::factory()->create(['created_at' => now()]);

        $recentLogs = SyncLog::recent()->get();

        $this->assertCount(3, $recentLogs);
        // Should be ordered by most recent first
        $this->assertTrue($recentLogs->first()->created_at->isAfter($recentLogs->last()->created_at));
    }

    /** @test */
    public function sync_log_metadata_is_cast_to_array()
    {
        $syncLog = SyncLog::factory()->create([
            'metadata' => ['key1' => 'value1', 'key2' => 'value2']
        ]);

        $this->assertIsArray($syncLog->metadata);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $syncLog->metadata);
    }

    /** @test */
    public function sync_log_error_details_is_cast_to_array()
    {
        $syncLog = SyncLog::factory()->create([
            'error_details' => ['code' => 404, 'message' => 'Not found']
        ]);

        $this->assertIsArray($syncLog->error_details);
        $this->assertEquals(['code' => 404, 'message' => 'Not found'], $syncLog->error_details);
    }

    /** @test */
    public function sync_log_has_correct_fillable_fields()
    {
        $expectedFillable = [
            'invoice_id',
            'sync_type',
            'status',
            'started_at',
            'completed_at',
            'result_message',
            'error_message',
            'error_details',
            'metadata'
        ];

        $syncLog = new SyncLog();
        $actualFillable = $syncLog->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }
}
