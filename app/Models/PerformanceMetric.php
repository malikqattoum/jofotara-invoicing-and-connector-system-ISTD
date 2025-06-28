<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_name',
        'value',
        'unit',
        'category',
        'metadata',
        'recorded_at'
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

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', today());
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }

    public function scopeByMetricName($query, string $metricName)
    {
        return $query->where('metric_name', $metricName);
    }
}
