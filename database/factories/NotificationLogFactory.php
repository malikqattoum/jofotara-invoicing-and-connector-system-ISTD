<?php

namespace Database\Factories;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition()
    {
        return [
            'notification_id' => $this->faker->uuid,
            'user_id' => $this->faker->randomNumber(),
            'channel' => $this->faker->randomElement(['email', 'sms', 'push']),
            'status' => $this->faker->randomElement(['sent', 'failed', 'pending']),
            'sent_at' => $this->faker->optional()->dateTime(),
            'error_message' => $this->faker->optional()->sentence(),
            'metadata' => json_encode($this->faker->optional()->randomElements()),
        ];
    }
}
