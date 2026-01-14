<?php

namespace App\Services;

use App\Models\Rank;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserRankHistory;
use App\Models\Setting;

class RankService
{
    public function checkAndUpgradeRank(User $user)
    {
        // Check global setting first
        if (!Setting::get('auto_rank_upgrade', true)) {
            return;
        }

        // Calculate Total Transaction Volume
        // Assuming we sum up 'deposit' transactions, or all allowed types for ranking.
        // Usually, VIP ranks are based on DEPOSITS or TOTAL VOLUME.
        // The HTML Sample column "Trx Amount" implies a target needed.
        // Let's assume this is the sum of completed "deposits" for now, or we could include transfers if business logic allows.
        // But typical HYIP/Investment/Banking apps use Deposit Volume.
        
        $totalVolume = Transaction::whereHas('account', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('status', 'completed')
            ->where('transaction_type', 'deposit') // Adjust if other types count towards rank
            ->sum('amount');

        // Find all active ranks
        $ranks = Rank::where('is_active', true)
            ->orderBy('min_transaction_volume', 'asc')
            ->get();

        // Determine the highest achievable rank
        $achievedRank = null;

        foreach ($ranks as $rank) {
            if ($totalVolume >= $rank->min_transaction_volume) {
                $achievedRank = $rank;
            }
        }

        if (!$achievedRank) {
           return;
        }

        // If user has no rank, or achieved rank is higher than current rank (by volume or specific order logic)
        // Since we ordered by volume ASC, if the new rank volume > current rank volume, it's an upgrade.
        
        $currentRank = $user->rank;
        
        // Check if we actually need to upgrade (is it a new rank?)
        if (!$currentRank || $achievedRank->id !== $currentRank->id) {
             // To prevent downgrading if volume drops (unlikely for lifetime deposits, but possible if calculated differently),
             // ensure we only upgrade.
             if ($currentRank && $achievedRank->min_transaction_volume < $currentRank->min_transaction_volume) {
                 return; 
             }
             
             $this->assignRank($user, $achievedRank);
        }
    }

    public function assignRank(User $user, Rank $rank)
    {
        // 1. Update User
        $user->update(['current_rank_id' => $rank->id]);

        // 2. Check if reward already given (via history)
        $exists = UserRankHistory::where('user_id', $user->id)
            ->where('rank_id', $rank->id)
            ->exists();

        if (!$exists) {
            // 3. Give Reward
            if ($rank->reward > 0) {
                $this->giveRankReward($user, $rank);
            }

            // 4. Log History
            UserRankHistory::create([
                'user_id' => $user->id,
                'rank_id' => $rank->id,
                'reward_amount' => $rank->reward,
                'unlocked_at' => now(),
            ]);
        }
    }

    protected function giveRankReward(User $user, Rank $rank)
    {
        $account = $user->accounts()->first(); // Or primary account
        if (!$account) return;

        Transaction::create([
            'account_id' => $account->id,
            'transaction_type' => 'referral_reward', // Or specific 'rank_reward' type if enum allows? reusing referral_reward or transfer_in for now as "Bonus"
            // Wait, we changed transaction_type to string! So we can use 'rank_reward' safely.
            'transaction_type' => 'rank_reward',
            'amount' => $rank->reward,
            'currency' => 'USD',
            'status' => 'completed',
            'description' => "Rank Achievement Bonus: {$rank->name}",
            'reference_number' => 'RANK-' . strtoupper(uniqid()),
            'completed_at' => now(),
        ]);

        $account->increment('balance', $rank->reward);
    }
}
