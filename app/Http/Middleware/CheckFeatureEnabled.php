<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureEnabled
{
    /**
     * Feature mapping from route parameter to setting key
     */
    protected array $featureMap = [
        'accounts' => 'feature_accounts',
        'transactions' => 'feature_transactions',
        'deposit' => 'feature_deposit',
        'withdraw' => 'feature_withdraw',
        'transfer' => 'feature_transfer',
        'transfer.internal' => 'feature_transfer_internal',
        'transfer.own' => 'feature_transfer_own',
        'transfer.domestic' => 'feature_transfer_domestic',
        'transfer.wire' => 'feature_transfer_wire',
        'voucher' => 'feature_voucher',
        'loans' => 'feature_loans',
        'grants' => 'feature_grants',
        'funding-sources' => 'feature_funding_sources',
        'applications' => 'feature_applications',
        'kyc' => 'feature_kyc',
        'support' => 'feature_support',
        'support-tickets' => 'feature_support',
        'ranks' => 'feature_ranks',
        'referrals' => 'feature_referrals',
        'notifications' => 'feature_notifications',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $settingKey = $this->featureMap[$feature] ?? "feature_{$feature}";
        
        // Check if the feature is enabled (default to true if not set)
        $isEnabled = (bool) Setting::get($settingKey, true);
        
        if (!$isEnabled) {
            // For transfer sub-features, also check if master transfer is enabled
            if (str_starts_with($feature, 'transfer.')) {
                $masterEnabled = (bool) Setting::get('feature_transfer', true);
                if (!$masterEnabled) {
                    return $this->featureDisabledResponse($request, 'transfer');
                }
            }
            
            return $this->featureDisabledResponse($request, $feature);
        }
        
        return $next($request);
    }

    /**
     * Return appropriate response when feature is disabled
     */
    protected function featureDisabledResponse(Request $request, string $feature): Response
    {
        $featureLabel = ucfirst(str_replace(['.', '-', '_'], ' ', $feature));
        
        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$featureLabel} feature is currently disabled.",
                'feature' => $feature,
            ], 403);
        }

        // For Inertia requests, redirect to dashboard with error
        if ($request->header('X-Inertia')) {
            return redirect()->route('dashboard')
                ->with('error', "{$featureLabel} feature is currently disabled by administrator.");
        }

        // For regular requests, redirect with error
        return redirect()->route('dashboard')
            ->with('error', "{$featureLabel} feature is currently disabled by administrator.");
    }
}
