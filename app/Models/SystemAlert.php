<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'severity',
        'message',
        'value',
        'threshold',
        'metadata',
        'status',
        'resolved_at',
        'resolved_by'
    ];

    protected $casts = [
        'value' => 'float',
        'threshold' => 'float',
        'metadata' => 'array',
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

    public function resolve(User $user): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $user->id
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
}
