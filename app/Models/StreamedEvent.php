<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StreamedEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'stream_name',
        'event_type',
        'event_data',
        'metadata',
        'version'
    ];

    protected $casts = [
        'event_data' => 'array',
        'metadata' => 'array',
        'version' => 'integer'
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(EventStream::class, 'stream_name', 'name');
    }

    public function getEventSize(): int
    {
        return strlen(json_encode($this->event_data));
    }
}
