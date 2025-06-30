<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PosCustomer;

class PosCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample POS customers for testing
        $customers = [
            [
                'customer_id' => 'DEMO_001',
                'customer_name' => 'Mario\'s Pizza Restaurant',
                'business_type' => 'restaurant',
                'email' => 'mario@mariospizza.com',
                'phone' => '+1-555-PIZZA',
                'address' => '123 Pizza Street, Food City, FC 12345',
                'api_key' => 'demo_pizza_api_key_123',
                'sync_interval' => 300,
                'debug_mode' => true,
                'auto_start' => true,
                'support_contact' => '+1-800-JOFOTARA',
                'notes' => 'Demo restaurant customer for testing Universal POS Connector',
            ],
            [
                'customer_id' => 'DEMO_002',
                'customer_name' => 'Fashion Boutique Store',
                'business_type' => 'retail',
                'email' => 'manager@fashionboutique.com',
                'phone' => '+1-555-FASHION',
                'address' => '456 Style Avenue, Fashion District, FD 67890',
                'api_key' => 'demo_retail_api_key_456',
                'sync_interval' => 180,
                'debug_mode' => false,
                'auto_start' => true,
                'support_contact' => '+1-800-JOFOTARA',
                'notes' => 'Demo retail customer for testing Universal POS Connector',
            ],
            [
                'customer_id' => 'DEMO_003',
                'customer_name' => 'Downtown Medical Clinic',
                'business_type' => 'medical',
                'email' => 'billing@downtownmedical.com',
                'phone' => '+1-555-HEALTH',
                'address' => '789 Health Boulevard, Medical Center, MC 11111',
                'api_key' => 'demo_medical_api_key_789',
                'sync_interval' => 600,
                'debug_mode' => false,
                'auto_start' => true,
                'support_contact' => '+1-800-JOFOTARA',
                'notes' => 'Demo medical customer for testing Universal POS Connector',
            ],
        ];

        foreach ($customers as $customerData) {
            PosCustomer::create($customerData);
        }

        $this->command->info('Created ' . count($customers) . ' demo POS customers');
    }
}
