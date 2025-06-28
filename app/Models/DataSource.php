<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'connection_name',
        'configuration',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean'
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDatabase(): bool
    {
        return $this->type === 'database';
    }

    public function isAPI(): bool
    {
        return $this->type === 'api';
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    public function testConnection(): bool
    {
        // Implementation for testing data source connection
        return true;
    }
}
