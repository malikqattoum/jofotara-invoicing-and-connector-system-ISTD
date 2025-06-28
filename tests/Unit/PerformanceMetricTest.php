<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PerformanceMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PerformanceMetricTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function performance_metric_can_be_created()
    {
        $metricData = [
            'metric_name' => 'response_time',
            'value' => 125.5,
            'unit' => 'ms',
            'category' => 'api',
            'metadata' => ['endpoint' => '/api/invoices'],
            'recorded_at' => now(),
        ];

        $metric = PerformanceMetric::create($metricData);

        $this->assertInstanceOf(PerformanceMetric::class, $metric);
        $this->assertEquals('response_time', $metric->metric_name);
        $this->assertEquals(125.5, $metric->value);
        $this->assertEquals('ms', $metric->unit);
    }

    /** @test */
    public function performance_metric_can_calculate_averages()
    {
        // Create multiple metrics for the same metric name
        PerformanceMetric::factory()->create([
            'metric_name' => 'response_time',
            'value' => 100,
            'recorded_at' => now()->subMinutes(5)
        ]);

        PerformanceMetric::factory()->create([
            'metric_name' => 'response_time',
            'value' => 200,
            'recorded_at' => now()->subMinutes(10)
        ]);

        PerformanceMetric::factory()->create([
            'metric_name' => 'response_time',
            'value' => 150,
            'recorded_at' => now()->subMinutes(15)
        ]);

        $average = PerformanceMetric::where('metric_name', 'response_time')->avg('value');

        $this->assertEquals(150, $average);
    }

    /** @test */
    public function performance_metric_scopes_work_correctly()
    {
        // Create metrics for different categories
        PerformanceMetric::factory()->create(['category' => 'api']);
        PerformanceMetric::factory()->create(['category' => 'database']);
        PerformanceMetric::factory()->create(['category' => 'api']);

        $apiMetrics = PerformanceMetric::forCategory('api')->get();
        $this->assertCount(2, $apiMetrics);

        // Test time-based scopes
        PerformanceMetric::factory()->create([
            'recorded_at' => now()->subHours(2)
        ]);

        PerformanceMetric::factory()->create([
            'recorded_at' => now()->subDays(2)
        ]);

        $todayMetrics = PerformanceMetric::today()->get();
        $this->assertCount(4, $todayMetrics); // Previous 3 + 1 from today
    }

    /** @test */
    public function performance_metric_can_find_peaks()
    {
        // Create metrics with different values
        $highMetric = PerformanceMetric::factory()->create([
            'metric_name' => 'cpu_usage',
            'value' => 95.0
        ]);

        PerformanceMetric::factory()->create([
            'metric_name' => 'cpu_usage',
            'value' => 45.0
        ]);

        PerformanceMetric::factory()->create([
            'metric_name' => 'cpu_usage',
            'value' => 65.0
        ]);

        $maxValue = PerformanceMetric::where('metric_name', 'cpu_usage')->max('value');
        $this->assertEquals(95.0, $maxValue);
    }

    /** @test */
    public function performance_metric_metadata_is_cast_to_array()
    {
        $metric = PerformanceMetric::factory()->create([
            'metadata' => ['test_key' => 'test_value']
        ]);

        $this->assertIsArray($metric->metadata);
        $this->assertEquals(['test_key' => 'test_value'], $metric->metadata);
    }

    /** @test */
    public function performance_metric_recorded_at_is_cast_to_datetime()
    {
        $metric = PerformanceMetric::factory()->create([
            'recorded_at' => '2024-01-01 12:00:00'
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $metric->recorded_at);
    }

    /** @test */
    public function performance_metric_can_be_grouped_by_category()
    {
        PerformanceMetric::factory(2)->create(['category' => 'api']);
        PerformanceMetric::factory(3)->create(['category' => 'database']);
        PerformanceMetric::factory(1)->create(['category' => 'cache']);

        $grouped = PerformanceMetric::selectRaw('category, COUNT(*) as count')
                                   ->groupBy('category')
                                   ->pluck('count', 'category')
                                   ->toArray();

        $this->assertEquals(2, $grouped['api']);
        $this->assertEquals(3, $grouped['database']);
        $this->assertEquals(1, $grouped['cache']);
    }

    /** @test */
    public function performance_metric_can_track_trends()
    {
        // Create metrics over time to test trend analysis
        $baseTime = Carbon::parse('2024-01-01 00:00:00');

        for ($i = 0; $i < 24; $i++) {
            PerformanceMetric::factory()->create([
                'metric_name' => 'api_requests',
                'value' => 100 + ($i * 5), // Increasing trend
                'recorded_at' => $baseTime->copy()->addHours($i)
            ]);
        }

        $firstValue = PerformanceMetric::where('metric_name', 'api_requests')
                                      ->orderBy('recorded_at')
                                      ->first()
                                      ->value;

        $lastValue = PerformanceMetric::where('metric_name', 'api_requests')
                                     ->orderBy('recorded_at', 'desc')
                                     ->first()
                                     ->value;

        $this->assertEquals(100, $firstValue);
        $this->assertEquals(215, $lastValue); // 100 + (23 * 5)
    }
}
