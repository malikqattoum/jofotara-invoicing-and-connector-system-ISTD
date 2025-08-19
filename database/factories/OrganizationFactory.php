<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Organization;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'tax_number' => fake()->numerify('TN######'),
            'logo_path' => fake()->optional()->filePath(),
            'status' => fake()->randomElement(['active', 'inactive', 'suspended']),
            'created_by' => fake()->numberBetween(1, 10),
            'updated_by' => fake()->numberBetween(1, 10),
        ];
    }
}
