<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundingSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'amount_min',
        'amount_max',
        'category',
        'funding_category_id',
        'url',
        'deadline',
        'is_active',
        'is_internal',
        'requirements',
        'form_fields',
        'max_applications_per_user',
        'total_slots',
    ];

    protected $casts = [
        'amount_min' => 'decimal:2',
        'amount_max' => 'decimal:2',
        'deadline' => 'date',
        'is_active' => 'boolean',
        'is_internal' => 'boolean',
        'form_fields' => 'array',
    ];

    /**
     * Get requirements as an array (one per line)
     */
    public function getRequirementsListAttribute(): array
    {
        if (empty($this->requirements)) {
            return [];
        }
        
        // If stored as string, split by newlines
        if (is_string($this->requirements)) {
            return array_filter(array_map('trim', explode("\n", $this->requirements)));
        }
        
        // If already an array
        return $this->requirements;
    }

    public function fundingCategory()
    {
        return $this->belongsTo(FundingCategory::class);
    }

    public function applications()
    {
        return $this->hasMany(FundingApplication::class);
    }

    /**
     * Get count of approved/disbursed applications (slots used)
     */
    public function getSlotsUsedAttribute(): int
    {
        return $this->applications()
            ->whereIn('status', ['approved', 'disbursed'])
            ->count();
    }

    /**
     * Get remaining slots
     */
    public function getSlotsRemainingAttribute(): ?int
    {
        if ($this->total_slots === null) {
            return null; // Unlimited
        }
        return max(0, $this->total_slots - $this->slots_used);
    }

    /**
     * Check if user can apply
     */
    public function canUserApply(User $user): array
    {
        // Check if active
        if (!$this->is_active) {
            return ['can_apply' => false, 'reason' => 'This funding source is no longer active.'];
        }

        // Check deadline
        if ($this->deadline && $this->deadline->isPast()) {
            return ['can_apply' => false, 'reason' => 'The application deadline has passed.'];
        }

        // Check slots
        if ($this->total_slots !== null && $this->slots_remaining <= 0) {
            return ['can_apply' => false, 'reason' => 'All available slots have been filled.'];
        }

        // Check user's application limit
        if ($this->max_applications_per_user !== null) {
            $userApplications = $this->applications()
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->count();
            
            if ($userApplications >= $this->max_applications_per_user) {
                return ['can_apply' => false, 'reason' => 'You have reached the maximum number of applications for this funding source.'];
            }
        }

        // Check for existing pending/under_review application
        $existingActive = $this->applications()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'under_review'])
            ->exists();
        
        if ($existingActive) {
            return ['can_apply' => false, 'reason' => 'You already have an active application for this funding source.'];
        }

        return ['can_apply' => true, 'reason' => null];
    }
}
