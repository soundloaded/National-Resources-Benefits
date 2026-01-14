<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'allowed_transaction_types' => 'array',
        'min_transaction_volume' => 'decimal:2',
        'reward' => 'decimal:2',
    ];
}
