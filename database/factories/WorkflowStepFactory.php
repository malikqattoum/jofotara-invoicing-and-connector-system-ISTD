<?php

namespace Database\Factories;

use App\Models\WorkflowStep;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowStepFactory extends Factory
{
    protected $model = WorkflowStep::class;

    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'name' => $this->faker->sentence(2),
            'type' => $this->faker->randomElement(['validation', 'transformation', 'notification', 'approval']),
            'configuration' => [
                'rules' => [$this->faker->word() => $this->faker->word()]
            ],
            'order' => $this->faker->numberBetween(1, 10),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
