<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanPlan;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LoanController extends Controller
{
    /**
     * Show the loans dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Check if loans are enabled
        $loansEnabled = (bool) Setting::get('loans_enabled', true);
        
        if (!$loansEnabled) {
            return Inertia::render('Loans/Index', [
                'loansEnabled' => false,
                'activeLoans' => [],
                'loanHistory' => [],
                'settings' => $this->getSettings(),
                'accounts' => [],
            ]);
        }
        
        // Get user's accounts
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
        
        // Get active loans (pending, approved, disbursed)
        $activeLoans = Loan::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'disbursed', 'active'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($loan) => $this->formatLoan($loan));
        
        // Get loan history (completed, rejected, defaulted)
        $loanHistory = Loan::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'rejected', 'defaulted', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($loan) => $this->formatLoan($loan));
        
        // Check if user can apply for a loan (no active loan)
        $hasActiveLoan = $activeLoans->isNotEmpty();
        $canApplyForLoan = !$hasActiveLoan && $accounts->isNotEmpty();
        $disabledReason = null;
        
        if ($hasActiveLoan) {
            $disabledReason = 'You already have an active loan. Please repay it before applying for a new one.';
        } elseif ($accounts->isEmpty()) {
            $disabledReason = 'You need at least one account to apply for a loan.';
        }

        // Get available loan plans
        $loanPlans = LoanPlan::active()
            ->ordered()
            ->get()
            ->map(fn($plan) => $this->formatLoanPlan($plan));
        
        return Inertia::render('Loans/Index', [
            'loansEnabled' => true,
            'activeLoans' => $activeLoans,
            'activeLoan' => $activeLoans->first(),
            'loanHistory' => $loanHistory,
            'loanPlans' => $loanPlans,
            'settings' => $this->getSettings(),
            'accounts' => $accounts,
            'canApplyForLoan' => $canApplyForLoan,
            'disabledReason' => $disabledReason,
        ]);
    }

    /**
     * Format loan plan for frontend.
     */
    private function formatLoanPlan(LoanPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'tagline' => $plan->tagline,
            'description' => $plan->description,
            'min_amount' => (float) $plan->min_amount,
            'max_amount' => (float) $plan->max_amount,
            'interest_rate' => (float) $plan->interest_rate,
            'min_duration_months' => $plan->min_duration_months,
            'max_duration_months' => $plan->max_duration_months,
            'default_duration_months' => $plan->default_duration_months,
            'icon' => $plan->icon,
            'color' => $plan->color,
            'gradient_from' => $plan->gradient_from ?? "{$plan->color}-500",
            'gradient_to' => $plan->gradient_to ?? "{$plan->color}-600",
            'features' => $plan->features ?? [],
            'is_featured' => $plan->is_featured,
            'processing_fee' => (float) $plan->processing_fee,
            'processing_fee_type' => $plan->processing_fee_type,
            'approval_days' => $plan->approval_days,
            'requires_collateral' => $plan->requires_collateral,
            'early_repayment_allowed' => $plan->early_repayment_allowed,
        ];
    }
    
    /**
     * Show a specific loan.
     */
    public function show(Request $request, Loan $loan)
    {
        // Verify loan belongs to user
        if ($loan->user_id !== $request->user()->id) {
            abort(403);
        }
        
        $loan->load('user');
        
        // Get payment history for this loan
        $transactions = Transaction::where('metadata->loan_id', $loan->id)
            ->where('transaction_type', 'loan_repayment')
            ->orderBy('created_at', 'asc') // Oldest first for balance calculation
            ->get();
        
        // Calculate balance_after for each transaction
        $totalLoanAmount = $loan->total_amount; // Total amount to repay
        $runningBalance = $totalLoanAmount;
        
        $payments = $transactions->map(function($tx) use (&$runningBalance) {
            // Only subtract if payment was completed
            if ($tx->status === 'completed') {
                $runningBalance -= $tx->amount;
            }
            
            return [
                'id' => $tx->id,
                'amount' => (float) $tx->amount, // Raw number for JS formatting
                'status' => $tx->status,
                'created_at' => $tx->created_at->format('M d, Y H:i'),
                'balance_after' => max(0, $runningBalance), // Remaining balance after this payment
            ];
        })->sortByDesc('created_at')->values(); // Sort back to newest first for display
        
        // Get user's accounts for repayment
        $accounts = $request->user()->accounts()
            ->with('walletType')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->walletType?->name ?? 'Main Account',
                'balance' => (float) $account->balance,
                'formatted_balance' => number_format($account->balance, 2),
            ]);
        
        // Get payment gateways for repayment
        $paymentGateways = \App\Services\PaymentService::getGatewaysForFrontend('payment');
        
        return Inertia::render('Loans/Show', [
            'loan' => $this->formatLoan($loan),
            'transactions' => $payments,
            'accounts' => $accounts,
            'paymentGateways' => $paymentGateways,
            'settings' => $this->getSettings(),
        ]);
    }
    
    /**
     * Apply for a new loan.
     */
    public function apply(Request $request)
    {
        $user = $request->user();
        
        // Check if loans are enabled
        if (!Setting::get('loans_enabled', true)) {
            return back()->withErrors(['error' => 'Loans are currently disabled.']);
        }
        
        // Check if user already has an active loan
        $hasActiveLoan = Loan::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'disbursed', 'active'])
            ->exists();
        
        if ($hasActiveLoan) {
            return back()->withErrors(['error' => 'You already have an active loan. Please repay it before applying for a new one.']);
        }
        
        $validated = $request->validate([
            'loan_plan_id' => 'nullable|exists:loan_plans,id',
            'amount' => 'required|numeric|min:1',
            'purpose' => 'required|string|max:500',
            'duration_months' => 'required|integer|min:1|max:60',
            'account_id' => 'required|exists:accounts,id',
        ]);
        
        // Verify account belongs to user
        $account = Account::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Get loan plan if selected
        $loanPlan = null;
        if (!empty($validated['loan_plan_id'])) {
            $loanPlan = LoanPlan::find($validated['loan_plan_id']);
        }
        
        // Get loan settings (from plan or global settings)
        $minAmount = $loanPlan ? (float) $loanPlan->min_amount : (float) Setting::get('loan_min_amount', 100);
        $maxAmount = $loanPlan ? (float) $loanPlan->max_amount : (float) Setting::get('loan_max_amount', 10000);
        $interestRate = $loanPlan ? (float) $loanPlan->interest_rate : (float) Setting::get('loan_interest_rate', 5);
        
        $amount = (float) $validated['amount'];
        
        if ($amount < $minAmount) {
            return back()->withErrors(['amount' => 'Minimum loan amount is ' . number_format($minAmount, 2)]);
        }
        
        if ($amount > $maxAmount) {
            return back()->withErrors(['amount' => 'Maximum loan amount is ' . number_format($maxAmount, 2)]);
        }
        
        // Validate duration against plan limits
        if ($loanPlan) {
            if ($validated['duration_months'] < $loanPlan->min_duration_months) {
                return back()->withErrors(['duration_months' => 'Minimum duration is ' . $loanPlan->min_duration_months . ' months']);
            }
            if ($validated['duration_months'] > $loanPlan->max_duration_months) {
                return back()->withErrors(['duration_months' => 'Maximum duration is ' . $loanPlan->max_duration_months . ' months']);
            }
        }
        
        // Calculate total payable (interest * duration)
        $totalInterest = $amount * ($interestRate / 100) * $validated['duration_months'];
        $totalPayable = $amount + $totalInterest;
        $monthlyPayment = $totalPayable / $validated['duration_months'];
        
        // Create loan application
        $loan = Loan::create([
            'user_id' => $user->id,
            'loan_plan_id' => $loanPlan?->id,
            'amount' => $amount,
            'interest_rate' => $interestRate,
            'total_payable' => $totalPayable,
            'duration_months' => $validated['duration_months'],
            'monthly_payment' => $monthlyPayment,
            'purpose' => $validated['purpose'],
            'account_id' => $account->id,
            'status' => 'pending',
            'due_date' => now()->addMonths($validated['duration_months']),
        ]);
        
        return redirect()->route('loans.show', $loan)
            ->with('success', 'Loan application submitted successfully. Reference: ' . $loan->loan_id);
    }
    
    /**
     * Make a loan repayment.
     */
    public function repay(Request $request, Loan $loan)
    {
        $user = $request->user();
        
        // Verify loan belongs to user
        if ($loan->user_id !== $user->id) {
            abort(403);
        }
        
        // Check loan status
        if (!in_array($loan->status, ['disbursed', 'active'])) {
            return back()->withErrors(['error' => 'This loan is not eligible for repayment.']);
        }
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:account,gateway',
            'account_id' => 'required_if:payment_method,account|nullable|exists:accounts,id',
            'gateway_id' => 'required_if:payment_method,gateway|nullable|exists:payment_gateways,id',
        ]);
        
        $amount = (float) $validated['amount'];
        $remainingBalance = (float) $loan->total_payable - (float) ($loan->amount_paid ?? 0);
        
        // Don't allow overpayment
        if ($amount > $remainingBalance) {
            $amount = $remainingBalance;
        }
        
        // Handle payment based on method
        if ($validated['payment_method'] === 'account') {
            return $this->repayFromAccount($request, $loan, $amount, $validated['account_id']);
        } else {
            return $this->repayViaGateway($request, $loan, $amount, $validated['gateway_id']);
        }
    }
    
    /**
     * Process loan repayment from user's account balance
     */
    protected function repayFromAccount(Request $request, Loan $loan, float $amount, string $accountId)
    {
        $user = $request->user();
        
        // Verify account belongs to user
        $account = Account::where('id', $accountId)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Check account balance
        if ($account->balance < $amount) {
            return back()->withErrors(['amount' => 'Insufficient balance. Available: ' . number_format($account->balance, 2)]);
        }
        
        DB::beginTransaction();
        
        try {
            // Deduct from account
            $account->decrement('balance', $amount);
            
            // Update loan
            $newAmountPaid = (float) ($loan->amount_paid ?? 0) + $amount;
            $loan->amount_paid = $newAmountPaid;
            
            // Check if loan is fully paid
            if ($newAmountPaid >= (float) $loan->total_payable) {
                $loan->status = 'completed';
                $loan->completed_at = now();
            }
            
            $loan->save();
            
            // Create transaction record
            Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => 'loan_repayment',
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'completed',
                'description' => 'Loan repayment for ' . $loan->loan_id,
                'reference_number' => 'LOANPAY-' . strtoupper(substr(md5(uniqid()), 0, 12)),
                'metadata' => [
                    'loan_id' => $loan->id,
                    'loan_reference' => $loan->loan_id,
                    'payment_method' => 'account',
                ],
                'completed_at' => now(),
            ]);
            
            DB::commit();
            
            $message = $loan->status === 'completed' 
                ? 'Congratulations! Your loan has been fully repaid.'
                : 'Payment of ' . Setting::get('currency_symbol', '$') . number_format($amount, 2) . ' received successfully.';
            
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Loan Repayment Failed', [
                'loan_id' => $loan->id,
                'amount' => $amount,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to process payment: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Process loan repayment via payment gateway
     */
    protected function repayViaGateway(Request $request, Loan $loan, float $amount, string $gatewayId)
    {
        $user = $request->user();
        $gateway = \App\Models\PaymentGateway::findOrFail($gatewayId);
        
        // Check gateway limits
        if (!$gateway->isAmountValid($amount)) {
            return back()->withErrors(['amount' => 'Amount must be between ' . number_format($gateway->min_limit, 2) . ' and ' . number_format($gateway->max_limit ?? 999999, 2)]);
        }
        
        $paymentService = new \App\Services\PaymentService();
        $reference = $paymentService->generateReference('LOAN');
        
        // Get the gateway's preferred currency (first supported currency or default to USD)
        $supportedCurrencies = $gateway->supported_currencies ?? ['USD'];
        $currency = is_array($supportedCurrencies) && count($supportedCurrencies) > 0 
            ? $supportedCurrencies[0] 
            : 'USD';
        
        // Prepare payment data
        $paymentData = [
            'amount' => $amount,
            'currency' => $currency,
            'reference' => $reference,
            'description' => 'Loan Repayment - ' . $loan->loan_id,
            'customer_email' => $user->email,
            'customer_name' => $user->name,
            'purpose' => 'loan_repayment',
            'success_url' => route('loans.payment.callback', ['loan' => $loan->id, 'reference' => $reference]),
            'cancel_url' => route('loans.show', $loan),
            'metadata' => [
                'loan_id' => $loan->id,
                'user_id' => $user->id,
                'type' => 'loan_repayment',
                'original_currency' => 'USD',
                'payment_currency' => $currency,
            ],
        ];
        
        // Initialize payment
        $result = $paymentService->initializeGenericPayment($gateway, $paymentData);
        
        if (!$result['success']) {
            return back()->withErrors(['error' => $result['error'] ?? 'Failed to initialize payment.']);
        }
        
        // Store pending payment reference (include session_id for Stripe)
        session(['pending_loan_payment' => [
            'reference' => $reference,
            'loan_id' => $loan->id,
            'amount' => $amount,
            'gateway_id' => $gateway->id,
            'session_id' => $result['session_id'] ?? null, // Stripe session ID
            'provider' => $gateway->provider,
        ]]);
        
        // For automatic gateways, redirect to checkout
        if ($result['type'] === 'redirect' && !empty($result['checkout_url'])) {
            return Inertia::location($result['checkout_url']);
        }
        
        // For manual gateways, return payment details
        return back()->with('manual_payment', $result);
    }
    
    /**
     * Handle payment gateway callback
     */
    public function paymentCallback(Request $request, Loan $loan)
    {
        $user = $request->user();
        
        // Verify loan belongs to user
        if ($loan->user_id !== $user->id) {
            abort(403);
        }
        
        // Paystack sends 'reference' and 'trxref', Stripe sends 'session_id'
        $reference = $request->query('reference') ?? $request->query('trxref');
        $sessionId = $request->query('session_id');
        
        \Log::info('Payment Callback', [
            'loan_id' => $loan->id,
            'reference' => $reference,
            'session_id' => $sessionId,
            'all_query' => $request->query(),
        ]);
        
        // Get pending payment from session
        $pendingPayment = session('pending_loan_payment');
        
        \Log::info('Pending Payment Session', ['data' => $pendingPayment]);
        
        if (!$pendingPayment) {
            // Try to recover from database or verify directly with the reference
            if ($reference) {
                return $this->handleDirectPaymentVerification($request, $loan, $reference);
            }
            if ($sessionId) {
                return $this->handleStripeDirectVerification($request, $loan, $sessionId);
            }
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Payment session expired. If payment was made, please contact support.']);
        }
        
        if ($pendingPayment['loan_id'] !== $loan->id) {
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Invalid payment session.']);
        }
        
        $gateway = \App\Models\PaymentGateway::find($pendingPayment['gateway_id']);
        
        if (!$gateway) {
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Payment gateway not found.']);
        }
        
        $paymentService = new \App\Services\PaymentService();
        
        // Verify payment based on provider
        // For Stripe: use session_id from query OR from pending payment session
        // For Paystack/others: use reference from query OR from pending payment
        $verificationRef = null;
        if ($gateway->provider === 'stripe') {
            $verificationRef = $sessionId ?? ($pendingPayment['session_id'] ?? null);
        } else {
            $verificationRef = $reference ?? ($pendingPayment['reference'] ?? null);
        }
        
        if (!$verificationRef) {
            session()->forget('pending_loan_payment');
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Missing payment reference. Please try again or contact support.']);
        }
        
        $result = $paymentService->verifyGenericPayment($gateway, $verificationRef);
        
        if (!$result['success'] || !$result['paid']) {
            session()->forget('pending_loan_payment');
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Payment verification failed. Please try again.']);
        }
        
        $amount = $pendingPayment['amount'];
        
        DB::beginTransaction();
        
        try {
            // Update loan
            $newAmountPaid = (float) ($loan->amount_paid ?? 0) + $amount;
            $loan->amount_paid = $newAmountPaid;
            
            // Check if loan is fully paid
            if ($newAmountPaid >= (float) $loan->total_payable) {
                $loan->status = 'completed';
                $loan->completed_at = now();
            }
            
            $loan->save();
            
            // Create transaction record
            Transaction::create([
                'account_id' => $user->accounts()->first()?->id,
                'transaction_type' => 'loan_repayment',
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'completed',
                'description' => 'Loan repayment via ' . $gateway->name,
                'reference_number' => 'LOANPAY-' . strtoupper(substr(md5($reference ?? uniqid()), 0, 12)),
                'metadata' => [
                    'loan_id' => $loan->id,
                    'loan_reference' => $loan->loan_id,
                    'payment_method' => 'gateway',
                    'gateway_id' => $gateway->id,
                    'gateway_name' => $gateway->name,
                    'payment_reference' => $reference,
                ],
                'completed_at' => now(),
            ]);
            
            DB::commit();
            
            session()->forget('pending_loan_payment');
            
            $message = $loan->status === 'completed' 
                ? 'Congratulations! Your loan has been fully repaid.'
                : 'Payment of ' . Setting::get('currency_symbol', '$') . number_format($amount, 2) . ' received successfully.';
            
            return redirect()->route('loans.show', $loan)->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Failed to process payment confirmation. Please contact support.']);
        }
    }
    
    /**
     * Handle direct payment verification when session is lost (fallback)
     */
    protected function handleDirectPaymentVerification(Request $request, Loan $loan, string $reference)
    {
        $user = $request->user();
        
        // Try to find the gateway - for Paystack, check active paystack gateway
        $gateway = \App\Models\PaymentGateway::where('provider', 'paystack')
            ->where('is_active', true)
            ->first();
        
        if (!$gateway) {
            $gateway = \App\Models\PaymentGateway::where('provider', 'flutterwave')
                ->where('is_active', true)
                ->first();
        }
        
        if (!$gateway) {
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Unable to verify payment. Please contact support with reference: ' . $reference]);
        }
        
        $paymentService = new \App\Services\PaymentService();
        $result = $paymentService->verifyGenericPayment($gateway, $reference);
        
        \Log::info('Direct Payment Verification', ['reference' => $reference, 'result' => $result]);
        
        if (!$result['success'] || !$result['paid']) {
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Payment not verified. Reference: ' . $reference]);
        }
        
        // Get the amount from the verification result
        $amount = $result['amount'] ?? 0;
        
        if ($amount <= 0) {
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Invalid payment amount.']);
        }
        
        DB::beginTransaction();
        
        try {
            // Update loan
            $newAmountPaid = (float) ($loan->amount_paid ?? 0) + $amount;
            $loan->amount_paid = $newAmountPaid;
            
            if ($newAmountPaid >= (float) $loan->total_payable) {
                $loan->status = 'completed';
                $loan->completed_at = now();
            }
            
            $loan->save();
            
            // Create transaction record
            Transaction::create([
                'account_id' => $user->accounts()->first()?->id,
                'transaction_type' => 'loan_repayment',
                'amount' => $amount,
                'currency' => $result['currency'] ?? 'USD',
                'status' => 'completed',
                'description' => 'Loan repayment via ' . $gateway->name,
                'reference_number' => 'LOANPAY-' . strtoupper(substr(md5($reference), 0, 12)),
                'metadata' => [
                    'loan_id' => $loan->id,
                    'loan_reference' => $loan->loan_id,
                    'payment_method' => 'gateway',
                    'gateway_id' => $gateway->id,
                    'gateway_name' => $gateway->name,
                    'payment_reference' => $reference,
                    'verified_directly' => true,
                ],
                'completed_at' => now(),
            ]);
            
            DB::commit();
            
            $message = $loan->status === 'completed' 
                ? 'Congratulations! Your loan has been fully repaid.'
                : 'Payment of ' . Setting::get('currency_symbol', '$') . number_format($amount, 2) . ' received successfully.';
            
            return redirect()->route('loans.show', $loan)->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Direct Payment Processing Failed', ['error' => $e->getMessage()]);
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Payment verified but processing failed. Please contact support. Reference: ' . $reference]);
        }
    }
    
    /**
     * Handle Stripe direct verification when session is lost (fallback)
     */
    protected function handleStripeDirectVerification(Request $request, Loan $loan, string $sessionId)
    {
        $user = $request->user();
        
        // Find active Stripe gateway
        $gateway = \App\Models\PaymentGateway::where('provider', 'stripe')
            ->where('is_active', true)
            ->first();
        
        if (!$gateway) {
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Unable to verify Stripe payment. Please contact support.']);
        }
        
        $paymentService = new \App\Services\PaymentService();
        $result = $paymentService->verifyGenericPayment($gateway, $sessionId);
        
        \Log::info('Stripe Direct Verification', ['session_id' => $sessionId, 'result' => $result]);
        
        if (!$result['success'] || !$result['paid']) {
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Stripe payment not verified. Please try again or contact support.']);
        }
        
        // Get the amount from the verification result
        $amount = $result['amount'] ?? 0;
        
        if ($amount <= 0) {
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Invalid payment amount.']);
        }
        
        DB::beginTransaction();
        
        try {
            // Update loan
            $newAmountPaid = (float) ($loan->amount_paid ?? 0) + $amount;
            $loan->amount_paid = $newAmountPaid;
            
            if ($newAmountPaid >= (float) $loan->total_payable) {
                $loan->status = 'completed';
                $loan->completed_at = now();
            }
            
            $loan->save();
            
            // Create transaction record
            Transaction::create([
                'account_id' => $user->accounts()->first()?->id,
                'transaction_type' => 'loan_repayment',
                'amount' => $amount,
                'currency' => $result['currency'] ?? 'USD',
                'status' => 'completed',
                'description' => 'Loan repayment via ' . $gateway->name,
                'reference_number' => 'LOANPAY-' . strtoupper(substr(md5($sessionId), 0, 12)),
                'metadata' => [
                    'loan_id' => $loan->id,
                    'loan_reference' => $loan->loan_id,
                    'payment_method' => 'gateway',
                    'gateway_id' => $gateway->id,
                    'gateway_name' => $gateway->name,
                    'stripe_session_id' => $sessionId,
                    'verified_directly' => true,
                ],
                'completed_at' => now(),
            ]);
            
            DB::commit();
            
            $message = $loan->status === 'completed' 
                ? 'Congratulations! Your loan has been fully repaid.'
                : 'Payment of ' . Setting::get('currency_symbol', '$') . number_format($amount, 2) . ' received successfully.';
            
            return redirect()->route('loans.show', $loan)->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Stripe Direct Payment Processing Failed', ['error' => $e->getMessage()]);
            return redirect()->route('loans.show', $loan)
                ->withErrors(['error' => 'Payment verified but processing failed. Please contact support.']);
        }
    }
    
    /**
     * Get loan settings.
     */
    private function getSettings(): array
    {
        return [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
            'loan_min_amount' => (float) Setting::get('loan_min_amount', 100),
            'loan_max_amount' => (float) Setting::get('loan_max_amount', 10000),
            'loan_interest_rate' => (float) Setting::get('loan_interest_rate', 5),
        ];
    }
    
    /**
     * Format loan for frontend.
     */
    private function formatLoan(Loan $loan): array
    {
        $amount = (float) $loan->amount;
        $amountPaid = (float) ($loan->amount_paid ?? 0);
        $totalPayable = (float) $loan->total_payable;
        $remainingBalance = max(0, $totalPayable - $amountPaid);
        $progressPercent = $totalPayable > 0 ? min(100, ($amountPaid / $totalPayable) * 100) : 0;
        
        return [
            'id' => $loan->id,
            'loan_id' => $loan->loan_id,
            'amount' => $amount,
            'amount_formatted' => number_format($amount, 2),
            'interest_rate' => (float) $loan->interest_rate,
            'total_payable' => $totalPayable,
            'total_payable_formatted' => number_format($totalPayable, 2),
            'amount_paid' => $amountPaid,
            'amount_paid_formatted' => number_format($amountPaid, 2),
            'remaining_balance' => $remainingBalance,
            'remaining_balance_formatted' => number_format($remainingBalance, 2),
            'monthly_payment' => (float) ($loan->monthly_payment ?? 0),
            'monthly_payment_formatted' => number_format($loan->monthly_payment ?? 0, 2),
            'duration_months' => $loan->duration_months,
            'purpose' => $loan->purpose,
            'status' => $loan->status,
            'progress_percent' => round($progressPercent, 1),
            'due_date' => $loan->due_date?->format('M d, Y'),
            'approved_at' => $loan->approved_at?->format('M d, Y'),
            'created_at' => $loan->created_at->format('M d, Y H:i'),
        ];
    }
}
