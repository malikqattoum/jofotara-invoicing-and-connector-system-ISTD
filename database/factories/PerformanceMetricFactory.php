<?php

namespace Database\Factories;

use App\Models\PerformanceMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformanceMetricFactory extends Factory
{
    protected $model = PerformanceMetric::class;

    public function definition(): array
    {
        return [
            'metric_name' => $this->faker->randomElement([
                'response_time', 'memory_usage', 'cpu_usage', 'disk_io',
                'api_requests', 'database_queries', 'queue_size'
            ]),
            'value' => $this->faker->randomFloat(2, 0, 1000),
            'unit' => $this->faker->randomElement(['ms', '%', 'MB', 'GB', 'count', 'ops/sec']),
            'category' => $this->faker->randomElement(['api', 'database', 'system', 'cache', 'queue']),
            'metadata' => [
                'endpoint' => $this->faker->randomElement(['/api/invoices', '/api/users', '/api/reports']),
                'server' => $this->faker->randomElement(['web-01', 'web-02', 'api-01'])
            ],
            'recorded_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function forCategory(string $category)
    {
        return $this->state(['category' => $category]);
    }

    public function today()
    {
        return $this->state(['recorded_at' => today()]);
    }

    public function responseTime()
    {
        return $this->state([
            'metric_name' => 'response_time',
            'unit' => 'ms',
            'value' => $this->faker->numberBetween(50, 2000)
        ]);
    }
}
