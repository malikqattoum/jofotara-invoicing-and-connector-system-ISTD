<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_setting_id',
        'name',
        'event_type',
        'channel',
        'recipients',
        'conditions',
        'is_active'
    ];

    protected $casts = [
        'recipients' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean'
    ];

    public function integrationSetting(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
