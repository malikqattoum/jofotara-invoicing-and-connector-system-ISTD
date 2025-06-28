<?php

namespace Database\Factories;

use App\Models\DataSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataSourceFactory extends Factory
{
    protected $model = DataSource::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['database', 'api', 'file']);

        $configurations = [
            'database' => [
                'host' => $this->faker->ipv4(),
                'port' => 5432,
                'database' => $this->faker->word(),
                'username' => $this->faker->userName(),
                'password' => $this->faker->password()
            ],
            'api' => [
                'base_url' => $this->faker->url(),
                'api_key' => $this->faker->sha256(),
                'timeout' => 30,
                'rate_limit' => 1000
            ],
            'file' => [
                'path' => '/data/' . $this->faker->word() . '.csv',
                'format' => $this->faker->randomElement(['csv', 'json', 'xml']),
                'delimiter' => ',',
                'encoding' => 'UTF-8'
            ]
        ];

        return [
            'name' => $this->faker->company() . ' Data Source',
            'type' => $type,
            'connection_name' => $this->faker->slug(2),
            'configuration' => $configurations[$type],
            'is_active' => $this->faker->boolean(80),
            'created_by' => User::factory(),
        ];
    }

    public function database()
    {
        return $this->state([
            'type' => 'database',
            'configuration' => [
                'host' => 'localhost',
                'port' => 5432,
                'database' => 'invoicing_db',
                'username' => 'db_user',
                'password' => 'secret'
            ]
        ]);
    }

    public function api()
    {
        return $this->state([
            'type' => 'api',
            'configuration' => [
                'base_url' => 'https://api.example.com',
                'api_key' => 'api_key_123',
                'timeout' => 30
            ]
        ]);
    }

    public function file()
    {
        return $this->state([
            'type' => 'file',
            'configuration' => [
                'path' => '/data/invoices.csv',
                'format' => 'csv',
                'delimiter' => ','
            ]
        ]);
    }
}
