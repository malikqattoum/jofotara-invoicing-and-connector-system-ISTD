<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\SystemAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SystemAlertTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function system_alert_can_be_created()
    {
        $alertData = [
            'title' => 'System Maintenance',
            'message' => 'System will be under maintenance',
            'severity' => 'warning',
            'category' => 'system',
            'is_active' => true,
            'metadata' => ['duration' => '2 hours'],
        ];

        $alert = SystemAlert::create($alertData);

        $this->assertInstanceOf(SystemAlert::class, $alert);
        $this->assertEquals('System Maintenance', $alert->title);
        $this->assertEquals('warning', $alert->severity);
        $this->assertTrue($alert->is_active);
    }

    /** @test */
    public function system_alert_can_be_acknowledged()
    {
        $user = User::factory()->create();
        $alert = SystemAlert::factory()->create();

        $alert->acknowledge($user->id);

        $this->assertFalse($alert->fresh()->is_active);
        $this->assertEquals($user->id, $alert->fresh()->acknowledged_by);
        $this->assertNotNull($alert->fresh()->acknowledged_at);
    }

    /** @test */
    public function system_alert_can_be_resolved()
    {
        $user = User::factory()->create();
        $alert = SystemAlert::factory()->create();

        $alert->resolve($user->id, 'Issue fixed');

        $this->assertEquals('resolved', $alert->fresh()->status);
        $this->assertEquals($user->id, $alert->fresh()->resolved_by);
        $this->assertEquals('Issue fixed', $alert->fresh()->resolution_notes);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    /** @test */
    public function system_alert_scopes_work_correctly()
    {
        SystemAlert::factory()->create(['severity' => 'critical']);
        SystemAlert::factory()->create(['severity' => 'warning']);
        SystemAlert::factory()->create(['severity' => 'info']);

        $criticalAlerts = SystemAlert::critical()->get();
        $this->assertCount(1, $criticalAlerts);

        SystemAlert::factory()->create(['is_active' => true]);
        SystemAlert::factory()->create(['is_active' => false]);

        $activeAlerts = SystemAlert::active()->get();
        $this->assertCount(4, $activeAlerts); // Previous 3 + 1 new active
    }

    /** @test */
    public function system_alert_has_correct_fillable_fields()
    {
        $expectedFillable = [
            'title',
            'message',
            'severity',
            'category',
            'is_active',
            'metadata',
            'acknowledged_by',
            'acknowledged_at',
            'resolved_by',
            'resolved_at',
            'resolution_notes',
            'status'
        ];

        $alert = new SystemAlert();
        $actualFillable = $alert->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function system_alert_casts_fields_correctly()
    {
        $alert = SystemAlert::factory()->create([
            'metadata' => ['key' => 'value'],
            'is_active' => true,
            'acknowledged_at' => now(),
            'resolved_at' => now()
        ]);

        $this->assertIsArray($alert->metadata);
        $this->assertIsBool($alert->is_active);
        $this->assertInstanceOf(\Carbon\Carbon::class, $alert->acknowledged_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $alert->resolved_at);
    }
}
