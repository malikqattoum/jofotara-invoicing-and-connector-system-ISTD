<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PipelineExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'status',
        'started_at',
        'completed_at',
        'failed_at',
        'records_processed',
        'records_success',
        'records_failed',
        'metrics',
        'error_message'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'records_processed' => 'integer',
        'records_success' => 'integer',
        'records_failed' => 'integer',
        'metrics' => 'array'
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(DataPipeline::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getSuccessRate(): float
    {
        if ($this->records_processed === 0) return 100;
        return ($this->records_success / $this->records_processed) * 100;
    }
}
