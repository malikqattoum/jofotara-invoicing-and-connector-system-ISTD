<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FixInvoiceAssociations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:fix-associations {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix invoice associations with users and organizations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Analyzing invoice associations...');

        $dryRun = $this->option('dry-run');

        // Check invoices without vendor_id
        $orphanedInvoices = Invoice::whereNull('vendor_id')->count();
        $this->info("📊 Found {$orphanedInvoices} invoices without vendor_id");

        // Check invoices without organization_id
        $noOrgInvoices = Invoice::whereNull('organization_id')->count();
        $this->info("📊 Found {$noOrgInvoices} invoices without organization_id");

        // Check users and their invoice counts
        $users = User::withCount('invoices')->get();
        $this->info("👥 Found {$users->count()} users in the system");

        $this->table(
            ['User ID', 'Email', 'Organization ID', 'Invoice Count'],
            $users->map(function($user) {
                return [
                    $user->id,
                    $user->email,
                    $user->organization_id ?? 'NULL',
                    $user->invoices_count
                ];
            })->toArray()
        );

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');

            // Show what would be fixed
            if ($orphanedInvoices > 0) {
                $this->info("Would assign {$orphanedInvoices} orphaned invoices to users");
            }

            if ($noOrgInvoices > 0) {
                $this->info("Would set organization_id for {$noOrgInvoices} invoices");
            }

            return;
        }

        if ($orphanedInvoices > 0 || $noOrgInvoices > 0) {
            if (!$this->confirm('Do you want to proceed with fixing these associations?')) {
                $this->info('Operation cancelled.');
                return;
            }

            $this->info('🔧 Fixing invoice associations...');

            // Fix orphaned invoices
            if ($orphanedInvoices > 0) {
                $firstUser = User::first();
                if ($firstUser) {
                    Invoice::whereNull('vendor_id')->update(['vendor_id' => $firstUser->id]);
                    $this->info("✅ Assigned {$orphanedInvoices} orphaned invoices to user: {$firstUser->email}");
                }
            }

            // Fix missing organization_id
            if ($noOrgInvoices > 0) {
                DB::statement("
                    UPDATE invoices
                    SET organization_id = COALESCE(
                        (SELECT organization_id FROM users WHERE users.id = invoices.vendor_id),
                        1
                    )
                    WHERE organization_id IS NULL
                    AND vendor_id IS NOT NULL
                ");
                $this->info("✅ Fixed organization_id for invoices");
            }

            $this->info('🎉 Invoice associations have been fixed!');
        } else {
            $this->info('✅ All invoice associations are already correct!');
        }

        // Final summary
        $totalInvoices = Invoice::count();
        $properlyAssigned = Invoice::whereNotNull('vendor_id')->whereNotNull('organization_id')->count();

        $this->info("📈 Summary: {$properlyAssigned}/{$totalInvoices} invoices are properly associated");
    }
}
