<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Vendor;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function invoice_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('invoices'));

        $expectedColumns = [
            'id', 'organization_id', 'vendor_id', 'invoice_number', 'invoice_date', 'due_date',
            'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'total_amount',
            'net_amount', 'tax_amount', 'discount_amount', 'status', 'payment_status', 'currency',
            'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('invoices', $column),
                "Invoices table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function invoice_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $invoice = Invoice::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $invoice->organization);
        $this->assertEquals($organization->id, $invoice->organization->id);
    }

    /** @test */
    public function invoice_has_many_items()
    {
        $invoice = Invoice::factory()->create();
        $items = InvoiceItem::factory()->count(3)->create(['invoice_id' => $invoice->id]);

        $this->assertCount(3, $invoice->items);
        $this->assertInstanceOf(InvoiceItem::class, $invoice->items->first());
    }

    /** @test */
    public function invoice_can_be_marked_as_paid()
    {
        $invoice = Invoice::factory()->create(['payment_status' => 'pending']);

        $invoice->markAsPaid('credit_card', 'PAY-12345');

        $this->assertEquals('paid', $invoice->fresh()->payment_status);
        $this->assertEquals('credit_card', $invoice->fresh()->payment_method);
        $this->assertEquals('PAY-12345', $invoice->fresh()->payment_reference);
    }

    /** @test */
    public function invoice_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'organization_id', 'vendor_id', 'invoice_number', 'invoice_date', 'due_date',
            'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'total_amount',
            'net_amount', 'tax_amount', 'discount_amount', 'status', 'payment_status', 'currency'
        ];

        $invoice = new Invoice();
        $actualFillable = $invoice->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function invoice_can_check_overdue_status()
    {
        $overdueInvoice = Invoice::factory()->create(['due_date' => now()->subDay(), 'payment_status' => 'pending']);
        $this->assertTrue($overdueInvoice->isOverdue);

        $currentInvoice = Invoice::factory()->create(['due_date' => now()->addDay(), 'payment_status' => 'pending']);
        $this->assertFalse($currentInvoice->isOverdue);
    }

    /** @test */
    public function invoice_scopes_work_correctly()
    {
        Invoice::factory()->count(3)->create(['status' => 'draft']);
        Invoice::factory()->count(2)->create(['status' => 'submitted']);

        $this->assertCount(2, Invoice::submitted()->get());
        $this->assertCount(3, Invoice::where('status', 'draft')->get());
    }
}
