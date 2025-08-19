<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PerformanceMetric;
use App\Models\IntegrationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class PerformanceMetricTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function performance_metric_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('performance_metrics'));

        $expectedColumns = [
            'id', 'metric_name', 'value', 'unit', 'category', 'metadata',
            'recorded_at', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('performance_metrics', $column),
                "PerformanceMetric table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function performance_metric_belongs_to_integration()
    {
        $integration = IntegrationSetting::factory()->create();
        $metric = PerformanceMetric::factory()->create(['integration_id' => $integration->id]);

        $this->assertInstanceOf(IntegrationSetting::class, $metric->integration);
        $this->assertEquals($integration->id, $metric->integration->id);
    }

    /** @test */
    public function performance_metric_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'metric_name', 'value', 'unit', 'category', 'metadata', 'recorded_at'
        ];

        $metric = new PerformanceMetric();
        $actualFillable = $metric->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function performance_metric_scopes_work_correctly()
    {
        PerformanceMetric::factory()->create(['category' => 'sync']);
        PerformanceMetric::factory()->create(['category' => 'api']);

        $this->assertCount(1, PerformanceMetric::forCategory('sync')->get());
        $this->assertCount(1, PerformanceMetric::forCategory('api')->get());

        $todayMetrics = PerformanceMetric::today()->get();
        $this->assertCount(2, $todayMetrics);

        $recentMetrics = PerformanceMetric::recent(1)->get();
        $this->assertCount(2, $recentMetrics);
    }

    /** @test */
    public function performance_metric_can_filter_by_metric_name()
    {
        PerformanceMetric::factory()->create(['metric_name' => 'sync_duration']);
        PerformanceMetric::factory()->create(['metric_name' => 'api_latency']);

        $this->assertCount(1, PerformanceMetric::byMetricName('sync_duration')->get());
        $this->assertCount(1, PerformanceMetric::byMetricName('api_latency')->get());
    }
}
