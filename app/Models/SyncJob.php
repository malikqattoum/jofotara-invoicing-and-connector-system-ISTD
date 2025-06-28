<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SyncJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_id',
        'type',
        'status',
        'priority',
        'configuration',
        'scheduled_at',
        'started_at',
        'completed_at',
        'failed_at',
        'execution_time',
        'records_processed',
        'records_synced',
        'records_failed',
        'error_message',
        'worker_id',
        'parent_job_id',
        'retry_count',
        'created_by'
    ];

    protected $casts = [
        'configuration' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'execution_time' => 'float',
        'records_processed' => 'integer',
        'records_synced' => 'integer',
        'records_failed' => 'integer',
        'retry_count' => 'integer'
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class);
    }

    public function parentJob(): BelongsTo
    {
        return $this->belongsTo(SyncJob::class, 'parent_job_id');
    }

    public function childJobs()
    {
        return $this->hasMany(SyncJob::class, 'parent_job_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function getSuccessRate(): float
    {
        if ($this->records_processed === 0) return 0;
        return ($this->records_synced / $this->records_processed) * 100;
    }
}
