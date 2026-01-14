<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'deposit_enabled' => 'boolean',
        'deposit_levels' => 'array',
        'payment_enabled' => 'boolean',
        'payment_levels' => 'array',
    ];
}
