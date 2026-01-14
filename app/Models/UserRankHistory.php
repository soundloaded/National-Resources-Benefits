<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRankHistory extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'unlocked_at' => 'datetime',
        'reward_amount' => 'decimal:2',
    ];

    /**
     * Get the rank associated with this history entry.
     */
    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    /**
     * Get the user associated with this history entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
