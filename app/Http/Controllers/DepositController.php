<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepositController extends Controller
{
    /**
     * Show the deposit hub page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get user's accounts
        $accounts = $user->accounts()
            ->with('walletType')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->walletType?->name ?? 'Main Account',
                'balance' => number_format($account->balance, 2),
                'currency' => $account->walletType?->currency ?? 'USD',
            ]);
        
        // Check if user has deposit permission
        $canDeposit = $user->can_deposit ?? true;
        
        // Get deposit limits from settings
        $settings = [
            'deposit_min' => Setting::get('deposit_min', 10),
            'deposit_max' => Setting::get('deposit_max', 100000),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        // Count available methods
        $manualMethodsCount = PaymentGateway::where('type', 'manual')
            ->where('category', 'deposit')
            ->where('is_active', true)
            ->count();
            
        $automaticMethodsCount = PaymentGateway::where('type', 'automatic')
            ->where('category', 'deposit')
            ->where('is_active', true)
            ->count();
        
        return Inertia::render('Deposit/Index', [
            'accounts' => $accounts,
            'canDeposit' => $canDeposit,
            'settings' => $settings,
            'manualMethodsCount' => $manualMethodsCount,
            'automaticMethodsCount' => $automaticMethodsCount,
        ]);
    }
    
    /**
     * Show manual deposit methods.
     */
    public function manual(Request $request)
    {
        $user = $request->user();
        
        // Get user's accounts
        $accounts = $user->accounts()
            ->with('walletType')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->walletType?->name ?? 'Main Account',
                'balance' => number_format($account->balance, 2),
                'currency' => $account->walletType?->currency ?? 'USD',
            ]);
        
        // Get active manual deposit methods
        $methods = PaymentGateway::where('type', 'manual')
            ->where('category', 'deposit')
            ->where('is_active', true)
            ->get()
            ->map(fn($gateway) => [
                'id' => $gateway->id,
                'name' => $gateway->name,
                'code' => $gateway->code,
                'instructions' => $gateway->instructions,
                'min_limit' => $gateway->min_limit,
                'max_limit' => $gateway->max_limit,
                'fee_fixed' => $gateway->fee_fixed,
                'fee_percentage' => $gateway->fee_percentage,
                'logo' => $gateway->logo,
                // Only expose bank details needed for display
                'bank_details' => $this->getBankDetails($gateway->credentials),
            ]);
        
        $settings = [
            'deposit_min' => Setting::get('deposit_min', 10),
            'deposit_max' => Setting::get('deposit_max', 100000),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Deposit/Manual', [
            'accounts' => $accounts,
            'methods' => $methods,
            'settings' => $settings,
        ]);
    }
    
    /**
     * Show automatic deposit methods (payment gateways).
     */
    public function automatic(Request $request)
    {
        $user = $request->user();
        
        // Get user's accounts
        $accounts = $user->accounts()
            ->with('walletType')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->walletType?->name ?? 'Main Account',
                'balance' => number_format($account->balance, 2),
                'currency' => $account->walletType?->currency ?? 'USD',
            ]);
        
        // Get active automatic payment gateways
        $gateways = PaymentGateway::where('type', 'automatic')
            ->where('category', 'deposit')
            ->where('is_active', true)
            ->get()
            ->map(fn($gateway) => [
                'id' => $gateway->id,
                'name' => $gateway->name,
                'code' => $gateway->code,
                'min_limit' => $gateway->min_limit,
                'max_limit' => $gateway->max_limit,
                'fee_fixed' => $gateway->fee_fixed,
                'fee_percentage' => $gateway->fee_percentage,
                'logo' => $gateway->logo,
            ]);
        
        $settings = [
            'deposit_min' => Setting::get('deposit_min', 10),
            'deposit_max' => Setting::get('deposit_max', 100000),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Deposit/Automatic', [
            'accounts' => $accounts,
            'gateways' => $gateways,
            'settings' => $settings,
        ]);
    }
    
    /**
     * Show deposit history.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $deposits = Transaction::whereHas('account', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('transaction_type', 'deposit')
            ->with(['account.walletType'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(fn($transaction) => [
                'id' => $transaction->id,
                'amount' => number_format($transaction->amount, 2),
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'account_name' => $transaction->account?->walletType?->name ?? 'Main Account',
                'method' => $transaction->metadata['method'] ?? 'N/A',
                'created_at' => $transaction->created_at->format('M d, Y H:i'),
                'completed_at' => $transaction->completed_at?->format('M d, Y H:i'),
            ]);
        
        return Inertia::render('Deposit/History', [
            'deposits' => $deposits,
        ]);
    }
    
    /**
     * Extract displayable bank details from gateway credentials.
     */
    private function getBankDetails(?array $credentials): array
    {
        if (!$credentials) {
            return [];
        }
        
        // Only return safe fields for display (no API keys, etc.)
        $safeFields = ['bank_name', 'account_name', 'account_number', 'routing_number', 
                       'swift_code', 'iban', 'bank_address', 'reference', 'wallet_address'];
        
        return array_filter(
            $credentials,
            fn($key) => in_array($key, $safeFields),
            ARRAY_FILTER_USE_KEY
        );
    }
}
