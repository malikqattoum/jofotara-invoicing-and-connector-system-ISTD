<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'severity',
        'category',
        'is_active',
        'metadata',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'status'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function acknowledge(int $userId): void
    {
        $this->update([
            'is_active' => false,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now()
        ]);
    }

    public function resolve(int $userId, string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'resolution_notes' => $notes
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
}
