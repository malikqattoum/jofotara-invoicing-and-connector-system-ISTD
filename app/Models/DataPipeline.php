<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataPipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'data_sources',
        'transformations',
        'validation_rules',
        'destination',
        'schedule',
        'configuration',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'data_sources' => 'array',
        'transformations' => 'array',
        'validation_rules' => 'array',
        'destination' => 'array',
        'schedule' => 'array',
        'configuration' => 'array',
        'is_active' => 'boolean'
    ];

    public function executions(): HasMany
    {
        return $this->hasMany(PipelineExecution::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getLatestExecution(): ?PipelineExecution
    {
        return $this->executions()->latest()->first();
    }

    public function getSuccessRate(): float
    {
        $total = $this->executions()->count();
        if ($total === 0) return 100;

        $successful = $this->executions()->where('status', 'completed')->count();
        return ($successful / $total) * 100;
    }
}
