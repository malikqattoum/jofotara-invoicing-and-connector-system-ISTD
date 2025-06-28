<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataEncryption extends Model
{
    use HasFactory;

    protected $table = 'data_encryptions';

    protected $fillable = [
        'field_name',
        'encryption_algorithm',
        'key_version',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'key_version' => 'integer'
    ];

    public function isCurrentVersion(): bool
    {
        return $this->key_version === config('security.current_key_version', 1);
    }
}
