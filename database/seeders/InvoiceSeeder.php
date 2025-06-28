<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\IntegrationSetting;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds to create sample data for InvoiceQ-inspired dashboard
     */
    public function run(): void
    {
        // Skip integration settings creation for now
        // Focus on creating comprehensive invoice data for dashboard testing

        // 1. Recent successful invoices (this month)
        Invoice::factory(25)->submitted()->paid()->create([
            'created_at' => now()->subDays(rand(1, 30)),
            'invoice_date' => now()->subDays(rand(1, 30)),
        ]);

        // 2. Pending invoices (submitted but not paid)
        Invoice::factory(15)->submitted()->create([
            'payment_status' => 'pending',
            'created_at' => now()->subDays(rand(1, 45)),
        ]);

        // 3. Overdue invoices for testing collection features
        Invoice::factory(8)->overdue()->create();

        // 4. Rejected invoices for compliance tracking
        Invoice::factory(5)->rejected()->create([
            'created_at' => now()->subDays(rand(1, 60)),
        ]);

        // 5. High-value customers for top debtors analysis
        $highValueCustomers = [
            ['name' => 'Tech Solutions Ltd', 'tax' => 'TAX12345678901'],
            ['name' => 'Global Trading Co', 'tax' => 'TAX98765432109'],
            ['name' => 'Premium Services LLC', 'tax' => 'TAX11111111111'],
        ];

        foreach ($highValueCustomers as $customer) {
            // Create multiple invoices for each high-value customer
            Invoice::factory(rand(3, 7))
                ->forCustomer($customer['name'], $customer['tax'])
                ->highValue()
                ->submitted()
                ->create([
                    'created_at' => now()->subDays(rand(1, 180)),
                ]);

            // Some unpaid invoices for receivables analysis
            Invoice::factory(rand(1, 3))
                ->forCustomer($customer['name'], $customer['tax'])
                ->highValue()
                ->submitted()
                ->create([
                    'payment_status' => 'pending',
                    'created_at' => now()->subDays(rand(1, 60)),
                ]);
        }

        // 6. Draft invoices
        Invoice::factory(10)->create([
            'status' => 'draft',
            'created_at' => now()->subDays(rand(1, 7)),
        ]);

        // 7. Historical data for trend analysis (last 6 months)
        for ($month = 1; $month <= 6; $month++) {
            $monthStart = now()->subMonths($month)->startOfMonth();
            $monthEnd = now()->subMonths($month)->endOfMonth();

            // Simulate varying business volumes
            $invoiceCount = rand(15, 40);

            Invoice::factory($invoiceCount)->submitted()->create([
                'created_at' => fake()->dateTimeBetween($monthStart, $monthEnd),
                'invoice_date' => fake()->dateTimeBetween($monthStart, $monthEnd),
            ]);

            // Some paid invoices from historical data
            Invoice::factory(rand(10, 25))->paid()->create([
                'created_at' => fake()->dateTimeBetween($monthStart, $monthEnd),
                'invoice_date' => fake()->dateTimeBetween($monthStart, $monthEnd),
            ]);
        }

        // 8. Create some invoices with specific scenarios for testing analytics

        // Seasonal pattern simulation (higher volume in certain months)
        $seasonalMonths = [3, 6, 9, 12]; // Quarterly peaks
        foreach ($seasonalMonths as $month) {
            if ($month <= 12) {
                $seasonalStart = now()->month($month)->startOfMonth();
                $seasonalEnd = now()->month($month)->endOfMonth();

                // Higher volume for seasonal months
                Invoice::factory(rand(30, 50))->submitted()->create([
                    'created_at' => fake()->dateTimeBetween($seasonalStart, $seasonalEnd),
                ]);
            }
        }

        // 9. Create invoices with different integration types
        $integrationTypes = ['zatca', 'e_invoice', 'manual'];
        foreach ($integrationTypes as $type) {
            Invoice::factory(rand(5, 15))->create([
                'integration_type' => $type,
                'created_at' => now()->subDays(rand(1, 90)),
            ]);
        }

        // 10. Create some invoices with audit trails for compliance demonstration
        Invoice::factory(5)->create([
            'audit_trail' => [
                [
                    'action' => 'created',
                    'timestamp' => now()->subDays(2)->toISOString(),
                    'user' => 'john.doe@example.com',
                ],
                [
                    'action' => 'submitted',
                    'timestamp' => now()->subDays(1)->toISOString(),
                    'user' => 'john.doe@example.com',
                ],
                [
                    'action' => 'approved',
                    'timestamp' => now()->subHours(12)->toISOString(),
                    'user' => 'system',
                ],
            ],
            'status' => 'submitted',
            'compliance_status' => 'approved',
        ]);

        $this->command->info('Created comprehensive invoice test data:');
        $this->command->info('- Total invoices: ' . Invoice::count());
        $this->command->info('- Submitted: ' . Invoice::where('status', 'submitted')->count());
        $this->command->info('- Paid: ' . Invoice::where('payment_status', 'paid')->count());
        $this->command->info('- Overdue: ' . Invoice::overdue()->count());
        $this->command->info('- Rejected: ' . Invoice::where('status', 'rejected')->count());
        $this->command->info('- Total Revenue: $' . number_format(Invoice::submitted()->sum('total_amount'), 2));
    }
}
