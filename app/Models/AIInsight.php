<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AIInsight extends Model
{
    use HasFactory;

    protected $table = 'ai_insights';

    protected $fillable = [
        'integration_id',
        'type',
        'insights',
        'confidence_score',
        'generated_at',
        'expires_at'
    ];

    protected $casts = [
        'insights' => 'array',
        'confidence_score' => 'float',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence_score >= 0.8;
    }
}
