<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycTemplate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'applicable_to' => 'array',
        'form_fields' => 'array',
        'is_active' => 'boolean',
    ];
}
