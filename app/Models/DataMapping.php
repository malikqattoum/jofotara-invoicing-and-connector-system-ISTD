<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_setting_id',
        'data_type',
        'source_field',
        'target_field',
        'transformation_rules',
        'is_required',
        'default_value'
    ];

    protected $casts = [
        'transformation_rules' => 'array',
        'is_required' => 'boolean'
    ];

    public function integrationSetting(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class);
    }

    public function scopeByDataType($query, string $dataType)
    {
        return $query->where('data_type', $dataType);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
