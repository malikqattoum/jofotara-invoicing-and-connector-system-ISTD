<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\IntegrationSetting;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->user = User::factory()->create([
            'organization_id' => $this->organization->id
        ]);
    }

    /** @test */
    public function authenticated_user_can_view_dashboard()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas(['user', 'invoices', 'integration', 'stats']);
    }

    /** @test */
    public function guest_cannot_view_dashboard()
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function dashboard_shows_user_invoices_only()
    {
        // Create invoices for this user's organization
        $userInvoices = Invoice::factory(3)->create([
            'organization_id' => $this->organization->id
        ]);

        // Create invoices for different organization
        $otherOrganization = Organization::factory()->create();
        $otherInvoices = Invoice::factory(2)->create([
            'organization_id' => $otherOrganization->id
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);

        $invoices = $response->viewData('invoices');
        $this->assertCount(3, $invoices);

        foreach ($userInvoices as $invoice) {
            $this->assertTrue($invoices->contains($invoice));
        }

        foreach ($otherInvoices as $invoice) {
            $this->assertFalse($invoices->contains($invoice));
        }
    }

    /** @test */
    public function dashboard_can_filter_by_status()
    {
        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'draft'
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'submitted'
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard?status=draft');

        $response->assertStatus(200);
        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
        $this->assertEquals('draft', $invoices->first()->status);
    }

    /** @test */
    public function dashboard_can_search_invoices()
    {
        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'invoice_number' => 'INV-001',
            'customer_name' => 'John Doe'
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'invoice_number' => 'INV-002',
            'customer_name' => 'Jane Smith'
        ]);

        // Search by invoice number
        $response = $this->actingAs($this->user)->get('/dashboard?search=INV-001');
        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
        $this->assertEquals('INV-001', $invoices->first()->invoice_number);

        // Search by customer name
        $response = $this->actingAs($this->user)->get('/dashboard?search=Jane');
        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
        $this->assertEquals('Jane Smith', $invoices->first()->customer_name);
    }

    /** @test */
    public function dashboard_shows_correct_statistics()
    {
        // Create invoices with different statuses
        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'draft'
        ]);

        Invoice::factory(2)->create([
            'organization_id' => $this->organization->id,
            'status' => 'submitted'
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'rejected'
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $stats = $response->viewData('stats');
        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['submitted']);
        $this->assertEquals(1, $stats['rejected']);
        $this->assertEquals(1, $stats['draft']);
    }

    /** @test */
    public function dashboard_shows_integration_settings()
    {
        $integration = IntegrationSetting::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $responseIntegration = $response->viewData('integration');
        $this->assertEquals($integration->id, $responseIntegration->id);
    }

    /** @test */
    public function dashboard_handles_user_without_organization()
    {
        $userWithoutOrg = User::factory()->create(['organization_id' => null]);

        $response = $this->actingAs($userWithoutOrg)->get('/dashboard');

        $response->assertStatus(200);
        $stats = $response->viewData('stats');
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['submitted']);
        $this->assertEquals(0, $stats['rejected']);
        $this->assertEquals(0, $stats['draft']);
    }

    /** @test */
    public function dashboard_limits_invoices_to_ten()
    {
        Invoice::factory(15)->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $invoices = $response->viewData('invoices');
        $this->assertCount(10, $invoices);
    }

    /** @test */
    public function dashboard_orders_invoices_by_newest_first()
    {
        $oldInvoice = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'created_at' => now()->subDays(2)
        ]);

        $newInvoice = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'created_at' => now()
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $invoices = $response->viewData('invoices');
        $this->assertEquals($newInvoice->id, $invoices->first()->id);
    }

    /** @test */
    public function dashboard_can_search_by_customer_email()
    {
        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'customer_email' => 'john@example.com'
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'customer_email' => 'jane@example.com'
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard?search=john@example.com');

        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
        $this->assertEquals('john@example.com', $invoices->first()->customer_email);
    }

    /** @test */
    public function dashboard_can_search_by_customer_phone()
    {
        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'customer_phone' => '+1234567890'
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'customer_phone' => '+0987654321'
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard?search=+1234567890');

        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
        $this->assertEquals('+1234567890', $invoices->first()->customer_phone);
    }
}
