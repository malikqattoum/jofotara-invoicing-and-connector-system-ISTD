<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'value',
        'metadata',
        'recorded_at',
        'integration_id'
    ];

    protected $casts = [
        'value' => 'float',
        'metadata' => 'array',
        'recorded_at' => 'datetime'
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }
}
