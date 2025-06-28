<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataAnomaly extends Model
{
    use HasFactory;

    protected $table = 'data_anomalies';

    protected $fillable = [
        'integration_id',
        'type',
        'severity',
        'description',
        'detected_at',
        'resolved_at',
        'data',
        'status',
        'resolved_by'
    ];

    protected $casts = [
        'data' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class);
    }

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
}
