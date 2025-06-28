<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function invoice_can_be_created_with_valid_data()
    {
        $invoiceData = [
            'organization_id' => 1,
            'vendor_id' => 1,
            'invoice_number' => 'INV-001',
            'invoice_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(30),
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'total_amount' => 1000.00,
            'net_amount' => 800.00,
            'tax_amount' => 200.00,
            'status' => 'draft',
            'payment_status' => 'pending',
            'currency' => 'USD',
        ];

        $invoice = Invoice::create($invoiceData);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals('INV-001', $invoice->invoice_number);
        $this->assertEquals(1000.00, $invoice->total_amount);
        $this->assertEquals('draft', $invoice->status);
    }

    /** @test */
    public function invoice_can_be_created_as_draft()
    {
        $invoiceData = [
            'organization_id' => 1,
            'vendor_id' => 1,
            'invoice_number' => 'INV-002',
            'invoice_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(30),
            'customer_name' => 'Jane Doe',
            'total_amount' => 500.00,
        ];

        $invoice = Invoice::createDraft($invoiceData);

        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals('pending', $invoice->payment_status);
        $this->assertEquals(1, $invoice->revision_number);
        $this->assertEquals('pending', $invoice->compliance_status);
    }

    /** @test */
    public function invoice_can_be_submitted()
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $invoice->submit();

        $this->assertEquals('submitted', $invoice->status);
        $this->assertNotNull($invoice->submitted_at);
    }

    /** @test */
    public function invoice_can_be_marked_as_paid()
    {
        $invoice = Invoice::factory()->create([
            'status' => 'submitted',
            'payment_status' => 'pending'
        ]);

        $invoice->markAsPaid('credit_card', 'REF123');

        $this->assertEquals('paid', $invoice->payment_status);
        $this->assertEquals('credit_card', $invoice->payment_method);
        $this->assertEquals('REF123', $invoice->payment_reference);
        $this->assertNotNull($invoice->paid_at);
    }

    /** @test */
    public function invoice_can_be_rejected()
    {
        $invoice = Invoice::factory()->create(['status' => 'submitted']);

        $invoice->reject('Invalid data');

        $this->assertEquals('rejected', $invoice->status);
        $this->assertEquals('Invalid data', $invoice->rejection_reason);
        $this->assertNotNull($invoice->processed_at);
    }

    /** @test */
    public function invoice_overdue_scope_works()
    {
        // Create overdue invoice
        $overdueInvoice = Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'payment_status' => 'pending',
            'status' => 'submitted'
        ]);

        // Create non-overdue invoice
        $currentInvoice = Invoice::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'payment_status' => 'pending',
            'status' => 'submitted'
        ]);

        $overdueInvoices = Invoice::overdue()->get();

        $this->assertTrue($overdueInvoices->contains($overdueInvoice));
        $this->assertFalse($overdueInvoices->contains($currentInvoice));
    }

    /** @test */
    public function invoice_is_overdue_attribute_works()
    {
        $overdueInvoice = Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'payment_status' => 'pending',
            'status' => 'submitted'
        ]);

        $currentInvoice = Invoice::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'payment_status' => 'pending',
            'status' => 'submitted'
        ]);

        $this->assertTrue($overdueInvoice->is_overdue);
        $this->assertFalse($currentInvoice->is_overdue);
    }

    /** @test */
    public function invoice_payment_delay_days_calculated_correctly()
    {
        $invoice = Invoice::factory()->create([
            'due_date' => Carbon::parse('2024-01-01'),
            'paid_at' => Carbon::parse('2024-01-05'),
        ]);

        $this->assertEquals(4, $invoice->payment_delay_days);
    }

    /** @test */
    public function invoice_processing_time_calculated_correctly()
    {
        $invoice = Invoice::factory()->create([
            'submitted_at' => Carbon::parse('2024-01-01 10:00:00'),
            'processed_at' => Carbon::parse('2024-01-01 14:00:00'),
        ]);

        $this->assertEquals(4, $invoice->processing_time);
    }

    /** @test */
    public function invoice_has_audit_trail()
    {
        $invoice = Invoice::factory()->create();

        $invoice->addToAuditTrail('test_action', ['key' => 'value']);

        $this->assertNotEmpty($invoice->audit_trail);
        $this->assertEquals('test_action', $invoice->audit_trail[0]['action']);
        $this->assertEquals(['key' => 'value'], $invoice->audit_trail[0]['data']);
    }

    /** @test */
    public function invoice_has_items_relationship()
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertTrue($invoice->items->contains($item));
    }

    /** @test */
    public function invoice_scopes_work_correctly()
    {
        $invoice1 = Invoice::factory()->create(['vendor_id' => 1, 'organization_id' => 1]);
        $invoice2 = Invoice::factory()->create(['vendor_id' => 2, 'organization_id' => 1]);
        $invoice3 = Invoice::factory()->create(['vendor_id' => 1, 'organization_id' => 2]);

        $vendorInvoices = Invoice::forVendor(1)->get();
        $orgInvoices = Invoice::forOrganization(1)->get();

        $this->assertCount(2, $vendorInvoices);
        $this->assertCount(2, $orgInvoices);
    }
}
