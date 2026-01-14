<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'max_uses' => 'integer',
        'current_uses' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'expiration_date' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    /**
     * Check if voucher is currently valid for redemption
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->start_date && now()->lt($this->start_date)) {
            return false;
        }

        if ($this->expiration_date && now()->gt($this->expiration_date)) {
            return false;
        }

        if ($this->voucher_type === 'single_use' && $this->current_uses >= 1) {
            return false;
        }

        if ($this->voucher_type === 'multi_use' && $this->max_uses && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Check if user has already redeemed this voucher
     */
    public function hasBeenRedeemedBy(User $user): bool
    {
        return $this->redemptions()->where('user_id', $user->id)->exists();
    }

    /**
     * Get remaining uses
     */
    public function getRemainingUsesAttribute(): ?int
    {
        if ($this->voucher_type === 'single_use') {
            return $this->current_uses >= 1 ? 0 : 1;
        }

        if ($this->max_uses === null) {
            return null; // unlimited
        }

        return max(0, $this->max_uses - $this->current_uses);
    }

    /**
     * Get status label
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->expiration_date && now()->gt($this->expiration_date)) {
            return 'expired';
        }

        if ($this->start_date && now()->lt($this->start_date)) {
            return 'scheduled';
        }

        if ($this->voucher_type === 'single_use' && $this->current_uses >= 1) {
            return 'used';
        }

        if ($this->voucher_type === 'multi_use' && $this->max_uses && $this->current_uses >= $this->max_uses) {
            return 'exhausted';
        }

        return 'active';
    }

    /**
     * Scope for active vouchers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            });
    }

    /**
     * Scope for available vouchers (not fully used)
     */
    public function scopeAvailable($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->where('voucher_type', 'multi_use')
                    ->where(function ($q2) {
                        $q2->whereNull('max_uses')
                            ->orWhereColumn('current_uses', '<', 'max_uses');
                    });
            })
            ->orWhere(function ($q) {
                $q->where('voucher_type', 'single_use')
                    ->where('current_uses', '<', 1);
            });
    }
}
