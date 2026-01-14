<?php

namespace Database\Seeders;

use App\Models\LoanPlan;
use Illuminate\Database\Seeder;

class LoanPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Quick Loan',
                'slug' => 'quick-loan',
                'tagline' => 'Fast approval for urgent needs',
                'description' => 'Get quick access to funds with minimal documentation. Perfect for short-term financial needs.',
                'min_amount' => 100,
                'max_amount' => 5000,
                'interest_rate' => 5,
                'min_duration_months' => 1,
                'max_duration_months' => 3,
                'default_duration_months' => 3,
                'icon' => 'pi-bolt',
                'color' => 'green',
                'gradient_from' => 'green-500',
                'gradient_to' => 'emerald-600',
                'features' => [
                    ['icon' => 'pi-check-circle', 'title' => 'No Collateral', 'description' => 'Unsecured loan'],
                    ['icon' => 'pi-clock', 'title' => 'Quick Disbursement', 'description' => 'Within 24 hours'],
                ],
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 1,
                'processing_fee' => 0,
                'approval_days' => 1,
                'requires_collateral' => false,
                'early_repayment_allowed' => true,
            ],
            [
                'name' => 'Standard Loan',
                'slug' => 'standard-loan',
                'tagline' => 'Perfect balance of amount & term',
                'description' => 'Our most popular loan option with competitive rates and flexible repayment terms.',
                'min_amount' => 500,
                'max_amount' => 15000,
                'interest_rate' => 4.5,
                'min_duration_months' => 3,
                'max_duration_months' => 6,
                'default_duration_months' => 6,
                'icon' => 'pi-wallet',
                'color' => 'blue',
                'gradient_from' => 'blue-500',
                'gradient_to' => 'blue-700',
                'features' => [
                    ['icon' => 'pi-shield', 'title' => 'Early Repayment', 'description' => 'No penalty fees'],
                    ['icon' => 'pi-calendar', 'title' => 'Flexible Terms', 'description' => '3-6 month options'],
                ],
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 2,
                'processing_fee' => 0,
                'approval_days' => 1,
                'requires_collateral' => false,
                'early_repayment_allowed' => true,
            ],
            [
                'name' => 'Premium Loan',
                'slug' => 'premium-loan',
                'tagline' => 'Maximum amount, best rates',
                'description' => 'For larger financial needs with the lowest interest rates and extended repayment periods.',
                'min_amount' => 1000,
                'max_amount' => 50000,
                'interest_rate' => 4,
                'min_duration_months' => 6,
                'max_duration_months' => 12,
                'default_duration_months' => 12,
                'icon' => 'pi-crown',
                'color' => 'purple',
                'gradient_from' => 'purple-500',
                'gradient_to' => 'violet-600',
                'features' => [
                    ['icon' => 'pi-money-bill', 'title' => 'Higher Limits', 'description' => 'Up to $50,000'],
                    ['icon' => 'pi-user', 'title' => 'Priority Support', 'description' => 'Dedicated manager'],
                ],
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 3,
                'processing_fee' => 0,
                'approval_days' => 2,
                'requires_collateral' => false,
                'early_repayment_allowed' => true,
            ],
        ];

        foreach ($plans as $plan) {
            LoanPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
