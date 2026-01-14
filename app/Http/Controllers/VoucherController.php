<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Setting;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VoucherController extends Controller
{
    /**
     * Show the voucher redemption page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get user's accounts for selection
        $accounts = $user->accounts()
            ->with('walletType')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->walletType?->name ?? 'Main Account',
                'balance' => (float) $account->balance,
                'formatted_balance' => number_format($account->balance, 2),
                'currency' => $account->walletType?->currency ?? 'USD',
            ]);
        
        // Check if user can use vouchers
        $canUseVoucher = $user->can_use_voucher ?? true;
        
        // Get recent redemptions for quick reference
        $recentRedemptions = VoucherRedemption::where('user_id', $user->id)
            ->with(['voucher', 'account.walletType'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn($redemption) => [
                'id' => $redemption->id,
                'voucher_code' => $redemption->voucher?->code ?? 'N/A',
                'amount' => number_format($redemption->amount_redeemed, 2),
                'account_name' => $redemption->account?->walletType?->name ?? 'Main Account',
                'redeemed_at' => $redemption->redeemed_at?->format('M d, Y H:i') ?? $redemption->created_at->format('M d, Y H:i'),
            ]);
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Voucher/Index', [
            'accounts' => $accounts,
            'canUseVoucher' => $canUseVoucher,
            'recentRedemptions' => $recentRedemptions,
            'settings' => $settings,
        ]);
    }
    
    /**
     * Validate a voucher code (AJAX).
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);
        
        $user = $request->user();
        $code = strtoupper(trim($request->code));
        
        $voucher = Voucher::where('code', $code)->first();
        
        if (!$voucher) {
            return response()->json([
                'valid' => false,
                'message' => 'Voucher code not found.',
            ]);
        }
        
        if (!$voucher->isValid()) {
            $status = $voucher->status;
            $messages = [
                'inactive' => 'This voucher has been deactivated.',
                'expired' => 'This voucher has expired.',
                'scheduled' => 'This voucher is not yet active.',
                'used' => 'This voucher has already been used.',
                'exhausted' => 'This voucher has reached its maximum uses.',
            ];
            
            return response()->json([
                'valid' => false,
                'message' => $messages[$status] ?? 'This voucher is not available.',
            ]);
        }
        
        if ($voucher->hasBeenRedeemedBy($user)) {
            return response()->json([
                'valid' => false,
                'message' => 'You have already redeemed this voucher.',
            ]);
        }
        
        return response()->json([
            'valid' => true,
            'voucher' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->name,
                'description' => $voucher->description,
                'value' => (float) $voucher->value,
                'formatted_value' => number_format($voucher->value, 2),
                'voucher_type' => $voucher->voucher_type,
                'remaining_uses' => $voucher->remaining_uses,
                'expiration_date' => $voucher->expiration_date?->format('M d, Y'),
            ],
            'message' => 'Voucher is valid and ready to redeem!',
        ]);
    }
    
    /**
     * Redeem a voucher.
     */
    public function redeem(Request $request)
    {
        $user = $request->user();
        
        // Check permission
        if (!($user->can_use_voucher ?? true)) {
            return back()->withErrors(['error' => 'Your account is not enabled for voucher redemption. Please contact support.']);
        }
        
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'account_id' => 'required|exists:accounts,id',
        ]);
        
        $code = strtoupper(trim($validated['code']));
        
        // Verify account belongs to user
        $account = Account::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Find and validate voucher
        $voucher = Voucher::where('code', $code)->first();
        
        if (!$voucher) {
            return back()->withErrors(['code' => 'Voucher code not found.']);
        }
        
        if (!$voucher->isValid()) {
            return back()->withErrors(['code' => 'This voucher is no longer valid.']);
        }
        
        if ($voucher->hasBeenRedeemedBy($user)) {
            return back()->withErrors(['code' => 'You have already redeemed this voucher.']);
        }
        
        DB::beginTransaction();
        
        try {
            // Create redemption record
            $redemption = VoucherRedemption::create([
                'voucher_id' => $voucher->id,
                'user_id' => $user->id,
                'account_id' => $account->id,
                'amount_redeemed' => $voucher->value,
                'redeemed_at' => now(),
            ]);
            
            // Credit account
            $account->increment('balance', $voucher->value);
            
            // Increment voucher usage
            $voucher->increment('current_uses');
            
            // Notify user
            $user->notify(new \App\Notifications\VoucherRedeemed($voucher, $voucher->value));
            
            DB::commit();
            
            return redirect()->route('voucher.index')
                ->with('success', 'Voucher redeemed successfully! ' . Setting::get('currency_symbol', '$') . number_format($voucher->value, 2) . ' has been credited to your account.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to redeem voucher. Please try again.']);
        }
    }
    
    /**
     * Show voucher redemption history.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $redemptions = VoucherRedemption::where('user_id', $user->id)
            ->with(['voucher', 'account.walletType'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(fn($redemption) => [
                'id' => $redemption->id,
                'voucher_code' => $redemption->voucher?->code ?? 'N/A',
                'voucher_name' => $redemption->voucher?->name ?? 'Unknown Voucher',
                'amount' => number_format($redemption->amount_redeemed, 2),
                'account_name' => $redemption->account?->walletType?->name ?? 'Main Account',
                'redeemed_at' => $redemption->redeemed_at?->format('M d, Y H:i') ?? $redemption->created_at->format('M d, Y H:i'),
            ]);
        
        // Calculate total redeemed
        $totalRedeemed = VoucherRedemption::where('user_id', $user->id)->sum('amount_redeemed');
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Voucher/History', [
            'redemptions' => $redemptions,
            'totalRedeemed' => number_format($totalRedeemed, 2),
            'settings' => $settings,
        ]);
    }
}
