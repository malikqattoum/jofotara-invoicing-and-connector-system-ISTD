<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Organization;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'organization_id', 'client_id', 'secret_key', 'income_source_sequence', 'environment_url',
        'private_key_path', 'public_cert_path'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
