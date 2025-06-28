<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'vendor_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_tax_number',
        'total_amount',
        'net_amount',
        'tax_amount',
        'discount_amount',
        'status',
        'payment_status',
        'paid_at',
        'payment_method',
        'payment_reference',
        'uuid',
        'currency',
        'integration_type',
        'submitted_at',
        'processed_at',
        'rejection_reason',
        'revision_number',
        'compliance_status',
        'line_items_summary',
        'invoice_type',
        'audit_trail',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'submitted_at' => 'datetime',
        'processed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_items_summary' => 'array',
        'audit_trail' => 'array',
        'revision_number' => 'integer',
    ];

    /**
     * Relationships
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class, 'vendor_id', 'vendor_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'invoice_id');
    }

    /**
     * Scopes for enhanced querying
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('payment_status', '!=', 'paid')
                    ->where('status', 'submitted');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    /**
     * Accessors and Mutators
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date &&
               $this->due_date->isPast() &&
               $this->payment_status !== 'paid' &&
               $this->status === 'submitted';
    }

    public function getPaymentDelayDaysAttribute(): int
    {
        if (!$this->paid_at || !$this->due_date) {
            return 0;
        }

        return max(0, $this->due_date->diffInDays($this->paid_at, false));
    }

    public function getProcessingTimeAttribute(): ?int
    {
        if (!$this->submitted_at || !$this->processed_at) {
            return null;
        }

        return $this->submitted_at->diffInHours($this->processed_at);
    }

    /**
     * Business Logic Methods
     */
    public function markAsPaid(string $paymentMethod = null, string $reference = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'payment_reference' => $reference,
        ]);

        $this->addToAuditTrail('payment_received', [
            'payment_method' => $paymentMethod,
            'reference' => $reference,
            'paid_at' => now(),
        ]);
    }

    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->addToAuditTrail('submitted', [
            'submitted_at' => now(),
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'processed_at' => now(),
        ]);

        $this->addToAuditTrail('rejected', [
            'reason' => $reason,
            'rejected_at' => now(),
        ]);
    }

    public function addToAuditTrail(string $action, array $data = []): void
    {
        $trail = $this->audit_trail ?? [];

        $trail[] = [
            'action' => $action,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()?->name ?? 'system',
        ];

        $this->update(['audit_trail' => $trail]);
    }

    /**
     * Static factory methods for common scenarios
     */
    public static function createDraft(array $data): self
    {
        return self::create(array_merge($data, [
            'status' => 'draft',
            'payment_status' => 'pending',
            'revision_number' => 1,
            'compliance_status' => 'pending',
            'created_by' => auth()->user()?->name ?? 'system',
        ]));
    }
}
