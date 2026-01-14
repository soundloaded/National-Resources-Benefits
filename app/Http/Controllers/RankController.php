<?php

namespace App\Http\Controllers;

use App\Models\Rank;
use App\Models\User;
use App\Models\UserRankHistory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RankController extends Controller
{
    /**
     * Display user's rank information and all available ranks.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get all active ranks ordered by min_transaction_volume
        $ranks = Rank::where('is_active', true)
            ->orderBy('min_transaction_volume', 'asc')
            ->get()
            ->map(fn($rank) => [
                'id' => $rank->id,
                'name' => $rank->name,
                'description' => $rank->description,
                'icon' => $rank->icon ? asset('storage/' . $rank->icon) : null,
                'min_transaction_volume' => (float) $rank->min_transaction_volume,
                'reward' => (float) $rank->reward,
                'max_wallets' => $rank->max_wallets,
                'max_referral_level' => $rank->max_referral_level,
                'allowed_transaction_types' => $rank->allowed_transaction_types ?? [],
                'is_default' => $rank->is_default,
            ]);
        
        // Get user's current rank
        $currentRank = $user->rank ? [
            'id' => $user->rank->id,
            'name' => $user->rank->name,
            'description' => $user->rank->description,
            'icon' => $user->rank->icon ? asset('storage/' . $user->rank->icon) : null,
            'min_transaction_volume' => (float) $user->rank->min_transaction_volume,
            'reward' => (float) $user->rank->reward,
            'max_wallets' => $user->rank->max_wallets,
            'max_referral_level' => $user->rank->max_referral_level,
            'allowed_transaction_types' => $user->rank->allowed_transaction_types ?? [],
        ] : null;
        
        // Calculate user's total transaction volume for rank progress
        $userTransactionVolume = $this->calculateUserTransactionVolume($user);
        
        // Get next rank (if any)
        $nextRank = null;
        if ($currentRank) {
            $nextRank = Rank::where('is_active', true)
                ->where('min_transaction_volume', '>', $currentRank['min_transaction_volume'])
                ->orderBy('min_transaction_volume', 'asc')
                ->first();
        } else {
            // If no current rank, get the first rank
            $nextRank = Rank::where('is_active', true)
                ->orderBy('min_transaction_volume', 'asc')
                ->first();
        }
        
        $nextRankData = $nextRank ? [
            'id' => $nextRank->id,
            'name' => $nextRank->name,
            'min_transaction_volume' => (float) $nextRank->min_transaction_volume,
            'reward' => (float) $nextRank->reward,
            'progress' => $nextRank->min_transaction_volume > 0 
                ? min(100, ($userTransactionVolume / $nextRank->min_transaction_volume) * 100)
                : 100,
            'remaining' => max(0, $nextRank->min_transaction_volume - $userTransactionVolume),
        ] : null;
        
        // Get user's rank history
        $rankHistory = UserRankHistory::where('user_id', $user->id)
            ->with('rank:id,name,icon')
            ->orderBy('unlocked_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($history) => [
                'id' => $history->id,
                'rank_name' => $history->rank?->name ?? 'Unknown',
                'rank_icon' => $history->rank?->icon ? asset('storage/' . $history->rank->icon) : null,
                'reward_amount' => (float) $history->reward_amount,
                'unlocked_at' => $history->unlocked_at,
                'created_at' => $history->created_at->toIso8601String(),
            ]);
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Ranks/Index', [
            'ranks' => $ranks,
            'currentRank' => $currentRank,
            'nextRank' => $nextRankData,
            'userTransactionVolume' => $userTransactionVolume,
            'rankHistory' => $rankHistory,
            'settings' => $settings,
        ]);
    }
    
    /**
     * Calculate user's total transaction volume for rank progression.
     */
    private function calculateUserTransactionVolume(User $user): float
    {
        // Sum completed deposits and transfers
        $accountIds = $user->accounts()->pluck('id');
        
        $depositVolume = \App\Models\Transaction::whereIn('account_id', $accountIds)
            ->where('transaction_type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');
        
        $transferVolume = \App\Models\Transaction::whereIn('account_id', $accountIds)
            ->whereIn('transaction_type', ['transfer_out', 'transfer_in'])
            ->where('status', 'completed')
            ->sum('amount');
        
        return (float) ($depositVolume + $transferVolume);
    }
}
