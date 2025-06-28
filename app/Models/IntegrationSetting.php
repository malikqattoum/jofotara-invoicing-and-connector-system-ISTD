<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Organization;
use App\Models\User;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'vendor_id',
        'client_id',
        'secret_key',
        'income_source_sequence',
        'environment_url',
        'private_key_path',
        'public_cert_path',
        'vendor_name',
        'integration_type',
        'name',
        'settings',
        'sync_frequency',
        'auto_sync_enabled',
        'status',
        'sync_status',
        'last_sync_at',
        'last_sync_started_at',
        'last_tested_at',
        'last_error'
    ];

    protected $casts = [
        'settings' => 'array',
        'auto_sync_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
        'last_sync_started_at' => 'datetime',
        'last_tested_at' => 'datetime'
    ];

    /**
     * Get the organization that owns the integration
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user that owns the integration
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vendor that owns the integration
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get all sync logs for this integration
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'integration_id');
    }

    /**
     * Get recent sync logs (last 10)
     */
    public function recentSyncLogs(): HasMany
    {
        return $this->syncLogs()->orderBy('created_at', 'desc')->limit(10);
    }

    /**
     * Check if integration is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if integration is currently syncing
     */
    public function isSyncing(): bool
    {
        return $this->sync_status === 'syncing';
    }

    /**
     * Get display name for the integration
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->vendor_name;
    }
}
