<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'retention_days',
        'max_events',
        'schema',
        'configuration',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'retention_days' => 'integer',
        'max_events' => 'integer',
        'schema' => 'array',
        'configuration' => 'array',
        'is_active' => 'boolean'
    ];

    public function events(): HasMany
    {
        return $this->hasMany(StreamedEvent::class, 'stream_name', 'name');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(EventSubscription::class, 'stream_name', 'name');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getEventCount(): int
    {
        return $this->events()->count();
    }

    public function getActiveSubscriptionsCount(): int
    {
        return $this->subscriptions()->where('status', 'active')->count();
    }
}
