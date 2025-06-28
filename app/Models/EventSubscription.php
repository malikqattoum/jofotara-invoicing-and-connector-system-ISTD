<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'stream_name',
        'filters',
        'status',
        'created_by'
    ];

    protected $casts = [
        'filters' => 'array'
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(EventStream::class, 'stream_name', 'name');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
