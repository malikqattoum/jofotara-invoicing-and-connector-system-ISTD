<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function authenticated_user_can_view_invoices_index()
    {
        $response = $this->actingAs($this->user)->get('/invoices');

        $response->assertStatus(200);
        $response->assertViewIs('invoices.index');
    }

    /** @test */
    public function guest_cannot_view_invoices_index()
    {
        $response = $this->get('/invoices');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_user_can_create_invoice()
    {
        $organization = Organization::factory()->create();

        $invoiceData = [
            'organization_id' => $organization->id,
            'vendor_id' => 1,
            'invoice_number' => 'INV-001',
            'invoice_date' => Carbon::today()->format('Y-m-d'),
            'due_date' => Carbon::today()->addDays(30)->format('Y-m-d'),
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'total_amount' => 1000.00,
            'net_amount' => 800.00,
            'tax_amount' => 200.00,
            'currency' => 'USD',
        ];

        $response = $this->actingAs($this->user)->post('/invoices', $invoiceData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-001',
            'customer_name' => 'John Doe',
            'total_amount' => 1000.00,
        ]);
    }

    /** @test */
    public function authenticated_user_can_view_single_invoice()
    {
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($this->user)->get("/invoices/{$invoice->id}");

        $response->assertStatus(200);
        $response->assertViewIs('invoices.show');
        $response->assertViewHas('invoice', $invoice);
    }

    /** @test */
    public function authenticated_user_can_update_invoice()
    {
        $invoice = Invoice::factory()->create([
            'customer_name' => 'Old Name'
        ]);

        $updateData = [
            'customer_name' => 'New Name',
            'total_amount' => 1500.00,
        ];

        $response = $this->actingAs($this->user)
                         ->put("/invoices/{$invoice->id}", $updateData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'customer_name' => 'New Name',
            'total_amount' => 1500.00
        ]);
    }

    /** @test */
    public function authenticated_user_can_delete_invoice()
    {
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($this->user)
                         ->delete("/invoices/{$invoice->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    /** @test */
    public function authenticated_user_can_submit_invoice()
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)
                         ->patch("/invoices/{$invoice->id}/submit");

        $response->assertStatus(302);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'submitted'
        ]);
    }

    /** @test */
    public function authenticated_user_can_mark_invoice_as_paid()
    {
        $invoice = Invoice::factory()->create([
            'status' => 'submitted',
            'payment_status' => 'pending'
        ]);

        $paymentData = [
            'payment_method' => 'credit_card',
            'payment_reference' => 'REF123'
        ];

        $response = $this->actingAs($this->user)
                         ->patch("/invoices/{$invoice->id}/pay", $paymentData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'payment_status' => 'paid',
            'payment_method' => 'credit_card'
        ]);
    }

    /** @test */
    public function invoice_validation_works_correctly()
    {
        $response = $this->actingAs($this->user)->post('/invoices', [
            'invoice_number' => '', // Required field missing
            'total_amount' => 'invalid', // Invalid number
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['invoice_number', 'total_amount']);
    }

    /** @test */
    public function user_can_filter_invoices()
    {
        Invoice::factory()->create(['status' => 'draft']);
        Invoice::factory()->create(['status' => 'submitted']);
        Invoice::factory()->create(['status' => 'paid']);

        $response = $this->actingAs($this->user)
                         ->get('/invoices?status=submitted');

        $response->assertStatus(200);
        $response->assertViewHas('invoices');
    }

    /** @test */
    public function user_can_search_invoices()
    {
        Invoice::factory()->create(['invoice_number' => 'INV-001']);
        Invoice::factory()->create(['invoice_number' => 'INV-002']);

        $response = $this->actingAs($this->user)
                         ->get('/invoices?search=INV-001');

        $response->assertStatus(200);
        $response->assertViewHas('invoices');
    }
}
