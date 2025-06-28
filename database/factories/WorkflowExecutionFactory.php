<?php

namespace Database\Factories;

use App\Models\WorkflowExecution;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowExecutionFactory extends Factory
{
    protected $model = WorkflowExecution::class;

    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $completedAt = $this->faker->boolean(80) ?
            $this->faker->dateTimeBetween($startedAt, 'now') : null;

        return [
            'workflow_id' => Workflow::factory(),
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'triggered_by' => $this->faker->randomElement(['user', 'system', 'schedule']),
            'input_data' => [
                'invoice_id' => $this->faker->numberBetween(1, 1000),
                'amount' => $this->faker->randomFloat(2, 100, 5000)
            ],
            'output_data' => $completedAt ? [
                'result' => $this->faker->randomElement(['success', 'partial', 'failed']),
                'message' => $this->faker->sentence()
            ] : null,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'error_message' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
        ];
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'completed_at' => now(),
                'output_data' => [
                    'result' => 'success',
                    'message' => 'Workflow completed successfully'
                ]
            ];
        });
    }

    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Workflow execution failed'
            ];
        });
    }
}
