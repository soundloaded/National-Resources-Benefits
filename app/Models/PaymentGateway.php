<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PaymentGateway extends Model
{
    use HasUuids, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'credentials' => 'array',  // Changed from encrypted:array for Filament compatibility
        'supported_currencies' => 'array',
        'is_active' => 'boolean',
        'min_limit' => 'decimal:2',
        'max_limit' => 'decimal:2',
        'fee_fixed' => 'decimal:2',
        'fee_percentage' => 'decimal:2',
    ];

    /**
     * Boot the model - sync category_new to category for backwards compatibility
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($gateway) {
            // Sync category_new to category (old column) for backwards compatibility
            if ($gateway->category_new) {
                $gateway->category = $gateway->category_new === 'payment' ? 'deposit' : $gateway->category_new;
            }
        });
    }

    /**
     * Available providers for automatic gateways
     */
    public const PROVIDERS = [
        'stripe' => 'Stripe',
        'paystack' => 'Paystack',
        'flutterwave' => 'Flutterwave',
    ];

    /**
     * Gateway types
     */
    public const TYPES = [
        'automatic' => 'Automatic (API)',
        'manual' => 'Manual',
    ];

    /**
     * Gateway categories
     */
    public const CATEGORIES = [
        'deposit' => 'Deposit',
        'withdrawal' => 'Withdrawal',
        'payment' => 'Payment (Generic)',
    ];

    /**
     * Scope for active gateways
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for automatic gateways
     */
    public function scopeAutomatic($query)
    {
        return $query->where('type', 'automatic');
    }

    /**
     * Scope for manual gateways
     */
    public function scopeManual($query)
    {
        return $query->where('type', 'manual');
    }

    /**
     * Scope by category
     */
    public function scopeForCategory($query, string $category)
    {
        return $query->where('category_new', $category);
    }

    /**
     * Scope for deposit gateways
     */
    public function scopeForDeposit($query)
    {
        return $query->where('category_new', 'deposit');
    }

    /**
     * Scope for withdrawal gateways
     */
    public function scopeForWithdrawal($query)
    {
        return $query->where('category_new', 'withdrawal');
    }

    /**
     * Scope for payment gateways (generic payments like loan repayment)
     */
    public function scopeForPayment($query)
    {
        return $query->where('category_new', 'payment');
    }

    /**
     * Scope ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if gateway is automatic
     */
    public function isAutomatic(): bool
    {
        return $this->type === 'automatic';
    }

    /**
     * Check if gateway is manual
     */
    public function isManual(): bool
    {
        return $this->type === 'manual';
    }

    /**
     * Check if gateway is in live mode
     */
    public function isLiveMode(): bool
    {
        return $this->mode === 'live';
    }

    /**
     * Get credential by key
     */
    public function getCredential(string $key, $default = null)
    {
        return $this->credentials[$key] ?? $default;
    }

    /**
     * Get the appropriate API keys based on mode
     */
    public function getApiKeys(): array
    {
        $prefix = $this->isLiveMode() ? 'live_' : 'test_';
        
        return [
            'public_key' => $this->getCredential($prefix . 'public_key'),
            'secret_key' => $this->getCredential($prefix . 'secret_key'),
            'webhook_secret' => $this->getCredential($prefix . 'webhook_secret'),
        ];
    }

    /**
     * Calculate fee for a given amount
     */
    public function calculateFee(float $amount): float
    {
        $percentageFee = $amount * ($this->fee_percentage / 100);
        return round($percentageFee + $this->fee_fixed, 2);
    }

    /**
     * Calculate total with fee
     */
    public function calculateTotal(float $amount): float
    {
        return round($amount + $this->calculateFee($amount), 2);
    }

    /**
     * Check if amount is within limits
     */
    public function isAmountValid(float $amount): bool
    {
        if ($amount < $this->min_limit) {
            return false;
        }
        
        if ($this->max_limit && $amount > $this->max_limit) {
            return false;
        }
        
        return true;
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        
        // Return default logo based on provider
        if ($this->provider) {
            return asset("images/gateways/{$this->provider}.svg");
        }
        
        return null;
    }
}
