<?php

namespace Database\Factories;

use App\Models\EventStream;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventStreamFactory extends Factory
{
    protected $model = EventStream::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->slug(2),
            'description' => $this->faker->sentence(),
            'retention_days' => $this->faker->numberBetween(7, 365),
            'max_events' => $this->faker->numberBetween(1000, 100000),
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'event_type' => ['type' => 'string'],
                    'data' => ['type' => 'object'],
                    'timestamp' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],
            'configuration' => [
                'compression' => $this->faker->randomElement(['gzip', 'lz4', 'none']),
                'batch_size' => $this->faker->numberBetween(10, 1000)
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
