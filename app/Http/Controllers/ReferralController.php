<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\ReferralSetting;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReferralController extends Controller
{
    /**
     * Display user's referral dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Ensure user has a referral code (for users created before the feature)
        if (empty($user->referral_code)) {
            $user->referral_code = User::generateReferralCode();
            $user->save();
        }
        
        // Get referral settings
        $referralSettings = ReferralSetting::first();
        
        // Get user's referrals (people they invited)
        $referrals = User::where('referred_by', $user->id)
            ->select(['id', 'name', 'email', 'created_at', 'kyc_verified_at'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($referral) => [
                'id' => $referral->id,
                'name' => $referral->name,
                'email' => $this->maskEmail($referral->email),
                'status' => $referral->kyc_verified_at ? 'verified' : 'pending',
                'joined_at' => $referral->created_at->toIso8601String(),
                'joined_at_human' => $referral->created_at->diffForHumans(),
            ]);
        
        // Get referral records with rewards
        $referralRecords = Referral::where('referrer_id', $user->id)
            ->with('referee:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($ref) => [
                'id' => $ref->id,
                'referee_name' => $ref->referee?->name ?? 'Pending',
                'status' => $ref->status,
                'reward_amount' => (float) $ref->reward_amount,
                'completed_at' => $ref->completed_at?->toIso8601String(),
                'created_at' => $ref->created_at->toIso8601String(),
            ]);
        
        // Calculate stats
        $stats = [
            'total_referrals' => $referrals->count(),
            'verified_referrals' => $referrals->where('status', 'verified')->count(),
            'pending_referrals' => $referrals->where('status', 'pending')->count(),
            'total_earnings' => Referral::where('referrer_id', $user->id)
                ->whereIn('status', ['completed', 'paid'])
                ->sum('reward_amount'),
            'pending_earnings' => Referral::where('referrer_id', $user->id)
                ->where('status', 'pending')
                ->sum('reward_amount'),
        ];
        
        // Get referrer info (who referred this user)
        $referrer = null;
        if ($user->referred_by) {
            $referrerUser = User::find($user->referred_by);
            if ($referrerUser) {
                $referrer = [
                    'name' => $referrerUser->name,
                    'joined_at' => $user->created_at->toIso8601String(),
                ];
            }
        }
        
        // Build referral link
        $referralLink = url('/register?ref=' . $user->referral_code);
        
        // Get max referral level from user's rank (default to 1 if no rank)
        $maxReferralLevel = $user->rank?->max_referral_level ?? 1;
        
        // Helper function to process commission levels
        $processLevels = function($levels) use ($maxReferralLevel) {
            return collect($levels)
                ->sortBy('level')
                ->values()
                ->map(fn($item) => [
                    'level' => $item['level'] ?? 0,
                    'commission' => $item['commission'] ?? 0,
                    'unlocked' => ($item['level'] ?? 0) <= $maxReferralLevel,
                ])->toArray();
        };
        
        // Get deposit commission levels
        $depositLevels = [];
        if ($referralSettings && $referralSettings->deposit_enabled && $referralSettings->deposit_levels) {
            $depositLevels = $processLevels($referralSettings->deposit_levels);
        }
        
        // Get payment commission levels
        $paymentLevels = [];
        if ($referralSettings && $referralSettings->payment_enabled && $referralSettings->payment_levels) {
            $paymentLevels = $processLevels($referralSettings->payment_levels);
        }
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
            'deposit_enabled' => $referralSettings?->deposit_enabled ?? false,
            'payment_enabled' => $referralSettings?->payment_enabled ?? false,
            'deposit_levels' => $depositLevels,
            'payment_levels' => $paymentLevels,
        ];
        
        return Inertia::render('Referrals/Index', [
            'referralCode' => $user->referral_code,
            'referralLink' => $referralLink,
            'referrals' => $referrals,
            'referralRecords' => $referralRecords,
            'stats' => $stats,
            'referrer' => $referrer,
            'maxReferralLevel' => $maxReferralLevel,
            'settings' => $settings,
        ]);
    }
    
    /**
     * Show referral earnings history.
     */
    public function earnings(Request $request)
    {
        $user = $request->user();
        
        $earnings = Referral::where('referrer_id', $user->id)
            ->whereIn('status', ['completed', 'paid'])
            ->with('referee:id,name')
            ->orderBy('completed_at', 'desc')
            ->paginate(15)
            ->through(fn($ref) => [
                'id' => $ref->id,
                'referee_name' => $ref->referee?->name ?? 'Unknown',
                'reward_amount' => (float) $ref->reward_amount,
                'status' => $ref->status,
                'completed_at' => $ref->completed_at?->format('M d, Y H:i'),
            ]);
        
        $totalEarnings = Referral::where('referrer_id', $user->id)
            ->whereIn('status', ['completed', 'paid'])
            ->sum('reward_amount');
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Referrals/Earnings', [
            'earnings' => $earnings,
            'totalEarnings' => (float) $totalEarnings,
            'settings' => $settings,
        ]);
    }
    
    /**
     * Mask email for privacy.
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }
        
        $name = $parts[0];
        $domain = $parts[1];
        
        $maskedName = strlen($name) > 2 
            ? substr($name, 0, 2) . str_repeat('*', min(5, strlen($name) - 2))
            : $name;
        
        return $maskedName . '@' . $domain;
    }
}
