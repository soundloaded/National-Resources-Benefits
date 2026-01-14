<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'verified_at' => 'datetime',
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function template(): BelongsTo
    {
        return $this->belongsTo(KycTemplate::class, 'kyc_template_id');
    }
}
