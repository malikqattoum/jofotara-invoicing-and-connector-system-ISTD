<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_setting_id',
        'sync_type',
        'frequency',
        'frequency_value',
        'time_of_day',
        'day_of_week',
        'day_of_month',
        'timezone',
        'is_active',
        'filters',
        'last_run_at',
        'next_run_at',
        'run_count',
        'error_count',
        'last_error'
    ];

    protected $casts = [
        'filters' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'is_active' => 'boolean',
        'run_count' => 'integer',
        'error_count' => 'integer'
    ];

    public function integrationSetting(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    public function getFrequencyDescriptionAttribute(): string
    {
        return match ($this->frequency) {
            'hourly' => "Every {$this->frequency_value} hour(s)",
            'daily' => "Daily at {$this->time_of_day}",
            'weekly' => "Weekly on " . $this->getDayName($this->day_of_week) . " at {$this->time_of_day}",
            'monthly' => "Monthly on day {$this->day_of_month} at {$this->time_of_day}",
            'custom' => "Custom schedule",
            default => "Unknown frequency"
        };
    }

    protected function getDayName(int $dayOfWeek): string
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return $days[$dayOfWeek] ?? 'Unknown';
    }
}
