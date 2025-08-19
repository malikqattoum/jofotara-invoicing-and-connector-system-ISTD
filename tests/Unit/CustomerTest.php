<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function customer_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('customers'));

        $expectedColumns = [
            'id', 'organization_id', 'name', 'email', 'phone', 'address', 'tax_number', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('customers', $column),
                "Customers table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function customer_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $customer = Customer::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $customer->organization);
        $this->assertEquals($organization->id, $customer->organization->id);
    }

    /** @test */
    public function customer_has_many_invoices()
    {
        $customer = Customer::factory()->create();
        $invoices = Invoice::factory()->count(3)->create(['customer_id' => $customer->id]);

        $this->assertCount(3, $customer->invoices);
        $this->assertInstanceOf(Invoice::class, $customer->invoices->first());
    }

    /** @test */
    public function customer_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'organization_id', 'name', 'email', 'phone', 'address', 'tax_number'
        ];

        $customer = new Customer();
        $actualFillable = $customer->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function customer_can_be_created_with_valid_data()
    {
        $organization = Organization::factory()->create();

        $customer = Customer::create([
            'organization_id' => $organization->id,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '123-456-7890',
            'address' => '123 Main St',
            'tax_number' => 'TAX123'
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('Test Customer', $customer->name);
        $this->assertEquals('customer@example.com', $customer->email);
    }
}
