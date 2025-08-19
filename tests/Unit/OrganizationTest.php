<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Organization;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function organization_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('organizations'));

        $expectedColumns = [
            'id', 'name', 'email', 'phone', 'address', 'tax_number', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('organizations', $column),
                "Organizations table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function organization_has_many_users()
    {
        $organization = Organization::factory()->create();
        $users = User::factory()->count(3)->create(['organization_id' => $organization->id]);

        $this->assertCount(3, $organization->users);
        $this->assertInstanceOf(User::class, $organization->users->first());
    }

    /** @test */
    public function organization_has_many_invoices()
    {
        $organization = Organization::factory()->create();
        $invoices = Invoice::factory()->count(5)->create(['organization_id' => $organization->id]);

        $this->assertCount(5, $organization->invoices);
        $this->assertInstanceOf(Invoice::class, $organization->invoices->first());
    }

    /** @test */
    public function organization_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'name', 'email', 'phone', 'address', 'tax_number'
        ];

        $organization = new Organization();
        $actualFillable = $organization->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function organization_can_be_created_with_valid_data()
    {
        $organization = Organization::create([
            'name' => 'Test Organization',
            'email' => 'org@example.com',
            'phone' => '123-456-7890',
            'address' => '123 Business St',
            'tax_number' => 'TAX123456'
        ]);

        $this->assertInstanceOf(Organization::class, $organization);
        $this->assertEquals('Test Organization', $organization->name);
        $this->assertEquals('org@example.com', $organization->email);
    }
}
