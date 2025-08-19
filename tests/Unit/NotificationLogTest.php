<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function notification_log_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('notification_logs'));

        $expectedColumns = [
            'id', 'rule_id', 'event_type', 'payload', 'status', 'delivery_attempts',
            'sent_at', 'error_message', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('notification_logs', $column),
                "NotificationLog table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function notification_log_belongs_to_rule()
    {
        $rule = NotificationRule::factory()->create();
        $log = NotificationLog::factory()->create(['rule_id' => $rule->id]);

        $this->assertInstanceOf(NotificationRule::class, $log->rule);
        $this->assertEquals($rule->id, $log->rule->id);
    }

    /** @test */
    public function notification_log_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'rule_id', 'event_type', 'payload', 'status', 'delivery_attempts',
            'sent_at', 'error_message'
        ];

        $log = new NotificationLog();
        $actualFillable = $log->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function notification_log_can_check_delivery_status()
    {
        $deliveredLog = NotificationLog::factory()->create(['status' => 'delivered']);
        $this->assertTrue($deliveredLog->isDelivered());
        $this->assertFalse($deliveredLog->isFailed());
        $this->assertFalse($deliveredLog->isPending());

        $failedLog = NotificationLog::factory()->create(['status' => 'failed']);
        $this->assertFalse($failedLog->isDelivered());
        $this->assertTrue($failedLog->isFailed());
        $this->assertFalse($failedLog->isPending());

        $pendingLog = NotificationLog::factory()->create(['status' => 'pending']);
        $this->assertFalse($pendingLog->isDelivered());
        $this->assertFalse($pendingLog->isFailed());
        $this->assertTrue($pendingLog->isPending());
    }

    /** @test */
    public function notification_log_scopes_work_correctly()
    {
        NotificationLog::factory()->count(2)->create(['status' => 'delivered']);
        NotificationLog::factory()->count(3)->create(['status' => 'failed']);
        NotificationLog::factory()->count(4)->create(['status' => 'pending']);

        $this->assertCount(2, NotificationLog::where('status', 'delivered')->get());
        $this->assertCount(3, NotificationLog::where('status', 'failed')->get());
        $this->assertCount(4, NotificationLog::where('status', 'pending')->get());
    }
}
