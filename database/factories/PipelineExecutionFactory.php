<?php

namespace Database\Factories;

use App\Models\PipelineExecution;
use App\Models\DataPipeline;
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelineExecutionFactory extends Factory
{
    protected $model = PipelineExecution::class;

    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 week', 'now');
        $completedAt = $this->faker->boolean(85) ?
            $this->faker->dateTimeBetween($startedAt, 'now') : null;

        return [
            'data_pipeline_id' => DataPipeline::factory(),
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'records_processed' => $this->faker->numberBetween(0, 1000),
            'records_failed' => $this->faker->numberBetween(0, 50),
            'execution_log' => [
                'steps' => [
                    ['step' => 'extract', 'status' => 'completed', 'duration' => '2s'],
                    ['step' => 'transform', 'status' => 'completed', 'duration' => '5s'],
                    ['step' => 'load', 'status' => 'completed', 'duration' => '3s']
                ]
            ],
            'error_details' => $this->faker->boolean(20) ? [
                'error' => $this->faker->sentence(),
                'code' => $this->faker->numberBetween(400, 500)
            ] : null,
        ];
    }

    public function completed()
    {
        return $this->state([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function failed()
    {
        return $this->state([
            'status' => 'failed',
            'completed_at' => now(),
            'error_details' => [
                'error' => 'Pipeline execution failed',
                'code' => 500
            ]
        ]);
    }
}
