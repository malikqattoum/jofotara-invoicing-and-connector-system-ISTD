<?php

namespace Database\Factories;

use App\Models\SystemAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemAlertFactory extends Factory
{
    protected $model = SystemAlert::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'message' => $this->faker->paragraph(),
            'severity' => $this->faker->randomElement(['info', 'warning', 'error', 'critical']),
            'category' => $this->faker->randomElement(['system', 'security', 'performance', 'integration']),
            'is_active' => $this->faker->boolean(70),
            'metadata' => [
                'source' => $this->faker->randomElement(['system', 'monitoring', 'user']),
                'component' => $this->faker->randomElement(['api', 'database', 'queue'])
            ],
            'acknowledged_by' => $this->faker->boolean(40) ? User::factory() : null,
            'acknowledged_at' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'resolved_by' => $this->faker->boolean(30) ? User::factory() : null,
            'resolved_at' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'resolution_notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            'status' => $this->faker->randomElement(['open', 'acknowledged', 'resolved']),
        ];
    }

    public function critical()
    {
        return $this->state(['severity' => 'critical']);
    }

    public function active()
    {
        return $this->state(['is_active' => true]);
    }

    public function resolved()
    {
        return $this->state([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => 'Issue has been resolved'
        ]);
    }
}
