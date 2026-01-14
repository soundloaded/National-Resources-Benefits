<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkedWithdrawalAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_name',
        'account_data',
        'is_default',
        'is_verified',
        'is_active',
        'verified_at',
    ];

    protected $casts = [
        'account_data' => 'array',
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user that owns this linked account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only verified accounts
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Get the display name with masked account info
     */
    public function getDisplayNameAttribute(): string
    {
        $data = $this->account_data;
        $masked = '';
        
        // Try to find and mask account number
        if (isset($data['account_number'])) {
            $num = $data['account_number'];
            $masked = '****' . substr($num, -4);
        }
        
        if ($masked) {
            return $this->account_name . ' (' . $masked . ')';
        }
        
        return $this->account_name;
    }

    /**
     * Get a specific field value from account data
     */
    public function getFieldValue(string $fieldName): mixed
    {
        return $this->account_data[$fieldName] ?? null;
    }

    /**
     * Check if user has reached their account limit
     */
    public static function hasReachedLimit(int $userId): bool
    {
        $limit = (int) Setting::get('withdrawal_account_limit', 3);
        $count = static::where('user_id', $userId)->active()->count();
        
        return $count >= $limit;
    }

    /**
     * Get the account limit
     */
    public static function getAccountLimit(): int
    {
        return (int) Setting::get('withdrawal_account_limit', 3);
    }

    /**
     * Set this account as default and unset others
     */
    public function setAsDefault(): void
    {
        // Unset other defaults for this user
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        $this->update(['is_default' => true]);
    }
}
