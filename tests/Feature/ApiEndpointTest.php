<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ApiEndpointTest extends TestCase
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
    public function api_requires_authentication()
    {
        $response = $this->getJson('/api/invoices');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function authenticated_user_can_get_invoices_via_api()
    {
        Sanctum::actingAs($this->user);

        Invoice::factory(3)->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->getJson('/api/invoices');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'invoice_number',
                    'customer_name',
                    'total_amount',
                    'status',
                    'created_at'
                ]
            ]
        ]);
    }

    /** @test */
    public function user_can_create_invoice_via_api()
    {
        Sanctum::actingAs($this->user);

        $invoiceData = [
            'organization_id' => $this->organization->id,
            'vendor_id' => 1,
            'invoice_number' => 'API-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'customer_name' => 'API Customer',
            'customer_email' => 'api@example.com',
            'total_amount' => 1500.00,
            'net_amount' => 1200.00,
            'tax_amount' => 300.00,
            'currency' => 'USD',
        ];

        $response = $this->postJson('/api/invoices', $invoiceData);

        $response->assertStatus(201);
        $response->assertJson([
            'data' => [
                'invoice_number' => 'API-001',
                'customer_name' => 'API Customer',
                'total_amount' => 1500.00
            ]
        ]);

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'API-001',
            'customer_name' => 'API Customer'
        ]);
    }

    /** @test */
    public function user_can_update_invoice_via_api()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'customer_name' => 'Old Name'
        ]);

        $updateData = [
            'customer_name' => 'Updated Name',
            'total_amount' => 2000.00
        ];

        $response = $this->putJson("/api/invoices/{$invoice->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'customer_name' => 'Updated Name',
                'total_amount' => 2000.00
            ]
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'customer_name' => 'Updated Name'
        ]);
    }

    /** @test */
    public function user_can_delete_invoice_via_api()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->deleteJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    /** @test */
    public function user_cannot_access_other_organization_invoices()
    {
        Sanctum::actingAs($this->user);

        $otherOrganization = Organization::factory()->create();
        $otherInvoice = Invoice::factory()->create([
            'organization_id' => $otherOrganization->id
        ]);

        $response = $this->getJson("/api/invoices/{$otherInvoice->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function api_validates_invoice_data()
    {
        Sanctum::actingAs($this->user);

        $invalidData = [
            'invoice_number' => '', // Required
            'total_amount' => 'invalid', // Must be numeric
            'invoice_date' => 'invalid-date' // Must be valid date
        ];

        $response = $this->postJson('/api/invoices', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'invoice_number',
            'total_amount',
            'invoice_date'
        ]);
    }

    /** @test */
    public function api_can_filter_invoices_by_status()
    {
        Sanctum::actingAs($this->user);

        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'draft'
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'submitted'
        ]);

        $response = $this->getJson('/api/invoices?status=draft');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('draft', $data[0]['status']);
    }

    /** @test */
    public function api_can_search_invoices()
    {
        Sanctum::actingAs($this->user);

        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'invoice_number' => 'SEARCH-001'
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'invoice_number' => 'OTHER-001'
        ]);

        $response = $this->getJson('/api/invoices?search=SEARCH');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('SEARCH-001', $data[0]['invoice_number']);
    }

    /** @test */
    public function api_returns_paginated_results()
    {
        Sanctum::actingAs($this->user);

        Invoice::factory(25)->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->getJson('/api/invoices');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links',
            'meta' => [
                'current_page',
                'total',
                'per_page'
            ]
        ]);
    }

    /** @test */
    public function api_can_submit_invoice()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'draft'
        ]);

        $response = $this->patchJson("/api/invoices/{$invoice->id}/submit");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'status' => 'submitted'
            ]
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'submitted'
        ]);
    }

    /** @test */
    public function api_returns_invoice_statistics()
    {
        Sanctum::actingAs($this->user);

        Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'draft'
        ]);

        Invoice::factory(2)->create([
            'organization_id' => $this->organization->id,
            'status' => 'submitted'
        ]);

        $response = $this->getJson('/api/invoices/statistics');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 3,
            'draft' => 1,
            'submitted' => 2,
            'rejected' => 0
        ]);
    }
}
