<?php

namespace Database\Factories;

use App\Models\SyncLog;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class SyncLogFactory extends Factory
{
    protected $model = SyncLog::class;

    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $completedAt = $this->faker->boolean(80) ?
            $this->faker->dateTimeBetween($startedAt, 'now') : null;

        return [
            'invoice_id' => Invoice::factory(),
            'sync_type' => $this->faker->randomElement(['import', 'export', 'update', 'validation']),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'result_message' => $completedAt ? $this->faker->sentence() : null,
            'error_message' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
            'error_details' => $this->faker->boolean(20) ? [
                'code' => $this->faker->numberBetween(400, 500),
                'details' => $this->faker->sentence()
            ] : null,
            'metadata' => [
                'batch_id' => $this->faker->uuid(),
                'source' => $this->faker->randomElement(['api', 'csv', 'manual'])
            ],
        ];
    }

    public function completed()
    {
        return $this->state([
            'status' => 'completed',
            'completed_at' => now(),
            'result_message' => 'Sync completed successfully'
        ]);
    }

    public function failed()
    {
        return $this->state([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => 'Sync operation failed'
        ]);
    }

    public function pending()
    {
        return $this->state([
            'status' => 'pending',
            'completed_at' => null
        ]);
    }

    public function ofType(string $type)
    {
        return $this->state(['sync_type' => $type]);
    }

    public function recent()
    {
        return $this->state(['created_at' => now()->subHours(rand(1, 24))]);
    }
}
