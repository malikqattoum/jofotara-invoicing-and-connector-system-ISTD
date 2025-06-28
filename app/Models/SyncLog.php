<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'integration_id',
        'sync_type',
        'status',
        'started_at',
        'completed_at',
        'result_message',
        'error_message',
        'error_details',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class, 'integration_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
                    ->orderBy('created_at', 'desc');
    }

    public function markAsCompleted(string $message = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'result_message' => $message
        ]);
    }

    public function markAsFailed(string $errorMessage, array $errorDetails = null): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'error_details' => $errorDetails
        ]);
    }

    public function getDurationMinutesAttribute(): int
    {
        if (!$this->started_at || !$this->completed_at) {
            return 0;
        }

        return $this->started_at->diffInMinutes($this->completed_at);
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
