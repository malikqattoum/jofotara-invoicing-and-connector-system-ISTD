<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\DataAnomaly;
use App\Models\User;
use App\Models\IntegrationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class DataAnomalyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function data_anomaly_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('data_anomalies'));

        $expectedColumns = [
            'id', 'type', 'description', 'severity', 'is_resolved', 'resolved_by', 'resolution_notes', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('data_anomalies', $column),
                "DataAnomaly table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function data_anomaly_belongs_to_integration_setting()
    {
        $integration = IntegrationSetting::factory()->create();
        $anomaly = DataAnomaly::factory()->create(['integration_id' => $integration->id]);

        $this->assertInstanceOf(IntegrationSetting::class, $anomaly->integration);
        $this->assertEquals($integration->id, $anomaly->integration->id);
    }

    /** @test */
    public function data_anomaly_can_be_resolved()
    {
        $user = User::factory()->create();
        $anomaly = DataAnomaly::factory()->create();

        $anomaly->resolve($user, 'Test resolution notes');

        $this->assertTrue($anomaly->fresh()->is_resolved);
        $this->assertEquals($user->id, $anomaly->fresh()->resolved_by);
        $this->assertEquals('Test resolution notes', $anomaly->fresh()->resolution_notes);
    }

    /** @test */
    public function data_anomaly_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'type', 'description', 'severity', 'is_resolved', 'resolved_by', 'resolution_notes'
        ];

        $anomaly = new DataAnomaly();
        $actualFillable = $anomaly->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function data_anomaly_can_check_critical_status()
    {
        $criticalAnomaly = DataAnomaly::factory()->create(['severity' => 'critical']);
        $this->assertTrue($criticalAnomaly->isCritical());

        $nonCriticalAnomaly = DataAnomaly::factory()->create(['severity' => 'low']);
        $this->assertFalse($nonCriticalAnomaly->isCritical());
    }
}
