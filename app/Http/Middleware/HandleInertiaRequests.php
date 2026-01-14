<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->getFilamentAvatarUrl(),
                    'kyc_verified' => (bool) $user->kyc_verified_at,
                    'is_active' => (bool) $user->is_active,
                ] : null,
                'permissions' => $user ? [
                    'can_deposit' => (bool) $user->can_deposit,
                    'can_withdraw' => (bool) $user->can_withdraw,
                    'can_transfer' => (bool) $user->can_transfer,
                    'can_use_voucher' => (bool) $user->can_use_voucher,
                    'can_exchange' => (bool) $user->can_exchange,
                    'can_request' => (bool) $user->can_request,
                ] : null,
                'impersonating' => $request->session()->has('impersonator_id'),
            ],
            'settings' => [
                'site_name' => Setting::get('site_name', 'National Resource Benefits'),
                'site_logo' => Setting::get('site_logo') ? asset('storage/' . Setting::get('site_logo')) : null,
                'site_logo_dark' => Setting::get('site_logo_dark') ? asset('storage/' . Setting::get('site_logo_dark')) : null,
                'site_favicon' => Setting::get('site_favicon') ? asset('storage/' . Setting::get('site_favicon')) : null,
                'currency_symbol' => Setting::get('currency_symbol', '$'),
                'support_email' => Setting::get('support_email'),
                'support_phone' => Setting::get('support_phone'),
                'maintenance_mode' => (bool) Setting::get('maintenance_mode', false),
                // Feature toggles
                'referral_enabled' => (bool) Setting::get('referral_enabled', true),
                'loans_enabled' => (bool) Setting::get('loans_enabled', true),
                'transfer_internal_active' => (bool) Setting::get('transfer_internal_active', true),
                'transfer_self_active' => (bool) Setting::get('transfer_self_active', true),
                // Limits (for frontend validation)
                'deposit_min' => (float) Setting::get('deposit_min', 10),
                'deposit_max' => (float) Setting::get('deposit_max', 50000),
                'withdrawal_min' => (float) Setting::get('withdrawal_min', 50),
                'withdrawal_max' => (float) Setting::get('withdrawal_max', 50000),
                'transfer_internal_min' => (float) Setting::get('transfer_internal_min', 1),
                'transfer_internal_max' => (float) Setting::get('transfer_internal_max', 5000),
                // Social links
                'social' => [
                    'facebook' => Setting::get('social_facebook'),
                    'twitter' => Setting::get('social_twitter'),
                    'instagram' => Setting::get('social_instagram'),
                    'linkedin' => Setting::get('social_linkedin'),
                    'youtube' => Setting::get('social_youtube'),
                    'tiktok' => Setting::get('social_tiktok'),
                ],
            ],
            // Feature flags - controls what's visible in sidebar and accessible
            'features' => [
                'accounts' => (bool) Setting::get('feature_accounts', true),
                'transactions' => (bool) Setting::get('feature_transactions', true),
                'deposit' => (bool) Setting::get('feature_deposit', true),
                'withdraw' => (bool) Setting::get('feature_withdraw', true),
                'withdraw_bank' => (bool) Setting::get('feature_withdraw_bank', true),
                'withdraw_express' => (bool) Setting::get('feature_withdraw_express', true),
                'withdraw_verification' => (bool) Setting::get('feature_withdraw_verification', true),
                'transfer' => (bool) Setting::get('feature_transfer', true),
                'transfer_internal' => (bool) Setting::get('feature_transfer_internal', true),
                'transfer_own' => (bool) Setting::get('feature_transfer_own', true),
                'transfer_domestic' => (bool) Setting::get('feature_transfer_domestic', true),
                'transfer_wire' => (bool) Setting::get('feature_transfer_wire', true),
                'voucher' => (bool) Setting::get('feature_voucher', true),
                'loans' => (bool) Setting::get('feature_loans', true),
                'grants' => (bool) Setting::get('feature_grants', true),
                'funding_sources' => (bool) Setting::get('feature_funding_sources', true),
                'applications' => (bool) Setting::get('feature_applications', true),
                'kyc' => (bool) Setting::get('feature_kyc', true),
                'support' => (bool) Setting::get('feature_support', true),
                'ranks' => (bool) Setting::get('feature_ranks', true),
                'referrals' => (bool) Setting::get('feature_referrals', true),
                'notifications' => (bool) Setting::get('feature_notifications', true),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'unreadNotifications' => fn () => $user ? $user->unreadNotifications()->count() : 0,
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
