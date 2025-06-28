<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationLog extends Model
{
    use HasFactory;

    protected $table = 'notification_logs';

    protected $fillable = [
        'rule_id',
        'channel',
        'recipient',
        'message',
        'status',
        'response',
        'sent_at',
        'delivered_at',
        'failed_at',
        'error_message'
    ];

    protected $casts = [
        'message' => 'array',
        'response' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime'
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class);
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
