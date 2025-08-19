<?php

namespace Database\Factories;

use App\Models\SecurityEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityEventFactory extends Factory
{
    protected $model = SecurityEvent::class;

    public function definition()
    {
        return [
            'event_type' => $this->faker->word,
            'description' => $this->faker->sentence,
            'severity' => $this->faker->randomElement(['low', 'medium', 'high']),
            'resolved' => $this->faker->boolean,
            'resolved_at' => $this->faker->optional()->dateTime(),
            'metadata' => json_encode($this->faker->optional()->randomElements()),
        ];
    }
}
