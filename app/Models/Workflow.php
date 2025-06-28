<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'trigger_event',
        'trigger_conditions',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'is_active' => 'boolean'
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('order');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getLatestExecution(): ?WorkflowExecution
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
