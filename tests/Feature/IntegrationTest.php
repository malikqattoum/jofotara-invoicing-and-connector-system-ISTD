<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\IntegrationSetting;
use App\Models\Workflow;
use App\Models\DataPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IntegrationTest extends TestCase
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
    public function complete_invoice_workflow_integration()
    {
        $this->actingAs($this->user);

        // 1. Create integration settings
        $integration = IntegrationSetting::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        // 2. Create a workflow
        $workflow = Workflow::factory()->create([
            'trigger_event' => 'invoice.created',
            'is_active' => true
        ]);

        // 3. Create invoice via web interface
        $invoiceData = [
            'organization_id' => $this->organization->id,
            'vendor_id' => 1,
            'invoice_number' => 'INT-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'customer_name' => 'Integration Test Customer',
            'customer_email' => 'integration@test.com',
            'total_amount' => 1500.00,
            'net_amount' => 1200.00,
            'tax_amount' => 300.00,
            'currency' => 'USD',
        ];

        $response = $this->post('/invoices', $invoiceData);
        $response->assertStatus(302);

        // 4. Verify invoice was created
        $invoice = Invoice::where('invoice_number', 'INT-001')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('draft', $invoice->status);

        // 5. Submit invoice
        $response = $this->patch("/invoices/{$invoice->id}/submit");
        $response->assertStatus(302);

        // 6. Verify submission
        $invoice->refresh();
        $this->assertEquals('submitted', $invoice->status);
        $this->assertNotNull($invoice->submitted_at);

        // 7. Mark as paid
        $response = $this->patch("/invoices/{$invoice->id}/pay", [
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'REF123456'
        ]);
        $response->assertStatus(302);

        // 8. Verify payment
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->payment_status);
        $this->assertEquals('bank_transfer', $invoice->payment_method);
        $this->assertNotNull($invoice->paid_at);
    }

    /** @test */
    public function api_to_web_interface_integration()
    {
        // 1. Create invoice via API
        $response = $this->actingAs($this->user)->postJson('/api/invoices', [
            'organization_id' => $this->organization->id,
            'vendor_id' => 1,
            'invoice_number' => 'API-WEB-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'customer_name' => 'API Customer',
            'total_amount' => 2000.00,
            'currency' => 'USD',
        ]);

        $response->assertStatus(201);
        $invoiceId = $response->json('data.id');

        // 2. Verify it appears in web dashboard
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('API-WEB-001');

        // 3. Update via web interface
        $response = $this->actingAs($this->user)->put("/invoices/{$invoiceId}", [
            'customer_name' => 'Updated API Customer',
            'total_amount' => 2500.00
        ]);

        $response->assertStatus(302);

        // 4. Verify update via API
        $response = $this->actingAs($this->user)->getJson("/api/invoices/{$invoiceId}");
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'customer_name' => 'Updated API Customer',
                'total_amount' => 2500.00
            ]
        ]);
    }

    /** @test */
    public function data_pipeline_workflow_integration()
    {
        $this->actingAs($this->user);

        // 1. Create data pipeline
        $pipeline = DataPipeline::factory()->create([
            'name' => 'Invoice Processing Pipeline',
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        // 2. Create some invoices to process
        $invoices = Invoice::factory(5)->create([
            'organization_id' => $this->organization->id,
            'status' => 'draft'
        ]);

        // 3. Simulate pipeline execution
        foreach ($invoices as $invoice) {
            // Pipeline would normally validate and transform data
            $invoice->update([
                'status' => 'validated',
                'compliance_status' => 'compliant'
            ]);
        }

        // 4. Verify pipeline results
        $validatedInvoices = Invoice::where('status', 'validated')->count();
        $this->assertEquals(5, $validatedInvoices);

        // 5. Submit validated invoices
        foreach ($invoices as $invoice) {
            $invoice->refresh();
            $invoice->submit();
        }

        // 6. Verify final status
        $submittedInvoices = Invoice::where('status', 'submitted')->count();
        $this->assertEquals(5, $submittedInvoices);
    }

    /** @test */
    public function multi_organization_data_isolation()
    {
        // Create second organization and user
        $org2 = Organization::factory()->create();
        $user2 = User::factory()->create(['organization_id' => $org2->id]);

        // Create invoices for both organizations
        $invoice1 = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'invoice_number' => 'ORG1-001'
        ]);

        $invoice2 = Invoice::factory()->create([
            'organization_id' => $org2->id,
            'invoice_number' => 'ORG2-001'
        ]);

        // User 1 should only see org 1 invoices
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertSee('ORG1-001');
        $response->assertDontSee('ORG2-001');

        // User 2 should only see org 2 invoices
        $response = $this->actingAs($user2)->get('/dashboard');
        $response->assertSee('ORG2-001');
        $response->assertDontSee('ORG1-001');

        // API should also respect organization boundaries
        $response = $this->actingAs($this->user)->getJson('/api/invoices');
        $invoiceNumbers = collect($response->json('data'))->pluck('invoice_number');
        $this->assertTrue($invoiceNumbers->contains('ORG1-001'));
        $this->assertFalse($invoiceNumbers->contains('ORG2-001'));
    }

    /** @test */
    public function error_handling_integration()
    {
        $this->actingAs($this->user);

        // 1. Try to create invalid invoice
        $response = $this->postJson('/api/invoices', [
            'invoice_number' => '', // Invalid
            'total_amount' => 'invalid'  // Invalid
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['invoice_number', 'total_amount']);

        // 2. Try to access non-existent invoice
        $response = $this->get('/invoices/99999');
        $response->assertStatus(404);

        // 3. Try to update with invalid data
        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->putJson("/api/invoices/{$invoice->id}", [
            'total_amount' => -100  // Invalid negative amount
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function performance_monitoring_integration()
    {
        $this->actingAs($this->user);

        // Create multiple invoices to test performance
        $startTime = microtime(true);

        for ($i = 0; $i < 50; $i++) {
            Invoice::factory()->create([
                'organization_id' => $this->organization->id,
                'invoice_number' => 'PERF-' . str_pad($i, 3, '0', STR_PAD_LEFT)
            ]);
        }

        $creationTime = microtime(true) - $startTime;

        // Test dashboard loading performance
        $startTime = microtime(true);
        $response = $this->get('/dashboard');
        $dashboardTime = microtime(true) - $startTime;

        // Test API performance
        $startTime = microtime(true);
        $response = $this->getJson('/api/invoices');
        $apiTime = microtime(true) - $startTime;

        // Assertions
        $response->assertStatus(200);
        $this->assertLessThan(2.0, $creationTime, 'Invoice creation should be fast');
        $this->assertLessThan(1.0, $dashboardTime, 'Dashboard should load quickly');
        $this->assertLessThan(0.5, $apiTime, 'API should respond quickly');
    }

    /** @test */
    public function audit_trail_integration()
    {
        $this->actingAs($this->user);

        // Create invoice
        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'draft'
        ]);

        // Perform various operations
        $invoice->submit();
        $invoice->markAsPaid('credit_card', 'CC123');

        // Verify audit trail
        $invoice->refresh();
        $auditTrail = $invoice->audit_trail;

        $this->assertIsArray($auditTrail);
        $this->assertCount(2, $auditTrail);

        // Check submission audit
        $this->assertEquals('submitted', $auditTrail[0]['action']);
        $this->assertArrayHasKey('submitted_at', $auditTrail[0]['data']);

        // Check payment audit
        $this->assertEquals('payment_received', $auditTrail[1]['action']);
        $this->assertEquals('credit_card', $auditTrail[1]['data']['payment_method']);
    }

    /** @test */
    public function system_configuration_integration()
    {
        // Test that system works with different configurations
        config(['app.timezone' => 'Europe/London']);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'invoice_date' => now()
        ]);

        $this->assertNotNull($invoice->invoice_date);

        // Test with different currency
        config(['app.default_currency' => 'EUR']);

        $response = $this->actingAs($this->user)->postJson('/api/invoices', [
            'organization_id' => $this->organization->id,
            'vendor_id' => 1,
            'invoice_number' => 'EUR-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'customer_name' => 'Euro Customer',
            'total_amount' => 1000.00,
            'currency' => 'EUR'
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'data' => ['currency' => 'EUR']
        ]);
    }

    /** @test */
    public function backup_and_restore_integration()
    {
        // Create test data
        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'invoice_number' => 'BACKUP-001'
        ]);

        // Simulate backup process
        $backupData = [
            'invoices' => Invoice::all()->toArray(),
            'organizations' => Organization::all()->toArray(),
            'users' => User::all()->toArray()
        ];

        $this->assertNotEmpty($backupData['invoices']);
        $this->assertNotEmpty($backupData['organizations']);
        $this->assertNotEmpty($backupData['users']);

        // Verify specific data exists
        $this->assertTrue(
            collect($backupData['invoices'])->contains('invoice_number', 'BACKUP-001')
        );
    }
}
