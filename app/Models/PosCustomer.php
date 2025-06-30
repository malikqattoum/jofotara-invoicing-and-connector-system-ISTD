<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PosCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'business_type',
        'email',
        'phone',
        'address',
        'api_key',
        'pos_connector_version',
        'pos_systems_detected',
        'sync_interval',
        'debug_mode',
        'auto_start',
        'connector_active',
        'last_seen',
        'last_transaction_sync',
        'total_transactions_synced',
        'connector_status',
        'support_contact',
        'notes',
    ];

    protected $casts = [
        'pos_systems_detected' => 'array',
        'connector_status' => 'array',
        'debug_mode' => 'boolean',
        'auto_start' => 'boolean',
        'connector_active' => 'boolean',
        'last_seen' => 'datetime',
        'last_transaction_sync' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function transactions()
    {
        return $this->hasMany(PosTransaction::class);
    }

    public function invoices()
    {
        return $this->hasManyThrough(Invoice::class, PosTransaction::class, 'pos_customer_id', 'id', 'id', 'invoice_id');
    }

    /**
     * Generate unique API key for customer
     */
    public static function generateApiKey($customerId = null): string
    {
        $seed = ($customerId ?: Str::random(10)) . '_' . now()->timestamp . '_' . Str::random(16);
        return hash('sha256', $seed);
    }

    /**
     * Generate unique customer ID
     */
    public static function generateCustomerId(): string
    {
        do {
            $customerId = 'CUST_' . strtoupper(Str::random(8));
        } while (self::where('customer_id', $customerId)->exists());

        return $customerId;
    }

    /**
     * Check if connector is currently active (seen within last 10 minutes)
     */
    public function getIsConnectorActiveAttribute(): bool
    {
        return $this->last_seen && $this->last_seen->diffInMinutes(now()) <= 10;
    }

    /**
     * Get transactions from today
     */
    public function todaysTransactions()
    {
        return $this->transactions()->whereDate('transaction_date', today());
    }

    /**
     * Get transactions from this week
     */
    public function weeklyTransactions()
    {
        return $this->transactions()->whereBetween('transaction_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Update connector heartbeat
     */
    public function updateHeartbeat(array $status = [])
    {
        $this->update([
            'last_seen' => now(),
            'connector_active' => true,
            'connector_status' => array_merge($this->connector_status ?? [], $status)
        ]);
    }

    /**
     * Create customer config for connector
     */
    public function getConnectorConfig(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'api_url' => config('app.url') . '/api/pos-transactions',
            'api_key' => $this->api_key,
            'sync_interval' => $this->sync_interval,
            'debug_mode' => $this->debug_mode,
            'auto_start' => $this->auto_start,
            'contact_info' => [
                'support_email' => config('mail.from.address'),
                'support_phone' => $this->support_contact ?? config('app.support_phone', '+1-800-SUPPORT')
            ],
            'created_date' => $this->created_at->toISOString(),
            'version' => '2.0.0'
        ];
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('connector_active', true);
    }

    public function scopeRecentlyActive($query, $minutes = 10)
    {
        return $query->where('last_seen', '>=', now()->subMinutes($minutes));
    }
}
