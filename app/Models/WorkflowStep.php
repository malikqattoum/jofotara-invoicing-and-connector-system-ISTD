<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'name',
        'type',
        'configuration',
        'conditions',
        'order',
        'continue_on_failure'
    ];

    protected $casts = [
        'configuration' => 'array',
        'conditions' => 'array',
        'order' => 'integer',
        'continue_on_failure' => 'boolean'
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
