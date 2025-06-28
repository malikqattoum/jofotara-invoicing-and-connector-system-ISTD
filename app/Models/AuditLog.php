<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'event',
        'ip_address',
        'user_agent',
        'context',
        'timestamp',
        'session_id',
        'severity',
        'fingerprint'
    ];

    protected $casts = [
        'context' => 'array',
        'timestamp' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isSecurityRelated(): bool
    {
        return in_array($this->event, [
            'user.login_failed',
            'security.breach_detected',
            'permission.denied',
            'unauthorized_access'
        ]);
    }
}
