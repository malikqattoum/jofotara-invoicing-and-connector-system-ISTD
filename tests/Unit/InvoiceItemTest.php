<?php

namespace Tests\Unit;

use App\Models\InvoiceItem;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_invoice_item()
    {
        $invoice = Invoice::factory()->create();

        $invoiceItem = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_name' => 'Test Item',
            'quantity' => 2,
            'unit_price' => 15.99,
            'tax' => 3.20,
            'total' => 31.98,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'id' => $invoiceItem->id,
            'invoice_id' => $invoice->id,
            'item_name' => 'Test Item',
            'quantity' => 2,
            'unit_price' => 15.99,
            'tax' => 3.20,
            'total' => 31.98,
        ]);
    }

    public function test_invoice_relationship()
    {
        $invoice = Invoice::factory()->create();
        $invoiceItem = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_name' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 10.00,
            'total' => 10.00,
        ]);

        $this->assertInstanceOf(Invoice::class, $invoiceItem->invoice);
        $this->assertEquals($invoice->id, $invoiceItem->invoice->id);
    }

    public function test_required_fields_are_fillable()
    {
        $invoiceItem = new InvoiceItem([
            'invoice_id' => 1,
            'item_name' => 'Test Item',
            'quantity' => 3,
            'unit_price' => 25.50,
            'tax' => 5.10,
            'total' => 76.50,
        ]);

        $this->assertEquals(1, $invoiceItem->invoice_id);
        $this->assertEquals('Test Item', $invoiceItem->item_name);
        $this->assertEquals(3, $invoiceItem->quantity);
        $this->assertEquals(25.50, $invoiceItem->unit_price);
        $this->assertEquals(5.10, $invoiceItem->tax);
        $this->assertEquals(76.50, $invoiceItem->total);
    }
}
