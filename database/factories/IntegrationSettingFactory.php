<?php

namespace Database\Factories;

use App\Models\IntegrationSetting;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationSettingFactory extends Factory
{
    protected $model = IntegrationSetting::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'vendor_id' => fake()->uuid(),
            'client_id' => fake()->uuid(),
            'secret_key' => fake()->sha256(),
            'income_source_sequence' => fake()->numerify('INV-####'),
            'environment_url' => fake()->url(),
            'private_key_path' => fake()->filePath(),
            'public_cert_path' => fake()->filePath(),
            'vendor_name' => fake()->company(),
            'integration_type' => fake()->randomElement(['api', 'file', 'database']),
            'name' => fake()->word() . ' Integration',
            'settings' => [
                'api_key' => fake()->sha256(),
                'endpoint' => fake()->url(),
            ],
            'sync_frequency' => fake()->randomElement(['realtime', 'hourly', 'daily', 'weekly']),
            'auto_sync_enabled' => fake()->boolean(),
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
            'sync_status' => fake()->randomElement(['idle', 'syncing', 'error']),
            'last_sync_at' => fake()->dateTimeThisMonth(),
            'last_sync_started_at' => fake()->dateTimeThisMonth(),
            'last_tested_at' => fake()->dateTimeThisMonth(),
            'last_error' => fake()->sentence(),
        ];
    }
}
