<?php

namespace Database\Factories;

use App\Models\EventSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventSubscriptionFactory extends Factory
{
    protected $model = EventSubscription::class;

    public function definition(): array
    {
        return [
            'stream_name' => $this->faker->slug(2),
            'subscriber_name' => $this->faker->company() . ' Subscriber',
            'endpoint_url' => $this->faker->url(),
            'event_types' => $this->faker->randomElements([
                'invoice.created', 'invoice.updated', 'payment.received',
                'user.login', 'system.alert'
            ], $this->faker->numberBetween(1, 3)),
            'filters' => [
                'organization_id' => $this->faker->numberBetween(1, 10),
                'min_amount' => $this->faker->numberBetween(100, 1000)
            ],
            'status' => $this->faker->randomElement(['active', 'inactive', 'paused']),
            'retry_policy' => [
                'max_retries' => $this->faker->numberBetween(3, 10),
                'retry_delay' => $this->faker->numberBetween(30, 300)
            ],
            'created_by' => User::factory(),
        ];
    }

    public function active()
    {
        return $this->state(['status' => 'active']);
    }

    public function inactive()
    {
        return $this->state(['status' => 'inactive']);
    }

    public function forStream(string $streamName)
    {
        return $this->state(['stream_name' => $streamName]);
    }
}
