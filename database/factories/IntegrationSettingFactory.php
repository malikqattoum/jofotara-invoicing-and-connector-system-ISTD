<?php

namespace Database\Factories;

use App\Models\IntegrationSetting;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationSettingFactory extends Factory
{
    protected $model = IntegrationSetting::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'client_id' => $this->faker->uuid(),
            'secret_key' => $this->faker->sha256(),
            'income_source_sequence' => $this->faker->numberBetween(1, 100),
            'environment_url' => $this->faker->url(),
            'private_key_path' => '/path/to/private.key',
            'public_cert_path' => '/path/to/public.cert',
        ];
    }
}
