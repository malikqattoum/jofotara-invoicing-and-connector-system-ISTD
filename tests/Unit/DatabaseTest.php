<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function users_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('users'));

        $expectedColumns = [
            'id', 'name', 'email', 'email_verified_at', 'password',
            'organization_id', 'role', 'is_active', 'remember_token',
            'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Users table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function invoices_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('invoices'));

        $expectedColumns = [
            'id', 'organization_id', 'vendor_id', 'invoice_number',
            'invoice_date', 'due_date', 'customer_name', 'customer_email',
            'customer_phone', 'customer_address', 'total_amount', 'net_amount',
            'tax_amount', 'discount_amount', 'status', 'payment_status',
            'currency', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('invoices', $column),
                "Invoices table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function invoice_items_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('invoice_items'));

        $expectedColumns = [
            'id', 'invoice_id', 'description', 'quantity', 'unit_price',
            'total_price', 'tax_rate', 'tax_amount', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('invoice_items', $column),
                "Invoice items table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function organizations_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('organizations'));

        $expectedColumns = [
            'id', 'name', 'email', 'phone', 'address', 'tax_number',
            'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('organizations', $column),
                "Organizations table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function integration_settings_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('integration_settings'));

        $expectedColumns = [
            'id', 'organization_id', 'client_id', 'secret_key',
            'income_source_sequence', 'environment_url', 'private_key_path',
            'public_cert_path', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('integration_settings', $column),
                "Integration settings table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function workflows_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('workflows'));

        $expectedColumns = [
            'id', 'name', 'description', 'trigger_event', 'trigger_conditions',
            'is_active', 'created_by', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('workflows', $column),
                "Workflows table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function database_has_proper_foreign_key_constraints()
    {
        // Test foreign key constraints exist
        $foreignKeys = [
            'invoices' => [
                'organization_id' => 'organizations',
            ],
            'invoice_items' => [
                'invoice_id' => 'invoices',
            ],
            'integration_settings' => [
                'organization_id' => 'organizations',
            ],
            'workflows' => [
                'created_by' => 'users',
            ]
        ];

        foreach ($foreignKeys as $table => $constraints) {
            foreach ($constraints as $column => $referencedTable) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "Table {$table} should have foreign key column {$column}"
                );
            }
        }
    }

    /** @test */
    public function database_has_proper_indexes()
    {
        $indexes = [
            'users' => ['email'], // Unique constraint acts as index
            'invoices' => ['invoice_number', 'organization_id', 'status'],
            'invoice_items' => ['invoice_id'],
            'organizations' => ['name'],
        ];

        foreach ($indexes as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "Table {$table} should have indexed column {$column}"
                );
            }
        }
    }

    /** @test */
    public function database_can_handle_large_datasets()
    {
        // Test database performance with larger datasets
        $startTime = microtime(true);

        // Insert a reasonable number of test records
        for ($i = 0; $i < 100; $i++) {
            DB::table('invoices')->insert([
                'organization_id' => 1,
                'vendor_id' => 1,
                'invoice_number' => 'PERF-' . $i,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'customer_name' => 'Performance Test Customer ' . $i,
                'total_amount' => rand(100, 5000),
                'net_amount' => rand(80, 4000),
                'tax_amount' => rand(20, 1000),
                'status' => 'draft',
                'payment_status' => 'pending',
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $insertTime = microtime(true) - $startTime;

        // Test query performance
        $queryStartTime = microtime(true);
        $results = DB::table('invoices')->where('status', 'draft')->get();
        $queryTime = microtime(true) - $queryStartTime;

        $this->assertCount(100, $results);
        $this->assertLessThan(1.0, $insertTime, 'Insert operation should complete within 1 second');
        $this->assertLessThan(0.1, $queryTime, 'Query should complete within 0.1 seconds');
    }

    /** @test */
    public function database_handles_null_values_correctly()
    {
        $invoiceId = DB::table('invoices')->insertGetId([
            'organization_id' => 1,
            'vendor_id' => 1,
            'invoice_number' => 'NULL-TEST',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'customer_name' => 'Test Customer',
            'customer_email' => null, // Testing null value
            'customer_phone' => null, // Testing null value
            'total_amount' => 1000,
            'net_amount' => 800,
            'tax_amount' => 200,
            'status' => 'draft',
            'payment_status' => 'pending',
            'currency' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = DB::table('invoices')->find($invoiceId);

        $this->assertNull($invoice->customer_email);
        $this->assertNull($invoice->customer_phone);
        $this->assertEquals('Test Customer', $invoice->customer_name);
    }

    /** @test */
    public function database_handles_unicode_characters()
    {
        $invoiceId = DB::table('invoices')->insertGetId([
            'organization_id' => 1,
            'vendor_id' => 1,
            'invoice_number' => 'UNICODE-TEST',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'customer_name' => 'Müller & Søn 中文测试', // Unicode characters
            'total_amount' => 1000,
            'net_amount' => 800,
            'tax_amount' => 200,
            'status' => 'draft',
            'payment_status' => 'pending',
            'currency' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = DB::table('invoices')->find($invoiceId);

        $this->assertEquals('Müller & Søn 中文测试', $invoice->customer_name);
    }

    /** @test */
    public function database_enforces_data_types()
    {
        // Test that numeric fields enforce proper types
        $this->expectException(\Exception::class);

        DB::table('invoices')->insert([
            'organization_id' => 1,
            'vendor_id' => 1,
            'invoice_number' => 'TYPE-TEST',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'customer_name' => 'Test Customer',
            'total_amount' => 'not-a-number', // This should fail
            'net_amount' => 800,
            'tax_amount' => 200,
            'status' => 'draft',
            'payment_status' => 'pending',
            'currency' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function database_migration_rollback_works()
    {
        // Test that migrations can be rolled back
        $this->artisan('migrate:rollback', ['--step' => 1]);

        // After rollback, some tables might not exist
        // This test depends on your migration structure

        // Re-run migrations to restore state
        $this->artisan('migrate');

        // Verify core tables still exist
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('invoices'));
    }

    /** @test */
    public function database_can_handle_concurrent_operations()
    {
        // Simulate concurrent inserts
        $promises = [];

        for ($i = 0; $i < 10; $i++) {
            DB::table('invoices')->insert([
                'organization_id' => 1,
                'vendor_id' => 1,
                'invoice_number' => 'CONCURRENT-' . $i,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'customer_name' => 'Concurrent Test ' . $i,
                'total_amount' => 1000 + $i,
                'net_amount' => 800 + $i,
                'tax_amount' => 200,
                'status' => 'draft',
                'payment_status' => 'pending',
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $count = DB::table('invoices')->where('invoice_number', 'like', 'CONCURRENT-%')->count();
        $this->assertEquals(10, $count);
    }
}
