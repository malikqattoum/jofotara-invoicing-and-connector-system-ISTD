<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_customer_id',
        'transaction_id',
        'source_pos_system',
        'source_file',
        'transaction_date',
        'customer_name',
        'customer_email',
        'customer_phone',
        'items',
        'subtotal',
        'tax_amount',
        'total_amount',
        'tip_amount',
        'payment_method',
        'payment_reference',
        'payment_status',
        'location',
        'employee',
        'notes',
        'invoice_created',
        'invoice_id',
        'processed_at',
        'processing_errors',
        'raw_data',
    ];

    protected $casts = [
        'items' => 'array',
        'processing_errors' => 'array',
        'raw_data' => 'array',
        'transaction_date' => 'datetime',
        'processed_at' => 'datetime',
        'invoice_created' => 'boolean',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'tip_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function posCustomer()
    {
        return $this->belongsTo(PosCustomer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Create invoice from this POS transaction
     */
    public function createInvoice(): ?Invoice
    {
        if ($this->invoice_created) {
            return $this->invoice;
        }

        try {
            // Create customer if not exists
            $customer = $this->findOrCreateCustomer();

            // Create invoice
            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'issue_date' => $this->transaction_date->toDateString(),
                'due_date' => $this->transaction_date->addDays(30)->toDateString(),
                'subtotal' => $this->subtotal ?? $this->total_amount,
                'tax_amount' => $this->tax_amount ?? 0,
                'total_amount' => $this->total_amount,
                'status' => 'paid', // POS transactions are usually already paid
                'payment_method' => $this->payment_method,
                'notes' => "Auto-created from POS transaction: {$this->transaction_id}",
            ]);

            // Add items to invoice
            $this->createInvoiceItems($invoice);

            // Update transaction
            $this->update([
                'invoice_created' => true,
                'invoice_id' => $invoice->id,
                'processed_at' => now(),
            ]);

            // Update customer transaction count
            $this->posCustomer->increment('total_transactions_synced');
            $this->posCustomer->update(['last_transaction_sync' => now()]);

            return $invoice;

        } catch (\Exception $e) {
            $this->update([
                'processing_errors' => [
                    'error' => $e->getMessage(),
                    'occurred_at' => now()->toISOString()
                ]
            ]);

            \Log::error('Failed to create invoice from POS transaction', [
                'pos_transaction_id' => $this->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Find or create customer for invoice
     */
    private function findOrCreateCustomer(): Customer
    {
        // Try to find existing customer by email or name
        $customer = null;

        if ($this->customer_email) {
            $customer = Customer::where('email', $this->customer_email)->first();
        }

        if (!$customer && $this->customer_name) {
            $customer = Customer::where('name', 'LIKE', '%' . $this->customer_name . '%')->first();
        }

        // Create new customer if not found
        if (!$customer) {
            $customer = Customer::create([
                'name' => $this->customer_name ?: 'POS Customer',
                'email' => $this->customer_email ?: 'pos-customer-' . $this->id . '@example.com',
                'phone' => $this->customer_phone,
                'address' => $this->location,
                'notes' => "Auto-created from POS transaction via {$this->posCustomer->customer_name}",
            ]);
        }

        return $customer;
    }

    /**
     * Create invoice items from POS transaction items
     */
    private function createInvoiceItems(Invoice $invoice): void
    {
        if (!$this->items) {
            // Create single item if no item details available
            $invoice->items()->create([
                'description' => $this->source_pos_system
                    ? "Transaction from {$this->source_pos_system}"
                    : 'POS Transaction',
                'quantity' => 1,
                'unit_price' => $this->total_amount,
                'total' => $this->total_amount,
            ]);
            return;
        }

        // Create items from POS data
        foreach ($this->items as $item) {
            $invoice->items()->create([
                'description' => $item['description'] ?? $item['name'] ?? $item['item'] ?? 'Item',
                'quantity' => $item['quantity'] ?? $item['qty'] ?? 1,
                'unit_price' => $item['unit_price'] ?? $item['price'] ?? $item['cost'] ?? 0,
                'total' => $item['total'] ?? ($item['quantity'] ?? 1) * ($item['unit_price'] ?? $item['price'] ?? 0),
            ]);
        }
    }

    /**
     * Scopes
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('invoice_created', false);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('transaction_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('transaction_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Attributes
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total_amount, 2);
    }

    public function getIsProcessedAttribute(): bool
    {
        return $this->invoice_created && $this->invoice_id;
    }
}
