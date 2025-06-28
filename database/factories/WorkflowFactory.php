<?php

namespace Database\Factories;

use App\Models\Workflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'trigger_event' => $this->faker->randomElement(['invoice.created', 'invoice.updated', 'payment.received']),
            'trigger_conditions' => [
                'amount' => ['>', $this->faker->numberBetween(100, 1000)]
            ],
            'is_active' => $this->faker->boolean(80),
            'created_by' => User::factory(),
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
