<?php

namespace Database\Factories;

use App\Models\DataPipeline;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataPipelineFactory extends Factory
{
    protected $model = DataPipeline::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'data_sources' => [$this->faker->randomElement(['api', 'csv', 'database', 'xml'])],
            'transformations' => [$this->faker->randomElement(['normalize', 'validate', 'enrich', 'filter'])],
            'validation_rules' => [
                'amount' => 'required|numeric|min:0',
                'email' => 'required|email'
            ],
            'destination' => [
                'type' => $this->faker->randomElement(['database', 'file', 'api']),
                'config' => ['table' => 'invoices']
            ],
            'schedule' => [
                'frequency' => $this->faker->randomElement(['hourly', 'daily', 'weekly']),
                'time' => $this->faker->time('H:i')
            ],
            'configuration' => [
                'batch_size' => $this->faker->numberBetween(10, 100),
                'timeout' => $this->faker->numberBetween(30, 300)
            ],
            'is_active' => $this->faker->boolean(80),
            'created_by' => User::factory(),
        ];
    }

    public function active()
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive()
    {
        return $this->state(['is_active' => false]);
    }
}
