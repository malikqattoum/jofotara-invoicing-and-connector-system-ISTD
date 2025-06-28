<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_setting_id',
        'sync_type',
        'status',
        'records_processed',
        'duration_seconds',
        'error_message',
        'metadata',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'records_processed' => 'integer',
        'duration_seconds' => 'float'
    ];

    public function integrationSetting(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        if ($this->duration_seconds < 60) {
            return round($this->duration_seconds, 1) . 's';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return $minutes . 'm ' . round($seconds, 1) . 's';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'success' => '<span class="badge badge-success">Success</span>',
            'failed' => '<span class="badge badge-danger">Failed</span>',
            'running' => '<span class="badge badge-warning">Running</span>',
            default => '<span class="badge badge-secondary">Unknown</span>'
        };
    }
}
