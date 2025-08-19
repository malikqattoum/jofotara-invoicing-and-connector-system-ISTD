<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class SecurityEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function security_event_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('security_events'));

        $expectedColumns = [
            'id', 'type', 'severity', 'description', 'user_id', 'ip_address',
            'event', 'context', 'detected_at', 'resolved_at', 'status', 'resolved_by'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('security_events', $column),
                "SecurityEvent table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function security_event_belongs_to_user()
    {
        $user = User::factory()->create();
        $event = SecurityEvent::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
    }

    /** @test */
    public function security_event_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'type', 'severity', 'description', 'user_id', 'ip_address',
            'event', 'context', 'detected_at', 'resolved_at', 'status', 'resolved_by'
        ];

        $event = new SecurityEvent();
        $actualFillable = $event->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function security_event_can_check_critical_status()
    {
        $criticalEvent = SecurityEvent::factory()->create(['severity' => 'critical']);
        $this->assertTrue($criticalEvent->isCritical());

        $nonCriticalEvent = SecurityEvent::factory()->create(['severity' => 'low']);
        $this->assertFalse($nonCriticalEvent->isCritical());
    }

    /** @test */
    public function security_event_can_be_resolved()
    {
        $user = User::factory()->create();
        $event = SecurityEvent::factory()->create(['status' => 'active']);

        $event->resolve($user);

        $this->assertFalse($event->fresh()->isActive());
        $this->assertEquals($user->id, $event->fresh()->resolved_by);
        $this->assertNotNull($event->fresh()->resolved_at);
    }
}
