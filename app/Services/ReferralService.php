<?php

namespace App\Services;

use App\Models\ReferralSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public function distributeDepositRewards(Transaction $depositTransaction)
    {
        // Check global referral setting
        if (!Setting::get('referral_enabled', true)) {
            return;
        }

        $settings = ReferralSetting::first();
        if (!$settings || !$settings->deposit_enabled || empty($settings->deposit_levels)) {
            return;
        }

        $user = $depositTransaction->account->user; // Assuming Transaction -> Account -> User relation
        if (!$user) return;

        $amount = $depositTransaction->amount;
        $levels = $settings->deposit_levels; // JSON array: [['level' => 1, 'commission' => 10], ...]

        // Normalize levels array for easier lookup by level number
        $levelMap = [];
        foreach ($levels as $l) {
            $levelMap[$l['level']] = $l['commission'];
        }

        $currentUpline = $user->referrer;
        $currentLevel = 1;

        while ($currentUpline && isset($levelMap[$currentLevel])) {
            $percentage = $levelMap[$currentLevel];
            $commissionAmount = ($amount * $percentage) / 100;

            if ($commissionAmount > 0) {
                $this->createCommissionTransaction($currentUpline, $commissionAmount, $user, $currentLevel, 'deposit');
            }

            // Move up
            $currentUpline = $currentUpline->referrer;
            $currentLevel++;
        }
    }

    public function distributePaymentRewards(Transaction $paymentTransaction)
    {
         $settings = ReferralSetting::first();
        if (!$settings || !$settings->payment_enabled || empty($settings->payment_levels)) {
            return;
        }

        $user = $paymentTransaction->account->user;
        if (!$user) return;

        $amount = $paymentTransaction->amount; // Assuming absolute positive amount
        $levels = $settings->payment_levels; 

        $levelMap = [];
        foreach ($levels as $l) {
            $levelMap[$l['level']] = $l['commission'];
        }

        $currentUpline = $user->referrer;
        $currentLevel = 1;

        while ($currentUpline && isset($levelMap[$currentLevel])) {
            $percentage = $levelMap[$currentLevel];
            $commissionAmount = ($amount * $percentage) / 100;

            if ($commissionAmount > 0) {
                 $this->createCommissionTransaction($currentUpline, $commissionAmount, $user, $currentLevel, 'payment');
            }

            $currentUpline = $currentUpline->referrer;
            $currentLevel++;
        }
    }

    protected function createCommissionTransaction(User $beneficiary, $amount, User $sourceUser, $level, $type)
    {
        // Assuming beneficiary has a primary account or we create one or just use the first one.
        // For simplicity, let's assume User hasOne Account or hasMany and we pick the first.
        // Or create a new transaction on the user's primary account.
        
        $account = $beneficiary->accounts()->first();
        if (!$account) {
            // Log error or create account? 
            // For now, skip if no account
            return;
        }

        Transaction::create([
            'account_id' => $account->id,
            'transaction_type' => 'referral_reward', // You might need to add this to enum if validation exists
            'amount' => $amount,
            'currency' => 'USD', // Defaulting to USD for now
            'status' => 'completed',
            'completed_at' => now(),
            'description' => "Referral Reward (Level $level) from {$sourceUser->name} - " . ucfirst($type),
            'metadata' => [
                'source_user_id' => $sourceUser->id,
                'level' => $level,
                'reward_type' => $type
            ]
        ]);
        
        // Also update Account balance
        $account->increment('balance', $amount);
    }
}
