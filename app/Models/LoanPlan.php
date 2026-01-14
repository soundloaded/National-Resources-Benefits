<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoanPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'tagline',
        'min_amount',
        'max_amount',
        'interest_rate',
        'min_duration_months',
        'max_duration_months',
        'default_duration_months',
        'icon',
        'color',
        'gradient_from',
        'gradient_to',
        'features',
        'is_featured',
        'is_active',
        'sort_order',
        'processing_fee',
        'processing_fee_type',
        'approval_days',
        'requires_collateral',
        'early_repayment_allowed',
        'early_repayment_fee',
        'eligibility_criteria',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'early_repayment_fee' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'requires_collateral' => 'boolean',
        'early_repayment_allowed' => 'boolean',
        'features' => 'array',
        'eligibility_criteria' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });

        static::updating(function ($plan) {
            if ($plan->isDirty('name') && !$plan->isDirty('slug')) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getFormattedMinAmountAttribute()
    {
        return number_format($this->min_amount, 2);
    }

    public function getFormattedMaxAmountAttribute()
    {
        return number_format($this->max_amount, 2);
    }

    public function getGradientClassAttribute()
    {
        $from = $this->gradient_from ?? "{$this->color}-500";
        $to = $this->gradient_to ?? "{$this->color}-600";
        return "from-{$from} to-{$to}";
    }
}
