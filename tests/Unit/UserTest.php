<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\Invoice;
use App\Models\IntegrationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('users'));

        $expectedColumns = [
            'id', 'name', 'email', 'password', 'organization_name', 'organization_address',
            'organization_phone', 'tax_number', 'role', 'is_active', 'company_name',
            'address', 'phone', 'settings', 'is_admin', 'organization_id', 'email_verified_at',
            'remember_token', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Users table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function user_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $user->organization);
        $this->assertEquals($organization->id, $user->organization->id);
    }

    /** @test */
    public function user_has_many_invoices()
    {
        $user = User::factory()->create();
        $invoices = Invoice::factory()->count(3)->create(['vendor_id' => $user->id]);

        $this->assertCount(3, $user->invoices);
        $this->assertInstanceOf(Invoice::class, $user->invoices->first());
    }

    /** @test */
    public function user_has_many_integration_settings()
    {
        $user = User::factory()->create();
        $settings = IntegrationSetting::factory()->count(2)->create(['vendor_id' => $user->id]);

        $this->assertCount(2, $user->integrationSettings);
        $this->assertInstanceOf(IntegrationSetting::class, $user->integrationSettings->first());
    }

    /** @test */
    public function user_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'name', 'email', 'password', 'organization_name', 'organization_address',
            'organization_phone', 'tax_number', 'role', 'is_active', 'company_name',
            'address', 'phone', 'settings', 'is_admin', 'organization_id'
        ];

        $user = new User();
        $actualFillable = $user->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function user_can_be_created_with_valid_data()
    {
        $organization = Organization::factory()->create();

        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'organization_id' => $organization->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('user@example.com', $user->email);
        $this->assertTrue($user->is_active);
    }
}
