<?php

namespace Database\Seeders;

use App\Models\Invoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SimpleInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds to create basic invoice data for testing dashboard
     */
    public function run(): void
    {
        // Create some basic invoices for demo purposes
        for ($i = 1; $i <= 25; $i++) {
            Invoice::create([
                'organization_id' => 1,
                'invoice_number' => 'INV-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'invoice_date' => now()->subDays(rand(1, 60)),
                'customer_name' => 'Customer ' . $i,
                'customer_tax_number' => 'TAX' . str_pad($i, 9, '0', STR_PAD_LEFT),
                'total_amount' => rand(500, 5000),
                'tax_amount' => rand(80, 800),
                'status' => ['draft', 'submitted', 'rejected'][rand(0, 2)],
                'currency' => 'JOD',
                'uuid' => Str::uuid(),
            ]);
        }

        $this->command->info('Created basic invoice test data:');
        $this->command->info('- Total invoices: ' . Invoice::count());
        $this->command->info('- Submitted: ' . Invoice::where('status', 'submitted')->count());
        $this->command->info('- Draft: ' . Invoice::where('status', 'draft')->count());
        $this->command->info('- Rejected: ' . Invoice::where('status', 'rejected')->count());
        $this->command->info('- Total Revenue: $' . number_format(Invoice::where('status', 'submitted')->sum('total_amount'), 2));
    }
}
