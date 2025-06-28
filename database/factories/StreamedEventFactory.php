<?php

namespace Database\Factories;

use App\Models\StreamedEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class StreamedEventFactory extends Factory
{
    protected $model = StreamedEvent::class;

    public function definition(): array
    {
        return [
            'stream_name' => $this->faker->slug(2),
            'event_type' => $this->faker->randomElement([
                'invoice.created', 'invoice.updated', 'invoice.submitted',
                'payment.received', 'user.login', 'system.alert'
            ]),
            'event_data' => [
                'id' => $this->faker->numberBetween(1, 1000),
                'timestamp' => now()->toISOString(),
                'user_id' => $this->faker->numberBetween(1, 100),
                'details' => $this->faker->sentence()
            ],
            'event_version' => $this->faker->randomElement(['1.0', '1.1', '2.0']),
            'correlation_id' => $this->faker->uuid(),
            'partition_key' => $this->faker->randomElement(['user', 'invoice', 'payment']),
            'occurred_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function forStream(string $streamName)
    {
        return $this->state(['stream_name' => $streamName]);
    }

    public function invoiceEvent()
    {
        return $this->state([
            'event_type' => 'invoice.created',
            'event_data' => [
                'invoice_id' => $this->faker->numberBetween(1, 1000),
                'amount' => $this->faker->randomFloat(2, 100, 5000),
                'customer' => $this->faker->name()
            ]
        ]);
    }
}
