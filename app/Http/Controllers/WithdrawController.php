<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\LinkedWithdrawalAccount;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\WithdrawalFormField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WithdrawController extends Controller
{
    /**
     * Show the withdrawal hub page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get user's accounts with balances
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
        
        // Check withdrawal permissions
        $canWithdraw = $user->can_withdraw ?? true;
        $withdrawalStatus = $user->withdrawal_status ?? 'approved';
        
        // Get withdrawal limits from settings
        $settings = [
            'withdrawal_min' => (float) Setting::get('withdrawal_min', 50),
            'withdrawal_max' => (float) Setting::get('withdrawal_max', 50000),
            'withdrawal_limit_daily' => (float) Setting::get('withdrawal_limit_daily', 25000),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        // Count available withdrawal methods
        $manualMethodsCount = PaymentGateway::where('type', 'manual')
            ->where('category', 'withdrawal')
            ->where('is_active', true)
            ->count();
            
        $automaticMethodsCount = PaymentGateway::where('type', 'automatic')
            ->where('category', 'withdrawal')
            ->where('is_active', true)
            ->count();
        
        // Get today's withdrawal total
        $todayWithdrawals = Transaction::whereHas('account', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('transaction_type', 'withdrawal')
            ->whereDate('created_at', today())
            ->sum('amount');
        
        // Calculate remaining daily limit
        $remainingDailyLimit = max(0, $settings['withdrawal_limit_daily'] - $todayWithdrawals);
        
        // Check if user needs to verify codes
        $requiresVerification = $this->checkVerificationRequired($user);
        
        return Inertia::render('Withdraw/Index', [
            'accounts' => $accounts,
            'canWithdraw' => $canWithdraw,
            'withdrawalStatus' => $withdrawalStatus,
            'settings' => $settings,
            'manualMethodsCount' => $manualMethodsCount,
            'automaticMethodsCount' => $automaticMethodsCount,
            'todayWithdrawals' => number_format($todayWithdrawals, 2),
            'remainingDailyLimit' => number_format($remainingDailyLimit, 2),
            'requiresVerification' => $requiresVerification,
        ]);
    }
    
    /**
     * Show manual withdrawal methods.
     */
    public function manual(Request $request)
    {
        $user = $request->user();
        
        // Get user's accounts with balances
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
        
        // Get active manual withdrawal methods
        $methods = PaymentGateway::where('type', 'manual')
            ->where('category', 'withdrawal')
            ->where('is_active', true)
            ->get()
            ->map(fn($gateway) => [
                'id' => $gateway->id,
                'name' => $gateway->name,
                'code' => $gateway->code,
                'instructions' => $gateway->instructions,
                'min_limit' => (float) ($gateway->min_limit ?? 0),
                'max_limit' => (float) ($gateway->max_limit ?? 0),
                'fee_fixed' => (float) ($gateway->fee_fixed ?? 0),
                'fee_percentage' => (float) ($gateway->fee_percentage ?? 0),
                'logo' => $gateway->logo,
                'processing_time' => $gateway->credentials['processing_time'] ?? '1-3 business days',
            ]);
        
        $settings = [
            'withdrawal_min' => (float) Setting::get('withdrawal_min', 50),
            'withdrawal_max' => (float) Setting::get('withdrawal_max', 50000),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        // Check verification status
        $requiresVerification = $this->checkVerificationRequired($user);
        $verificationStatus = $this->getVerificationStatus($user);
        
        // Get user's linked withdrawal accounts
        $linkedAccounts = $user->linkedWithdrawalAccounts()
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'account_name' => $account->account_name,
                'display_name' => $account->display_name,
                'account_data' => $account->account_data,
                'is_default' => $account->is_default,
                'is_verified' => $account->is_verified,
            ]);
        
        // Get withdrawal form fields for linking new accounts
        $withdrawalFormFields = WithdrawalFormField::getActiveFields();
        
        // Check if user can add more linked accounts
        $canAddMoreLinkedAccounts = !LinkedWithdrawalAccount::hasReachedLimit($user->id);
        $accountLimit = LinkedWithdrawalAccount::getAccountLimit();
        
        return Inertia::render('Withdraw/Manual', [
            'accounts' => $accounts,
            'methods' => $methods,
            'settings' => $settings,
            'requiresVerification' => $requiresVerification,
            'verificationStatus' => $verificationStatus,
            'linkedAccounts' => $linkedAccounts,
            'withdrawalFormFields' => $withdrawalFormFields,
            'canAddMoreLinkedAccounts' => $canAddMoreLinkedAccounts,
            'accountLimit' => $accountLimit,
        ]);
    }
    
    /**
     * Show automatic withdrawal methods.
     */
    public function automatic(Request $request)
    {
        $user = $request->user();
        
        // Get user's accounts with balances
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
        
        // Get active automatic withdrawal gateways
        $gateways = PaymentGateway::where('type', 'automatic')
            ->where('category', 'withdrawal')
            ->where('is_active', true)
            ->get()
            ->map(fn($gateway) => [
                'id' => $gateway->id,
                'name' => $gateway->name,
                'code' => $gateway->code,
                'min_limit' => (float) ($gateway->min_limit ?? 0),
                'max_limit' => (float) ($gateway->max_limit ?? 0),
                'fee_fixed' => (float) ($gateway->fee_fixed ?? 0),
                'fee_percentage' => (float) ($gateway->fee_percentage ?? 0),
                'logo' => $gateway->logo,
            ]);
        
        $settings = [
            'withdrawal_min' => (float) Setting::get('withdrawal_min', 50),
            'withdrawal_max' => (float) Setting::get('withdrawal_max', 50000),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        // Check verification status
        $requiresVerification = $this->checkVerificationRequired($user);
        $verificationStatus = $this->getVerificationStatus($user);
        
        return Inertia::render('Withdraw/Automatic', [
            'accounts' => $accounts,
            'gateways' => $gateways,
            'settings' => $settings,
            'requiresVerification' => $requiresVerification,
            'verificationStatus' => $verificationStatus,
        ]);
    }
    
    /**
     * Store a withdrawal request.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Check withdrawal permission
        if (!($user->can_withdraw ?? true)) {
            return back()->withErrors(['error' => 'Your withdrawal capability has been disabled. Please contact support.']);
        }
        
        // Check withdrawal status
        if (($user->withdrawal_status ?? 'approved') !== 'approved') {
            return back()->withErrors(['error' => 'Your withdrawal status is currently: ' . ucfirst($user->withdrawal_status) . '. Please contact support.']);
        }
        
        // Check verification
        if ($this->checkVerificationRequired($user)) {
            return redirect()->route('withdraw.verify')->withErrors(['error' => 'Please complete verification before withdrawing.']);
        }
        
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'gateway_id' => 'nullable|exists:payment_gateways,id',
            'linked_account_id' => 'nullable|exists:linked_withdrawal_accounts,id',
            'amount' => 'required|numeric|min:1',
            'bank_details' => 'nullable|array',
            'bank_details.bank_name' => 'nullable|string|max:255',
            'bank_details.account_name' => 'nullable|string|max:255',
            'bank_details.account_number' => 'nullable|string|max:50',
            'bank_details.routing_number' => 'nullable|string|max:50',
            'bank_details.swift_code' => 'nullable|string|max:20',
            'bank_details.iban' => 'nullable|string|max:50',
            'bank_details.bank_address' => 'nullable|string|max:500',
        ]);
        
        // Validate that either gateway_id or linked_account_id is provided
        if (empty($validated['gateway_id']) && empty($validated['linked_account_id'])) {
            return back()->withErrors(['error' => 'Please select a withdrawal method or linked account.']);
        }
        
        // Verify account belongs to user
        $account = Account::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Get gateway if provided (for fee calculation)
        $gateway = null;
        $linkedAccount = null;
        $methodName = 'Bank Transfer';
        $methodCode = 'bank_transfer';
        
        if (!empty($validated['gateway_id'])) {
            $gateway = PaymentGateway::findOrFail($validated['gateway_id']);
            $methodName = $gateway->name;
            $methodCode = $gateway->code;
        }
        
        // Get linked account if provided
        if (!empty($validated['linked_account_id'])) {
            $linkedAccount = LinkedWithdrawalAccount::where('id', $validated['linked_account_id'])
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->firstOrFail();
        }
        
        // Calculate fee (only if gateway is provided)
        $amount = (float) $validated['amount'];
        $feeFixed = (float) ($gateway->fee_fixed ?? 0);
        $feePercentage = (float) ($gateway->fee_percentage ?? 0);
        $fee = $feeFixed + ($amount * $feePercentage / 100);
        $totalDeduction = $amount + $fee;
        
        // Check balance
        if ($account->balance < $totalDeduction) {
            return back()->withErrors(['amount' => 'Insufficient balance. Available: ' . number_format($account->balance, 2)]);
        }
        
        // Check limits
        $minLimit = max((float) Setting::get('withdrawal_min', 50), (float) ($gateway->min_limit ?? 0));
        $maxLimit = min((float) Setting::get('withdrawal_max', 50000), (float) ($gateway->max_limit ?? PHP_FLOAT_MAX));
        
        if ($amount < $minLimit) {
            return back()->withErrors(['amount' => 'Minimum withdrawal amount is ' . number_format($minLimit, 2)]);
        }
        
        if ($amount > $maxLimit) {
            return back()->withErrors(['amount' => 'Maximum withdrawal amount is ' . number_format($maxLimit, 2)]);
        }
        
        // Check daily limit
        $dailyLimit = (float) Setting::get('withdrawal_limit_daily', 25000);
        $todayWithdrawals = Transaction::whereHas('account', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('transaction_type', 'withdrawal')
            ->whereDate('created_at', today())
            ->sum('amount');
        
        if (($todayWithdrawals + $amount) > $dailyLimit) {
            $remaining = max(0, $dailyLimit - $todayWithdrawals);
            return back()->withErrors(['amount' => 'Daily withdrawal limit exceeded. Remaining: ' . number_format($remaining, 2)]);
        }
        
        // Prepare bank details for admin - use linked account data if available
        $bankDetails = $validated['bank_details'] ?? [];
        if ($linkedAccount) {
            $bankDetails = array_merge($bankDetails, $linkedAccount->account_data ?? []);
            $bankDetails['linked_account_id'] = $linkedAccount->id;
            $bankDetails['linked_account_name'] = $linkedAccount->account_name;
        }
        
        DB::beginTransaction();
        
        try {
            // Generate reference number
            $referenceNumber = 'WD-' . strtoupper(\Illuminate\Support\Str::random(12));
            
            // Create withdrawal transaction
            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => 'withdrawal',
                'method' => $methodCode,
                'amount' => $amount,
                'fee' => $fee,
                'currency' => $account->walletType?->currency ?? 'USD',
                'status' => 'pending',
                'description' => 'Withdrawal via ' . $methodName,
                'reference_number' => $referenceNumber,
                'metadata' => [
                    'method' => $methodName,
                    'gateway_id' => $gateway?->id,
                    'gateway_code' => $gateway?->code,
                    'linked_account_id' => $linkedAccount?->id,
                    'linked_account_name' => $linkedAccount?->account_name,
                    'bank_details' => $bankDetails,
                    'total_deduction' => $totalDeduction,
                ],
            ]);
            
            // Deduct from account balance (hold)
            $account->decrement('balance', $totalDeduction);
            
            DB::commit();
            
            // Notify user of pending withdrawal
            $user->notify(new \App\Notifications\WithdrawalProcessed($transaction, 'pending'));
            
            return redirect()->route('withdraw.history')
                ->with('success', 'Withdrawal request submitted successfully. Reference: ' . substr($transaction->id, 0, 8));
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process withdrawal. Please try again.']);
        }
    }
    
    /**
     * Show verification page.
     */
    public function verify(Request $request)
    {
        $user = $request->user();
        
        $verificationStatus = $this->getVerificationStatus($user);
        
        return Inertia::render('Withdraw/Verify', [
            'verificationStatus' => $verificationStatus,
            'requiresVerification' => $this->checkVerificationRequired($user),
        ]);
    }
    
    /**
     * Process verification codes.
     */
    public function verifyCode(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'code_type' => 'required|in:imf_code,tax_code,cot_code',
            'code' => 'required|string',
        ]);
        
        $codeType = $validated['code_type'];
        $code = $validated['code'];
        
        // Check if code matches
        $storedCode = $user->{$codeType};
        
        if (!$storedCode) {
            return back()->withErrors([$codeType => 'No code has been assigned. Please contact support.']);
        }
        
        if ($storedCode !== $code) {
            return back()->withErrors([$codeType => 'Invalid code. Please check and try again.']);
        }
        
        // Mark as verified in session (or you could use a database field)
        $verifiedCodes = session('verified_codes', []);
        $verifiedCodes[$codeType] = true;
        session(['verified_codes' => $verifiedCodes]);
        
        return back()->with('success', ucfirst(str_replace('_', ' ', $codeType)) . ' verified successfully.');
    }
    
    /**
     * Show withdrawal history.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $withdrawals = Transaction::whereHas('account', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('transaction_type', 'withdrawal')
            ->with(['account.walletType'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(fn($transaction) => [
                'id' => $transaction->id,
                'reference' => substr($transaction->id, 0, 8),
                'amount' => number_format($transaction->amount, 2),
                'fee' => number_format($transaction->fee ?? 0, 2),
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'account_name' => $transaction->account?->walletType?->name ?? 'Main Account',
                'method' => $transaction->metadata['method'] ?? 'N/A',
                'created_at' => $transaction->created_at->format('M d, Y H:i'),
                'completed_at' => $transaction->completed_at?->format('M d, Y H:i'),
            ]);
        
        return Inertia::render('Withdraw/History', [
            'withdrawals' => $withdrawals,
        ]);
    }
    
    /**
     * Check if user needs to verify any codes.
     */
    private function checkVerificationRequired($user): bool
    {
        // Check if any codes are set and need verification
        $verifiedCodes = session('verified_codes', []);
        
        if ($user->imf_code && !($verifiedCodes['imf_code'] ?? false)) {
            return true;
        }
        
        if ($user->tax_code && !($verifiedCodes['tax_code'] ?? false)) {
            return true;
        }
        
        if ($user->cot_code && !($verifiedCodes['cot_code'] ?? false)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get verification status for each code type.
     */
    private function getVerificationStatus($user): array
    {
        $verifiedCodes = session('verified_codes', []);
        
        return [
            'imf_code' => [
                'required' => !empty($user->imf_code),
                'verified' => $verifiedCodes['imf_code'] ?? false,
                'label' => 'IMF Code',
                'description' => 'International Monetary Fund clearance code',
            ],
            'tax_code' => [
                'required' => !empty($user->tax_code),
                'verified' => $verifiedCodes['tax_code'] ?? false,
                'label' => 'Tax Code',
                'description' => 'Tax clearance certificate code',
            ],
            'cot_code' => [
                'required' => !empty($user->cot_code),
                'verified' => $verifiedCodes['cot_code'] ?? false,
                'label' => 'COT Code',
                'description' => 'Cost of Transfer code',
            ],
        ];
    }
}
